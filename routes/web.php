<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ReservationManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Customer\BrowseController;
use App\Http\Controllers\Customer\ReservationController as CustomerReservationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/items', [BrowseController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [BrowseController::class, 'show'])->name('items.show');

Route::get('/dashboard', function (Request $request) {
    return $request->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('customer.home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/home', [BrowseController::class, 'home'])->name('home');
    Route::get('/reservations', [CustomerReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations', [CustomerReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{reservation}', [CustomerReservationController::class, 'show'])->name('reservations.show');
    Route::patch('/reservations/{reservation}/extend', [CustomerReservationController::class, 'extend'])
        ->name('reservations.extend');
    Route::patch('/reservations/{reservation}/request-cancellation', [CustomerReservationController::class, 'requestCancellation'])
        ->name('reservations.request-cancellation');
    Route::patch('/reservations/{reservation}/request-reschedule', [CustomerReservationController::class, 'requestReschedule'])
        ->name('reservations.request-reschedule');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');

    Route::delete('/inventory/{item}/permanent', [InventoryController::class, 'forceDestroy'])
        ->name('inventory.force-destroy');

    Route::resource('inventory', InventoryController::class)
        ->parameters(['inventory' => 'item']);

    Route::resource('categories', CategoryController::class)
        ->except(['show']);

    Route::get('/reservations', [ReservationManagementController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [ReservationManagementController::class, 'show'])->name('reservations.show');
    Route::patch('/reservations/{reservation}/status', [ReservationManagementController::class, 'updateStatus'])
        ->name('reservations.update-status');
    Route::patch('/reservations/{reservation}/customer-request', [ReservationManagementController::class, 'updateCustomerRequest'])
        ->name('reservations.update-customer-request');

    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
});

require __DIR__.'/auth.php';
