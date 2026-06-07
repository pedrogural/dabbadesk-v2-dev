<?php

namespace App\Http\Controllers;

use App\Services\OrderRequests\ConvertOrderRequestService;
use App\Support\Search\SmartSearch;
use App\Services\Intake\OrderRequestAttachmentService;
use App\Services\Intake\RetailerLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
                'source',
                'status',
                'estimated_total',
                'submitted_at',
                'created_at',
                'converted_at',
                'converted_draft_order_id',
                'purchase_mode',
            ])
            ->when($status === 'open', function ($query) {
                $query->whereNull('converted_at')
                    ->where(function ($statusQuery) {
                        $statusQuery->whereNull('status')->orWhereNotIn('status', ['converted', 'cancelled']);
                    });
            })
            ->when($status !== '' && $status !== 'all' && $status !== 'open', fn($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                SmartSearch::apply($query, $search, function ($inner, SmartSearch $smart) {
                    $like = $smart->phraseLike();

                    $inner
                        ->where('request_ref', 'like', $like)
                        ->orWhere('reference_number', 'like', $like)
                        ->orWhere('source', 'like', $like)
                        ->orWhere('customer_first_name', 'like', $like)
                        ->orWhere('customer_last_name', 'like', $like)
                        ->orWhere('customer_company_name', 'like', $like)
                        ->orWhere('customer_email', 'like', $like)
                        ->orWhere('customer_phone_digits', 'like', $like)
                        ->orWhere('customer_address_line1', 'like', $like)
                        ->orWhere('customer_address_postcode', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereRaw("CONCAT_WS(' ', customer_first_name, customer_last_name) like ?", [$like])
                        ->orWhereRaw("CONCAT_WS(' ', customer_last_name, customer_first_name) like ?", [$like]);

                    $smart->orWhereAllTokensAcross($inner, [
                        'customer_first_name',
                        'customer_last_name',
                        'customer_company_name',
                        'customer_email',
                        'customer_address_line1',
                        'customer_address_postcode',
                        'notes',
                    ]);

                    if ($smart->digits !== '') {
                        $inner->orWhere('customer_phone_digits', 'like', $smart->digitsLike());
                    }
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

    public function createManual(Request $request): View
    {
        $customerSearch = trim((string) $request->query('customer_q', ''));
        $customerOptions = $customerSearch !== '' ? $this->customerSearchResults($customerSearch) : collect();
        $countries = $this->countryOptions();
        $defaultCountryId = (int) ($countries->firstWhere('iso2', 'GI')->id ?? $countries->firstWhere('name', 'Gibraltar')->id ?? 0);
        $selectedPurchaseMode = old('purchase_mode', 'standard');

        return view('order-requests.create-manual', [
            'customerSearch' => $customerSearch,
            'customerOptions' => $customerOptions,
            'countries' => $countries,
            'defaultPhoneCountryId' => $defaultCountryId,
            'defaultAddressCountryId' => $defaultCountryId,
            'defaultPostcode' => 'GX11 1AA',
            'selectedPurchaseMode' => $selectedPurchaseMode,
            'newRequestCount' => $this->newRequestCount(),
        ]);
    }

    public function storeManual(Request $request, ConvertOrderRequestService $converter, OrderRequestAttachmentService $attachments): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(['office', 'email', 'whatsapp', 'phone', 'other'])],
            'purchase_mode' => ['required', Rule::in(['standard', 'customer_self_purchase'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'customer_mode' => ['required', Rule::in(['existing', 'create'])],
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
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt,doc,docx'],
        ]);

        if ($validated['customer_mode'] === 'create') {
            $defaultCountryId = $this->defaultCountryId('GI');

            if (empty($validated['phone_country_id']) && $defaultCountryId) {
                $validated['phone_country_id'] = $defaultCountryId;
            }

            if (empty($validated['address_country_id']) && $defaultCountryId) {
                $validated['address_country_id'] = $defaultCountryId;
            }

            if (trim((string) ($validated['address_postcode'] ?? '')) === '') {
                $validated['address_postcode'] = 'GX11 1AA';
            }
        }

        if ($validated['customer_mode'] === 'existing' && empty($validated['customer_id'])) {
            return back()->withInput()->withErrors(['customer_id' => 'Choose an existing customer before creating the draft.']);
        }

        if ($validated['customer_mode'] === 'create') {
            $hasName = trim((string) ($validated['first_name'] ?? '')) !== ''
                || trim((string) ($validated['last_name'] ?? '')) !== ''
                || trim((string) ($validated['company_name'] ?? '')) !== '';

            if (! $hasName) {
                return back()->withInput()->withErrors(['first_name' => 'Add a customer name or company name before creating the draft.']);
            }
        }

        $userId = (int) auth()->id();

        try {
            $created = DB::transaction(function () use ($request, $validated, $converter, $attachments, $userId): array {
                $requestRef = $this->nextRequestRef();
                $customerSnapshot = $this->manualRequestCustomerSnapshot($validated);

                $orderRequestId = DB::table('order_requests')->insertGetId([
                    'request_ref' => $requestRef,
                    'source' => 'manual_' . (string) $validated['source'],
                    'reference_number' => null,
                    'purchase_mode' => (string) ($validated['purchase_mode'] ?? 'standard'),
                    'customer_first_name' => $customerSnapshot['first_name'],
                    'customer_last_name' => $customerSnapshot['last_name'],
                    'customer_company_name' => $customerSnapshot['company_name'],
                    'customer_email' => $customerSnapshot['email'],
                    'customer_phone_country_id' => $customerSnapshot['phone_country_id'],
                    'customer_phone_digits' => $customerSnapshot['phone_digits'],
                    'customer_address_line1' => $customerSnapshot['address_line1'],
                    'customer_address_postcode' => $customerSnapshot['address_postcode'],
                    'customer_address_country_id' => $customerSnapshot['address_country_id'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'received',
                    'estimated_total' => 0,
                    'disclaimer_accepted_at' => now(),
                    'submitted_at' => now(),
                    'submitted_ip' => $request->ip(),
                    'user_agent' => Str::limit('DabbaDesk manual intake: ' . (string) $validated['source'], 255, ''),
                    'submission_uuid' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $incomingAttachments = $request->file('attachments', []);
                if ($incomingAttachments instanceof \Illuminate\Http\UploadedFile) {
                    $incomingAttachments = [$incomingAttachments];
                }

                $attachments->storeForRequest($orderRequestId, $requestRef, array_filter($incomingAttachments));

                $draftId = $converter->convert(
                    $orderRequestId,
                    (string) $validated['customer_mode'],
                    ! empty($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                    $validated,
                    $userId,
                    'keep',
                    true
                );

                $this->logOrderRequestActivity(
                    $orderRequestId,
                    'manual_request_created',
                    'Manual request created',
                    'Created from ' . $this->sourceLabel('manual_' . (string) $validated['source']) . ' and opened as an empty draft.',
                    $userId
                );

                return ['order_request_id' => $orderRequestId, 'request_ref' => $requestRef, 'draft_id' => $draftId];
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'manual_request' => $exception->getMessage() ?: 'Could not create the manual request and draft.',
            ]);
        }

        return redirect()
            ->route('draft-orders.show', $created['draft_id'])
            ->with('success', 'Manual request #' . $created['request_ref'] . ' created and opened as an empty draft. Add products in the Draft Workbench.');
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

    public function openAttachment(int $orderRequest, int $attachment)
    {
        $requestExists = DB::table('order_requests')->where('id', $orderRequest)->exists();
        abort_unless($requestExists, 404);

        $attachmentRow = DB::table('order_request_attachments')
            ->where('id', $attachment)
            ->where('order_request_id', $orderRequest)
            ->first();

        abort_unless($attachmentRow, 404);

        $path = (string) ($attachmentRow->path ?? '');
        abort_if($path === '' || str_contains($path, '..') || ! Storage::disk('local')->exists($path), 404);

        $name = (string) ($attachmentRow->original_name ?? basename($path));
        $mime = (string) ($attachmentRow->mime ?? 'application/octet-stream');

        return Storage::disk('local')->response($path, $name, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
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


    public function updateItem(Request $request, RetailerLookupService $retailerLookup, int $orderRequest, int $item): RedirectResponse
    {
        $validated = $request->validate([
            'retailer_url' => ['nullable', 'string', 'max:2048'],
            'retailer_name' => ['nullable', 'string', 'max:191'],
            'product_code' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $requestRow = DB::table('order_requests')->where('id', $orderRequest)->first();
        abort_unless($requestRow, 404);

        if ($requestRow->converted_at || ($requestRow->status ?? '') === 'converted') {
            return back()->withErrors(['item' => 'Converted order requests cannot be edited here. Edit the draft instead.']);
        }

        if (($requestRow->status ?? '') === 'cancelled') {
            return back()->withErrors(['item' => 'Cancelled order requests cannot be edited.']);
        }

        $itemRow = DB::table('order_request_items')
            ->where('id', $item)
            ->where('order_request_id', $orderRequest)
            ->whereNull('deleted_at')
            ->first();

        abort_unless($itemRow, 404);

        $retailerUrl = trim((string) ($validated['retailer_url'] ?? ''));
        $retailerNameInput = trim((string) ($validated['retailer_name'] ?? ''));
        $productCode = trim((string) ($validated['product_code'] ?? ''));
        $description = trim((string) ($validated['description'] ?? ''));
        $quantity = max(1, (int) $validated['quantity']);
        $unitPrice = round((float) $validated['unit_price'], 2);
        $lineTotal = round($unitPrice * $quantity, 2);
        $notes = trim((string) ($validated['notes'] ?? ''));

        $detected = $retailerLookup->detect($retailerUrl, $productCode, $retailerNameInput);
        $retailerId = ! empty($detected['retailer_id']) ? (int) $detected['retailer_id'] : null;
        $retailerName = trim((string) ($detected['name'] ?? '')) ?: ($retailerNameInput ?: null);

        DB::transaction(function () use ($orderRequest, $item, $retailerUrl, $retailerName, $retailerId, $productCode, $description, $unitPrice, $quantity, $lineTotal, $notes): void {
            DB::table('order_request_items')
                ->where('id', $item)
                ->where('order_request_id', $orderRequest)
                ->update([
                    'retailer_id' => $retailerId,
                    'retailer_name' => $retailerName,
                    'retailer_url' => $retailerUrl !== '' ? $retailerUrl : null,
                    'product_code' => $productCode !== '' ? $productCode : null,
                    'description' => $description !== '' ? $description : null,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                    'notes' => $notes !== '' ? $notes : null,
                    'updated_at' => now(),
                ]);

            $this->logOrderRequestActivity(
                $orderRequest,
                'item_updated',
                'Order request item updated',
                'Item #' . $item . ' was corrected before draft conversion.',
                (int) auth()->id()
            );
        });

        if ($retailerId) {
            return redirect()->route('order-requests.show', $orderRequest)->with('status', 'Item updated and retailer matched to ' . $retailerName . '.');
        }

        return redirect()->route('order-requests.show', $orderRequest)->with('status', 'Item updated. Retailer still needs review because no existing retailer matched the corrected link/name.');
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

        $unresolvedCount = DB::table('order_request_items')
            ->leftJoin('retailers', 'retailers.id', '=', 'order_request_items.retailer_id')
            ->where('order_request_items.order_request_id', $orderRequest)
            ->whereNull('order_request_items.deleted_at')
            ->where(function ($query) {
                $query->whereNull('order_request_items.retailer_id')
                    ->orWhereNull('retailers.id');
            })
            ->count();

        if ($unresolvedCount > 0) {
            return back()->withInput()->withErrors([
                'convert' => 'Resolve all request item retailers before converting to draft. Order Requests are the intake correction stage; drafts and orders must not inherit unresolved retailers.',
            ]);
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

    private function nextRequestRef(): string
    {
        $counter = DB::table('order_ref_counter')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        $latestNumericRef = DB::table('order_requests')
            ->whereNotNull('request_ref')
            ->whereRaw("request_ref REGEXP '^[0-9]+$'")
            ->max(DB::raw('CAST(request_ref AS UNSIGNED)'));

        $nextValue = max(
            (int) ($counter->next_value ?? 0),
            ((int) $latestNumericRef) + 1
        );

        if ($counter) {
            DB::table('order_ref_counter')
                ->where('id', 1)
                ->update(['next_value' => $nextValue + 1]);
        } else {
            DB::table('order_ref_counter')->insert([
                'id' => 1,
                'next_value' => $nextValue + 1,
            ]);
        }

        return (string) $nextValue;
    }

    private function manualRequestCustomerSnapshot(array $payload): array
    {
        if (($payload['customer_mode'] ?? '') === 'existing' && ! empty($payload['customer_id'])) {
            $profile = $this->customerProfile((int) $payload['customer_id']);
            if (! $profile) {
                throw new \RuntimeException('Selected customer could not be found.');
            }

            return [
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'company_name' => $profile->company_name,
                'email' => $profile->email,
                'phone_country_id' => $profile->phone_country_id,
                'phone_digits' => preg_replace('/\D+/', '', (string) ($profile->phone_digits ?? '')) ?: null,
                'address_line1' => $profile->address_line1,
                'address_postcode' => $profile->address_postcode,
                'address_country_id' => $profile->address_country_id,
            ];
        }

        return [
            'first_name' => Str::title(trim((string) ($payload['first_name'] ?? ''))) ?: null,
            'last_name' => Str::title(trim((string) ($payload['last_name'] ?? ''))) ?: null,
            'company_name' => trim((string) ($payload['company_name'] ?? '')) ?: null,
            'email' => strtolower(trim((string) ($payload['email'] ?? ''))) ?: null,
            'phone_country_id' => ! empty($payload['phone_country_id']) ? (int) $payload['phone_country_id'] : null,
            'phone_digits' => preg_replace('/\D+/', '', (string) ($payload['phone_digits'] ?? '')) ?: null,
            'address_line1' => trim((string) ($payload['address_line1'] ?? '')) ?: null,
            'address_postcode' => trim((string) ($payload['address_postcode'] ?? '')) ?: null,
            'address_country_id' => ! empty($payload['address_country_id']) ? (int) $payload['address_country_id'] : null,
        ];
    }

    private function sourceLabel(?string $source): string
    {
        return match ((string) $source) {
            'manual_office', 'office' => 'Office',
            'manual_email', 'email' => 'Email',
            'manual_whatsapp', 'whatsapp' => 'WhatsApp',
            'manual_phone', 'phone' => 'Phone',
            'manual_other', 'other' => 'Other',
            'order_app_v2', 'public', '' => 'Public',
            default => Str::headline(str_replace(['manual_', '_'], ['', ' '], (string) $source)),
        };
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
        $smart = SmartSearch::from($search);
        $digits = $smart->digits;
        $email = strtolower($search);
        $phraseLike = $smart->phraseLike();

        return DB::table('customers')
            ->leftJoin('customer_emails', function ($join) {
                $join->on('customer_emails.customer_id', '=', 'customers.id')->where('customer_emails.is_active', 1);
            })
            ->leftJoin('emails', 'emails.id', '=', 'customer_emails.email_id')
            ->leftJoin('customer_phones', function ($join) {
                $join->on('customer_phones.customer_id', '=', 'customers.id')->where('customer_phones.is_active', 1);
            })
            ->leftJoin('phones', 'phones.id', '=', 'customer_phones.phone_id')
            ->leftJoin('customer_addresses', function ($join) {
                $join->on('customer_addresses.customer_id', '=', 'customers.id')->where('customer_addresses.is_active', 1);
            })
            ->leftJoin('addresses', 'addresses.id', '=', 'customer_addresses.address_id')
            ->leftJoin('countries', 'countries.id', '=', 'addresses.country_id')
            ->where('customers.is_active', 1)
            ->where(function ($query) use ($smart, $phraseLike, $email, $digits) {
                $query
                    ->where('customers.first_name', 'like', $phraseLike)
                    ->orWhere('customers.last_name', 'like', $phraseLike)
                    ->orWhere('customers.company_name', 'like', $phraseLike)
                    ->orWhere('customers.reference', 'like', $phraseLike)
                    ->orWhere('emails.email', 'like', '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $email) . '%')
                    ->orWhere('addresses.line1', 'like', $phraseLike)
                    ->orWhere('addresses.line2', 'like', $phraseLike)
                    ->orWhere('addresses.city', 'like', $phraseLike)
                    ->orWhere('addresses.region', 'like', $phraseLike)
                    ->orWhere('addresses.postcode', 'like', $phraseLike)
                    ->orWhereRaw("CONCAT_WS(' ', customers.first_name, customers.last_name) like ?", [$phraseLike])
                    ->orWhereRaw("CONCAT_WS(' ', customers.last_name, customers.first_name) like ?", [$phraseLike])
                    ->orWhereRaw("CONCAT_WS(' ', customers.last_name, LEFT(customers.first_name, 1)) like ?", [$phraseLike])
                    ->orWhereRaw("CONCAT_WS(' ', customers.first_name, LEFT(customers.last_name, 1)) like ?", [$phraseLike]);

                $smart->orWhereAllTokensAcross($query, [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.company_name',
                    'emails.email',
                    'addresses.line1',
                    'addresses.line2',
                    'addresses.city',
                    'addresses.region',
                    'addresses.postcode',
                ]);

                $smart->orWhereAllTokensAcrossRaw($query, [
                    "CONCAT_WS(' ', customers.first_name, customers.last_name)",
                    "CONCAT_WS(' ', customers.last_name, customers.first_name)",
                    "CONCAT_WS(' ', customers.last_name, LEFT(customers.first_name, 1))",
                    "CONCAT_WS(' ', customers.first_name, LEFT(customers.last_name, 1))",
                ]);

                if ($digits !== '') {
                    $query->orWhere('phones.phone', 'like', $smart->digitsLike());
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
                DB::raw('MIN(addresses.line1) as address_line1'),
                DB::raw('MIN(addresses.postcode) as postcode'),
                DB::raw('MIN(countries.name) as country_name'),
            ])
            ->orderBy('customers.first_name')
            ->orderBy('customers.last_name')
            ->limit(30)
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

    private function defaultCountryId(string $iso2): ?int
    {
        $country = DB::table('countries')
            ->where('is_active', 1)
            ->where(function ($query) use ($iso2) {
                $query->where('iso2', strtoupper($iso2));
                if (strtoupper($iso2) === 'GI') {
                    $query->orWhere('name', 'Gibraltar');
                }
            })
            ->orderByRaw("CASE WHEN iso2 = ? THEN 0 ELSE 1 END", [strtoupper($iso2)])
            ->first(['id']);

        return $country ? (int) $country->id : null;
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