<?php

namespace App\Http\Controllers;

use App\Services\Drafts\DraftOrderWorkspaceService;
use App\Services\Drafts\DraftRetailerDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DraftOrdersController extends Controller
{
    public function index(Request $request, DraftOrderWorkspaceService $drafts)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
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

        return view('draft-orders.show', [
            'draft' => $draft,
            'items' => $drafts->items($draftOrder),
            'retailerSummaries' => $drafts->retailerSummaries($draftOrder),
            'notes' => $drafts->notes($draftOrder),
            'customerDetails' => $drafts->customerDetails((int) $draft->customer_id),
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
        $request->validate([
            'status' => ['required', 'string', 'max:30'],
            'fee_mode' => ['required', 'string', 'max:20'],
            'home_delivery_requested' => ['nullable'],
        ]);

        $drafts->updateDraft($draftOrder, $request->only(['status', 'fee_mode', 'home_delivery_requested']), Auth::id());

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Draft settings updated.');
    }

    public function updateItem(int $draftOrder, int $item, Request $request, DraftOrderWorkspaceService $drafts)
    {
        $data = $request->validate([
            'retailer_id' => ['required', 'integer', 'exists:retailers,id'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'product_code' => ['nullable', 'string', 'max:50'],
            'sku' => ['nullable', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'item_retailer_delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $drafts->updateItem($draftOrder, $item, $data, Auth::id());

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Draft item updated.');
    }

    public function addItem(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts, DraftRetailerDetectionService $detector)
    {
        $data = $request->validate([
            'retailer_id' => ['required', 'integer', 'exists:retailers,id'],
            'description' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'product_code' => ['nullable', 'string', 'max:50'],
            'sku' => ['nullable', 'string', 'max:100'],
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

    public function deleteItem(int $draftOrder, int $item, DraftOrderWorkspaceService $drafts)
    {
        $drafts->deleteItem($draftOrder, $item, Auth::id());

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Draft item removed.');
    }

    public function addNote(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
    {
        $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $drafts->addNote($draftOrder, $request->string('body')->toString(), Auth::id());

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Note added.');
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
