<?php

use App\Http\Controllers\Manage\FloorController;
use App\Http\Controllers\Manage\LocationController;
use App\Http\Controllers\Manage\OrganizationController;
use Illuminate\Support\Facades\Route;

/*
| Admin portal: owners and admins set up offices and draw floor plans.
| Everything under {organization} is checked by org.manage, and scoped
| bindings make sure each {location} and {floor} belongs to its parent.
*/

Route::middleware(['auth', 'verified'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');

    Route::middleware('org.manage')
        ->prefix('organizations/{organization:slug}')
        ->name('organizations.')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [OrganizationController::class, 'show'])->name('show');

            Route::get('locations/create', [LocationController::class, 'create'])->name('locations.create');
            Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
            Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');

            Route::post('locations/{location}/floors', [FloorController::class, 'store'])->name('floors.store');
            Route::get('locations/{location}/floors/{floor}/edit', [FloorController::class, 'edit'])->name('floors.edit');
            Route::put('locations/{location}/floors/{floor}', [FloorController::class, 'update'])->name('floors.update');
            Route::delete('locations/{location}/floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy');
        });
});
