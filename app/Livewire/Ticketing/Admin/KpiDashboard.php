<?php

namespace App\Livewire\Ticketing\Admin;

use App\Domains\Ticketing\Actions\CalculateKpiMetrics;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class KpiDashboard extends Component
{
    public array $picMetrics = [];

    public function mount(CalculateKpiMetrics $calculator)
    {
        $user = Auth::user();
        if (! $user || ! ($user->hasRole('super-admin') || $user->hasRole('admin') || $user->hasAnyPermission(['ticketing.admin', 'system.admin']))) {
            abort(403);
        }

        $pics = User::whereHas('assignedTickets')->get();

        foreach ($pics as $pic) {
            $this->picMetrics[] = $calculator->execute($pic);
        }
    }

    #[Layout('new.layouts.app')]
    public function render()
    {
        return view('livewire.ticketing.admin.kpi-dashboard');
    }
}
