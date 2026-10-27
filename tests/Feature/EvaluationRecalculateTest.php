<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Models\AttendanceRecord;
use App\Models\EvaluationData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('non-super-admin cannot trigger evaluation recalculate', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('evaluation.recalculate'), [
            'month' => 10,
            'year' => 2026,
        ])
        ->assertForbidden();
});

test('super-admin can recalculate evaluation scores when attendance changes', function () {
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Employee::factory()->create([
        'nik' => '99001',
        'employment_type' => 'KONTRAK',
    ]);

    // Create attendance records for October 2026
    AttendanceRecord::create([
        'nik' => '99001',
        'shift_date' => '2026-10-05',
        'alpha' => 0,
        'telat' => 1,
        'cuti' => 2,
        'izin' => 0,
        'sakit' => 0,
    ]);

    // Initially, evaluation data had an old snapshot with 1 izin (penalty -2) and 1 telat (penalty -0.5)
    // Regular scoring: base 40 + (A:10 + A:10 + A:20 + A:10 + A:10 = 60) - penalties
    // Perfect regular score = 100.
    // With 1 izin (-2) and 1 telat (-0.5), old total was 97.5 -> 97.
    $evalData = EvaluationData::create([
        'NIK' => '99001',
        'Month' => '2026-10-01',
        'evaluation_type' => 'regular',
        'approval_status' => 'graded',
        'kerajinan_kerja' => 'A', // 10
        'kerapian_kerja' => 'A',  // 10
        'prestasi' => 'A',        // 20
        'loyalitas' => 'A',       // 10
        'perilaku_kerja' => 'A',  // 10
        'Alpha' => 0,
        'Telat' => 1,
        'Izin' => 1, // old snapshot had 1
        'Sakit' => 0,
        'total' => 97,
    ]);

    // Call recalculate endpoint
    $response = $this->actingAs($admin)
        ->postJson(route('evaluation.recalculate'), [
            'month' => 10,
            'year' => 2026,
            'type' => 'all',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'count' => 1,
        ]);

    $evalData->refresh();

    // After recalculate, Izin is updated to 0, and total should be 40 + 60 - (1 * 0.5) = 99.5 -> 99
    expect($evalData->Izin)->toBe(0)
        ->and($evalData->Telat)->toBe(1)
        ->and($evalData->total)->toBe(99);
});
