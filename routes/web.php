<?php

use App\Http\Controllers\Admin\GlobalFeesController;
use App\Http\Controllers\Admin\LegacyTextCleanupController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraftOrdersController;
use App\Http\Controllers\MoneyDeskController;
use App\Http\Controllers\OrderRequestsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PurchasingController;
use App\Http\Controllers\PurchaseDeskV2Controller;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicIntakeSupportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    // New purchasing rebuild. Keep this separate from the old Purchasing Desk until Phase 2 is complete.
    Route::get('/purchases', [PurchaseDeskV2Controller::class, 'index'])->name('purchases.index');
    Route::get('/purchases/orders/{order}', [PurchaseDeskV2Controller::class, 'show'])->name('purchases.orders.show');
    Route::post('/purchases/orders/{order}/basket', [PurchaseDeskV2Controller::class, 'storeBasket'])->name('purchases.basket.store');
    Route::post('/purchases/orders/{order}/suppliers', [PurchaseDeskV2Controller::class, 'storeSupplier'])->name('purchases.suppliers.store');
    Route::patch('/purchases/orders/{order}/purchase-batches', [PurchaseDeskV2Controller::class, 'updateBatch'])->name('purchases.batches.update');
    Route::post('/purchases/orders/{order}/purchase-batches/undo', [PurchaseDeskV2Controller::class, 'undoBatch'])->name('purchases.batches.undo');
    Route::patch('/purchases/orders/{order}/purchase-lines/{purchase}', [PurchaseDeskV2Controller::class, 'updateLine'])->name('purchases.lines.update');
    Route::post('/purchases/orders/{order}/purchase-lines/{purchase}/undo', [PurchaseDeskV2Controller::class, 'undoLine'])->name('purchases.lines.undo');
    Route::post('/purchases/orders/{order}/items/{item}/purchases', [PurchaseDeskV2Controller::class, 'storePurchase'])->name('purchases.purchases.store');

    // Temporary backwards-compatible aliases for any old bookmarks during the transition.
    Route::redirect('/purchase-desk-v2', '/purchases')->name('purchase-desk-v2.index');
    Route::redirect('/purchase-desk-v2/orders/{order}', '/purchases/orders/{order}')->name('purchase-desk-v2.orders.show');

    Route::get('/purchasing', [PurchasingController::class, 'index'])->name('purchasing.index');
    Route::get('/purchasing/orders/{order}', [PurchasingController::class, 'show'])->name('purchasing.orders.show');
    Route::get('/purchasing/order/{order}', [PurchasingController::class, 'show'])->name('purchasing.show');
    Route::post('/purchasing/purchases', [PurchasingController::class, 'storePurchase'])->name('purchasing.purchases.store');
    Route::post('/purchasing/purchases/bulk', [PurchasingController::class, 'storeBulkPurchase'])->name('purchasing.purchases.bulk');
    Route::post('/purchasing/retailers/quick-store', [PurchasingController::class, 'quickStoreRetailer'])->name('purchasing.retailers.quick-store');
    Route::post('/purchasing/problems', [PurchasingController::class, 'storeProblem'])->name('purchasing.problems.store');
    Route::patch('/purchasing/problems/{problem}', [PurchasingController::class, 'updateProblem'])->name('purchasing.problems.update');
    Route::post('/purchasing/problems/{problem}/contacted', [PurchasingController::class, 'markCustomerContacted'])->name('purchasing.problems.contacted');
    Route::post('/purchasing/problems/{problem}/cancel', [PurchasingController::class, 'cancelProblem'])->name('purchasing.problems.cancel');
    Route::post('/purchasing/problems/{problem}/resolve', [PurchasingController::class, 'resolveProblem'])->name('purchasing.problems.resolve');
    Route::post('/purchasing/purchases/{purchase}/undo', [PurchasingController::class, 'undoPurchase'])->name('purchasing.purchases.undo');
    Route::patch('/purchasing/purchases/bulk', [PurchasingController::class, 'bulkUpdatePurchases'])->name('purchasing.purchases.bulk-update');
    Route::patch('/purchasing/purchases/{purchase}', [PurchasingController::class, 'updatePurchase'])->name('purchasing.purchases.update');

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
    Route::get('/admin/text-cleanup', [LegacyTextCleanupController::class, 'index'])->name('admin.text-cleanup.index');
    Route::patch('/admin/text-cleanup', [LegacyTextCleanupController::class, 'update'])->name('admin.text-cleanup.update');
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