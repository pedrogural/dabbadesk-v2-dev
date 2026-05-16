<?php

use App\Http\Controllers\MoneyDeskController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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

require __DIR__ . '/auth.php';