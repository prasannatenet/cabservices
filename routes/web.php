<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('booking')->name('booking.')->group(function () {
    Route::match(['get', 'post'], '/search', [BookingController::class, 'search'])->name('search');
    Route::get('/create', [BookingController::class, 'create'])->name('create');
    Route::post('/store', [BookingController::class, 'store'])->name('store');
    Route::get('/confirmation/{bookingNumber}', [BookingController::class, 'confirmation'])->name('confirmation');
});

use App\Http\Controllers\Admin\AssociateController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\DriverLeaveController;
use App\Http\Controllers\Admin\NearbyCityController;
use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\VehicleCategoryController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Associate\BookingController as AssociateBookingController;
use App\Http\Controllers\Associate\CityController as AssociateCityController;
use App\Http\Controllers\Associate\DashboardController as AssociateDashboardController;
use App\Http\Controllers\Associate\DriverController as AssociateDriverController;
use App\Http\Controllers\Associate\DriverLeaveController as AssociateDriverLeaveController;
use App\Http\Controllers\Associate\ServiceTypeController as AssociateServiceTypeController;
use App\Http\Controllers\Associate\VehicleController as AssociateVehicleController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;

// Admin Routes (Protected)
Route::middleware(['auth', 'role:admin', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('associates', AssociateController::class)->except(['show']);
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

// Driver Portal Routes (Protected)
Route::middleware(['auth', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
    Route::post('/availability', [DriverDashboardController::class, 'toggleAvailability'])->name('availability.toggle');
    Route::get('/rides', [DriverDashboardController::class, 'rides'])->name('rides');
    Route::get('/cities', [DriverDashboardController::class, 'cities'])->name('cities');
    Route::post('/cities', [DriverDashboardController::class, 'syncCities'])->name('cities.sync');
});

// Associate Portal Routes (Protected)
// An associate is the admin of the cities assigned to him: he manages the fleet,
// drivers, services and bookings of those cities only. He can never open /admin.
Route::middleware(['auth', 'role:associate', 'verified'])->prefix('associate')->name('associate.')->group(function () {
    Route::get('/dashboard', [AssociateDashboardController::class, 'index'])->name('dashboard');

    Route::delete('vehicles/images/{image}', [AssociateVehicleController::class, 'destroyImage'])->name('vehicles.images.destroy');
    Route::resource('vehicles', AssociateVehicleController::class);

    Route::resource('drivers', AssociateDriverController::class);

    Route::get('drivers/{driver}/leaves', [AssociateDriverLeaveController::class, 'index'])->name('drivers.leaves');
    Route::post('drivers/{driver}/leaves', [AssociateDriverLeaveController::class, 'store'])->name('drivers.leaves.store');
    Route::put('drivers/{driver}/leaves/{leave}', [AssociateDriverLeaveController::class, 'update'])->name('drivers.leaves.update');
    Route::delete('drivers/{driver}/leaves/{leave}', [AssociateDriverLeaveController::class, 'destroy'])->name('drivers.leaves.destroy');

    Route::resource('service-types', AssociateServiceTypeController::class)->except(['show']);

    Route::resource('bookings', AssociateBookingController::class)->only(['index', 'show', 'update']);

    Route::get('cities', [AssociateCityController::class, 'index'])->name('cities.index');
});

// For convenience, redirect /dashboard to the dashboard of the authenticated role
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->homeRouteName());
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';
