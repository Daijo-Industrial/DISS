<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Application\Dashboard\DashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MySubmissions extends Component
{
    #[Computed]
    public function submissions()
    {
        return app(DashboardService::class)->getUserSubmissions(Auth::user(), 6);
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.my-submissions');
    }
}
