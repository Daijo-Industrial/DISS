<?php

namespace App\Livewire\Dashboard;

use App\Application\Dashboard\DashboardService;
use App\Services\NavigationService;
use Livewire\Component;

class GlobalDashboard extends Component
{
    public array $kpis = [];

    public function mount(DashboardService $dashboardService)
    {
        $this->kpis = $dashboardService->getKpiSummary(auth()->user());
    }

    public function render()
    {
        $user = auth()->user();
        $quickAccessItems = [];

        if ($user) {
            $items = NavigationService::getQuickAccessItems($user);
            // On /home, filter out 'home' to avoid redundant navigation to the current page
            $quickAccessItems = array_values(array_filter($items, fn ($item) => ($item['route'] ?? '') !== 'home'));
        }

        return view('livewire.dashboard.global-dashboard', [
            'quickAccessItems' => $quickAccessItems,
        ])->layout('new.layouts.app');
    }
}
