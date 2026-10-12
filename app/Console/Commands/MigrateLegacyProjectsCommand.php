<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Project\Enums\ProjectPriority;
use App\Domain\Project\Enums\ProjectStage;
use App\Domain\Project\Enums\ProjectStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Department;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectStageHistory;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyProjectsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diss:migrate-legacy-projects {--dry-run : Run without persisting changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy project_masters prototype data into the modern IT Projects module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('project_masters')) {
            $this->warn('Table "project_masters" does not exist. Nothing to migrate.');

            return self::SUCCESS;
        }

        $legacyProjects = DB::table('project_masters')->get();
        if ($legacyProjects->isEmpty()) {
            $this->info('No records found in "project_masters".');

            return self::SUCCESS;
        }

        $this->info("Found {$legacyProjects->count()} legacy project(s) to migrate.");
        $dryRun = (bool) $this->option('dry-run');

        $defaultUser = User::first();
        if (! $defaultUser) {
            $this->error('No users found in database to assign as fallback creator/PIC.');

            return self::FAILURE;
        }

        $defaultDept = Department::first();
        if (! $defaultDept) {
            $this->error('No departments found in database to assign as fallback department.');

            return self::FAILURE;
        }

        $migratedCount = 0;

        foreach ($legacyProjects as $legacy) {
            // Find or fallback department
            $dept = Department::find($legacy->dept) ?? $defaultDept;

            // Find matching PIC user by name or fallback
            $pic = null;
            if (! empty($legacy->pic)) {
                $pic = User::where('name', 'like', '%' . trim((string) $legacy->pic) . '%')->first();
            }
            $pic = $pic ?? $defaultUser;

            // Map legacy string status to modern Stage & Status
            $statusMap = $this->mapLegacyStatus((string) $legacy->status);
            $stage = $statusMap['stage'];
            $status = $statusMap['status'];

            $requestDate = ! empty($legacy->request_date) ? Carbon::parse($legacy->request_date) : Carbon::now();
            $startDate = ! empty($legacy->start_date) ? Carbon::parse($legacy->start_date) : $requestDate;
            $dueDate = ! empty($legacy->end_date)
                ? Carbon::parse($legacy->end_date)
                : (clone $requestDate)->addMonths(2);
            $finishedDate = ($status === ProjectStatus::COMPLETED)
                ? (! empty($legacy->end_date) ? Carbon::parse($legacy->end_date) : Carbon::parse($legacy->updated_at ?? now()))
                : null;

            $this->line("- Migrating: [ID {$legacy->id}] {$legacy->project_name} -> {$stage->value} ({$status->value})");

            if (! $dryRun) {
                DB::transaction(function () use ($legacy, $dept, $pic, $defaultUser, $stage, $status, $startDate, $dueDate, $finishedDate) {
                    $year = Carbon::parse($legacy->created_at ?? now())->format('Y');
                    $codeCount = Project::whereYear('created_at', $year)->count() + 1;
                    $projectCode = sprintf('IT-%s-%04d', $year, $codeCount);

                    $project = Project::create([
                        'project_code' => $projectCode,
                        'name' => $legacy->project_name,
                        'description' => $legacy->description ?? null,
                        'requester_name' => $pic->name,
                        'requester_department_id' => $dept->id,
                        'primary_pic_id' => $pic->id,
                        'priority' => ProjectPriority::MEDIUM,
                        'stage' => $stage,
                        'status' => $status,
                        'start_date' => $startDate->toDateString(),
                        'original_due_date' => $dueDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
                        'finished_date' => $finishedDate?->toDateString(),
                        'created_by' => $defaultUser->id,
                        'created_at' => $legacy->created_at ?? now(),
                        'updated_at' => $legacy->updated_at ?? now(),
                    ]);

                    // Migrate history if exists
                    if (Schema::hasTable('project_historys')) {
                        $histories = DB::table('project_historys')
                            ->where('project_id', $legacy->id)
                            ->orderBy('created_at')
                            ->get();

                        foreach ($histories as $hist) {
                            $histStatus = $this->mapLegacyStatus((string) $hist->status);
                            ProjectStageHistory::create([
                                'project_id' => $project->id,
                                'from_stage' => ProjectStage::INITIATION,
                                'to_stage' => $histStatus['stage'],
                                'changed_by' => $defaultUser->id,
                                'is_override' => false,
                                'reason' => $hist->remarks ?? 'Legacy status update',
                                'created_at' => $hist->created_at ?? now(),
                            ]);
                        }
                    }
                });
            }

            $migratedCount++;
        }

        $this->info("Successfully migrated {$migratedCount} legacy project(s)!" . ($dryRun ? ' (DRY RUN)' : ''));

        return self::SUCCESS;
    }

    /**
     * @return array{stage: ProjectStage, status: ProjectStatus}
     */
    private function mapLegacyStatus(string $legacyStatus): array
    {
        return match (strtolower(trim($legacyStatus))) {
            'accept', 'accepted', 'completed' => [
                'stage' => ProjectStage::CLOSURE,
                'status' => ProjectStatus::COMPLETED,
            ],
            'readytotest', 'ready to test', 'testing' => [
                'stage' => ProjectStage::MONITORING,
                'status' => ProjectStatus::ACTIVE,
            ],
            'ongoing', 'on going', 'in progress' => [
                'stage' => ProjectStage::DEVELOPMENT,
                'status' => ProjectStatus::ACTIVE,
            ],
            'needtoberevised', 'need to be revised', 'revision' => [
                'stage' => ProjectStage::DEVELOPMENT,
                'status' => ProjectStatus::ACTIVE,
            ],
            default => [
                'stage' => ProjectStage::INITIATION,
                'status' => ProjectStatus::ACTIVE,
            ],
        };
    }
}
