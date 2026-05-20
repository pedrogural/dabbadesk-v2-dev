<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MoneyDeskController;
use App\Http\Controllers\OrderRequestsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicIntakeSupportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/orders', [OrdersController::class, 'index'])
        ->name('orders.index');

    Route::get('/orders/{order}', [OrdersController::class, 'show'])
        ->name('orders.show');

    Route::get('/order-requests', [OrderRequestsController::class, 'index'])
        ->name('order-requests.index');

    Route::get('/order-requests/counter', [OrderRequestsController::class, 'counter'])
        ->name('order-requests.counter');

    Route::get('/order-requests/{orderRequest}', [OrderRequestsController::class, 'show'])
        ->name('order-requests.show');

    Route::patch('/order-requests/{orderRequest}/status', [OrderRequestsController::class, 'updateStatus'])
        ->name('order-requests.update-status');

    Route::post('/order-requests/{orderRequest}/notes', [OrderRequestsController::class, 'storeNote'])
        ->name('order-requests.notes.store');

    Route::get('/order-requests/{orderRequest}/attachments/{attachment}', [OrderRequestsController::class, 'attachment'])
        ->name('order-requests.attachments.show');

    Route::get('/money-desk', [MoneyDeskController::class, 'index'])
        ->name('money-desk.index');

    Route::get('/money-desk/customer-search', [MoneyDeskController::class, 'customerSearch'])
        ->name('money-desk.customer-search');

    Route::get('/money-desk/order-search', [MoneyDeskController::class, 'orderSearch'])
        ->name('money-desk.order-search');

    Route::get('/money-desk/anomalies', [MoneyDeskController::class, 'anomalies'])
        ->name('money-desk.anomalies');

    Route::get('/money-desk/customers/{customer}', [MoneyDeskController::class, 'customerShow'])
        ->name('money-desk.customers.show');

    Route::get('/money-desk/orders/{order}', [MoneyDeskController::class, 'orderShow'])
        ->name('money-desk.orders.show');

    Route::get('/order-requests/{orderRequest}', [OrderRequestsController::class, 'show'])
        ->name('order-requests.show');

    Route::post('/order-requests/{orderRequest}/convert', [OrderRequestsController::class, 'convert'])
        ->name('order-requests.convert');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
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
