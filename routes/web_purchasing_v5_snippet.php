<?php

use App\Http\Controllers\PurchasingController;
use Illuminate\Support\Facades\Route;

// Add this line inside the existing authenticated route group, next to the other purchasing routes:
Route::post('/purchasing/items/{item}/inspection', [PurchasingController::class, 'updateInspectionFlag'])->name('purchasing.items.inspection.update');
