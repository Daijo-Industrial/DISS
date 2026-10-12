<?php

namespace App\Providers;

use App\Application\Approval\Contracts\Approvals;
use App\Application\Auth\UserRoles;
use App\Domain\Approval\Contracts\RuleResolver;
use App\Domain\Attendance\Repositories\AttendanceRepository;
use App\Domain\Department\Repositories\DepartmentRepository;
use App\Domain\Employee\Repositories\EmployeeRepository;
use App\Domain\Expenses\Contracts\ExpenseReadRepository;
use App\Domain\Overtime\Models\OvertimeForm;
use App\Domain\Overtime\Observers\OvertimeFormObserver;
use App\Domain\Project\Observers\ProjectCheckpointObserver;
use App\Domain\Project\Observers\ProjectObserver;
use App\Domain\PurchaseRequest\Repositories\PurchaseRequestRepository;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\Verification\Repositories\VerificationReportRepository;
use App\Infrastructure\Approval\Services\ApprovalEngine;
use App\Infrastructure\Approval\Services\DefaultRuleResolver;
use App\Infrastructure\Auth\SpatieUserRoles;
use App\Infrastructure\Persistence\Eloquent\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectCheckpoint;
use App\Infrastructure\Persistence\Eloquent\Models\VerificationItem;
use App\Infrastructure\Persistence\Eloquent\Models\VerificationReport;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepartmentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchaseRequestRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentVerificationReportRepository;
use App\Infrastructure\Persistence\Laravel\ExpenseReadRepositoryDb;
use App\Models\PurchaseRequest;
use App\Observers\VerificationItemObserver;
use App\Services\Payroll\Contracts\JPayrollClientContract;
use App\Services\Payroll\JPayrollClient;
use App\Services\Payroll\PayrollSyncOrchestrator;
use App\Services\Payroll\Sync\Phases\AnnualLeaveSyncPhase;
use App\Services\Payroll\Sync\Phases\AttendanceSyncPhase;
use App\Services\Payroll\Sync\Phases\EmployeeSyncPhase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Yajra\DataTables\Html\Builder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            JPayrollClientContract::class,
            fn () => JPayrollClient::fromConfig(),
        );

        // Bind the ordered list of sync phases for PayrollSyncOrchestrator.
        // To add a new phase, append it here — no other files need changing.
        $this->app->bind(
            PayrollSyncOrchestrator::class,
            fn ($app) => new PayrollSyncOrchestrator([
                $app->make(EmployeeSyncPhase::class),
                $app->make(AnnualLeaveSyncPhase::class),
                $app->make(AttendanceSyncPhase::class),
            ]),
        );

        $this->app->bind(
            AttendanceRepository::class,
            EloquentAttendanceRepository::class,
        );

        $this->app->bind(
            ExpenseReadRepository::class,
            ExpenseReadRepositoryDb::class
        );

        $this->app->bind(
            VerificationReportRepository::class,
            EloquentVerificationReportRepository::class
        );

        $this->app->bind(RuleResolver::class, DefaultRuleResolver::class);
        $this->app->bind(Approvals::class, ApprovalEngine::class);

        $this->app->bind(UserRoles::class, SpatieUserRoles::class);

        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            EmployeeRepository::class,
            EloquentEmployeeRepository::class,
        );

        $this->app->bind(
            DepartmentRepository::class,
            EloquentDepartmentRepository::class,
        );

        $this->app->bind(PurchaseRequestRepository::class, EloquentPurchaseRequestRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Model::unguard();  -> kalau pake ini , semua model tidak perlu dibuat fillable / di definisikan
        OvertimeForm::observe(OvertimeFormObserver::class);
        VerificationItem::observe(VerificationItemObserver::class);
        Project::observe(ProjectObserver::class);
        ProjectCheckpoint::observe(ProjectCheckpointObserver::class);

        Relation::morphMap([
            'pr' => PurchaseRequest::class,
            'overtime' => OvertimeForm::class,
            'verification_report' => VerificationReport::class,
        ]);

        Blade::directive('currency', function ($expression) {
            return "<?php echo $expression !== null ? 'Rp ' . number_format(floatval($expression), 2, ',', '.') : ''; ?>";
        });

        Blade::directive('currencyUSD', function ($expression) {
            return "<?php echo $expression !== null ? '$ ' . number_format(floatval($expression), 2, ',', '.') : ''; ?>";
        });

        Blade::directive('currencyCNY', function ($expression) {
            return "<?php echo $expression !== null ? '¥ ' . number_format(floatval($expression), 2, ',', '.') : ''; ?>";
        });

        Blade::directive('formatDate', function ($expression) {
            return "<?php echo $expression !== null ? \Carbon\Carbon::parse($expression)->format('d-m-Y') : '-'; ?>";
        });
        Paginator::useBootstrap();
        Builder::useVite();
    }
}
