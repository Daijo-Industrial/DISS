<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Livewire\Vehicles\Form as VehicleForm;
use App\Livewire\Vehicles\Index as VehiclesIndex;
use App\Livewire\Vehicles\InspectionForm;
use App\Livewire\Vehicles\Scan as VehicleScan;
use App\Livewire\Vehicles\Show as VehicleShow;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use App\Notifications\VehicleDocumentExpiryNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleInspectionAndComplianceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Vehicle $vehicle;

    protected Vehicle $truck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'fleet.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'fleet.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'fleet.inspect', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'fleet.documents', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'fleet.view-costs', 'guard_name' => 'web']);
        $this->user->givePermissionTo(['fleet.manage', 'fleet.view', 'fleet.inspect', 'fleet.documents', 'fleet.view-costs']);
        $this->actingAs($this->user);

        // Passenger vehicle (Primary for P2H inspections)
        $this->vehicle = Vehicle::create([
            'plate_number' => 'B 9999 DISS',
            'driver_name' => 'Ahmad Supir',
            'brand' => 'Toyota',
            'model' => 'Innova Zenix',
            'category' => 'passenger',
            'fuel_type' => 'petrol',
            'requires_kir' => false,
            'odometer' => 50000,
            'status' => VehicleStatus::ACTIVE,
        ]);

        // Commercial truck (For KIR and non-passenger restriction tests)
        $this->truck = Vehicle::create([
            'plate_number' => 'B 8888 TRK',
            'driver_name' => 'Pak Supir Truk',
            'brand' => 'Hino',
            'model' => 'Dutro 130 HD',
            'category' => 'commercial_truck',
            'fuel_type' => 'diesel',
            'requires_kir' => true,
            'odometer' => 75000,
            'status' => VehicleStatus::ACTIVE,
        ]);
    }

    public function test_vehicle_model_attributes_and_relationships()
    {
        $this->assertEquals('Mobil Penumpang', $this->vehicle->category_label);
        $this->assertEquals('Bensin', $this->vehicle->fuel_type_label);
        $this->assertFalse($this->vehicle->requires_kir);
        $this->assertFalse($this->vehicle->is_out_on_trip);
        $this->assertStringContainsString('DKI Jakarta', $this->vehicle->region_name);

        $this->assertEquals('Truk / Mobil Gede', $this->truck->category_label);
        $this->assertEquals('Solar / Diesel', $this->truck->fuel_type_label);
        $this->assertTrue($this->truck->requires_kir);
    }

    public function test_vehicle_document_status_calculation()
    {
        // Expired KIR document on commercial truck
        $expiredDoc = VehicleDocument::create([
            'vehicle_id' => $this->truck->id,
            'document_type' => VehicleDocument::TYPE_KIR,
            'document_number' => 'KIR-TEST-001',
            'expired_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertEquals('expired', $expiredDoc->status);
        $this->assertStringContainsString('Expired', $expiredDoc->status_label);

        // Upcoming STNK 1-year document (expiring in 10 days)
        $warningDoc = VehicleDocument::create([
            'vehicle_id' => $this->vehicle->id,
            'document_type' => VehicleDocument::TYPE_STNK_ANNUAL,
            'document_number' => 'STNK-TEST-001',
            'expired_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertEquals('warning', $warningDoc->status);
        $this->assertStringContainsString('Perlu Diperpanjang', $warningDoc->status_label);

        // Safe STNK 5-year document (expiring in 200 days)
        $safeDoc = VehicleDocument::create([
            'vehicle_id' => $this->vehicle->id,
            'document_type' => VehicleDocument::TYPE_STNK_FIVE_YEAR,
            'document_number' => 'STNK5-TEST-001',
            'expired_date' => now()->addDays(200)->toDateString(),
        ]);

        $this->assertEquals('valid', $safeDoc->status);
    }

    public function test_p2h_checkout_and_checkin_lifecycle()
    {
        // 1. Perform Check-out (Pre-trip)
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Ahmad Supir')
            ->set('created_by', 'Petugas GA')
            ->set('odometer', 50100)
            ->set('fuel_percentage', 100)
            ->set('trip_purpose', 'Kirim komponen ke pabrik Cikarang')
            ->set('severity', 'none')
            ->call('save')
            ->assertRedirect(route('vehicles.index'));

        $this->vehicle->refresh();
        $this->assertEquals(50100, $this->vehicle->odometer);
        $this->assertTrue($this->vehicle->is_out_on_trip);
        $this->assertNotNull($this->vehicle->activeCheckOut);
        $this->assertEquals('Petugas GA', $this->vehicle->activeCheckOut->created_by);

        $checkOutInspection = $this->vehicle->activeCheckOut;

        // 2. Perform Check-in (Post-trip)
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_in'])
            ->set('driver_name', 'Ahmad Supir')
            ->set('created_by', 'Petugas Malam')
            ->set('odometer', 50250) // 150 km traveled
            ->set('fuel_percentage', 75)
            ->set('severity', 'none')
            ->call('save')
            ->assertRedirect(route('vehicles.index'));

        $this->vehicle->refresh();
        $this->assertEquals(50250, $this->vehicle->odometer);
        $this->assertFalse($this->vehicle->is_out_on_trip);

        // Verify trip distance calculation and created_by
        $latestInspection = $this->vehicle->inspections()->latest()->first();
        $this->assertEquals('check_in', $latestInspection->inspection_type);
        $this->assertEquals('Petugas Malam', $latestInspection->created_by);
        $this->assertEquals(150, $latestInspection->trip_distance);
        $this->assertEquals($checkOutInspection->id, $latestInspection->parent_inspection_id);
    }

    public function test_critical_defect_grounds_vehicle_to_maintenance()
    {
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Ahmad Supir')
            ->set('created_by', 'Petugas Bengkel')
            ->set('trip_purpose', 'Uji kelayakan jalan')
            ->set('odometer', 50300)
            ->set('fuel_percentage', 100)
            ->set('checklist.brake_lights.status', 'issue')
            ->set('checklist.brake_lights.notes', 'Lampu rem mati total')
            ->set('severity', 'critical_grounded')
            ->set('defect_notes', 'Lampu rem mati total, kabel putus')
            ->call('save')
            ->assertRedirect(route('vehicles.index'));

        $this->vehicle->refresh();
        $this->assertEquals(VehicleStatus::MAINTENANCE, $this->vehicle->status);
    }

    public function test_p2h_inspection_supports_photos_per_point_and_optional_defect_notes()
    {
        Storage::fake('public');

        $photo1 = UploadedFile::fake()->image('headlight_crack.jpg');
        $photo2 = UploadedFile::fake()->image('headlight_close.jpg');
        $photoTire = UploadedFile::fake()->image('tire_tread.jpg');

        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Ahmad Supir')
            ->set('created_by', 'Petugas Lapangan')
            ->set('trip_purpose', 'Operasional antar divisi')
            ->set('odometer', 50400)
            ->set('fuel_percentage', 90)
            ->set('checklist.headlights.status', 'issue')
            ->set('checklist.headlights.notes', 'Mika lampu depan retak halus')
            ->set('point_photos.headlights', [$photo1, $photo2])
            ->set('checklist.tires.notes', 'Tekanan angin 35 psi')
            ->set('point_photos.tires', [$photoTire])
            ->set('severity', 'minor')
            ->set('defect_notes', null) // Uraian temuan is optional even when severity is minor
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('vehicles.index'));

        $inspection = $this->vehicle->inspections()->latest()->first();
        $this->assertNotNull($inspection);
        $this->assertEquals('minor', $inspection->severity);
        $this->assertEquals('Petugas Lapangan', $inspection->created_by);
        $this->assertNull($inspection->defect_notes);

        $checklistResults = $inspection->checklist_results;
        $this->assertEquals('issue', $checklistResults['headlights']['status']);
        $this->assertEquals('Mika lampu depan retak halus', $checklistResults['headlights']['notes']);
        $this->assertCount(2, $checklistResults['headlights']['photos']);
        $this->assertEquals('Tekanan angin 35 psi', $checklistResults['tires']['notes']);
        $this->assertCount(1, $checklistResults['tires']['photos']);

        // Check storage has stored the files
        Storage::disk('public')->assertExists($checklistResults['headlights']['photos'][0]);
        Storage::disk('public')->assertExists($checklistResults['headlights']['photos'][1]);
        Storage::disk('public')->assertExists($checklistResults['tires']['photos'][0]);
    }

    public function test_check_reminders_command_notifies_recipients()
    {
        Notification::fake();

        // Buat dokumen yang akan expired 5 hari lagi
        $doc = VehicleDocument::create([
            'vehicle_id' => $this->truck->id,
            'document_type' => VehicleDocument::TYPE_KIR,
            'document_number' => 'KIR-ALERTER-1',
            'expired_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan('fleet:check-reminders')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->user,
            VehicleDocumentExpiryNotification::class,
            function ($notification) use ($doc) {
                return $notification->document->id === $doc->id;
            }
        );
    }

    public function test_vehicle_form_validation_and_category_kir_auto_toggle()
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        $this->user->assignRole('super-admin');

        Livewire::test(VehicleForm::class)
            ->assertSet('category', 'passenger')
            ->assertSet('requires_kir', false)
            ->set('category', 'commercial_truck')
            ->assertSet('requires_kir', true)
            ->set('plate_number', 'B 8888 NEW')
            ->set('driver_name', 'Budi Santoso')
            ->set('brand', 'Isuzu')
            ->set('model', 'Giga')
            ->set('fuel_type', 'diesel')
            ->set('odometer', 12000)
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vehicles', [
            'plate_number' => 'B 8888 NEW',
            'category' => 'commercial_truck',
            'fuel_type' => 'diesel',
            'requires_kir' => 1,
            'brand' => 'Isuzu',
            'model' => 'Giga',
        ]);
    }

    public function test_indonesian_plate_number_standard_validation_and_auto_normalization()
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        $this->user->assignRole('super-admin');

        // 1. Invalid plate number format should fail regex validation
        Livewire::test(VehicleForm::class)
            ->set('plate_number', 'INVALID_PLATE')
            ->call('save')
            ->assertHasErrors(['plate_number' => 'regex']);

        Livewire::test(VehicleForm::class)
            ->set('plate_number', 'B 0123 XYZ') // Cannot start with 0
            ->call('save')
            ->assertHasErrors(['plate_number' => 'regex']);

        // 2. Unspaced or lowercase plate should be auto-normalized into standard Indonesian format (B 1234 XYZ)
        Livewire::test(VehicleForm::class)
            ->set('plate_number', 'b1234xyz')
            ->set('category', 'passenger')
            ->set('fuel_type', 'petrol')
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vehicles', [
            'plate_number' => 'B 1234 XYZ',
        ]);
    }

    public function test_vehicle_uuid_primary_key_generated_automatically()
    {
        $newVehicle = Vehicle::create([
            'plate_number' => 'D 5678 GHI',
            'driver_name' => 'Budi',
            'category' => 'passenger',
            'fuel_type' => 'petrol',
            'status' => VehicleStatus::ACTIVE,
        ]);

        $this->assertIsString($newVehicle->id);
        $this->assertEquals(36, strlen($newVehicle->id));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $newVehicle->id);
    }

    public function test_vehicle_show_generates_qr_code_with_uuid_payload()
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        $this->user->assignRole('super-admin');

        $component = Livewire::test(VehicleShow::class, ['vehicle' => $this->vehicle])
            ->assertSet('showQrModal', false)
            ->call('openQrModal')
            ->assertSet('showQrModal', true);

        $this->assertNotNull($component->get('qrCodeBase64'));
        $this->assertNotEmpty($component->get('qrCodeBase64'));

        $component->call('closeQrModal')
            ->assertSet('showQrModal', false);
    }

    public function test_inspection_form_auto_detects_and_switches_type()
    {
        // When vehicle is in pool, default is check_out
        $this->assertFalse($this->vehicle->is_out_on_trip);
        $component = Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle]);
        $component->assertSet('type', 'check_out');

        // Can switch to check_in seamlessly
        $component->call('switchType', 'check_in')
            ->assertSet('type', 'check_in');

        // And switch back to check_out
        $component->call('switchType', 'check_out')
            ->assertSet('type', 'check_out');
    }

    public function test_vehicles_index_operational_tabs_filtering()
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        $this->user->assignRole('super-admin');

        $component = Livewire::test(VehiclesIndex::class);

        // Default tab is 'all'
        $component->assertSet('operationalTab', 'all')
            ->assertSee($this->vehicle->plate_number);

        // Filter 'in_pool' (our vehicle is not checked out, so it is in pool)
        $component->call('setOperationalTab', 'in_pool')
            ->assertSet('operationalTab', 'in_pool')
            ->assertSee($this->vehicle->plate_number);

        // Filter 'on_trip' (our vehicle is in pool, so on_trip should not show it)
        $component->call('setOperationalTab', 'on_trip')
            ->assertSet('operationalTab', 'on_trip')
            ->assertDontSee($this->vehicle->plate_number);

        // Filter 'maintenance'
        $component->call('setOperationalTab', 'maintenance')
            ->assertSet('operationalTab', 'maintenance')
            ->assertDontSee($this->vehicle->plate_number);
    }

    public function test_vehicle_profile_photo_upload_in_form_and_cockpit_management()
    {
        Storage::fake('public');
        Role::firstOrCreate(['name' => 'super-admin']);
        $this->user->assignRole('super-admin');

        // 1. Upload photo via Vehicle Form
        $profilePhoto = UploadedFile::fake()->image('innova_front.jpg');

        Livewire::test(VehicleForm::class)
            ->set('plate_number', 'B 7777 FTO')
            ->set('driver_name', 'Joko')
            ->set('brand', 'Toyota')
            ->set('model', 'Innova Zenix')
            ->set('category', 'passenger')
            ->set('fuel_type', 'petrol')
            ->set('status', 'active')
            ->set('photo', $profilePhoto)
            ->call('save')
            ->assertHasNoErrors();

        $newVehicle = Vehicle::where('plate_number', 'B 7777 FTO')->first();
        $this->assertNotNull($newVehicle);
        $this->assertNotNull($newVehicle->image_path);
        $this->assertNotNull($newVehicle->image_url);
        Storage::disk('public')->assertExists($newVehicle->image_path);

        // 2. Change / Upload photo via Vehicle Show Cockpit
        $newCockpitPhoto = UploadedFile::fake()->image('innova_new.jpg');

        Livewire::test(VehicleShow::class, ['vehicle' => $newVehicle])
            ->call('openPhotoModal')
            ->assertSet('showPhotoModal', true)
            ->set('new_photo', $newCockpitPhoto)
            ->call('saveVehiclePhoto')
            ->assertHasNoErrors()
            ->assertSet('showPhotoModal', false);

        $newVehicle->refresh();
        Storage::disk('public')->assertExists($newVehicle->image_path);

        // 3. Test lightbox toggle
        Livewire::test(VehicleShow::class, ['vehicle' => $newVehicle])
            ->call('openLightbox')
            ->assertSet('showLightbox', true)
            ->call('closeLightbox')
            ->assertSet('showLightbox', false);

        // 4. Delete photo via Vehicle Show Cockpit
        Livewire::test(VehicleShow::class, ['vehicle' => $newVehicle])
            ->call('deleteVehiclePhoto')
            ->assertHasNoErrors();

        $newVehicle->refresh();
        $this->assertNull($newVehicle->image_path);
        $this->assertNull($newVehicle->image_url);
    }

    public function test_vehicle_qr_scanner_page_and_resolution()
    {
        // 1. HTTP GET route to scanner page requires authentication
        $response = $this->actingAs($this->user)->get(route('vehicles.scan'));
        $response->assertStatus(200);
        $response->assertSee('Pindai QR Stiker Armada');

        // 2. Resolve raw UUID string
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->call('resolve', (string) $this->vehicle->id)
            ->assertRedirect(route('vehicles.inspect', ['vehicle' => $this->vehicle->id]));

        // 3. Resolve QR containing URL with UUID
        $urlWithUuid = 'https://diss.daijo.co.id/vehicles/' . $this->vehicle->id . '/inspect';
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->call('resolve', $urlWithUuid)
            ->assertRedirect(route('vehicles.inspect', ['vehicle' => $this->vehicle->id]));
    }

    public function test_vehicle_qr_scanner_manual_search_and_invalid_codes()
    {
        // 1. Resolve by plate number
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->set('manualInput', 'b9999diss')
            ->call('searchManual')
            ->assertRedirect(route('vehicles.inspect', ['vehicle' => $this->vehicle->id]));

        // 2. Resolve by vehicle details (model, brand, driver name, partial plate)
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->set('manualInput', 'Innova')
            ->call('searchManual')
            ->assertRedirect(route('vehicles.inspect', ['vehicle' => $this->vehicle->id]));

        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->set('manualInput', '9999')
            ->call('searchManual')
            ->assertRedirect(route('vehicles.inspect', ['vehicle' => $this->vehicle->id]));

        // 3. Search by UUID in manual search is explicitly disallowed
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->set('manualInput', (string) $this->vehicle->id)
            ->call('searchManual')
            ->assertSet('errorMessage', __('fleet.scanner.uuid_not_allowed'))
            ->assertNoRedirect();

        // 4. Non-existent vehicle returns error message and dispatches scan-failed
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->call('resolve', '00000000-0000-0000-0000-000000000000')
            ->assertSet('errorMessage', "Armada dengan kode/plat '00000000-0000-0000-0000-000000000000' tidak ditemukan dalam sistem DISS.")
            ->assertDispatched('scan-failed');

        // 5. Empty input returns generic error and dispatches scan-failed
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->call('resolve', '')
            ->assertSet('errorMessage', __('fleet.scanner.not_found_alert'))
            ->assertDispatched('scan-failed');

        // 6. Camera QR scan resolve() does NOT fallback to plate number or vehicle details
        Livewire::actingAs($this->user)
            ->test(VehicleScan::class)
            ->call('resolve', $this->vehicle->plate_number)
            ->assertSet('errorMessage', "Armada dengan kode/plat '{$this->vehicle->plate_number}' tidak ditemukan dalam sistem DISS.")
            ->assertDispatched('scan-failed')
            ->assertNoRedirect();
    }

    public function test_user_with_fleet_manage_permission_has_full_management_access()
    {
        Permission::firstOrCreate(['name' => 'fleet.manage', 'guard_name' => 'web']);
        $managerUser = User::factory()->create();
        $managerUser->givePermissionTo('fleet.manage');

        // 1. VehiclesIndex has canManage = true
        Livewire::actingAs($managerUser)
            ->test(VehiclesIndex::class)
            ->assertSet('canManage', true)
            ->assertSee(__('fleet.index.add_vehicle'));

        // 2. VehicleShow has canManage = true
        Livewire::actingAs($managerUser)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle])
            ->assertSet('canManage', true);

        // 3. VehicleForm can create new vehicle
        Livewire::actingAs($managerUser)
            ->test(VehicleForm::class)
            ->assertSet('canManage', true)
            ->set('plate_number', 'B 5555 MGR')
            ->set('category', 'passenger')
            ->set('fuel_type', 'petrol')
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vehicles', ['plate_number' => 'B 5555 MGR']);
    }

    public function test_user_without_fleet_manage_permission_cannot_create_or_delete_vehicle()
    {
        Permission::firstOrCreate(['name' => 'fleet.view', 'guard_name' => 'web']);
        $viewerUser = User::factory()->create();
        $viewerUser->givePermissionTo('fleet.view');

        // 1. Cannot delete vehicle
        Livewire::actingAs($viewerUser)
            ->test(VehiclesIndex::class)
            ->assertSet('canManage', false)
            ->assertDontSee('Registrasi Armada')
            ->call('deleteVehicle', $this->vehicle->id)
            ->assertStatus(403);

        // 2. Cannot access vehicle create form
        Livewire::actingAs($viewerUser)
            ->test(VehicleForm::class)
            ->assertStatus(403);

        // 3. VehicleShow has canManage = false
        Livewire::actingAs($viewerUser)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle])
            ->assertSet('canManage', false);
    }

    public function test_p2h_photos_and_documents_render_with_lightbox_triggers()
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        $this->user->assignRole('super-admin');

        // Create an inspection with checklist photos and defect photos
        VehicleInspection::create([
            'vehicle_id' => $this->vehicle->id,
            'inspection_type' => 'check_out',
            'driver_name' => 'Budi',
            'odometer' => 50100,
            'fuel_percentage' => 80,
            'checklist_results' => [
                'headlights' => [
                    'title' => 'Lampu Depan',
                    'status' => 'good',
                    'notes' => 'Kondisi jernih',
                    'photos' => ['vehicles/inspections/points/test_headlight.jpg'],
                ],
            ],
            'defect_photos' => ['vehicles/inspections/defects/test_defect.jpg'],
            'severity' => 'none',
            'inspector_id' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'inspections']);

        // Assert Lightbox dispatch event is present in the rendered HTML
        $component->assertSeeHtml('$dispatch(\'open-lightbox\'');
        $component->assertSeeHtml('test_headlight.jpg');
        $component->assertSeeHtml('test_defect.jpg');
    }

    public function test_user_locale_switching_and_persistence()
    {
        // 1. Initially user locale is null, default locale is 'id'
        $this->assertNull($this->user->locale);
        $this->assertEquals('id', app()->getLocale());

        // 2. Switch to English
        $response = $this->actingAs($this->user)->get(route('locale.switch', 'en'));
        $response->assertRedirect();
        $this->assertEquals('en', session('locale'));
        $this->assertEquals('en', $this->user->fresh()->locale);

        // 3. Switch back to Indonesian
        $response = $this->actingAs($this->user)->get(route('locale.switch', 'id'));
        $response->assertRedirect();
        $this->assertEquals('id', session('locale'));
        $this->assertEquals('id', $this->user->fresh()->locale);
    }

    public function test_vehicle_domain_localization_in_indonesian_and_english()
    {
        // Indonesian (Default)
        app()->setLocale('id');
        $this->assertEquals('Mobil Penumpang', $this->vehicle->category_label);
        $this->assertEquals('Bensin', $this->vehicle->fuel_type_label);
        $this->assertEquals('Truk / Mobil Gede', $this->truck->category_label);
        $this->assertEquals('Solar / Diesel', $this->truck->fuel_type_label);

        $doc = new VehicleDocument([
            'document_type' => 'kir',
            'expired_date' => now()->addDays(10),
        ]);
        $this->assertEquals('Uji Berkala KIR', $doc->type_label);
        $this->assertStringContainsString('Perlu Diperpanjang', $doc->status_label);

        $inspection = new VehicleInspection([
            'severity' => VehicleInspection::SEVERITY_NONE,
        ]);
        $this->assertEquals('Aman (Fit to Drive)', $inspection->severity_label);

        // English (Switched)
        app()->setLocale('en');
        $this->assertEquals('Passenger Vehicle', $this->vehicle->category_label);
        $this->assertEquals('Petrol', $this->vehicle->fuel_type_label);
        $this->assertEquals('Commercial Truck / Heavy', $this->truck->category_label);
        $this->assertEquals('Diesel', $this->truck->fuel_type_label);
        $this->assertEquals('Periodic KIR Inspection', $doc->type_label);
        $this->assertStringContainsString('Renewal Due', $doc->status_label);
        $this->assertEquals('Safe (Fit to Drive)', $inspection->severity_label);

        // Reset back to id
        app()->setLocale('id');
    }

    public function test_vehicle_legal_documents_lifecycle_lightbox_and_storage_cleanup()
    {
        Storage::fake('public');
        Permission::firstOrCreate(['name' => 'fleet.manage', 'guard_name' => 'web']);
        $this->user->givePermissionTo('fleet.manage');

        // 1. Invalid mime type is rejected
        $invalidFile = UploadedFile::fake()->create('script.sh', 50, 'application/x-sh');
        Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('openDocModal', VehicleDocument::TYPE_KIR)
            ->set('doc_type', VehicleDocument::TYPE_KIR)
            ->set('doc_number', 'KIR-TEST-001')
            ->set('expired_date', now()->addMonths(6)->toDateString())
            ->set('attachment', $invalidFile)
            ->call('saveDocument')
            ->assertHasErrors(['attachment' => 'mimes']);

        // 2. Valid image upload succeeds and stores file
        $validImage = UploadedFile::fake()->image('kir_scan.jpg');
        Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('openDocModal', VehicleDocument::TYPE_KIR)
            ->set('doc_type', VehicleDocument::TYPE_KIR)
            ->set('doc_number', 'KIR-TEST-001')
            ->set('expired_date', now()->addMonths(6)->toDateString())
            ->set('attachment', $validImage)
            ->call('saveDocument')
            ->assertHasNoErrors()
            ->assertSet('showDocModal', false);

        $savedDoc = VehicleDocument::where('vehicle_id', $this->vehicle->id)
            ->where('document_number', 'KIR-TEST-001')
            ->first();

        $this->assertNotNull($savedDoc);
        $this->assertNotNull($savedDoc->attachment_path);
        Storage::disk('public')->assertExists($savedDoc->attachment_path);
        $this->assertTrue($savedDoc->is_image_attachment);
        $this->assertEquals('Uji Berkala KIR', $savedDoc->document_type_label);
        $this->assertNotEmpty($savedDoc->attachment_url);

        // 3. Tab view renders lightbox trigger and document details
        $component = Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents']);
        $component->assertSeeHtml('$dispatch(\'open-lightbox\'');
        $component->assertSee('KIR-TEST-001');

        // 4. Delete document unlinks physical file from storage disk
        $attachmentPath = $savedDoc->attachment_path;
        Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('deleteDocument', $savedDoc->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('vehicle_documents', ['id' => $savedDoc->id]);
        Storage::disk('public')->assertMissing($attachmentPath);

        // 5. User without fleet.manage permission cannot delete document
        $newDoc = VehicleDocument::create([
            'vehicle_id' => $this->vehicle->id,
            'document_type' => VehicleDocument::TYPE_OTHER,
            'document_number' => 'DOC-READONLY',
            'expired_date' => now()->addYear(),
        ]);

        $viewerUser = User::factory()->create();
        $viewerUser->givePermissionTo('fleet.view');
        Livewire::actingAs($viewerUser)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('deleteDocument', $newDoc->id)
            ->assertStatus(403);
    }

    public function test_vehicle_unified_stnk_creation_and_synchronization()
    {
        Storage::fake('public');
        Permission::firstOrCreate(['name' => 'fleet.manage', 'guard_name' => 'web']);
        $this->user->givePermissionTo('fleet.manage');

        $annualDate = now()->addYear()->toDateString();
        $fiveYearDate = now()->addYears(5)->toDateString();

        // 1. Initial registration: no existing STNK, is_initial_stnk is true, last_renewed_date is null
        $initComponent = Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('openDocModal', VehicleDocument::TYPE_STNK)
            ->assertSet('is_initial_stnk', true)
            ->assertSet('renew_five_year', true)
            ->assertSet('last_renewed_date', null);

        // Validation fails if dates are empty
        $initComponent->set('doc_number', 'STNK-001/JK/2026')
            ->set('stnk_annual_expired_date', '')
            ->set('stnk_five_year_expired_date', '')
            ->call('saveDocument')
            ->assertHasErrors(['stnk_annual_expired_date', 'stnk_five_year_expired_date']);

        // 2. Successful initial creation saves both stnk_annual and stnk_five_year with null last_renewed_date
        $stnkScan = UploadedFile::fake()->image('stnk_scan.jpg');

        $initComponent->set('doc_number', 'STNK-001/JK/2026')
            ->set('stnk_annual_expired_date', $annualDate)
            ->set('stnk_five_year_expired_date', $fiveYearDate)
            ->set('attachment', $stnkScan)
            ->call('saveDocument')
            ->assertHasNoErrors()
            ->assertSet('showDocModal', false);

        $annualDoc = VehicleDocument::where('vehicle_id', $this->vehicle->id)
            ->where('document_type', VehicleDocument::TYPE_STNK_ANNUAL)
            ->first();

        $fiveYearDoc = VehicleDocument::where('vehicle_id', $this->vehicle->id)
            ->where('document_type', VehicleDocument::TYPE_STNK_FIVE_YEAR)
            ->first();

        $this->assertNotNull($annualDoc);
        $this->assertNotNull($fiveYearDoc);
        $this->assertNull($annualDoc->last_renewed_date);
        $this->assertNull($fiveYearDoc->last_renewed_date);
        $this->assertEquals($annualDate, $annualDoc->expired_date->toDateString());
        $this->assertEquals($fiveYearDate, $fiveYearDoc->expired_date->toDateString());
        Storage::disk('public')->assertExists($annualDoc->attachment_path);

        // 3. Document Tab renders unified STNK card with both dates
        $tabComponent = Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents']);
        $tabComponent->assertSee('STNK-001/JK/2026');
        $tabComponent->assertSee(__('fleet.show.stnk_card_title'));
        $tabComponent->assertSee(__('fleet.show.stnk_annual_label'));
        $tabComponent->assertSee(__('fleet.show.stnk_five_year_label'));

        // 4. Subsequent Routine Renewal: is_initial_stnk is false, last_renewed_date defaults to today
        $renewalPaymentDate = now()->subDays(2)->toDateString();
        $expectedNextAnnual = now()->subDays(2)->addYear()->toDateString();

        $renewalComponent = Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('openDocModal', 'stnk')
            ->assertSet('is_initial_stnk', false)
            ->assertSet('renew_five_year', false)
            ->assertSet('doc_number', 'STNK-001/JK/2026')
            ->assertSet('last_renewed_date', now()->toDateString())
            // Simulate changing the payment date to test auto-calculation of next 1-year tax
            ->set('last_renewed_date', $renewalPaymentDate)
            ->assertSet('stnk_annual_expired_date', $expectedNextAnnual);

        // Save routine renewal (only stnk_annual is renewed, 5-year plate is retained)
        $renewalComponent->call('saveDocument')
            ->assertHasNoErrors()
            ->assertSet('showDocModal', false);

        $latestAnnual = VehicleDocument::where('vehicle_id', $this->vehicle->id)
            ->where('document_type', VehicleDocument::TYPE_STNK_ANNUAL)
            ->latest('id')
            ->first();

        $this->assertEquals($expectedNextAnnual, $latestAnnual->expired_date->toDateString());
        $this->assertEquals($renewalPaymentDate, $latestAnnual->last_renewed_date->toDateString());
        // 5-Year STNK was NOT duplicated
        $this->assertEquals(1, VehicleDocument::where('vehicle_id', $this->vehicle->id)->where('document_type', VehicleDocument::TYPE_STNK_FIVE_YEAR)->count());

        // 5. deleteDocument on one record preserves the attachment for sibling STNK records
        Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('deleteDocument', $annualDoc->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('vehicle_documents', ['id' => $annualDoc->id]);
        $this->assertDatabaseHas('vehicle_documents', ['id' => $fiveYearDoc->id]);
        Storage::disk('public')->assertExists($fiveYearDoc->attachment_path);

        // 6. deleteStnk cleans up all remaining STNK records and unlinks file
        Livewire::actingAs($this->user)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->call('deleteStnk')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('vehicle_documents', ['vehicle_id' => $this->vehicle->id, 'document_type' => VehicleDocument::TYPE_STNK_FIVE_YEAR]);
        Storage::disk('public')->assertMissing($fiveYearDoc->attachment_path);
    }

    public function test_inspector_role_can_access_scanner_and_submit_p2h(): void
    {
        $inspector = User::factory()->create();
        $inspector->givePermissionTo(['fleet.view', 'fleet.inspect']);

        // 1. Can access scanner
        Livewire::actingAs($inspector)
            ->test(VehicleScan::class)
            ->assertOk()
            ->call('resolve', (string) $this->vehicle->id)
            ->assertRedirect(route('vehicles.inspect', ['vehicle' => $this->vehicle->id]));

        // 2. Can submit P2H inspection and gets redirected to vehicles.index
        Livewire::actingAs($inspector)
            ->test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Inspector Budi')
            ->set('created_by', 'Inspector Budi')
            ->set('trip_purpose', 'Patroli area industri')
            ->set('odometer', 50600)
            ->set('fuel_percentage', 95)
            ->set('severity', 'none')
            ->call('save')
            ->assertRedirect(route('vehicles.index'));

        // 3. Vehicles index shows scan button with standby CTA for inspector
        Livewire::actingAs($inspector)
            ->test(VehiclesIndex::class)
            ->assertSet('canInspect', true)
            ->assertSet('canManage', false)
            ->assertSee(__('fleet.index.scan_qr'));
    }

    public function test_inspector_cannot_access_sensitive_documents_or_service_costs(): void
    {
        $inspector = User::factory()->create();
        $inspector->givePermissionTo(['fleet.view', 'fleet.inspect']);

        // Create a legal document and a service record with cost
        VehicleDocument::create([
            'vehicle_id' => $this->vehicle->id,
            'document_type' => VehicleDocument::TYPE_STNK_ANNUAL,
            'document_number' => 'SECRET-STNK-999',
            'expired_date' => now()->addYear(),
        ]);

        ServiceRecord::create([
            'vehicle_id' => $this->vehicle->id,
            'service_date' => now()->subDays(5),
            'workshop' => 'Bengkel Resmi Hino',
            'odometer' => 49500,
            'total_cost' => 2500000,
        ]);

        // 1. VehicleShow defaults ?tab=documents back to inspections for inspector
        $component = Livewire::actingAs($inspector)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'documents'])
            ->assertSet('canViewDocuments', false)
            ->assertSet('canViewCosts', false)
            ->assertSet('tab', 'inspections')
            ->assertDontSee('SECRET-STNK-999');

        // 2. Calling setTab('documents') also falls back to inspections
        $component->call('setTab', 'documents')
            ->assertSet('tab', 'inspections')
            ->assertDontSee('SECRET-STNK-999');

        // 3. Viewing services tab hides financial figures
        $component->call('setTab', 'services')
            ->assertSet('tab', 'services')
            ->assertSee('Bengkel Resmi Hino')
            ->assertDontSee('Rp 2.500.000');
    }

    public function test_manager_retains_full_visibility_and_management_access(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['fleet.manage']);

        // Create a service record with cost
        ServiceRecord::create([
            'vehicle_id' => $this->vehicle->id,
            'service_date' => now()->subDays(2),
            'workshop' => 'Bengkel Authorized',
            'odometer' => 49800,
            'total_cost' => 1750000,
        ]);

        Livewire::actingAs($manager)
            ->test(VehicleShow::class, ['vehicle' => $this->vehicle, 'tab' => 'services'])
            ->assertSet('canManage', true)
            ->assertSet('canViewDocuments', true)
            ->assertSet('canViewCosts', true)
            ->assertSee('Rp 1.750.000')
            ->assertSee(__('fleet.show.btn_add_service'))
            ->assertSee(__('fleet.show.btn_edit_vehicle'));
    }

    public function test_p2h_validation_requires_trip_purpose_and_created_by(): void
    {
        // 1. Missing trip_purpose and created_by should fail step 1 validation
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Bambang supriyanto')
            ->set('created_by', '')
            ->set('trip_purpose', '')
            ->set('odometer', 50100)
            ->set('fuel_percentage', 100)
            ->call('nextStep')
            ->assertHasErrors(['created_by', 'trip_purpose'])
            ->assertSet('currentStep', 1);

        // 2. Fails save if missing trip_purpose or created_by
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Bambang supriyanto')
            ->set('created_by', '')
            ->set('trip_purpose', '')
            ->set('odometer', 50100)
            ->set('fuel_percentage', 100)
            ->call('save')
            ->assertHasErrors(['created_by', 'trip_purpose']);
    }

    public function test_driver_name_and_created_by_support_custom_and_configured_values(): void
    {
        $configuredDrivers = config('fleet.drivers');
        $this->assertContains('Bambang supriyanto', $configuredDrivers);
        $this->assertContains('Khumedi', $configuredDrivers);
        $this->assertContains('Endang', $configuredDrivers);

        // 1. Can submit using a configured driver name
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Bambang supriyanto')
            ->set('created_by', 'PIC Satpam')
            ->set('trip_purpose', 'Antar barang divisi produksi ke Plant 2')
            ->set('odometer', 50200)
            ->set('fuel_percentage', 85)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('vehicles.index'));

        $inspection = $this->vehicle->inspections()->latest()->first();
        $this->assertEquals('Bambang supriyanto', $inspection->driver_name);
        $this->assertEquals('PIC Satpam', $inspection->created_by);
        $this->assertEquals('Antar barang divisi produksi ke Plant 2', $inspection->trip_purpose);

        // 2. Can submit with a custom typed driver name and custom created_by
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_in'])
            ->set('driver_name', 'Supir Rental Baru')
            ->set('created_by', 'Inspektor Khusus')
            ->set('trip_purpose', 'Kembali dari Plant 2')
            ->set('odometer', 50350)
            ->set('fuel_percentage', 60)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('vehicles.index'));

        $checkIn = $this->vehicle->inspections()->latest()->first();
        $this->assertEquals('check_in', $checkIn->inspection_type);
        $this->assertEquals('Supir Rental Baru', $checkIn->driver_name);
        $this->assertEquals('Inspektor Khusus', $checkIn->created_by);
    }

    public function test_p2h_inspection_strictly_restricted_to_passenger_category(): void
    {
        // 1. Direct InspectionForm mount aborts 403 on non-passenger vehicle
        Livewire::test(InspectionForm::class, ['vehicle' => $this->truck])
            ->assertStatus(403);

        // 2. Scanner resolve with truck UUID rejects with passenger_only error and scan-failed event
        Livewire::test(VehicleScan::class)
            ->call('resolve', (string) $this->truck->id)
            ->assertSet('errorMessage', __('fleet.scanner.passenger_only'))
            ->assertDispatched('scan-failed')
            ->assertNoRedirect();

        // 3. Scanner manual search with truck plate number rejects with passenger_only error
        Livewire::test(VehicleScan::class)
            ->set('manualInput', $this->truck->plate_number)
            ->call('searchManual')
            ->assertSet('errorMessage', __('fleet.scanner.passenger_only'))
            ->assertNoRedirect();

        // 4. Scanner selectVehicle with truck ID rejects with passenger_only error
        Livewire::test(VehicleScan::class)
            ->call('selectVehicle', (string) $this->truck->id)
            ->assertSet('errorMessage', __('fleet.scanner.passenger_only'))
            ->assertNoRedirect();

        // 5. Scanner recent list only returns passenger vehicles
        Livewire::test(VehicleScan::class)
            ->assertSee($this->vehicle->plate_number)
            ->assertDontSee($this->truck->plate_number);

        // 6. VehiclesIndex defaults to passenger category, hiding truck and showing passenger
        Livewire::test(VehiclesIndex::class)
            ->assertSet('category', 'passenger')
            ->assertSee($this->vehicle->plate_number)
            ->assertDontSee($this->truck->plate_number)
            ->assertSeeHtml(route('vehicles.inspect', ['vehicle' => $this->vehicle, 'type' => 'check_out']));

        // When category is changed to all, both vehicles are visible, but only passenger has P2H link
        Livewire::test(VehiclesIndex::class)
            ->set('category', 'all')
            ->assertSee($this->vehicle->plate_number)
            ->assertSee($this->truck->plate_number)
            ->assertSeeHtml(route('vehicles.inspect', ['vehicle' => $this->vehicle, 'type' => 'check_out']))
            ->assertDontSeeHtml(route('vehicles.inspect', ['vehicle' => $this->truck, 'type' => 'check_out']));

        // 7. VehicleShow cockpit view does not show P2H button for truck
        Livewire::test(VehicleShow::class, ['vehicle' => $this->truck])
            ->assertDontSeeHtml(route('vehicles.inspect', ['vehicle' => $this->truck, 'type' => 'check_out']));

        // But shows P2H button for passenger vehicle
        Livewire::test(VehicleShow::class, ['vehicle' => $this->vehicle])
            ->assertSeeHtml(route('vehicles.inspect', ['vehicle' => $this->vehicle, 'type' => 'check_out']));
    }
}
