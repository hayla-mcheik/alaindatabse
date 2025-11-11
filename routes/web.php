<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnalyticsRecordController;
use App\Http\Controllers\AnaylticsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// Corrected Controller usage
Route::middleware('auth')->group(function () {
Route::get('/analytics', [AnalyticsRecordController::class, 'index'])->name('analytics.index');
Route::post('/analytics/import', [AnalyticsRecordController::class, 'import'])->name('analytics.import');
Route::delete('/analytics/{record}', [AnalyticsRecordController::class, 'destroy'])->name('analytics.destroy');
Route::get('/analytics/clear-all', [AnalyticsRecordController::class, 'clearAll'])->name('analytics.clear-all');
Route::get('/analytics/stats', [AnalyticsRecordController::class, 'stats'])->name('analytics.stats');
Route::get('/analytics/export', [AnalyticsRecordController::class, 'export'])->name('analytics.export');   

// Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
