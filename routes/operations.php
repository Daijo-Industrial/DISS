<?php

use App\Http\Controllers\MaterialPredictionController;
use App\Http\Controllers\PurchasingMaterialController;
use App\Livewire\DailyReports\Index;
use App\Livewire\DailyReports\Show;
use App\Livewire\DestinationForm;
use App\Livewire\DestinationIndex;
use App\Livewire\Services\Form as ServiceForm;
use App\Livewire\Vehicles\Form as VehiclesForm;
use App\Livewire\Vehicles\Index as VehiclesIndex;
use App\Livewire\Vehicles\InspectionForm;
use App\Livewire\Vehicles\Operations;
use App\Livewire\Vehicles\Radar;
use App\Livewire\Vehicles\Scan;
use App\Livewire\Vehicles\Show as VehiclesShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Operations Routes
|--------------------------------------------------------------------------
|
| Routes for managing daily reports, vehicles, services, destinations,
| and file library operations.
|
| RECOMMENDED PERMISSIONS:
| - operations.view-reports
| - operations.manage-vehicles
| - operations.manage-services
| - operations.manage-destinations
| - operations.access-files
|
| RECOMMENDED ROLES: admin, super-admin, operations, logistic, manager
|
*/

Route::middleware('auth')->group(function () {
    // Daily Reports
    Route::get('/daily-reports', Index::class)->name('daily-reports.index');
    Route::get('/daily-reports/{employee_id}', Show::class)->name('daily-reports.show');

    // Update Forecast Data Purchasing
    Route::get('/store-data', [PurchasingMaterialController::class, 'storeDataInNewTable'])->name('construct_data');
    Route::get('/insert-material_prediction', [MaterialPredictionController::class, 'processForemindFinalData'])->name('material_prediction');

    // Destinations
    Route::get('/destinations', DestinationIndex::class)->name('destination.index');
    Route::get('/destinations/create', DestinationForm::class)->name('destination.create');
    Route::get('/destinations/{id}/edit', DestinationForm::class)->name('destination.edit');

    // Vehicles
    Route::get('/vehicles', VehiclesIndex::class)->name('vehicles.index');
    Route::get('/vehicles/radar', Radar::class)->name('vehicles.radar')->middleware('can:fleet.view');
    Route::get('/vehicles/operations', Operations::class)->name('vehicles.operations')->middleware('can:fleet.manage');
    Route::get('/vehicles/scan', Scan::class)->name('vehicles.scan');
    Route::get('/vehicles/{vehicle}', VehiclesShow::class)->name('vehicles.show');
    Route::get('/vehicle/create', VehiclesForm::class)->name('vehicles.create');
    Route::get('/vehicles/{vehicle}/edit', VehiclesForm::class)->name('vehicles.edit');

    // Vehicle Inspections (P2H)
    Route::get('/vehicles/{vehicle}/inspect/{type?}', InspectionForm::class)->name('vehicles.inspect');

    // Vehicle Services
    Route::get('/services/create/{vehicle}', ServiceForm::class)->name('services.create');
    Route::get('/services/{record}/edit', ServiceForm::class)->name('services.edit');
});
