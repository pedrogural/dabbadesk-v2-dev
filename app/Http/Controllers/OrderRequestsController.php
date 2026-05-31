<?php

namespace App\Http\Controllers;

use App\Services\OrderRequests\ConvertOrderRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class OrderRequestsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'open'));

        $requests = DB::table('order_requests')
            ->select([
                'id',
                'request_ref',
                'customer_first_name',
                'customer_last_name',
                'customer_company_name',
                'customer_email',
                'notes',
                'status',
                'estimated_total',
                'submitted_at',
                'created_at',
                'converted_at',
                'converted_draft_order_id',
            ])
            ->when($status === 'open', function ($query) {
                $query->whereNull('converted_at')
                    ->where(function ($statusQuery) {
                        $statusQuery->whereNull('status')->orWhereNotIn('status', ['converted', 'cancelled']);
                    });
            })
            ->when($status !== '' && $status !== 'all' && $status !== 'open', fn($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('request_ref', 'like', "%{$search}%")
                        ->orWhere('customer_first_name', 'like', "%{$search}%")
                        ->orWhere('customer_last_name', 'like', "%{$search}%")
                        ->orWhere('customer_company_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone_digits', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('order-requests.index', [
            'requests' => $requests,
            'newRequestCount' => $this->newRequestCount(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function show(Request $request, int $orderRequest): View
    {
        $requestRow = DB::table('order_requests')
            ->leftJoin('countries as phone_country', 'phone_country.id', '=', 'order_requests.customer_phone_country_id')
            ->leftJoin('countries as address_country', 'address_country.id', '=', 'order_requests.customer_address_country_id')
            ->where('order_requests.id', $orderRequest)
            ->select([
                'order_requests.*',
                'phone_country.name as phone_country_name',
                'phone_country.phone_code as phone_country_code',
                'address_country.name as address_country_name',
            ])
            ->first();

        abort_unless($requestRow, 404);

        $items = DB::table('order_request_items')
            ->leftJoin('retailers', 'retailers.id', '=', 'order_request_items.retailer_id')
            ->where('order_request_items.order_request_id', $orderRequest)
            ->whereNull('order_request_items.deleted_at')
            ->orderBy('order_request_items.sort_order')
            ->orderBy('order_request_items.id')
            ->select([
                'order_request_items.*',
                'retailers.name as matched_retailer_name',
                'retailers.base_url as matched_retailer_base_url',
            ])
            ->get();

        $unresolvedRetailers = $this->unresolvedRetailersForItems($items);

        $attachments = DB::table('order_request_attachments')
            ->where('order_request_id', $orderRequest)
            ->orderBy('id')
            ->get();

        $cancellationLog = DB::table('activity_logs')
            ->where('subject_type', 'order_request')
            ->where('subject_id', $orderRequest)
            ->where('type', 'cancelled')
            ->orderByDesc('id')
            ->first();

        $customerSearch = trim((string) $request->query('customer_q', ''));
        $customerMatches = $this->customerMatches($requestRow);
        $customerSearchResults = $customerSearch !== '' ? $this->customerSearchResults($customerSearch) : collect();
        $customerOptions = $customerSearch !== '' ? $customerSearchResults : $customerMatches;

        $selectedCustomerId = (int) old('customer_id', $request->query('customer_id', $customerOptions->first()->id ?? 0));
        $selectedCustomer = $selectedCustomerId > 0 ? $this->customerProfile($selectedCustomerId) : null;
        $customerDifferences = $selectedCustomer ? $this->customerDifferences($requestRow, $selectedCustomer) : [];

        return view('order-requests.show', [
            'requestRow' => $requestRow,
            'items' => $items,
            'unresolvedRetailers' => $unresolvedRetailers,
            'attachments' => $attachments,
            'cancellationLog' => $cancellationLog,
            'customerMatches' => $customerMatches,
            'customerSearch' => $customerSearch,
            'customerSearchResults' => $customerSearchResults,
            'customerOptions' => $customerOptions,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedCustomer' => $selectedCustomer,
            'customerDifferences' => $customerDifferences,
            'countries' => $this->countryOptions(),
            'newRequestCount' => $this->newRequestCount(),
        ]);
    }

    public function markReviewed(int $orderRequest): RedirectResponse
    {
        $requestRow = DB::table('order_requests')->where('id', $orderRequest)->first();
        abort_unless($requestRow, 404);

        if ($requestRow->converted_at || $requestRow->status === 'converted') {
            return redirect()->route('order-requests.show', $orderRequest)->withErrors([
                'review' => 'Converted order requests cannot be marked for review.',
            ]);
        }

        if ($requestRow->status === 'cancelled') {
            return redirect()->route('order-requests.show', $orderRequest)->withErrors([
                'review' => 'Cancelled order requests cannot be marked for review.',
            ]);
        }

        DB::table('order_requests')
            ->where('id', $orderRequest)
            ->update([
                'status' => 'reviewing',
                'reviewed_at' => $requestRow->reviewed_at ?: now(),
                'reviewed_by_user_id' => $requestRow->reviewed_by_user_id ?: auth()->id(),
                'updated_at' => now(),
            ]);

        $this->logOrderRequestActivity($orderRequest, 'reviewing', 'Request marked for review', 'Order request marked as under review.', (int) auth()->id());

        return redirect()->route('order-requests.show', $orderRequest)->with('status', 'Request marked as under review.');
    }

    public function cancel(Request $request, int $orderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $requestRow = DB::table('order_requests')->where('id', $orderRequest)->first();
        abort_unless($requestRow, 404);

        if ($requestRow->converted_at || $requestRow->status === 'converted') {
            return redirect()->route('order-requests.show', $orderRequest)->withErrors([
                'cancel_reason' => 'Converted order requests cannot be cancelled here. Manage the draft/order lifecycle instead.',
            ]);
        }

        if ($requestRow->status === 'cancelled') {
            return redirect()->route('order-requests.show', $orderRequest)->with('status', 'Order request is already cancelled.');
        }

        DB::transaction(function () use ($orderRequest, $validated): void {
            DB::table('order_requests')
                ->where('id', $orderRequest)
                ->whereNull('converted_at')
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            $this->logOrderRequestActivity(
                $orderRequest,
                'cancelled',
                'Order request cancelled',
                trim((string) $validated['cancel_reason']),
                (int) auth()->id()
            );
        });

        return redirect()->route('order-requests.show', $orderRequest)->with('status', 'Order request cancelled.');
    }

    public function storeRetailerForRequest(Request $request, int $orderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'base_url' => ['required', 'string', 'max:191'],
        ]);

        $requestRow = DB::table('order_requests')->where('id', $orderRequest)->first();
        abort_unless($requestRow, 404);

        if ($requestRow->converted_at || ($requestRow->status ?? '') === 'converted') {
            return back()->withErrors(['retailer' => 'This request has already been converted.']);
        }

        $baseUrl = $this->cleanRetailerBaseUrl((string) $validated['base_url']);
        $name = trim((string) $validated['name']);

        if ($baseUrl === '') {
            return back()->withInput()->withErrors(['base_url' => 'Enter a valid retailer domain before adding it.']);
        }

        $retailer = DB::table('retailers')
            ->whereRaw('LOWER(TRIM(base_url)) = ?', [$baseUrl])
            ->when(Schema::hasColumn('retailers', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->first();

        if (! $retailer) {
            $insert = [
                'name' => Str::limit($name, 191, ''),
                'base_url' => $baseUrl,
            ];

            if (Schema::hasColumn('retailers', 'is_active')) $insert['is_active'] = 1;
            if (Schema::hasColumn('retailers', 'active')) $insert['active'] = 1;
            if (Schema::hasColumn('retailers', 'code')) $insert['code'] = Str::slug($name) ?: Str::slug($baseUrl);
            if (Schema::hasColumn('retailers', 'retailer_code')) $insert['retailer_code'] = Str::slug($name) ?: Str::slug($baseUrl);
            if (Schema::hasColumn('retailers', 'internal_note')) $insert['internal_note'] = 'Added from order request retailer review before draft conversion.';
            if (Schema::hasColumn('retailers', 'created_by_user_id')) $insert['created_by_user_id'] = (int) auth()->id();
            if (Schema::hasColumn('retailers', 'updated_by_user_id')) $insert['updated_by_user_id'] = (int) auth()->id();
            if (Schema::hasColumn('retailers', 'created_at')) $insert['created_at'] = now();
            if (Schema::hasColumn('retailers', 'updated_at')) $insert['updated_at'] = now();

            $retailerId = DB::table('retailers')->insertGetId($insert);
        } else {
            $retailerId = (int) $retailer->id;
        }

        $itemIds = DB::table('order_request_items')
            ->where('order_request_id', $orderRequest)
            ->whereNull('deleted_at')
            ->get(['id', 'retailer_url'])
            ->filter(fn ($item) => $this->cleanRetailerBaseUrl((string) $item->retailer_url) === $baseUrl)
            ->pluck('id')
            ->values()
            ->all();

        if (! empty($itemIds)) {
            DB::table('order_request_items')
                ->whereIn('id', $itemIds)
                ->update([
                    'retailer_id' => $retailerId,
                    'updated_at' => now(),
                ]);
        }

        return back()->with('status', 'Retailer added and linked to matching request item' . (count($itemIds) === 1 ? '' : 's') . '.');
    }

    public function convert(Request $request, ConvertOrderRequestService $converter, int $orderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'customer_mode' => ['required', Rule::in(['create', 'existing'])],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'phone_digits' => ['nullable', 'string', 'max:40'],
            'address_line1' => ['nullable', 'string', 'max:191'],
            'address_postcode' => ['nullable', 'string', 'max:32'],
            'address_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'existing_customer_action' => ['nullable', Rule::in(['keep', 'update'])],
        ]);

        $requestRow = DB::table('order_requests')->where('id', $orderRequest)->first();
        abort_unless($requestRow, 404);

        if ($requestRow->status === 'cancelled') {
            return back()->withInput()->withErrors(['convert' => 'Cancelled order requests cannot be converted to draft.']);
        }

        if ($requestRow->converted_at || $requestRow->status === 'converted') {
            return redirect()->route('order-requests.show', $orderRequest)->withErrors(['convert' => 'This order request has already been converted.']);
        }

        if ($validated['customer_mode'] === 'existing' && empty($validated['customer_id'])) {
            return back()->withInput()->withErrors(['customer_id' => 'Choose an existing customer before converting.']);
        }

        try {
            $draftId = $converter->convert(
                $orderRequest,
                (string) $validated['customer_mode'],
                ! empty($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                $validated,
                (int) auth()->id(),
                (string) ($validated['existing_customer_action'] ?? 'keep')
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'convert' => $exception->getMessage() ?: 'Failed to convert order request.',
                ]);
        }

        return redirect()
            ->route('draft-orders.show', $draftId)
            ->with('success', 'Order request converted successfully.');
    }

    public function counter(): JsonResponse
    {
        return response()->json(['ok' => true, 'count' => $this->newRequestCount()]);
    }

    private function unresolvedRetailersForItems($items)
    {
        return $items
            ->filter(fn ($item) => empty($item->retailer_id) || empty($item->matched_retailer_name))
            ->map(function ($item) {
                $host = $this->cleanRetailerBaseUrl((string) ($item->retailer_url ?? ''));
                $name = trim((string) ($item->retailer_name ?? ''));
                $suggested = $name !== ''
                    ? Str::title($name)
                    : ($host !== '' ? Str::title(str_replace(['-', '.'], ' ', preg_replace('/\.co\.uk$|\.com$|\.net$|\.org$/', '', $host))) : 'Unknown Retailer');

                return [
                    'key' => $host !== '' ? 'host:' . $host : 'name:' . strtolower($suggested),
                    'name' => $suggested,
                    'base_url' => $host,
                    'item_id' => (int) $item->id,
                    'url' => (string) ($item->retailer_url ?? ''),
                ];
            })
            ->groupBy('key')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'key' => $first['key'],
                    'name' => $first['name'],
                    'base_url' => $first['base_url'],
                    'item_id' => $first['item_id'],
                    'url' => $first['url'],
                    'urls' => $rows->pluck('url')->filter()->unique()->values()->all(),
                    'item_ids' => $rows->pluck('item_id')->unique()->values()->all(),
                    'items_count' => $rows->pluck('item_id')->unique()->count(),
                ];
            })
            ->values();
    }

    private function cleanRetailerBaseUrl(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        if (! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
            $value = 'https://' . $value;
        }

        $host = parse_url($value, PHP_URL_HOST) ?: $value;
        $host = strtolower(trim((string) $host));
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        $host = preg_replace('/[^a-z0-9.\-]/', '', $host) ?: '';

        return Str::limit($host, 191, '');
    }

    private function logOrderRequestActivity(int $orderRequestId, string $type, string $title, string $body, int $userId): void
    {
        DB::table('activity_logs')->insert([
            'subject_type' => 'order_request',
            'subject_id' => $orderRequestId,
            'type' => $type,
            'is_pinned' => 0,
            'title' => $title,
            'body' => $body,
            'occurred_at' => now(),
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customerMatches(object $requestRow)
    {
        $email = strtolower(trim((string) $requestRow->customer_email));
        $phoneDigits = preg_replace('/\D+/', '', (string) $requestRow->customer_phone_digits) ?: '';
        $firstName = trim((string) $requestRow->customer_first_name);
        $lastName = trim((string) $requestRow->customer_last_name);
        $company = trim((string) $requestRow->customer_company_name);

        return DB::table('customers')
            ->leftJoin('customer_emails', function ($join) {
                $join->on('customer_emails.customer_id', '=', 'customers.id')->where('customer_emails.is_active', 1);
            })
            ->leftJoin('emails', 'emails.id', '=', 'customer_emails.email_id')
            ->leftJoin('customer_phones', function ($join) {
                $join->on('customer_phones.customer_id', '=', 'customers.id')->where('customer_phones.is_active', 1);
            })
            ->leftJoin('phones', 'phones.id', '=', 'customer_phones.phone_id')
            ->where('customers.is_active', 1)
            ->where(function ($query) use ($email, $phoneDigits, $firstName, $lastName, $company) {
                if ($email !== '') {
                    $query->orWhere('emails.email', $email);
                }
                if ($phoneDigits !== '') {
                    $query->orWhere('phones.phone', $phoneDigits);
                }
                if ($lastName !== '') {
                    $query->orWhere(function ($nameQuery) use ($firstName, $lastName) {
                        $nameQuery->where('customers.last_name', 'like', $lastName . '%');
                        if ($firstName !== '') {
                            $nameQuery->where('customers.first_name', 'like', $firstName . '%');
                        }
                    });
                }
                if ($company !== '') {
                    $query->orWhere('customers.company_name', 'like', '%' . $company . '%');
                }
            })
            ->groupBy('customers.id', 'customers.first_name', 'customers.last_name', 'customers.company_name')
            ->select([
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                DB::raw('MIN(emails.email) as email'),
                DB::raw('MIN(phones.phone) as phone'),
            ])
            ->limit(8)
            ->get();
    }

    private function customerSearchResults(string $search)
    {
        $search = trim($search);
        $digits = preg_replace('/\D+/', '', $search) ?: '';
        $email = strtolower($search);

        return DB::table('customers')
            ->leftJoin('customer_emails', function ($join) {
                $join->on('customer_emails.customer_id', '=', 'customers.id')->where('customer_emails.is_active', 1);
            })
            ->leftJoin('emails', 'emails.id', '=', 'customer_emails.email_id')
            ->leftJoin('customer_phones', function ($join) {
                $join->on('customer_phones.customer_id', '=', 'customers.id')->where('customer_phones.is_active', 1);
            })
            ->leftJoin('phones', 'phones.id', '=', 'customer_phones.phone_id')
            ->where('customers.is_active', 1)
            ->where(function ($query) use ($search, $digits, $email) {
                $query
                    ->where('customers.first_name', 'like', "%{$search}%")
                    ->orWhere('customers.last_name', 'like', "%{$search}%")
                    ->orWhere('customers.company_name', 'like', "%{$search}%")
                    ->orWhere('customers.reference', 'like', "%{$search}%")
                    ->orWhere('emails.email', 'like', "%{$email}%");
                if ($digits !== '') {
                    $query->orWhere('phones.phone', 'like', "%{$digits}%");
                }
            })
            ->groupBy('customers.id', 'customers.first_name', 'customers.last_name', 'customers.company_name')
            ->select([
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                DB::raw('MIN(emails.email) as email'),
                DB::raw('MIN(phones.phone) as phone'),
            ])
            ->orderBy('customers.first_name')
            ->limit(20)
            ->get();
    }

    private function customerProfile(int $customerId): ?object
    {
        return DB::table('customers')
            ->leftJoin('customer_emails', function ($join) {
                $join->on('customer_emails.customer_id', '=', 'customers.id')
                    ->where('customer_emails.is_primary', 1)
                    ->where('customer_emails.is_active', 1);
            })
            ->leftJoin('emails', 'emails.id', '=', 'customer_emails.email_id')
            ->leftJoin('customer_phones', function ($join) {
                $join->on('customer_phones.customer_id', '=', 'customers.id')
                    ->where('customer_phones.is_primary', 1)
                    ->where('customer_phones.is_active', 1);
            })
            ->leftJoin('phones', 'phones.id', '=', 'customer_phones.phone_id')
            ->leftJoin('customer_addresses', function ($join) {
                $join->on('customer_addresses.customer_id', '=', 'customers.id')
                    ->where('customer_addresses.is_primary', 1)
                    ->where('customer_addresses.is_active', 1);
            })
            ->leftJoin('addresses', 'addresses.id', '=', 'customer_addresses.address_id')
            ->where('customers.id', $customerId)
            ->select([
                'customers.id',
                'customers.first_name',
                'customers.last_name',
                'customers.company_name',
                'emails.email',
                'phones.phone as phone_digits',
                'phones.country_id as phone_country_id',
                'addresses.line1 as address_line1',
                'addresses.postcode as address_postcode',
                'addresses.country_id as address_country_id',
            ])
            ->first();
    }


    private function customerDifferences(object $requestRow, object $selectedCustomer): array
    {
        $checks = [
            'name' => [
                'label' => 'Name',
                'stored' => trim((string) ($selectedCustomer->first_name ?? '') . ' ' . (string) ($selectedCustomer->last_name ?? '')),
                'submitted' => trim((string) ($requestRow->customer_first_name ?? '') . ' ' . (string) ($requestRow->customer_last_name ?? '')),
            ],
            'company' => [
                'label' => 'Company',
                'stored' => (string) ($selectedCustomer->company_name ?? ''),
                'submitted' => (string) ($requestRow->customer_company_name ?? ''),
            ],
            'email' => [
                'label' => 'Email',
                'stored' => (string) ($selectedCustomer->email ?? ''),
                'submitted' => (string) ($requestRow->customer_email ?? ''),
            ],
            'phone' => [
                'label' => 'Phone',
                'stored' => (string) ($selectedCustomer->phone_digits ?? ''),
                'submitted' => (string) ($requestRow->customer_phone_digits ?? ''),
            ],
            'address' => [
                'label' => 'Address',
                'stored' => (string) ($selectedCustomer->address_line1 ?? ''),
                'submitted' => (string) ($requestRow->customer_address_line1 ?? ''),
            ],
            'postcode' => [
                'label' => 'Postcode',
                'stored' => (string) ($selectedCustomer->address_postcode ?? ''),
                'submitted' => (string) ($requestRow->customer_address_postcode ?? ''),
            ],
            'country' => [
                'label' => 'Address country',
                'stored' => (string) ($selectedCustomer->address_country_id ?? ''),
                'submitted' => (string) ($requestRow->customer_address_country_id ?? ''),
            ],
        ];

        $differences = [];
        foreach ($checks as $key => $check) {
            $stored = trim((string) $check['stored']);
            $submitted = trim((string) $check['submitted']);

            if ($submitted === '') {
                continue;
            }

            if ($this->normaliseComparable($stored) !== $this->normaliseComparable($submitted)) {
                $differences[$key] = [
                    'label' => $check['label'],
                    'stored' => $stored !== '' ? $stored : '—',
                    'submitted' => $submitted !== '' ? $submitted : '—',
                ];
            }
        }

        return $differences;
    }

    private function normaliseComparable(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?: '';
        $value = preg_replace('/[^a-z0-9@.\-\s]/', '', $value) ?: $value;

        return trim($value);
    }

    private function countryOptions()
    {
        return DB::table('countries')
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN iso2 = 'GI' THEN 0 WHEN iso2 = 'ES' THEN 1 WHEN iso2 = 'GB' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get(['id', 'name', 'iso2', 'phone_code']);
    }

    private function newRequestCount(): int
    {
        return (int) DB::table('order_requests')
            ->whereNull('converted_at')
            ->where(function ($query) {
                $query->whereNull('status')->orWhereNotIn('status', ['converted', 'cancelled']);
            })
            ->count();
    }
}