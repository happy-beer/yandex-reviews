<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\PlaceSyncController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reviews', [ReviewsController::class, 'index'])->name('reviews.index');
    Route::post('/places/{place}/sync', [PlaceSyncController::class, 'store'])->name('places.sync');
    Route::resource('places', PlaceController::class);
});

require __DIR__.'/auth.php';
