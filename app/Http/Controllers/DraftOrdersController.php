<?php

namespace App\Http\Controllers;

use App\Services\Drafts\DraftOrderWorkspaceService;
use App\Services\Drafts\DraftRetailerDetectionService;
use App\Services\Drafts\FinaliseDraftOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Throwable;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use DabbaDirect\IntakeTools\UrlTools;

class DraftOrdersController extends Controller
{
    public function index(Request $request, DraftOrderWorkspaceService $drafts)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'mine' => $request->boolean('mine'),
            'user_id' => Auth::id(),
        ];

        return view('draft-orders.index', [
            'filters' => $filters,
            'statusOptions' => $drafts->statusOptions(),
            'drafts' => $drafts->search($filters),
        ]);
    }

    public function show(int $draftOrder, DraftOrderWorkspaceService $drafts)
    {
        $draft = $drafts->find($draftOrder);
        abort_if(! $draft, 404);

        $requestAttachments = collect();

        if (! empty($draft->order_request_id)) {
            $requestAttachments = DB::table('order_request_attachments')
                ->where('order_request_id', (int) $draft->order_request_id)
                ->orderBy('id')
                ->get();
        }

        return view('draft-orders.show', [
            'draft' => $draft,
            'items' => $drafts->items($draftOrder),
            'retailerSummaries' => $drafts->retailerSummaries($draftOrder),
            'notes' => $drafts->notes($draftOrder),
            'requestNotes' => $drafts->requestNotes($draftOrder),
            'requestAttachments' => $requestAttachments,
            'activityLogs' => $drafts->activity($draftOrder),
            'customerDetails' => $drafts->customerDetails((int) $draft->customer_id),
            'countries' => $drafts->countries(),
            'retailers' => $drafts->retailers(),
            'staffUsers' => $drafts->staffUsers(),
            'statusOptions' => $drafts->statusOptions(),
        ]);
    }

    public function detectRetailer(Request $request, DraftRetailerDetectionService $detector)
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'manual_retailer_name' => ['nullable', 'string', 'max:191'],
        ]);

        $result = $detector->detect($data['url'], $data['manual_retailer_name'] ?? null);

        return response()->json([
            'ok' => true,
            'retailer' => $result->toArray(),
        ]);
    }

    public function quickStoreRetailer(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'base_url' => ['required', 'string', 'max:191'],
        ]);

        $baseUrl = $this->cleanBaseUrl($data['base_url']);
        $name = trim($data['name']);

        $existing = DB::table('retailers')
            ->where('base_url', $baseUrl)
            ->when(Schema::hasColumn('retailers', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => true,
                'retailer' => [
                    'id' => (int) $existing->id,
                    'name' => (string) $existing->name,
                    'base_url' => (string) $existing->base_url,
                    'already_exists' => true,
                ],
            ]);
        }

        $insert = [
            'name' => $name,
            'base_url' => $baseUrl,
        ];

        if (Schema::hasColumn('retailers', 'is_active')) $insert['is_active'] = 1;
        if (Schema::hasColumn('retailers', 'active')) $insert['active'] = 1;
        if (Schema::hasColumn('retailers', 'code')) $insert['code'] = Str::slug($name) ?: Str::slug($baseUrl);
        if (Schema::hasColumn('retailers', 'retailer_code')) $insert['retailer_code'] = Str::slug($name) ?: Str::slug($baseUrl);
        if (Schema::hasColumn('retailers', 'created_by_user_id')) $insert['created_by_user_id'] = Auth::id();
        if (Schema::hasColumn('retailers', 'updated_by_user_id')) $insert['updated_by_user_id'] = Auth::id();
        if (Schema::hasColumn('retailers', 'created_at')) $insert['created_at'] = now();
        if (Schema::hasColumn('retailers', 'updated_at')) $insert['updated_at'] = now();

        $id = DB::table('retailers')->insertGetId($insert);

        return response()->json([
            'ok' => true,
            'retailer' => [
                'id' => (int) $id,
                'name' => $name,
                'base_url' => $baseUrl,
                'already_exists' => false,
            ],
        ]);
    }

    public function update(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
    {
        if ($response = $this->guardConsumedDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        if ($request->attributes->get('consumed_draft_reopened') && in_array((string) $request->input('status'), ['consumed', 'finalised'], true)) {
            $request->merge(['status' => 'open']);
        }

        $request->validate([
            'status' => ['required', 'string', 'max:30'],
            'fee_mode' => ['required', 'string', 'max:20'],
            'home_delivery_requested' => ['nullable'],
        ]);

        $draft = $drafts->find($draftOrder);
        if ((string) $request->input('status') === 'cancelled' && ! empty($draft?->finalized_order_id)) {
            return redirect()
                ->route('draft-orders.show', $draftOrder)
                ->withErrors(['status' => 'This draft has already created an order and cannot be cancelled. Cancel the order instead if required.']);
        }

        $drafts->updateDraft($draftOrder, $request->only(['status', 'fee_mode', 'home_delivery_requested']), Auth::id());

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Draft settings updated.');
    }


    public function updateCustomer(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
    {
        $draft = $drafts->find($draftOrder);
        abort_if(! $draft, 404);

        if ($response = $this->guardCancelledDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        if ($response = $this->guardConsumedDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:191'],
            'last_name' => ['nullable', 'string', 'max:191'],
            'company_name' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
            'phone_country_id' => ['nullable', 'integer'],
            'line1' => ['nullable', 'string', 'max:191'],
            'line2' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'region' => ['nullable', 'string', 'max:191'],
            'postcode' => ['nullable', 'string', 'max:32'],
            'country_id' => ['nullable', 'integer'],
        ]);

        $drafts->updateCustomer((int) $draft->customer_id, $draftOrder, $data, Auth::id());

        return redirect()
            ->route('draft-orders.show', [$draftOrder, 'tab' => 'customer'])
            ->with('success', 'Customer details updated.');
    }

    public function updateFees(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
    {
        if ($response = $this->guardCancelledDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        if ($response = $this->guardConsumedDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        $data = $request->validate([
            'fee_mode' => ['required', 'string', 'in:standard,fee_disabled'],
            'dabba_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'dabba_fee_min' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);

        $drafts->updateFees($draftOrder, $data, Auth::id());

        return redirect()
            ->route('draft-orders.show', [$draftOrder, 'tab' => 'fees'])
            ->with('success', 'Dabba fee policy updated.');
    }

    public function updateItem(int $draftOrder, int $item, Request $request, DraftOrderWorkspaceService $drafts, DraftRetailerDetectionService $detector)
    {
        if ($response = $this->guardCancelledDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        if ($response = $this->guardConsumedDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        Log::info('Draft item autosave reached controller', [
            'draft_order_id' => $draftOrder,
            'item_id' => $item,
            'method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'expects_json' => $request->expectsJson(),
            'payload_keys' => array_keys($request->all()),
            'payload' => $request->except(['_token']),
        ]);

        try {
            $data = $request->validate([
                'retailer_id' => ['required', 'integer', 'exists:retailers,id'],
                'description' => ['nullable', 'string'],
                'url' => ['nullable', 'string', 'max:2048'],
                'product_code' => ['nullable', 'string', 'max:191'],
                'sku' => ['nullable', 'string', 'max:191'],
                'qty' => ['required', 'integer', 'min:1', 'max:999'],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'item_retailer_delivery_fee' => ['nullable', 'numeric', 'min:0'],
                'reviewed' => ['nullable', 'boolean'],
                'needs_attention' => ['nullable', 'boolean'],
                'needs_attention_note' => ['nullable', 'string', 'max:255'],
            ]);
        } catch (ValidationException $exception) {
            Log::warning('Draft item autosave validation failed', [
                'draft_order_id' => $draftOrder,
                'item_id' => $item,
                'errors' => $exception->errors(),
                'payload' => $request->except(['_token']),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Validation failed: ' . collect($exception->errors())->flatten()->first(),
                    'errors' => $exception->errors(),
                ], 422);
            }

            throw $exception;
        }

        $retailerChanged = false;
        $urlResolveWarning = null;

        $data['item_retailer_delivery_fee'] = $data['item_retailer_delivery_fee'] ?? 0;
        $data['product_code'] = $data['product_code'] ?? null;
        $data['sku'] = $data['sku'] ?? null;
        $data['reviewed'] = $request->boolean('reviewed');
        $data['needs_attention'] = $request->boolean('needs_attention');
        $data['needs_attention_note'] = $data['needs_attention_note'] ?? null;

        if (! empty($data['url'])) {
            try {
                $detected = $detector->detect((string) $data['url'])->toArray();
                $expandedUrl = $detected['final_url'] ?? $detected['finalUrl'] ?? null;
                $detectedRetailerId = $detected['retailer_id'] ?? $detected['retailerId'] ?? null;
                $productId = $detected['product_id'] ?? $detected['productId'] ?? null;

                Log::info('Draft item autosave URL detector result', [
                    'draft_order_id' => $draftOrder,
                    'item_id' => $item,
                    'detected' => $detected,
                ]);

                if (is_string($expandedUrl) && trim($expandedUrl) !== '') {
                    $data['url'] = trim($expandedUrl);
                }

                if (! empty($detectedRetailerId) && (int) $detectedRetailerId !== (int) $data['retailer_id']) {
                    $data['retailer_id'] = (int) $detectedRetailerId;
                    $retailerChanged = true;
                }

                if (is_string($productId) && trim($productId) !== '' && trim((string) ($data['product_code'] ?? '')) === '') {
                    $data['product_code'] = trim($productId);
                }
            } catch (Throwable $exception) {
                Log::error('Draft item autosave URL resolving failed', [
                    'draft_order_id' => $draftOrder,
                    'item_id' => $item,
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
                $urlResolveWarning = 'Saved without URL resolving. The product URL resolver failed for this link.';
            }
        }

        try {
            $drafts->updateItem($draftOrder, $item, $data, Auth::id());
        } catch (Throwable $exception) {
            Log::error('Draft item autosave database update failed', [
                'draft_order_id' => $draftOrder,
                'item_id' => $item,
                'data' => $data,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Database update failed: ' . $exception->getMessage(),
                ], 500);
            }

            throw $exception;
        }

        Log::info('Draft item autosave completed', [
            'draft_order_id' => $draftOrder,
            'item_id' => $item,
            'retailer_changed' => $retailerChanged,
            'warning' => $urlResolveWarning,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $urlResolveWarning ?: ($retailerChanged ? 'Saved. Retailer changed after URL resolving.' : 'Saved.'),
                'reload' => $retailerChanged || (bool) $request->attributes->get('consumed_draft_reopened'),
            ]);
        }

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Draft item updated.');
    }

    public function addItem(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts, DraftRetailerDetectionService $detector)
    {
        if ($response = $this->guardCancelledDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        if ($response = $this->guardConsumedDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        $data = $request->validate([
            'retailer_id' => ['required', 'integer', 'exists:retailers,id'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'product_code' => ['nullable', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:191'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'item_retailer_delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['item_retailer_delivery_fee'] = $data['item_retailer_delivery_fee'] ?? 0;

        if (! empty($data['url'])) {
            $detected = $detector->detect((string) $data['url'])->toArray();
            $expandedUrl = $detected['final_url'] ?? $detected['finalUrl'] ?? null;
            if (is_string($expandedUrl) && trim($expandedUrl) !== '') {
                $data['url'] = trim($expandedUrl);
            }
        }

        $itemId = $drafts->addItem($draftOrder, $data, Auth::id());

        return redirect()
            ->route('draft-orders.show', $draftOrder)
            ->with('success', 'Item added to draft.')
            ->with('last_added_item_id', $itemId);
    }


    public function updateRetailerDelivery(int $draftOrder, int $retailer, Request $request, DraftOrderWorkspaceService $drafts)
    {
        if ($response = $this->guardCancelledDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        if ($response = $this->guardConsumedDraftMutation($draftOrder, $request, $drafts)) {
            return $response;
        }

        $data = $request->validate([
            'retailer_delivery_fee_total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $drafts->updateRetailerDeliveryFee(
            $draftOrder,
            $retailer,
            (float) ($data['retailer_delivery_fee_total'] ?? 0),
            Auth::id()
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Retailer delivery fee updated.', 'reload' => true]);
        }

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Retailer delivery fee updated.');
    }

    public function deleteItem(int $draftOrder, int $item, DraftOrderWorkspaceService $drafts)
    {
        if ($response = $this->guardCancelledDraftMutation($draftOrder, request(), $drafts)) {
            return $response;
        }

        if ($response = $this->guardConsumedDraftMutation($draftOrder, request(), $drafts)) {
            return $response;
        }

        $drafts->deleteItem($draftOrder, $item, Auth::id());

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Draft item removed.']);
        }

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Draft item removed.');
    }


    public function finalise(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts, FinaliseDraftOrderService $finaliser)
    {
        $draft = $drafts->find($draftOrder);
        abort_if(! $draft, 404);

        if ($this->isCancelledDraft($draft)) {
            return redirect()
                ->route('draft-orders.show', $draftOrder)
                ->withErrors(['draft' => 'This draft is cancelled and cannot be finalised. Reopen it first if a new order is required.']);
        }

        $isConsumed = ! empty($draft->finalized_order_id);

        if ($isConsumed && ! $request->boolean('confirm_new_version')) {
            return redirect()
                ->route('draft-orders.show', $draftOrder)
                ->withErrors(['finalise' => 'This draft has already created an order. Confirm that you want to create a new order version.']);
        }

        $unresolvedRetailerCount = DB::table('draft_order_items as i')
            ->leftJoin('retailers as r', 'r.id', '=', 'i.retailer_id')
            ->where('i.draft_order_id', $draftOrder)
            ->where(function ($query) {
                $query->whereNull('i.retailer_id')->orWhereNull('r.id');
            })
            ->count();

        $missingProductReferenceCount = DB::table('draft_order_items')
            ->where('draft_order_id', $draftOrder)
            ->where(function ($query) {
                $query->where(function ($urlQuery) {
                    $urlQuery->whereNull('url')->orWhereRaw("TRIM(COALESCE(url, '')) = ''");
                })->where(function ($codeQuery) {
                    $codeQuery->whereNull('product_code')->orWhereRaw("TRIM(COALESCE(product_code, '')) = ''");
                });
            })
            ->count();

        if ($unresolvedRetailerCount > 0 || $missingProductReferenceCount > 0) {
            return redirect()
                ->route('draft-orders.show', $draftOrder)
                ->withErrors([
                    'finalise' => 'Resolve all retailers and make sure every draft item has a product link or product code before creating the final order.',
                ]);
        }

        try {
            $orderId = $finaliser->finalise($draftOrder, Auth::id());
        } catch (Throwable $exception) {
            return redirect()
                ->route('draft-orders.show', $draftOrder)
                ->withErrors(['finalise' => $exception->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $orderId)
            ->with('success', $isConsumed ? 'New order version created. Previous order was marked as superseded.' : 'Draft consumed into an order.');
    }

    public function addNote(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
    {
        $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $drafts->addNote($draftOrder, $request->string('body')->toString(), Auth::id());

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Note added.');
    }


    private function guardCancelledDraftMutation(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
    {
        $draft = $drafts->find($draftOrder);
        abort_if(! $draft, 404);

        if (! $this->isCancelledDraft($draft)) {
            return null;
        }

        $message = 'This draft is cancelled and is locked for editing. Change the draft status back to open before making product, fee, customer, or delivery changes.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 423);
        }

        return redirect()
            ->route('draft-orders.show', $draftOrder)
            ->withErrors(['draft' => $message]);
    }

    private function isCancelledDraft(object $draft): bool
    {
        return in_array((string) ($draft->status ?? ''), ['cancelled', 'canceled'], true)
            || in_array((string) ($draft->state ?? ''), ['cancelled', 'canceled'], true);
    }

    private function guardConsumedDraftMutation(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
    {
        $draft = $drafts->find($draftOrder);
        abort_if(! $draft, 404);

        $isConsumed = in_array((string) ($draft->status ?? ''), ['consumed', 'finalised'], true)
            || in_array((string) ($draft->state ?? ''), ['consumed', 'finalised'], true);

        if (! $isConsumed) {
            return null;
        }

        $confirmed = $request->boolean('confirm_consumed_edit')
            || $request->header('X-Confirm-Consumed-Edit') === '1';

        if (! $confirmed) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'requires_consumed_edit_confirmation' => true,
                    'message' => 'This draft has already created an order. Confirm editing it before saving changes for a new order version.',
                ], 409);
            }

            return redirect()
                ->route('draft-orders.show', $draftOrder)
                ->withErrors(['draft' => 'This draft has already created an order. Confirm editing it before saving changes for a new order version.']);
        }

        $drafts->reopenConsumedDraftForNewVersion($draftOrder, Auth::id());
        $request->attributes->set('consumed_draft_reopened', true);

        return null;
    }

    private function cleanBaseUrl(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') return $value;

        if (! str_contains($value, '://')) {
            $value = 'https://' . $value;
        }

        $host = parse_url($value, PHP_URL_HOST) ?: $value;
        $host = strtolower((string) $host);
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        $host = trim($host, " \t\n\r\0\x0B/");

        return $host;
    }
}
