<?php

namespace App\Livewire\Ticketing;

use App\Domains\Ticketing\Actions\CalculateKpiMetrics;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class TicketDashboard extends Component
{
    public array $metrics = [];

    public function mount(CalculateKpiMetrics $calculator)
    {
        $user = Auth::user();
        if (! $user || ! ($user->hasRole('super-admin') || $user->hasRole('admin') || $user->hasAnyPermission(['ticketing.manage', 'system.admin']))) {
            abort(403);
        }

        $this->metrics = $calculator->execute($user);
    }

    #[Layout('new.layouts.app')]
    public function render()
    {
        return view('livewire.ticketing.ticket-dashboard');
    }
}
