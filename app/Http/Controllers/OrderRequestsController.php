<?php

namespace App\Http\Controllers;

use App\Services\OrderRequests\ConvertOrderRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'id', 'request_ref', 'customer_first_name', 'customer_last_name', 'customer_company_name',
                'customer_email', 'status', 'estimated_total', 'submitted_at', 'created_at',
                'converted_at', 'converted_draft_order_id',
            ])
            ->when($status === 'open', fn ($query) => $query->whereNull('converted_at'))
            ->when($status !== '' && $status !== 'all' && $status !== 'open', fn ($query) => $query->where('status', $status))
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

        $attachments = DB::table('order_request_attachments')
            ->where('order_request_id', $orderRequest)
            ->orderBy('id')
            ->get();

        $customerSearch = trim((string) $request->query('customer_q', ''));
        $customerMatches = $this->customerMatches($requestRow);
        $customerSearchResults = $customerSearch !== '' ? $this->customerSearchResults($customerSearch) : collect();
        $customerOptions = $customerSearch !== '' ? $customerSearchResults : $customerMatches;

        $selectedCustomerId = (int) old('customer_id', $request->query('customer_id', $customerOptions->first()->id ?? 0));
        $selectedCustomer = $selectedCustomerId > 0 ? $this->customerProfile($selectedCustomerId) : null;

        return view('order-requests.show', [
            'requestRow' => $requestRow,
            'items' => $items,
            'attachments' => $attachments,
            'customerMatches' => $customerMatches,
            'customerSearch' => $customerSearch,
            'customerSearchResults' => $customerSearchResults,
            'customerOptions' => $customerOptions,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedCustomer' => $selectedCustomer,
            'countries' => $this->countryOptions(),
            'newRequestCount' => $this->newRequestCount(),
        ]);
    }

    public function markReviewed(int $orderRequest): RedirectResponse
    {
        DB::table('order_requests')
            ->where('id', $orderRequest)
            ->whereNull('reviewed_at')
            ->update([
                'status' => 'reviewing',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => auth()->id(),
                'updated_at' => now(),
            ]);

        return redirect()->route('order-requests.show', $orderRequest)->with('status', 'Request marked as under review.');
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
        ]);

        if ($validated['customer_mode'] === 'existing' && empty($validated['customer_id'])) {
            return back()->withInput()->withErrors(['customer_id' => 'Choose an existing customer before converting.']);
        }

        try {
            $draftId = $converter->convert(
                $orderRequest,
                (string) $validated['customer_mode'],
                ! empty($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                $validated,
                (int) auth()->id()
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['convert' => $exception->getMessage() ?: 'Conversion failed.']);
        }

        return redirect()->route('order-requests.show', $orderRequest)->with('status', 'Converted to draft order #' . $draftId . '.');
    }

    public function counter(): JsonResponse
    {
        return response()->json(['ok' => true, 'count' => $this->newRequestCount()]);
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
                'customers.id', 'customers.first_name', 'customers.last_name', 'customers.company_name',
                DB::raw('MIN(emails.email) as email'), DB::raw('MIN(phones.phone) as phone'),
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
                'customers.id', 'customers.first_name', 'customers.last_name', 'customers.company_name',
                DB::raw('MIN(emails.email) as email'), DB::raw('MIN(phones.phone) as phone'),
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
                'customers.id', 'customers.first_name', 'customers.last_name', 'customers.company_name',
                'emails.email', 'phones.phone as phone_digits', 'phones.country_id as phone_country_id',
                'addresses.line1 as address_line1', 'addresses.postcode as address_postcode', 'addresses.country_id as address_country_id',
            ])
            ->first();
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
