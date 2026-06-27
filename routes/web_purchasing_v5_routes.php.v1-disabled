<?php

use App\Http\Controllers\PurchasingController;
use Illuminate\Support\Facades\Route;

// Put these inside your existing auth/admin middleware group with the other purchasing routes.
Route::get('/purchasing', [PurchasingController::class, 'index'])->name('purchasing.index');
Route::get('/purchasing/orders/{order}', [PurchasingController::class, 'show'])->name('purchasing.orders.show');
Route::post('/purchasing/purchases/bulk', [PurchasingController::class, 'storeBulkPurchase'])->name('purchasing.purchases.bulk');
Route::post('/purchasing/purchases/{purchase}/undo', [PurchasingController::class, 'undoPurchase'])->name('purchasing.purchases.undo');
Route::post('/purchasing/items/{item}/inspection', [PurchasingController::class, 'updateInspectionFlag'])->name('purchasing.items.inspection.update');
