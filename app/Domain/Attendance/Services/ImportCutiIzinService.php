<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services;

use App\Domain\Evaluation\Services\EvaluationScoreCalculatorService;
use App\Models\AttendanceRecord;
use App\Models\EvaluationData;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ImportCutiIzinService
{
    public function __construct(
        private readonly EvaluationScoreCalculatorService $calculator,
    ) {}

    /**
     * Import Cuti Izin Excel file and update attendance records + evaluation scores.
     *
     * @param string|UploadedFile $file Path or UploadedFile
     * @return array{
     *     period: string,
     *     month: int,
     *     year: int,
     *     total_processed: int,
     *     updated_employees: int,
     *     recalculated_evaluations: int
     * }
     */
    public function import(string|UploadedFile $file, ?int $overrideMonth = null, ?int $overrideYear = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($filePath)) {
            throw new RuntimeException("File tidak ditemukan: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Detect period from Row 5 (Cell C5 or A5: e.g. "PERIOD : 2026/09 - 2026/09")
        $detectedMonth = $overrideMonth;
        $detectedYear = $overrideYear;

        if (! $detectedMonth || ! $detectedYear) {
            $periodText = (string) ($sheet->getCell('C5')->getValue() ?? $sheet->getCell('A5')->getValue() ?? '');
            if (preg_match('/(\d{4})\/(\d{2})/', $periodText, $matches)) {
                $detectedYear ??= (int) $matches[1];
                $detectedMonth ??= (int) $matches[2];
            }
        }

        if (! $detectedMonth || ! $detectedYear) {
            throw new RuntimeException('Periode bulan dan tahun tidak dapat dideteksi dari file Excel.');
        }

        $month = $detectedMonth;
        $year = $detectedYear;

        // 2. Parse data rows starting from row 9
        $highestRow = $sheet->getHighestRow();
        $employeeIzinMap = []; // nik => count

        for ($row = 9; $row <= $highestRow; $row++) {
            $nikRaw = $sheet->getCell('B' . $row)->getValue();
            if ($nikRaw === null || trim((string) $nikRaw) === '') {
                continue;
            }

            $nik = trim((string) $nikRaw);
            $valRaw = $sheet->getCell('E' . $row)->getValue();
            $val = ($valRaw !== null && is_numeric($valRaw)) ? max(0, (int) $valRaw) : 0;

            $employeeIzinMap[$nik] = $val;
        }

        if (empty($employeeIzinMap)) {
            throw new RuntimeException('Tidak ada data karyawan yang ditemukan pada file Excel.');
        }

        $updatedEmployees = 0;
        $allNiks = array_keys($employeeIzinMap);

        // 3. Update attendance_records inside a transaction
        DB::transaction(function () use ($employeeIzinMap, $allNiks, $month, $year, &$updatedEmployees) {
            // First, reset izin to 0 for all employees in this batch for the period
            AttendanceRecord::whereIn('nik', $allNiks)
                ->whereYear('shift_date', $year)
                ->whereMonth('shift_date', $month)
                ->update(['izin' => 0]);

            // Then, for each employee with izin > 0, distribute or set their izin
            foreach ($employeeIzinMap as $nik => $izinCount) {
                if ($izinCount <= 0) {
                    continue;
                }

                $records = AttendanceRecord::where('nik', $nik)
                    ->whereYear('shift_date', $year)
                    ->whereMonth('shift_date', $month)
                    ->orderBy('shift_date', 'asc')
                    ->get();

                if ($records->isEmpty()) {
                    // Create an attendance record for day 1 if no records exist yet
                    AttendanceRecord::create([
                        'nik' => $nik,
                        'shift_date' => Carbon::create($year, $month, 1)->toDateString(),
                        'alpha' => 0,
                        'telat' => 0,
                        'cuti' => 0,
                        'izin' => $izinCount,
                        'sakit' => 0,
                        'synced_at' => now(),
                    ]);
                } else {
                    // Distribute 1 izin per day across the first $izinCount records
                    $recordCount = $records->count();
                    $daysToAssign = min($izinCount, $recordCount);

                    for ($i = 0; $i < $daysToAssign; $i++) {
                        $records[$i]->izin = 1;
                    }

                    // If izinCount exceeds total days in record, add remainder to the last day
                    if ($izinCount > $recordCount) {
                        $records[$recordCount - 1]->izin += ($izinCount - $recordCount);
                    }

                    foreach ($records as $r) {
                        if ($r->isDirty('izin')) {
                            $r->save();
                        }
                    }
                }

                $updatedEmployees++;
            }
        });

        // 4. Automatically recalculate evaluation_datas for that period
        $recalculatedEvaluations = $this->recalculateEvaluations($month, $year, $allNiks);

        Log::info('Cuti Izin Excel imported successfully', [
            'period' => "{$year}-{$month}",
            'total_rows' => count($employeeIzinMap),
            'updated_employees' => $updatedEmployees,
            'recalculated_evaluations' => $recalculatedEvaluations,
        ]);

        $periodLabel = Carbon::create($year, $month, 1)->translatedFormat('F Y');

        return [
            'period' => $periodLabel,
            'month' => $month,
            'year' => $year,
            'total_processed' => count($employeeIzinMap),
            'updated_employees' => $updatedEmployees,
            'recalculated_evaluations' => $recalculatedEvaluations,
        ];
    }

    private function recalculateEvaluations(int $month, int $year, array $niks): int
    {
        $evalRecords = EvaluationData::whereYear('Month', $year)
            ->whereMonth('Month', $month)
            ->whereIn('NIK', $niks)
            ->get();

        if ($evalRecords->isEmpty()) {
            return 0;
        }

        $attendanceData = AttendanceRecord::whereIn('nik', $niks)
            ->whereYear('shift_date', $year)
            ->whereMonth('shift_date', $month)
            ->selectRaw('
                nik,
                COALESCE(SUM(alpha), 0) AS agg_alpha,
                COALESCE(SUM(telat), 0) AS agg_telat,
                COALESCE(SUM(cuti),  0) AS agg_cuti,
                COALESCE(SUM(izin),  0) AS agg_izin,
                COALESCE(SUM(sakit), 0) AS agg_sakit
            ')
            ->groupBy('nik')
            ->get()
            ->keyBy('nik');

        $recalculated = 0;

        DB::transaction(function () use ($evalRecords, $attendanceData, &$recalculated) {
            foreach ($evalRecords as $record) {
                $att = $attendanceData->get($record->NIK);

                $record->Alpha = (int) ($att?->agg_alpha ?? 0);
                $record->Telat = (int) ($att?->agg_telat ?? 0);
                $record->Izin = (int) ($att?->agg_izin ?? 0);
                $record->Sakit = (int) ($att?->agg_sakit ?? 0);

                $fields = $record->useNewScoringSystem()
                    ? ['kemampuan_kerja', 'kecerdasan_kerja', 'qualitas_kerja', 'disiplin_kerja',
                        'kepatuhan_kerja', 'lembur', 'efektifitas_kerja', 'relawan', 'integritas']
                    : ['kerajinan_kerja', 'kerapian_kerja', 'prestasi', 'loyalitas', 'perilaku_kerja'];

                $scores = $record->only($fields);
                $hasScores = collect($scores)->filter(fn ($v) => ! is_null($v) && $v !== '')->isNotEmpty();

                if ($hasScores) {
                    $total = $record->useNewScoringSystem()
                        ? $this->calculator->calculateTotal($scores, $record)
                        : $this->calculator->calculateTotalOld($scores, $record);

                    $record->total = (int) $total;
                }

                $record->save();
                $recalculated++;
            }
        });

        return $recalculated;
    }
}
