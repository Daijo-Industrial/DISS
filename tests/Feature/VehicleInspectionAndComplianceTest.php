<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Vehicle;
use App\Livewire\Vehicles\Form as VehicleForm;
use App\Livewire\Vehicles\Index as VehiclesIndex;
use App\Livewire\Vehicles\InspectionForm;
use App\Livewire\Vehicles\Show as VehicleShow;
use App\Models\User;
use App\Models\VehicleDocument;
use App\Notifications\VehicleDocumentExpiryNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleInspectionAndComplianceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->vehicle = Vehicle::create([
            'plate_number' => 'B 9999 DISS',
            'driver_name' => 'Ahmad Supir',
            'brand' => 'Hino',
            'model' => 'Dutro 130 HD',
            'category' => 'commercial_truck',
            'fuel_type' => 'diesel',
            'requires_kir' => true,
            'odometer' => 50000,
            'status' => VehicleStatus::ACTIVE,
        ]);
    }

    public function test_vehicle_model_attributes_and_relationships()
    {
        $this->assertEquals('Truk / Mobil Gede', $this->vehicle->category_label);
        $this->assertEquals('Solar / Diesel', $this->vehicle->fuel_type_label);
        $this->assertTrue($this->vehicle->requires_kir);
        $this->assertFalse($this->vehicle->is_out_on_trip);
        $this->assertStringContainsString('DKI Jakarta', $this->vehicle->region_name);
    }

    public function test_vehicle_document_status_calculation()
    {
        // Expired KIR document
        $expiredDoc = VehicleDocument::create([
            'vehicle_id' => $this->vehicle->id,
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
            ->set('odometer', 50100)
            ->set('fuel_percentage', 100)
            ->set('trip_purpose', 'Kirim komponen ke pabrik Cikarang')
            ->set('severity', 'none')
            ->call('save')
            ->assertRedirect(route('vehicles.show', $this->vehicle));

        $this->vehicle->refresh();
        $this->assertEquals(50100, $this->vehicle->odometer);
        $this->assertTrue($this->vehicle->is_out_on_trip);
        $this->assertNotNull($this->vehicle->activeCheckOut);

        $checkOutInspection = $this->vehicle->activeCheckOut;

        // 2. Perform Check-in (Post-trip)
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_in'])
            ->set('driver_name', 'Ahmad Supir')
            ->set('odometer', 50250) // 150 km traveled
            ->set('fuel_percentage', 75)
            ->set('severity', 'none')
            ->call('save')
            ->assertRedirect(route('vehicles.show', $this->vehicle));

        $this->vehicle->refresh();
        $this->assertEquals(50250, $this->vehicle->odometer);
        $this->assertFalse($this->vehicle->is_out_on_trip);

        // Verify trip distance calculation
        $latestInspection = $this->vehicle->inspections()->latest()->first();
        $this->assertEquals('check_in', $latestInspection->inspection_type);
        $this->assertEquals(150, $latestInspection->trip_distance);
        $this->assertEquals($checkOutInspection->id, $latestInspection->parent_inspection_id);
    }

    public function test_critical_defect_grounds_vehicle_to_maintenance()
    {
        Livewire::test(InspectionForm::class, ['vehicle' => $this->vehicle, 'type' => 'check_out'])
            ->set('driver_name', 'Ahmad Supir')
            ->set('odometer', 50300)
            ->set('fuel_percentage', 100)
            ->set('checklist.brake_lights.status', 'issue')
            ->set('checklist.brake_lights.notes', 'Lampu rem mati total')
            ->set('severity', 'critical_grounded')
            ->set('defect_notes', 'Lampu rem mati total, kabel putus')
            ->call('save')
            ->assertRedirect(route('vehicles.show', $this->vehicle));

        $this->vehicle->refresh();
        $this->assertEquals(VehicleStatus::MAINTENANCE, $this->vehicle->status);
    }

    public function test_check_reminders_command_notifies_recipients()
    {
        Notification::fake();

        // Buat dokumen yang akan expired 5 hari lagi
        $doc = VehicleDocument::create([
            'vehicle_id' => $this->vehicle->id,
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
}
