<?php

namespace App\Services\Drafts;

use App\Support\Search\SmartSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DraftOrderWorkspaceService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $mineOnly = ! empty($filters['mine']);
        $userId = (int) ($filters['user_id'] ?? 0);

        $query = DB::table('draft_orders as d')
            ->leftJoin('customers as c', 'c.id', '=', 'd.customer_id')
            ->leftJoin('order_requests as r', 'r.id', '=', 'd.order_request_id')
            ->leftJoin('users as created_user', 'created_user.id', '=', 'd.created_by_user_id')
            ->leftJoin('users as updated_user', 'updated_user.id', '=', 'd.updated_by_user_id')
            ->select([
                'd.id', 'd.draft_number', 'd.state', 'd.status', 'd.kind', 'd.grand_total', 'd.items_subtotal',
                'd.retailer_delivery_total', 'd.dabba_fee_total', 'd.created_at', 'd.updated_at', 'd.finalized_order_id',
                'd.created_by_user_id', 'd.updated_by_user_id',
                'c.id as customer_id', 'c.first_name', 'c.last_name', 'c.company_name', 'r.request_ref',
                'created_user.name as created_by_name', 'updated_user.name as updated_by_name',
            ])
            ->selectRaw('(select count(*) from draft_order_items i where i.draft_order_id = d.id) as item_count')
            ->selectRaw('(select coalesce(sum(i.qty),0) from draft_order_items i where i.draft_order_id = d.id) as total_qty')
            ->orderByDesc('d.updated_at')
            ->orderByDesc('d.id');

        if ($status !== '') {
            $query->where('d.status', $status);
        }

        if ($mineOnly && $userId > 0) {
            $query->where('d.created_by_user_id', $userId);
        }

        if ($q !== '') {
            SmartSearch::apply($query, $q, function ($inner, SmartSearch $search) {
                $like = $search->phraseLike();

                $inner->where('d.draft_number', 'like', $like)
                    ->orWhere('r.request_ref', 'like', $like)
                    ->orWhere('c.first_name', 'like', $like)
                    ->orWhere('c.last_name', 'like', $like)
                    ->orWhere('c.company_name', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', c.first_name, c.last_name) like ?", [$like])
                    ->orWhereRaw("CONCAT_WS(' ', c.last_name, c.first_name) like ?", [$like]);

                $search->orWhereAllTokensAcross($inner, [
                    'c.first_name',
                    'c.last_name',
                    'c.company_name',
                ]);

                $inner->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('customer_emails as ce')
                        ->join('emails as e', 'e.id', '=', 'ce.email_id')
                        ->whereColumn('ce.customer_id', 'c.id')
                        ->where('e.email', 'like', $like);
                });

                if ($search->digits !== '') {
                    $digitsLike = $search->digitsLike();
                    $inner->orWhereExists(function ($sub) use ($digitsLike) {
                        $sub->selectRaw('1')
                            ->from('customer_phones as cp')
                            ->join('phones as p', 'p.id', '=', 'cp.phone_id')
                            ->whereColumn('cp.customer_id', 'c.id')
                            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(p.phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') like ?", [$digitsLike]);
                    });
                }

                $inner->orWhereExists(function ($sub) use ($like, $search) {
                    $sub->selectRaw('1')
                        ->from('draft_order_items as i')
                        ->whereColumn('i.draft_order_id', 'd.id')
                        ->where(function ($item) use ($like, $search) {
                            $item->where('i.description', 'like', $like)
                                ->orWhere('i.product_code', 'like', $like)
                                ->orWhere('i.sku', 'like', $like)
                                ->orWhere('i.url', 'like', $like);

                            $search->orWhereAllTokensAcross($item, [
                                'i.description',
                                'i.product_code',
                                'i.sku',
                            ]);
                        });
                });
            });
        }

        return $query->paginate(20)->withQueryString();
    }

    public function statusOptions(): array
    {
        return ['open', 'reviewing', 'ready', 'consumed', 'cancelled'];
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
                'r.request_ref', 'o.order_number as finalized_order_number', 'o.status as finalized_order_status',
            ])
            ->first();
    }

    public function items(int $draftId)
    {
        return DB::table('draft_order_items as i')
            ->leftJoin('retailers as r', 'r.id', '=', 'i.retailer_id')
            ->where('i.draft_order_id', $draftId)
            ->select('i.*', 'r.name as retailer_name', 'r.base_url as retailer_base_url', 'r.logo_path as retailer_logo_path')
            ->orderByDesc('i.created_at')
            ->orderByDesc('i.id')
            ->get();
    }

    public function retailerSummaries(int $draftId)
    {
        return DB::table('draft_order_retailers as dr')
            ->leftJoin('retailers as r', 'r.id', '=', 'dr.retailer_id')
            ->where('dr.draft_order_id', $draftId)
            ->select('dr.*', 'r.name as retailer_name', 'r.logo_path as retailer_logo_path')
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
            ->whereIn('a.type', ['note', 'system_note', 'supplier_note', 'draft_note', 'customer_request_note', 'request_note'])
            ->select('a.*', 'u.name as author_name')
            ->orderByDesc('a.is_pinned')
            ->orderByDesc(DB::raw('coalesce(a.occurred_at, a.created_at)'))
            ->limit(30)
            ->get();
    }


    public function requestNotes(int $draftId): array
    {
        $draft = DB::table('draft_orders as d')
            ->leftJoin('order_requests as r', 'r.id', '=', 'd.order_request_id')
            ->where('d.id', $draftId)
            ->select('d.order_request_id', 'd.draft_number', 'r.request_ref', 'r.notes')
            ->first();

        $requestNotes = $draft ? trim((string) ($draft->notes ?? '')) : '';
        $convertedNote = null;

        if (Schema::hasTable('activity_logs')) {
            $convertedNote = DB::table('activity_logs')
                ->where('subject_type', 'draft_order')
                ->where('subject_id', $draftId)
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->where('title', 'Order request converted')
                        ->orWhere('type', 'customer_request_note')
                        ->orWhere('type', 'request_note');
                })
                ->orderByDesc(DB::raw('coalesce(occurred_at, created_at)'))
                ->first();
        }

        return [
            'order_request_id' => $draft && $draft->order_request_id ? (int) $draft->order_request_id : null,
            'request_ref' => $draft->request_ref ?? null,
            'notes' => $requestNotes,
            'converted_note_body' => $convertedNote->body ?? null,
            'has_notes' => $requestNotes !== '' || trim((string) ($convertedNote->body ?? '')) !== '',
        ];
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
        $phoneCountryId = null;
        $address = null;
        $addressRow = null;

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
                ->select('p.phone', 'p.country_id')
                ->first();
            if ($row) {
                $phone = trim((string) ($row->phone ?? ''));
                $phoneCountryId = $row->country_id ? (int) $row->country_id : null;
            }
        }

        if (Schema::hasTable('customer_addresses') && Schema::hasTable('addresses')) {
            $row = DB::table('customer_addresses as ca')
                ->join('addresses as a', 'a.id', '=', 'ca.address_id')
                ->leftJoin('countries as c', 'c.id', '=', 'a.country_id')
                ->where('ca.customer_id', $customerId)
                ->where('ca.is_active', 1)
                ->orderByDesc('ca.is_primary')
                ->select('a.id', 'a.line1', 'a.line2', 'a.city', 'a.region', 'a.postcode', 'a.country_id', 'c.name as country_name')
                ->first();
            if ($row) {
                $parts = array_filter([$row->line1, $row->line2, $row->city, $row->region, $row->postcode, $row->country_name]);
                $address = implode("\n", $parts);
                $addressRow = [
                    'id' => (int) $row->id,
                    'line1' => (string) ($row->line1 ?? ''),
                    'line2' => (string) ($row->line2 ?? ''),
                    'city' => (string) ($row->city ?? ''),
                    'region' => (string) ($row->region ?? ''),
                    'postcode' => (string) ($row->postcode ?? ''),
                    'country_id' => $row->country_id ? (int) $row->country_id : null,
                ];
            }
        }

        return [
            'email' => $email,
            'phone' => $phone,
            'phone_country_id' => $phoneCountryId,
            'address' => $address,
            'address_row' => $addressRow,
        ];
    }

    public function countries()
    {
        if (! Schema::hasTable('countries')) {
            return collect();
        }

        return DB::table('countries')
            ->where('is_active', 1)
            ->select('id', 'name', 'iso2', 'phone_code')
            ->orderByRaw("case when name = 'Gibraltar' then 0 when name = 'Spain' then 1 when name in ('United Kingdom', 'UK') then 2 else 3 end")
            ->orderBy('name')
            ->get();
    }


    public function reopenConsumedDraftForNewVersion(int $draftId, int $userId): void
    {
        $draft = DB::table('draft_orders')->where('id', $draftId)->first();

        if (! $draft) {
            return;
        }

        $status = (string) ($draft->status ?? '');
        $state = (string) ($draft->state ?? '');

        if (! in_array($status, ['consumed', 'finalised'], true) && ! in_array($state, ['consumed', 'finalised'], true)) {
            return;
        }

        DB::table('draft_orders')->where('id', $draftId)->update([
            'status' => 'open',
            'state' => 'draft',
            'updated_by_user_id' => $userId,
            'updated_at' => now(),
        ]);

        $this->addSystemNote(
            $draftId,
            'Consumed draft reopened for new version',
            'Staff confirmed editing a consumed draft. The existing child order remains unchanged; future finalise will create a new order version.',
            $userId
        );
    }

    public function updateCustomer(int $customerId, int $draftId, array $data, int $userId): void
    {
        DB::transaction(function () use ($customerId, $draftId, $data, $userId) {
            $firstName = trim((string) ($data['first_name'] ?? '')) ?: null;
            $lastName = trim((string) ($data['last_name'] ?? '')) ?: null;
            $companyName = trim((string) ($data['company_name'] ?? '')) ?: null;

            DB::table('customers')->where('id', $customerId)->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company_name' => $companyName,
                'customer_type' => $companyName && ! ($firstName || $lastName) ? 'company' : 'individual',
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);

            $email = strtolower(trim((string) ($data['email'] ?? '')));
            if ($email !== '' && Schema::hasTable('emails') && Schema::hasTable('customer_emails')) {
                $emailId = DB::table('emails')->where('email', $email)->value('id');
                if (! $emailId) {
                    $emailId = DB::table('emails')->insertGetId([
                        'email' => $email,
                        'is_active' => 1,
                        'created_by_user_id' => $userId,
                        'updated_by_user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('customer_emails')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_by_user_id' => $userId, 'updated_at' => now()]);
                DB::table('customer_emails')->updateOrInsert(
                    ['customer_id' => $customerId, 'email_id' => $emailId],
                    ['is_primary' => 1, 'is_active' => 1, 'updated_by_user_id' => $userId, 'updated_at' => now(), 'created_by_user_id' => $userId, 'created_at' => now()]
                );
            }

            $phone = preg_replace('/[^0-9+]/', '', (string) ($data['phone'] ?? '')) ?: '';
            $phoneCountryId = ! empty($data['phone_country_id']) ? (int) $data['phone_country_id'] : null;
            if ($phone !== '' && Schema::hasTable('phones') && Schema::hasTable('customer_phones')) {
                $phoneId = DB::table('phones')->where('phone', $phone)->where('country_id', $phoneCountryId)->value('id');
                if (! $phoneId) {
                    $phoneId = DB::table('phones')->insertGetId([
                        'phone' => $phone,
                        'country_id' => $phoneCountryId,
                        'is_active' => 1,
                        'created_by_user_id' => $userId,
                        'updated_by_user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('customer_phones')->where('customer_id', $customerId)->update(['is_primary' => 0, 'updated_by_user_id' => $userId, 'updated_at' => now()]);
                DB::table('customer_phones')->updateOrInsert(
                    ['customer_id' => $customerId, 'phone_id' => $phoneId],
                    ['is_primary' => 1, 'is_active' => 1, 'updated_by_user_id' => $userId, 'updated_at' => now(), 'created_by_user_id' => $userId, 'created_at' => now()]
                );
            }

            $line1 = trim((string) ($data['line1'] ?? ''));
            if ($line1 !== '' && Schema::hasTable('addresses') && Schema::hasTable('customer_addresses')) {
                $addressPayload = [
                    'line1' => $line1,
                    'line2' => trim((string) ($data['line2'] ?? '')) ?: null,
                    'city' => trim((string) ($data['city'] ?? '')) ?: null,
                    'region' => trim((string) ($data['region'] ?? '')) ?: null,
                    'postcode' => trim((string) ($data['postcode'] ?? '')) ?: null,
                    'country_id' => ! empty($data['country_id']) ? (int) $data['country_id'] : null,
                    'is_active' => 1,
                    'updated_by_user_id' => $userId,
                    'updated_at' => now(),
                ];

                $addressId = DB::table('customer_addresses as ca')
                    ->join('addresses as a', 'a.id', '=', 'ca.address_id')
                    ->where('ca.customer_id', $customerId)
                    ->where('ca.is_active', 1)
                    ->orderByDesc('ca.is_primary')
                    ->value('a.id');

                if ($addressId) {
                    DB::table('addresses')->where('id', $addressId)->update($addressPayload);
                } else {
                    $addressPayload['created_by_user_id'] = $userId;
                    $addressPayload['created_at'] = now();
                    $addressId = DB::table('addresses')->insertGetId($addressPayload);
                    DB::table('customer_addresses')->insert([
                        'customer_id' => $customerId,
                        'address_id' => $addressId,
                        'is_primary' => 1,
                        'is_active' => 1,
                        'label' => 'Primary',
                        'created_by_user_id' => $userId,
                        'updated_by_user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->addSystemNote($draftId, 'Customer updated', 'Customer contact or address details were updated from the draft workbench.', $userId);
        });
    }


    public function updateFees(int $draftId, array $data, int $userId): void
    {
        DB::table('draft_orders')->where('id', $draftId)->update([
            'fee_mode' => $data['fee_mode'],
            'dabba_fee_rate' => round(((float) $data['dabba_fee_rate']) / 100, 4),
            'dabba_fee_min' => round((float) $data['dabba_fee_min'], 2),
            'updated_by_user_id' => $userId,
            'updated_at' => now(),
        ]);

        $this->addSystemNote(
            $draftId,
            'Fee policy updated',
            'Dabba fee policy was updated to ' . $data['fee_mode'] . ', rate ' . number_format((float) $data['dabba_fee_rate'], 2) . '%, minimum £' . number_format((float) $data['dabba_fee_min'], 2) . '.',
            $userId
        );

        $this->recalculate($draftId, $userId);
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

    public function updateRetailerDeliveryFee(int $draftId, int $retailerId, float $fee, int $userId): void
    {
        $fee = round(max(0, $fee), 2);

        DB::table('draft_order_retailers')
            ->where('draft_order_id', $draftId)
            ->where('retailer_id', $retailerId)
            ->update([
                'retailer_delivery_fee_total' => $fee,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]);

        $this->recalculate($draftId, $userId);
    }

    public function recalculate(int $draftId, ?int $userId = null): void
    {
        DB::transaction(function () use ($draftId, $userId) {
            $existingRetailerDeliveryFees = DB::table('draft_order_retailers')
                ->where('draft_order_id', $draftId)
                ->pluck('retailer_delivery_fee_total', 'retailer_id');

            $items = DB::table('draft_order_items')
                ->where('draft_order_id', $draftId)
                ->select('retailer_id')
                ->selectRaw('sum(coalesce(qty, 1) * coalesce(unit_price, 0)) as subtotal')
                ->selectRaw('sum(coalesce(item_retailer_delivery_fee, item_delivery_fee, 0)) as seller_delivery')
                ->groupBy('retailer_id')
                ->get();

            DB::table('draft_order_retailers')->where('draft_order_id', $draftId)->delete();

            $itemsSubtotal = 0.0;
            $deliveryTotal = 0.0;
            $feeTotal = 0.0;

            $draft = DB::table('draft_orders')->where('id', $draftId)->first();
            $storedRate = (float) ($draft->dabba_fee_rate ?? 0.20);
            $rate = $storedRate > 1 ? $storedRate : $storedRate * 100;
            $min = (float) ($draft->dabba_fee_min ?? 10);

            foreach ($items as $row) {
                $subtotal = round((float) $row->subtotal, 2);
                $sellerDelivery = round((float) $row->seller_delivery, 2);
                $retailerDelivery = round((float) ($existingRetailerDeliveryFees[$row->retailer_id] ?? 0), 2);
                $fee = $draft && $draft->fee_mode === 'fee_disabled' ? 0.0 : max($min, round($subtotal * ($rate / 100), 2));
                $grand = round($subtotal + $sellerDelivery + $retailerDelivery + $fee, 2);

                $itemsSubtotal += $subtotal;
                $deliveryTotal += ($sellerDelivery + $retailerDelivery);
                $feeTotal += $fee;

                DB::table('draft_order_retailers')->insert([
                    'draft_order_id' => $draftId,
                    'retailer_id' => $row->retailer_id,
                    'retailer_subtotal' => $subtotal,
                    'retailer_delivery_fee_total' => $retailerDelivery,
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
