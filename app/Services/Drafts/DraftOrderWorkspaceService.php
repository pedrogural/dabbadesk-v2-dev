<?php

namespace App\Services\Drafts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DraftOrderWorkspaceService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $query = DB::table('draft_orders as d')
            ->leftJoin('customers as c', 'c.id', '=', 'd.customer_id')
            ->leftJoin('order_requests as r', 'r.id', '=', 'd.order_request_id')
            ->select([
                'd.id', 'd.draft_number', 'd.state', 'd.status', 'd.kind', 'd.grand_total', 'd.items_subtotal',
                'd.retailer_delivery_total', 'd.dabba_fee_total', 'd.created_at', 'd.updated_at', 'd.finalized_order_id',
                'c.first_name', 'c.last_name', 'c.company_name', 'r.request_ref',
            ])
            ->selectRaw('(select count(*) from draft_order_items i where i.draft_order_id = d.id) as item_count')
            ->selectRaw('(select coalesce(sum(i.qty),0) from draft_order_items i where i.draft_order_id = d.id) as total_qty')
            ->orderByDesc('d.updated_at')
            ->orderByDesc('d.id');

        if ($status !== '') {
            $query->where('d.status', $status);
        }

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($inner) use ($like) {
                $inner->where('d.draft_number', 'like', $like)
                    ->orWhere('r.request_ref', 'like', $like)
                    ->orWhere('c.first_name', 'like', $like)
                    ->orWhere('c.last_name', 'like', $like)
                    ->orWhere('c.company_name', 'like', $like)
                    ->orWhereExists(function ($sub) use ($like) {
                        $sub->selectRaw('1')
                            ->from('draft_order_items as i')
                            ->whereColumn('i.draft_order_id', 'd.id')
                            ->where(function ($item) use ($like) {
                                $item->where('i.description', 'like', $like)
                                    ->orWhere('i.product_code', 'like', $like)
                                    ->orWhere('i.sku', 'like', $like)
                                    ->orWhere('i.url', 'like', $like);
                            });
                    });
            });
        }

        return $query->paginate(20)->withQueryString();
    }

    public function statusOptions(): array
    {
        return ['open', 'reviewing', 'ready', 'finalised', 'cancelled'];
    }

    public function find(int $draftId): ?object
    {
        return DB::table('draft_orders as d')
            ->leftJoin('customers as c', 'c.id', '=', 'd.customer_id')
            ->leftJoin('order_requests as r', 'r.id', '=', 'd.order_request_id')
            ->leftJoin('orders as o', 'o.id', '=', 'd.finalized_order_id')
            ->where('d.id', $draftId)
            ->select([
                'd.*',
                'c.first_name', 'c.last_name', 'c.company_name', 'c.reference as customer_reference',
                'c.dabba_fee_level as customer_fee_level', 'c.dabba_fee_rate as customer_fee_rate', 'c.dabba_fee_min as customer_fee_min',
                'r.request_ref', 'o.order_number as finalized_order_number',
            ])
            ->first();
    }

    public function items(int $draftId)
    {
        return DB::table('draft_order_items as i')
            ->leftJoin('retailers as r', 'r.id', '=', 'i.retailer_id')
            ->where('i.draft_order_id', $draftId)
            ->select('i.*', 'r.name as retailer_name', 'r.base_url as retailer_base_url')
            ->orderBy('r.name')
            ->orderBy('i.sort_order')
            ->orderBy('i.id')
            ->get();
    }

    public function retailerSummaries(int $draftId)
    {
        return DB::table('draft_order_retailers as dr')
            ->leftJoin('retailers as r', 'r.id', '=', 'dr.retailer_id')
            ->where('dr.draft_order_id', $draftId)
            ->select('dr.*', 'r.name as retailer_name')
            ->orderBy('r.name')
            ->get();
    }

    public function notes(int $draftId)
    {
        return DB::table('activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by_user_id')
            ->where('a.subject_type', 'draft_order')
            ->where('a.subject_id', $draftId)
            ->whereNull('a.deleted_at')
            ->whereIn('a.type', ['note', 'system_note', 'supplier_note', 'draft_note'])
            ->select('a.*', 'u.name as author_name')
            ->orderByDesc('a.is_pinned')
            ->orderByDesc(DB::raw('coalesce(a.occurred_at, a.created_at)'))
            ->limit(30)
            ->get();
    }

    public function retailers()
    {
        return DB::table('retailers')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'base_url')
            ->orderBy('name')
            ->get();
    }

    public function staffUsers()
    {
        return DB::table('users')
            ->where('is_disabled', 0)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();
    }

    public function customerDetails(int $customerId): array
    {
        $email = null;
        $phone = null;
        $address = null;

        if (Schema::hasTable('customer_emails') && Schema::hasTable('emails')) {
            $row = DB::table('customer_emails as ce')
                ->join('emails as e', 'e.id', '=', 'ce.email_id')
                ->where('ce.customer_id', $customerId)
                ->where('ce.is_active', 1)
                ->orderByDesc('ce.is_primary')
                ->select('e.email')
                ->first();
            $email = $row->email ?? null;
        }

        if (Schema::hasTable('customer_phones') && Schema::hasTable('phones')) {
            $row = DB::table('customer_phones as cp')
                ->join('phones as p', 'p.id', '=', 'cp.phone_id')
                ->where('cp.customer_id', $customerId)
                ->where('cp.is_active', 1)
                ->orderByDesc('cp.is_primary')
                ->select('p.phone')
                ->first();
            if ($row) {
                $phone = trim((string) ($row->phone ?? ''));
            }
        }

        if (Schema::hasTable('customer_addresses') && Schema::hasTable('addresses')) {
            $row = DB::table('customer_addresses as ca')
                ->join('addresses as a', 'a.id', '=', 'ca.address_id')
                ->leftJoin('countries as c', 'c.id', '=', 'a.country_id')
                ->where('ca.customer_id', $customerId)
                ->where('ca.is_active', 1)
                ->orderByDesc('ca.is_primary')
                ->select('a.line1', 'a.line2', 'a.city', 'a.region', 'a.postcode', 'c.name as country_name')
                ->first();
            if ($row) {
                $parts = array_filter([$row->line1, $row->line2, $row->city, $row->region, $row->postcode, $row->country_name]);
                $address = implode("\n", $parts);
            }
        }

        return [
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
        ];
    }

    public function updateDraft(int $draftId, array $data, int $userId): void
    {
        DB::table('draft_orders')->where('id', $draftId)->update([
            'status' => $data['status'],
            'home_delivery_requested' => ! empty($data['home_delivery_requested']) ? 1 : 0,
            'fee_mode' => $data['fee_mode'],
            'updated_by_user_id' => $userId,
            'updated_at' => now(),
        ]);

        $this->addSystemNote($draftId, 'Draft updated', 'Draft header, status or delivery settings were updated.', $userId);
        $this->recalculate($draftId, $userId);
    }

    public function updateItem(int $draftId, int $itemId, array $data, int $userId): void
    {
        $qty = max(1, (int) ($data['qty'] ?? 1));
        $unit = round((float) ($data['unit_price'] ?? 0), 2);
        $delivery = round((float) ($data['item_retailer_delivery_fee'] ?? 0), 2);
        $subtotal = round($qty * $unit, 2);
        $lineTotal = round($subtotal + $delivery, 2);

        DB::table('draft_order_items')
            ->where('id', $itemId)
            ->where('draft_order_id', $draftId)
            ->update([
                'retailer_id' => (int) $data['retailer_id'],
                'description' => trim((string) ($data['description'] ?? '')),
                'url' => trim((string) ($data['url'] ?? '')) ?: null,
                'product_code' => trim((string) ($data['product_code'] ?? '')) ?: null,
                'sku' => trim((string) ($data['sku'] ?? '')) ?: null,
                'qty' => $qty,
                'unit_price' => $unit,
                'line_subtotal' => $subtotal,
                'item_retailer_delivery_fee' => $delivery,
                'item_delivery_fee' => $delivery,
                'line_total' => $lineTotal,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);

        $this->recalculate($draftId, $userId);
    }

    public function addItem(int $draftId, array $data, int $userId): int
    {
        $qty = max(1, (int) ($data['qty'] ?? 1));
        $unit = round((float) ($data['unit_price'] ?? 0), 2);
        $delivery = round((float) ($data['item_retailer_delivery_fee'] ?? 0), 2);
        $subtotal = round($qty * $unit, 2);
        $lineTotal = round($subtotal + $delivery, 2);
        $sort = (int) DB::table('draft_order_items')->where('draft_order_id', $draftId)->max('sort_order') + 10;

        $id = DB::table('draft_order_items')->insertGetId([
            'draft_order_id' => $draftId,
            'retailer_id' => (int) $data['retailer_id'],
            'description' => trim((string) ($data['description'] ?? 'New item')),
            'url' => trim((string) ($data['url'] ?? '')) ?: null,
            'product_code' => trim((string) ($data['product_code'] ?? '')) ?: null,
            'sku' => trim((string) ($data['sku'] ?? '')) ?: null,
            'qty' => $qty,
            'unit_price' => $unit,
            'line_subtotal' => $subtotal,
            'item_retailer_delivery_fee' => $delivery,
            'item_delivery_fee' => $delivery,
            'line_total' => $lineTotal,
            'sort_order' => $sort,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->addSystemNote($draftId, 'Item added', 'A new draft item was added.', $userId);
        $this->recalculate($draftId, $userId);

        return $id;
    }

    public function deleteItem(int $draftId, int $itemId, int $userId): void
    {
        DB::table('draft_order_items')->where('id', $itemId)->where('draft_order_id', $draftId)->delete();
        $this->addSystemNote($draftId, 'Item removed', 'A draft item was removed.', $userId);
        $this->recalculate($draftId, $userId);
    }

    public function addNote(int $draftId, string $body, int $userId): void
    {
        $body = trim($body);
        if ($body === '') {
            return;
        }

        DB::table('activity_logs')->insert([
            'subject_type' => 'draft_order',
            'subject_id' => $draftId,
            'type' => 'note',
            'title' => null,
            'body' => $body,
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function recalculate(int $draftId, ?int $userId = null): void
    {
        DB::transaction(function () use ($draftId, $userId) {
            $items = DB::table('draft_order_items')
                ->where('draft_order_id', $draftId)
                ->select('retailer_id')
                ->selectRaw('sum(line_subtotal) as subtotal')
                ->selectRaw('sum(coalesce(item_retailer_delivery_fee, item_delivery_fee, 0)) as delivery')
                ->groupBy('retailer_id')
                ->get();

            DB::table('draft_order_retailers')->where('draft_order_id', $draftId)->delete();

            $itemsSubtotal = 0.0;
            $deliveryTotal = 0.0;
            $feeTotal = 0.0;

            $draft = DB::table('draft_orders')->where('id', $draftId)->first();
            $rate = (float) ($draft->dabba_fee_rate ?? 20);
            $min = (float) ($draft->dabba_fee_min ?? 10);

            foreach ($items as $row) {
                $subtotal = round((float) $row->subtotal, 2);
                $delivery = round((float) $row->delivery, 2);
                $fee = $draft && $draft->fee_mode === 'fee_disabled' ? 0.0 : max($min, round($subtotal * ($rate / 100), 2));
                $grand = round($subtotal + $delivery + $fee, 2);

                $itemsSubtotal += $subtotal;
                $deliveryTotal += $delivery;
                $feeTotal += $fee;

                DB::table('draft_order_retailers')->insert([
                    'draft_order_id' => $draftId,
                    'retailer_id' => $row->retailer_id,
                    'retailer_subtotal' => $subtotal,
                    'retailer_delivery_fee_total' => $delivery,
                    'dabba_fee_rate' => $rate,
                    'dabba_fee_min' => $min,
                    'dabba_fee_is_disabled' => $fee <= 0 ? 1 : 0,
                    'dabba_fee_reason' => $fee <= 0 ? 'Fee disabled on draft' : null,
                    'dabba_fee' => $fee,
                    'retailer_grand_total' => $grand,
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('draft_orders')->where('id', $draftId)->update([
                'items_subtotal' => round($itemsSubtotal, 2),
                'retailer_delivery_total' => round($deliveryTotal, 2),
                'dabba_fee_total' => round($feeTotal, 2),
                'grand_total' => round($itemsSubtotal + $deliveryTotal + $feeTotal, 2),
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    private function addSystemNote(int $draftId, string $title, string $body, int $userId): void
    {
        DB::table('activity_logs')->insert([
            'subject_type' => 'draft_order',
            'subject_id' => $draftId,
            'type' => 'system_note',
            'title' => $title,
            'body' => $body,
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}