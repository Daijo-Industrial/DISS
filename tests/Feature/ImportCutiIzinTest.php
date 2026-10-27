<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Models\AttendanceRecord;
use App\Models\EvaluationData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('non-super-admin cannot import cuti izin excel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('evaluation.import-cuti-izin'))
        ->assertForbidden();
});

test('super-admin can import cuti izin excel and update attendance records and evaluation scores', function () {
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    // Create employee matching NIK in Cuti Izin September 2026.xls (e.g. 00147 has 1 izin)
    Employee::factory()->create([
        'nik' => '00147',
        'employment_type' => 'KONTRAK',
    ]);

    // Initial attendance record with izin = 0
    AttendanceRecord::create([
        'nik' => '00147',
        'shift_date' => '2026-09-01',
        'alpha' => 0,
        'telat' => 0,
        'cuti' => 0,
        'izin' => 0,
        'sakit' => 0,
    ]);

    // Evaluation data with 0 izin, perfect score 100
    $evalData = EvaluationData::create([
        'NIK' => '00147',
        'Month' => '2026-09-01',
        'evaluation_type' => 'regular',
        'approval_status' => 'graded',
        'kerajinan_kerja' => 'A', // 10
        'kerapian_kerja' => 'A',  // 10
        'prestasi' => 'A',        // 20
        'loyalitas' => 'A',       // 10
        'perilaku_kerja' => 'A',  // 10
        'Alpha' => 0,
        'Telat' => 0,
        'Izin' => 0,
        'Sakit' => 0,
        'total' => 100,
    ]);

    $filePath = base_path('Cuti Izin September 2026.xls');
    expect(file_exists($filePath))->toBeTrue();

    $file = new UploadedFile(
        $filePath,
        'Cuti Izin September 2026.xls',
        'application/vnd.ms-excel',
        null,
        true
    );

    $response = $this->actingAs($admin)
        ->post(route('evaluation.import-cuti-izin'), [
            'file' => $file,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    // Verify attendance record updated: 00147 has 1 izin in the Excel file
    $totalIzin = AttendanceRecord::where('nik', '00147')
        ->whereYear('shift_date', 2026)
        ->whereMonth('shift_date', 9)
        ->sum('izin');

    expect((int) $totalIzin)->toBe(1);

    // Verify evaluation data recalculated: Izin updated to 1, total penalty -2 -> 98
    $evalData->refresh();
    expect($evalData->Izin)->toBe(1)
        ->and($evalData->total)->toBe(98);
});

test('artisan command attendance:import-cuti-izin imports excel successfully', function () {
    $filePath = base_path('Cuti Izin September 2026.xls');

    $exitCode = Artisan::call('attendance:import-cuti-izin', [
        'file' => $filePath,
    ]);

    expect($exitCode)->toBe(0);
});
