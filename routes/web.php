<?php

use App\Http\Controllers\Admin\GlobalFeesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraftOrdersController;
use App\Http\Controllers\MoneyDeskController;
use App\Http\Controllers\OrderRequestsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PurchasingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicIntakeSupportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    Route::get('/purchasing', [PurchasingController::class, 'index'])->name('purchasing.index');
    Route::get('/purchasing/orders/{order}', [PurchasingController::class, 'show'])->name('purchasing.orders.show');
    Route::get('/purchasing/order/{order}', [PurchasingController::class, 'show'])->name('purchasing.show');
    Route::post('/purchasing/purchases', [PurchasingController::class, 'storePurchase'])->name('purchasing.purchases.store');
    Route::post('/purchasing/purchases/bulk', [PurchasingController::class, 'storeBulkPurchase'])->name('purchasing.purchases.bulk');
    Route::post('/purchasing/problems', [PurchasingController::class, 'storeProblem'])->name('purchasing.problems.store');
    Route::post('/purchasing/purchases/{purchase}/undo', [PurchasingController::class, 'undoPurchase'])->name('purchasing.purchases.undo');

    Route::post('/purchasing/items/{item}/inspection', [PurchasingController::class, 'updateInspectionFlag'])->name('purchasing.items.inspection.update');

    Route::get('/customers', [CustomersController::class, 'index'])->name('customers.index');
    Route::get('/customers/live-search', [CustomersController::class, 'liveSearch'])->name('customers.live-search');
    Route::get('/customers/create', [CustomersController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomersController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomersController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomersController::class, 'edit'])->name('customers.edit');
    Route::patch('/customers/{customer}', [CustomersController::class, 'update'])->name('customers.update');
    Route::post('/customers/{customer}/notes', [CustomersController::class, 'storeNote'])->name('customers.notes.store');

    Route::get('/admin/fees', [GlobalFeesController::class, 'index'])->name('admin.fees.index');
    Route::post('/admin/fees', [GlobalFeesController::class, 'store'])->name('admin.fees.store');
    Route::post('/orders/{order}/notes', [OrdersController::class, 'storeNote'])->name('orders.notes.store');
    Route::post('/orders/{order}/payments', [OrdersController::class, 'storePayment'])->name('orders.payments.store');
    Route::post('/orders/{order}/invoices', [OrdersController::class, 'createInvoice'])->name('orders.invoices.store');
    Route::post('/orders/{order}/payments/{transaction}/void', [OrdersController::class, 'voidPayment'])->name('orders.payments.void');
    Route::post('/orders/{order}/ledger-payments/{ledger}/void', [OrdersController::class, 'voidLedgerPayment'])->name('orders.ledger-payments.void');
    Route::post('/orders/{order}/refunds', [OrdersController::class, 'issueRefund'])->name('orders.refunds.store');
    Route::post('/orders/{order}/credits', [OrdersController::class, 'issueCredit'])->name('orders.credits.store');
    Route::get('/orders/{order}', [OrdersController::class, 'show'])->name('orders.show');

    Route::get('/order-requests', [OrderRequestsController::class, 'index'])->name('order-requests.index');
    Route::get('/order-requests/create/manual', [OrderRequestsController::class, 'createManual'])->name('order-requests.create-manual');
    Route::post('/order-requests/create/manual', [OrderRequestsController::class, 'storeManual'])->name('order-requests.store-manual');
    Route::get('/order-requests/counter', [OrderRequestsController::class, 'counter'])->name('order-requests.counter');
    Route::post('/order-requests/{orderRequest}/review', [OrderRequestsController::class, 'markReviewed'])->name('order-requests.review');
    Route::post('/order-requests/{orderRequest}/cancel', [OrderRequestsController::class, 'cancel'])->name('order-requests.cancel');
    Route::post('/order-requests/{orderRequest}/retailers', [OrderRequestsController::class, 'storeRetailerForRequest'])->name('order-requests.retailers.store');
    Route::post('/order-requests/{orderRequest}/items/{item}', [OrderRequestsController::class, 'updateItem'])->name('order-requests.items.update');
    Route::post('/order-requests/{orderRequest}/convert', [OrderRequestsController::class, 'convert'])->name('order-requests.convert');
    Route::get('/order-requests/{orderRequest}/attachments/{attachment}', [OrderRequestsController::class, 'openAttachment'])->name('order-requests.attachments.show');
    Route::get('/order-requests/{orderRequest}', [OrderRequestsController::class, 'show'])->name('order-requests.show');

    Route::get('/draft-orders', [DraftOrdersController::class, 'index'])->name('draft-orders.index');
    Route::post('/draft-orders/detect-retailer', [DraftOrdersController::class, 'detectRetailer'])->name('draft-orders.detect-retailer');
    Route::get('/draft-orders/{draftOrder}', [DraftOrdersController::class, 'show'])->name('draft-orders.show');
    Route::post('/draft-orders/detect-retailer', [DraftOrdersController::class, 'detectRetailer'])->name('draft-orders.detect-retailer');
    Route::post('/draft-orders/retailers/quick-store', [DraftOrdersController::class, 'quickStoreRetailer'])->name('draft-orders.retailers.quick-store');
    Route::patch('/draft-orders/{draftOrder}', [DraftOrdersController::class, 'update'])->name('draft-orders.update');
    Route::patch('/draft-orders/{draftOrder}/customer', [DraftOrdersController::class, 'updateCustomer'])->name('draft-orders.customer.update');
    Route::patch('/draft-orders/{draftOrder}/fees', [DraftOrdersController::class, 'updateFees'])->name('draft-orders.fees.update');
    Route::post('/draft-orders/{draftOrder}/items', [DraftOrdersController::class, 'addItem'])->name('draft-orders.items.store');
    Route::patch('/draft-orders/{draftOrder}/items/{item}', [DraftOrdersController::class, 'updateItem'])->name('draft-orders.items.update');
    Route::patch('/draft-orders/{draftOrder}/retailers/{retailer}/delivery', [DraftOrdersController::class, 'updateRetailerDelivery'])->name('draft-orders.retailers.delivery.update');
    Route::delete('/draft-orders/{draftOrder}/items/{item}', [DraftOrdersController::class, 'deleteItem'])->name('draft-orders.items.destroy');
    Route::post('/draft-orders/{draftOrder}/finalise', [DraftOrdersController::class, 'finalise'])->name('draft-orders.finalise');
    Route::post('/draft-orders/{draftOrder}/notes', [DraftOrdersController::class, 'addNote'])->name('draft-orders.notes.store');

    Route::get('/money-desk', [MoneyDeskController::class, 'index'])->name('money-desk.index');
    Route::get('/money-desk/customer-search', [MoneyDeskController::class, 'customerSearch'])->name('money-desk.customer-search');
    Route::get('/money-desk/order-search', [MoneyDeskController::class, 'orderSearch'])->name('money-desk.order-search');
    Route::get('/money-desk/anomalies', [MoneyDeskController::class, 'anomalies'])->name('money-desk.anomalies');
    Route::get('/money-desk/customers/{customer}', [MoneyDeskController::class, 'customerShow'])->name('money-desk.customers.show');
    Route::get('/money-desk/orders/{order}', [MoneyDeskController::class, 'orderShow'])->name('money-desk.orders.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('public/intake')->name('public.intake.')->group(function () {
    Route::post('/detect-retailer', [PublicIntakeSupportController::class, 'detectRetailer'])->name('detect-retailer');
    Route::get('/fee-policy', [PublicIntakeSupportController::class, 'feePolicy'])->name('fee-policy');
    Route::post('/submit', [PublicIntakeSupportController::class, 'submit'])->name('submit');
    Route::get('/countries', [PublicIntakeSupportController::class, 'countries'])->name('countries');
});

require __DIR__ . '/auth.php';