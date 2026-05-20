<?php

namespace App\Http\Controllers;

use App\Services\Drafts\DraftOrderWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'retailers' => $drafts->retailers(),
            'staffUsers' => $drafts->staffUsers(),
            'statusOptions' => $drafts->statusOptions(),
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

    public function addItem(int $draftOrder, Request $request, DraftOrderWorkspaceService $drafts)
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

        $drafts->addItem($draftOrder, $data, Auth::id());

        return redirect()->route('draft-orders.show', $draftOrder)->with('success', 'Item added to draft.');
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
}