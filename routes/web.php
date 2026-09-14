<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('booking')->name('booking.')->group(function () {
    Route::post('/search', [BookingController::class, 'search'])->name('search');
    Route::get('/create', [BookingController::class, 'create'])->name('create');
    Route::post('/store', [BookingController::class, 'store'])->name('store');
    Route::get('/confirmation/{bookingNumber}', [BookingController::class, 'confirmation'])->name('confirmation');
});

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\DriverLeaveController;
use App\Http\Controllers\Admin\NearbyCityController;
use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\VehicleCategoryController;
use App\Http\Controllers\Admin\VehicleController;

// Admin Routes (Protected)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('bookings', AdminBookingController::class);
    Route::delete('vehicles/images/{image}', [VehicleController::class, 'destroyImage'])->name('vehicles.images.destroy');
    Route::resource('vehicles', VehicleController::class);
    Route::resource('drivers', DriverController::class);

    Route::resource('cities', CityController::class)->except(['show']);
    Route::get('cities/{city}/nearby', [NearbyCityController::class, 'index'])->name('cities.nearby');
    Route::post('cities/{city}/nearby', [NearbyCityController::class, 'store'])->name('cities.nearby.store');
    Route::put('cities/{city}/nearby/{nearby}', [NearbyCityController::class, 'update'])->name('cities.nearby.update');
    Route::delete('cities/{city}/nearby/{nearby}', [NearbyCityController::class, 'destroy'])->name('cities.nearby.destroy');

    Route::resource('vehicle-categories', VehicleCategoryController::class)->except(['show']);
    Route::resource('service-types', ServiceTypeController::class)->except(['show']);

    Route::get('drivers/{driver}/leaves', [DriverLeaveController::class, 'index'])->name('drivers.leaves');
    Route::post('drivers/{driver}/leaves', [DriverLeaveController::class, 'store'])->name('drivers.leaves.store');
    Route::put('drivers/{driver}/leaves/{leave}', [DriverLeaveController::class, 'update'])->name('drivers.leaves.update');
    Route::delete('drivers/{driver}/leaves/{leave}', [DriverLeaveController::class, 'destroy'])->name('drivers.leaves.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// For convenience, redirect /dashboard to /admin/dashboard
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
