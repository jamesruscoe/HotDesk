<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// shortcut: throwaway diagnostic for the FastAPI engine on Laravel Cloud, delete once the real integration exists.
Route::get('engine-test', function () {
    $engine = Http::baseUrl(config('services.engine.url'))->withToken((string) config('services.engine.token'))->timeout(10);

    try {
        $health = $engine->get('health');
        $beam = $engine->post('beam/calculate', ['span_m' => 6, 'load_kn_per_m' => 10, 'e_gpa' => 210, 'i_cm4' => 8356]);
        $slab = $engine->post('slab/calculate', ['length_x_m' => 5, 'length_y_m' => 4, 'thickness_mm' => 200, 'load_kpa' => 10, 'e_gpa' => 30]);
    } catch (ConnectionException $e) {
        return response()->json(['engine_url' => config('services.engine.url'), 'error' => $e->getMessage()], 502);
    }

    return response()->json([
        'engine_url' => config('services.engine.url'),
        'health' => ['status' => $health->status(), 'body' => $health->json()],
        'beam' => ['status' => $beam->status(), 'body' => $beam->json()],
        'slab' => ['status' => $slab->status(), 'body' => $slab->json()],
    ]);
})->middleware('auth')->name('engine-test');

require __DIR__.'/manage.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
