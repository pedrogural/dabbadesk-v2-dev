<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MoneyDeskController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicIntakeSupportController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/orders', [OrdersController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('orders.index');

Route::get('/orders/{order}', [OrdersController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('orders.show');

Route::get('/money-desk', [MoneyDeskController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('money-desk.index');

Route::get('/money-desk/customer-search', [MoneyDeskController::class, 'customerSearch'])
    ->middleware(['auth', 'verified'])
    ->name('money-desk.customer-search');

Route::get('/money-desk/order-search', [MoneyDeskController::class, 'orderSearch'])
    ->middleware(['auth', 'verified'])
    ->name('money-desk.order-search');

Route::get('/money-desk/anomalies', [MoneyDeskController::class, 'anomalies'])
    ->middleware(['auth', 'verified'])
    ->name('money-desk.anomalies');

Route::get('/money-desk/customers/{customer}', [MoneyDeskController::class, 'customerShow'])
    ->middleware(['auth', 'verified'])
    ->name('money-desk.customers.show');

Route::get('/money-desk/orders/{order}', [MoneyDeskController::class, 'orderShow'])
    ->middleware(['auth', 'verified'])
    ->name('money-desk.orders.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('public/intake')
    ->name('public.intake.')
    ->group(function () {
        Route::post('/detect-retailer', [PublicIntakeSupportController::class, 'detectRetailer'])
            ->name('detect-retailer');

        Route::get('/fee-policy', [PublicIntakeSupportController::class, 'feePolicy'])
            ->name('fee-policy');

        Route::post('/submit', [PublicIntakeSupportController::class, 'submit'])
            ->name('submit');

        Route::get('/countries', [PublicIntakeSupportController::class, 'countries'])
            ->name('countries');
    });




require __DIR__ . '/auth.php';