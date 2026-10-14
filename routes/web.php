<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\ReceptionOnly;
use App\Support\LiveVersion;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// set or reset a password with a link sent by email
Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');

Route::middleware('auth')->group(function () {
    // open pages ask this every few seconds to know if another user changed something
    Route::get('/live-version', fn () => response()->json(['version' => LiveVersion::current()]));

    // admin side: locations, rooms and accounts
    Route::middleware(AdminOnly::class)->group(function () {
        Route::get('/admin', [RoomController::class, 'admin']);

        Route::get('/admin/locations', [LocationController::class, 'index']);
        Route::post('/admin/locations', [LocationController::class, 'store']);
        Route::get('/admin/locations/{location}/edit', [LocationController::class, 'edit']);
        Route::put('/admin/locations/{location}', [LocationController::class, 'update']);
        Route::delete('/admin/locations/{location}', [LocationController::class, 'destroy']);

        Route::resource('rooms', RoomController::class)->except('show');

        Route::get('/admin/users', [UserController::class, 'index']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit']);
        Route::put('/admin/users/{user}', [UserController::class, 'update']);
        Route::post('/admin/users/{user}/toggle', [UserController::class, 'toggle']);
        Route::post('/admin/users/{user}/password-link', [UserController::class, 'sendLink']);
    });

    // reception side: the front desk
    Route::middleware(ReceptionOnly::class)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::post('/rooms/{room}/status', [RoomController::class, 'status']);

        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);

        // check-in process
        Route::get('/checkin', [ReceptionController::class, 'checkinForm']);
        Route::post('/checkin', [ReceptionController::class, 'checkin']);
        Route::get('/checkin/{booking}/slip', [ReceptionController::class, 'slip']);

        // check-out process
        Route::get('/checkout', [ReceptionController::class, 'checkoutList']);
        Route::get('/checkout/{booking}', [ReceptionController::class, 'checkoutProcess']);
        Route::post('/checkout/{booking}/verify', [ReceptionController::class, 'verifyCheckout']);
        Route::post('/checkout/{booking}/inspect', [ReceptionController::class, 'inspect']);
        Route::post('/checkout/{booking}/settle', [ReceptionController::class, 'settle']);
        Route::post('/checkout/{booking}/return-id', [ReceptionController::class, 'returnId']);
        Route::post('/checkout/{booking}/record', [ReceptionController::class, 'recordCheckout']);

        Route::get('/calendar', [ReceptionController::class, 'calendar']);
        Route::get('/reports', [ReceptionController::class, 'reports']);
        Route::get('/reports/export', [ReceptionController::class, 'export']);
        Route::get('/billing/{booking}', [ReceptionController::class, 'bill']);
    });
});
