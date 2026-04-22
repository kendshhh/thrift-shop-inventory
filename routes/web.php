<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PaymentSettingsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ReservationManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Customer\BrowseController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\ReservationController as CustomerReservationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

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
Route::get('/items/{item}/reserve-now', [BrowseController::class, 'reserveNow'])->name('items.reserve-now');
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
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/reservations', [CustomerReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations', [CustomerReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{reservation}', [CustomerReservationController::class, 'show'])->name('reservations.show');
    Route::patch('/reservations/{reservation}/extend', [CustomerReservationController::class, 'extend'])
        ->name('reservations.extend');
    Route::patch('/reservations/{reservation}/request-cancellation', [CustomerReservationController::class, 'requestCancellation'])
        ->name('reservations.request-cancellation');
    Route::patch('/reservations/{reservation}/request-reschedule', [CustomerReservationController::class, 'requestReschedule'])
        ->name('reservations.request-reschedule');
    Route::patch('/reservations/{reservation}/items/{reservationItem}/cancel', [CustomerReservationController::class, 'cancelItem'])
        ->name('reservations.cancel-item');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/payments', [PaymentSettingsController::class, 'edit'])->name('payments.edit');
    Route::put('/payments', [PaymentSettingsController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{paymentId}', [PaymentSettingsController::class, 'destroy'])->name('payments.destroy');

    Route::delete('/inventory/{item}/permanent', [InventoryController::class, 'forceDestroy'])
        ->name('inventory.force-destroy');
    Route::patch('/inventory/{item}/unarchive', [InventoryController::class, 'unarchive'])
        ->name('inventory.unarchive');

    Route::resource('inventory', InventoryController::class)
        ->parameters(['inventory' => 'item']);

    Route::resource('categories', CategoryController::class)
        ->except(['show']);

    Route::get('/reservations', [ReservationManagementController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/overview', [ReservationManagementController::class, 'overview'])->name('reservations.overview');
    Route::get('/reservations/{reservation}', [ReservationManagementController::class, 'show'])->name('reservations.show');
    Route::patch('/reservations/{reservation}/status', [ReservationManagementController::class, 'updateStatus'])
        ->name('reservations.update-status');
    Route::patch('/reservations/{reservation}/customer-request', [ReservationManagementController::class, 'updateCustomerRequest'])
        ->name('reservations.update-customer-request');
    Route::patch('/reservations/{reservation}/extend', [ReservationManagementController::class, 'extend'])
        ->name('reservations.extend');
    Route::patch('/reservations/{reservation}/mark-sold', [ReservationManagementController::class, 'markSold'])
        ->name('reservations.mark-sold');
    Route::patch('/reservations/{reservation}/items/{reservationItem}/remove', [ReservationManagementController::class, 'removeItem'])
        ->name('reservations.remove-item');
    Route::patch('/reservations/{reservation}/items/{reservationItem}/cancel-request', [ReservationManagementController::class, 'handleItemCancelRequest'])
        ->name('reservations.item-cancel-request');
    Route::patch('/reservations/users/{user}/cancel-all', [ReservationManagementController::class, 'cancelAllForUser'])
        ->name('reservations.cancel-all-for-user');

    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
});

Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
});

require __DIR__.'/auth.php';
