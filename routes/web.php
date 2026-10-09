<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\GuestOnly;
use App\Http\Middleware\ReceptionOnly;
use App\Http\Middleware\StaffOnly;
use Illuminate\Support\Facades\Route;

// public site: anyone can look at the rooms without an account
Route::get('/', [GuestController::class, 'home']);

// guests sign up (email, password, terms and conditions) before they can reserve
Route::middleware('guest')->group(function () {
    Route::get('/signup', [GuestController::class, 'home'])->defaults('open', 'signup');
    Route::post('/signup', [GuestController::class, 'signup'])->middleware('throttle:10,1');
});

// log in and sign up are pop-ups on the public home page
Route::get('/login', [GuestController::class, 'home'])->name('login')->defaults('open', 'login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// set or reset a password with a link sent by email
Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');

Route::middleware('auth')->group(function () {
    // guest side: reserve a room and see their own reservations
    Route::middleware(GuestOnly::class)->group(function () {
        Route::get('/book', [GuestController::class, 'bookForm']);
        Route::post('/book', [GuestController::class, 'book']);
        Route::get('/my-reservations', [GuestController::class, 'reservations']);
        Route::post('/my-reservations/{booking}/cancel', [GuestController::class, 'cancel']);

        // feedback or a question sent from the menu under the guest's name
        Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:10,1');
    });

    // the ID uploaded with an online reservation (staff, or the guest who sent it)
    Route::get('/approvals/{booking}/id', [ApprovalController::class, 'idPhoto']);

    // reception and the admin both approve or decline online reservations
    Route::middleware(StaffOnly::class)->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index']);
        Route::post('/approvals/{booking}/approve', [ApprovalController::class, 'approve']);
        Route::post('/approvals/{booking}/decline', [ApprovalController::class, 'decline']);
    });

    // admin side: locations, rooms and accounts
    Route::middleware(AdminOnly::class)->group(function () {
        Route::get('/admin', [RoomController::class, 'admin']);

        Route::get('/admin/locations', [LocationController::class, 'index']);
        Route::post('/admin/locations', [LocationController::class, 'store']);
        Route::get('/admin/locations/{location}/edit', [LocationController::class, 'edit']);
        Route::put('/admin/locations/{location}', [LocationController::class, 'update']);
        Route::delete('/admin/locations/{location}', [LocationController::class, 'destroy']);

        // contact details in the footer of the public site
        Route::get('/admin/contact', [SettingController::class, 'edit']);
        Route::put('/admin/contact', [SettingController::class, 'update']);
        Route::post('/admin/banner', [SettingController::class, 'saveBanner']);
        Route::delete('/admin/banner', [SettingController::class, 'removeBanner']);

        // feedback and questions from guests
        Route::get('/admin/messages', [MessageController::class, 'index']);

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
