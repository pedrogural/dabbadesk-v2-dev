<?php

namespace App\Http\Controllers;

use App\Services\Purchasing\PurchaseDeskV2Service;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurchaseDeskV2Controller extends Controller
{
    public function index(Request $request, PurchaseDeskV2Service $service)
    {
        return view('purchase-desk-v2.index', $service->index([
            'q' => $request->query('q', ''),
            'payment' => $request->query('payment', 'paid_or_part'),
        ]));
    }

    public function show(Request $request, int $order, PurchaseDeskV2Service $service)
    {
        $workspace = $service->orderWorkspace($order, [
            'q' => $request->query('q', ''),
            'view' => $request->query('view', 'actionable'),
        ]);

        abort_if(! $workspace, 404);

        return view('purchase-desk-v2.order', $workspace);
    }


    public function storeBasket(Request $request, int $order, PurchaseDeskV2Service $service)
    {
        $validated = $request->validate([
            'retailer_id' => ['nullable', 'integer'],
            'supplier_retailer_id' => ['required', 'integer', 'exists:retailers,id'],
            'retailer_order_reference' => ['required', 'string', 'max:255'],
            'ordered_at' => ['nullable', 'date'],
            'estimated_retailer_delivery_date' => ['nullable', 'date'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
            'lines.*.selected' => ['nullable'],
            'lines.*.qty' => ['nullable', 'integer', 'min:1'],
            'lines.*.purchase_unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $result = $service->recordPurchaseBasket($order, $validated, optional($request->user())->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Purchase basket could not be recorded. Nothing was changed.');
        }

        $created = (int) ($result['created'] ?? 0);
        $message = $created . ' purchase line' . ($created === 1 ? '' : 's') . ' recorded.';

        if (($result['resolved_issues'] ?? 0) > 0) {
            $message .= ' ' . $result['resolved_issues'] . ' pre-purchase issue' . (($result['resolved_issues'] ?? 0) === 1 ? '' : 's') . ' automatically resolved.';
        }

        return redirect()
            ->route('purchases.orders.show', ['order' => $order, 'view' => 'actionable'])
            ->with('success', $message);
    }

    public function storeSupplier(Request $request, int $order, PurchaseDeskV2Service $service)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:191'],
            'base_url' => ['required', 'string', 'min:3', 'max:2048'],
        ]);

        try {
            $service->createSupplier($validated, optional($request->user())->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Supplier could not be added.');
        }

        return redirect()
            ->route('purchases.orders.show', ['order' => $order])
            ->with('success', 'Supplier added. You can now select it in the purchase basket.');
    }


    public function updateBatch(Request $request, int $order, PurchaseDeskV2Service $service)
    {
        $validated = $request->validate([
            'purchase_ids' => ['required', 'array', 'min:1'],
            'purchase_ids.*' => ['required', 'integer', 'min:1'],
            'supplier_retailer_id' => ['required', 'integer', 'exists:retailers,id'],
            'retailer_order_reference' => ['nullable', 'string', 'max:255'],
            'ordered_at' => ['nullable', 'date'],
            'estimated_retailer_delivery_date' => ['nullable', 'date'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $result = $service->updatePurchaseBatch($order, $validated, optional($request->user())->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Purchase batch could not be updated. Nothing was changed.');
        }

        $updated = (int) ($result['updated'] ?? 0);

        return redirect()
            ->route('purchases.orders.show', ['order' => $order, 'view' => $request->query('view', 'actionable')])
            ->with('success', 'Purchase batch updated for ' . $updated . ' purchase line' . ($updated === 1 ? '' : 's') . '.');
    }


    public function undoBatch(Request $request, int $order, PurchaseDeskV2Service $service)
    {
        $validated = $request->validate([
            'purchase_ids' => ['required', 'array', 'min:1'],
            'purchase_ids.*' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            $result = $service->undoPurchaseBatch($order, $validated, optional($request->user())->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Purchase batch could not be undone. Nothing was changed.');
        }

        $undone = (int) ($result['undone'] ?? 0);

        return redirect()
            ->route('purchases.orders.show', ['order' => $order, 'view' => 'actionable'])
            ->with('success', $undone . ' purchase line' . ($undone === 1 ? '' : 's') . ' undone and returned to the buying list.');
    }

    public function undoLine(Request $request, int $order, int $purchase, PurchaseDeskV2Service $service)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            $service->undoPurchaseLine($order, $purchase, $validated['reason'], optional($request->user())->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Purchase line could not be undone. Nothing was changed.');
        }

        return redirect()
            ->route('purchases.orders.show', ['order' => $order, 'view' => 'actionable'])
            ->with('success', 'Purchase line undone and returned to the buying list.');
    }

    public function storePurchase(Request $request, int $order, int $item, PurchaseDeskV2Service $service)
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'purchase_unit_price' => ['required', 'numeric', 'min:0'],
            'ordered_at' => ['nullable', 'date'],
            'estimated_retailer_delivery_date' => ['nullable', 'date'],
            'retailer_order_reference' => ['nullable', 'string', 'max:255'],
            'marketplace_seller' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $result = $service->recordPurchase($order, $item, $validated, optional($request->user())->id);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Purchase could not be recorded. Nothing was changed.');
        }

        $message = 'Purchase recorded.';

        if (($result['resolved_issues'] ?? 0) > 0) {
            $message .= ' ' . $result['resolved_issues'] . ' pre-purchase issue' . (($result['resolved_issues'] ?? 0) === 1 ? '' : 's') . ' automatically resolved.';
        }

        return redirect()
            ->route('purchases.orders.show', ['order' => $order, 'view' => 'actionable'])
            ->with('success', $message);
    }
}
