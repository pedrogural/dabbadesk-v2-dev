<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderRequestsController extends Controller
{
    private const REVIEW_STATUSES = [
        'received',
        'in_review',
        'needs_clarification',
        'approved',
        'rejected',
    ];

    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', 'open'));
        $search = trim((string) $request->query('q', ''));

        $requests = DB::table('order_requests')
            ->leftJoin('order_request_items', function ($join) {
                $join->on('order_request_items.order_request_id', '=', 'order_requests.id')
                    ->whereNull('order_request_items.deleted_at');
            })
            ->leftJoin('order_request_attachments', 'order_request_attachments.order_request_id', '=', 'order_requests.id')
            ->select([
                'order_requests.id',
                'order_requests.request_ref',
                'order_requests.customer_first_name',
                'order_requests.customer_last_name',
                'order_requests.customer_company_name',
                'order_requests.customer_email',
                'order_requests.customer_phone_digits',
                'order_requests.status',
                'order_requests.estimated_total',
                'order_requests.submitted_at',
                'order_requests.created_at',
                'order_requests.reviewed_at',
                'order_requests.converted_at',
                DB::raw('COUNT(DISTINCT order_request_items.id) as item_count'),
                DB::raw('COUNT(DISTINCT NULLIF(order_request_items.retailer_name, "")) as retailer_count'),
                DB::raw('COUNT(DISTINCT order_request_attachments.id) as attachment_count'),
            ])
            ->when($status === 'open', function ($query) {
                $query->whereNull('order_requests.converted_at')
                    ->whereNotIn('order_requests.status', ['rejected', 'cancelled']);
            })
            ->when($status !== '' && $status !== 'all' && $status !== 'open', function ($query) use ($status) {
                $query->where('order_requests.status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('order_requests.request_ref', 'like', $like)
                        ->orWhere('order_requests.customer_first_name', 'like', $like)
                        ->orWhere('order_requests.customer_last_name', 'like', $like)
                        ->orWhere('order_requests.customer_company_name', 'like', $like)
                        ->orWhere('order_requests.customer_email', 'like', $like)
                        ->orWhere('order_requests.customer_phone_digits', 'like', $like)
                        ->orWhere('order_request_items.retailer_name', 'like', $like)
                        ->orWhere('order_request_items.product_code', 'like', $like)
                        ->orWhere('order_request_items.description', 'like', $like);
                });
            })
            ->groupBy([
                'order_requests.id',
                'order_requests.request_ref',
                'order_requests.customer_first_name',
                'order_requests.customer_last_name',
                'order_requests.customer_company_name',
                'order_requests.customer_email',
                'order_requests.customer_phone_digits',
                'order_requests.status',
                'order_requests.estimated_total',
                'order_requests.submitted_at',
                'order_requests.created_at',
                'order_requests.reviewed_at',
                'order_requests.converted_at',
            ])
            ->orderByDesc('order_requests.id')
            ->paginate(25)
            ->withQueryString();

        return view('order-requests.index', [
            'requests' => $requests,
            'newRequestCount' => $this->newRequestCount(),
            'status' => $status,
            'search' => $search,
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    public function show(int $orderRequest): View
    {
        $requestRow = DB::table('order_requests')
            ->leftJoin('users as reviewed_by', 'reviewed_by.id', '=', 'order_requests.reviewed_by_user_id')
            ->leftJoin('users as converted_by', 'converted_by.id', '=', 'order_requests.converted_by_user_id')
            ->where('order_requests.id', $orderRequest)
            ->select([
                'order_requests.*',
                'reviewed_by.name as reviewed_by_name',
                'converted_by.name as converted_by_name',
            ])
            ->first();

        abort_if(! $requestRow, 404);

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

        $retailerGroups = $items
            ->groupBy(fn ($item) => trim((string) ($item->retailer_name ?: $item->matched_retailer_name ?: 'Unknown retailer')))
            ->map(function ($group, $retailerName) {
                return (object) [
                    'name' => $retailerName,
                    'item_count' => $group->count(),
                    'subtotal' => $group->sum(fn ($item) => (float) ($item->line_total ?? ((float) $item->unit_price * (int) $item->quantity))),
                ];
            })
            ->values();

        $reviewNotes = DB::table('activity_logs')
            ->leftJoin('users', 'users.id', '=', 'activity_logs.created_by_user_id')
            ->where('activity_logs.subject_type', 'order_request')
            ->where('activity_logs.subject_id', $orderRequest)
            ->whereNull('activity_logs.deleted_at')
            ->whereIn('activity_logs.type', ['review_note', 'review_status'])
            ->orderByDesc(DB::raw('COALESCE(activity_logs.occurred_at, activity_logs.created_at)'))
            ->orderByDesc('activity_logs.id')
            ->select([
                'activity_logs.*',
                'users.name as created_by_name',
            ])
            ->get();

        return view('order-requests.show', [
            'orderRequest' => $requestRow,
            'items' => $items,
            'attachments' => $attachments,
            'retailerGroups' => $retailerGroups,
            'reviewNotes' => $reviewNotes,
        ]);
    }

    public function updateStatus(Request $request, int $orderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', self::REVIEW_STATUSES)],
            'status_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $existing = DB::table('order_requests')
            ->where('id', $orderRequest)
            ->first();

        abort_if(! $existing, 404);

        if ($existing->converted_at) {
            return back()->with('error', 'This request has already been converted and cannot be changed from the review screen.');
        }

        $status = $validated['status'];
        $note = trim((string) ($validated['status_note'] ?? ''));
        $now = now();
        $oldStatus = (string) $existing->status;

        DB::transaction(function () use ($orderRequest, $existing, $status, $note, $now, $oldStatus) {
            DB::table('order_requests')
                ->where('id', $orderRequest)
                ->update([
                    'status' => $status,
                    'reviewed_at' => in_array($status, ['in_review', 'approved', 'rejected', 'needs_clarification'], true)
                        ? ($existing->reviewed_at ?: $now)
                        : $existing->reviewed_at,
                    'reviewed_by_user_id' => in_array($status, ['in_review', 'approved', 'rejected', 'needs_clarification'], true)
                        ? auth()->id()
                        : $existing->reviewed_by_user_id,
                    'updated_at' => $now,
                ]);

            $title = 'Status changed';
            $body = 'Status changed from ' . $this->humanStatus($oldStatus) . ' to ' . $this->humanStatus($status) . '.';

            if ($note !== '') {
                $body .= "\n\n" . $note;
            }

            $this->logReviewEvent($orderRequest, 'review_status', $title, $body, $now);
        });

        return back()->with('success', 'Request status updated to ' . $this->humanStatus($status) . '.');
    }

    public function storeNote(Request $request, int $orderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $existing = DB::table('order_requests')
            ->where('id', $orderRequest)
            ->first();

        abort_if(! $existing, 404);

        $body = trim((string) $validated['body']);

        if ($body === '') {
            return back()->with('error', 'Please write a note before saving.');
        }

        $this->logReviewEvent(
            $orderRequest,
            'review_note',
            'Internal review note',
            $body,
            now()
        );

        return back()->with('success', 'Internal review note added.');
    }

    public function attachment(int $orderRequest, int $attachment): StreamedResponse
    {
        $file = DB::table('order_request_attachments')
            ->where('id', $attachment)
            ->where('order_request_id', $orderRequest)
            ->first();

        abort_if(! $file, 404);
        abort_unless(Storage::exists($file->path), 404);

        return Storage::download($file->path, $file->original_name);
    }

    public function counter(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'count' => $this->newRequestCount(),
        ]);
    }

    private function newRequestCount(): int
    {
        return DB::table('order_requests')
            ->where('status', 'received')
            ->whereNull('converted_at')
            ->count();
    }

    private function statusCounts(): array
    {
        $raw = DB::table('order_requests')
            ->whereNull('converted_at')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'open' => DB::table('order_requests')
                ->whereNull('converted_at')
                ->whereNotIn('status', ['rejected', 'cancelled'])
                ->count(),
            'received' => (int) ($raw['received'] ?? 0),
            'in_review' => (int) ($raw['in_review'] ?? 0),
            'needs_clarification' => (int) ($raw['needs_clarification'] ?? 0),
            'approved' => (int) ($raw['approved'] ?? 0),
            'rejected' => (int) ($raw['rejected'] ?? 0),
            // All means the full historical request register, including converted/rejected/closed requests.
            'all' => DB::table('order_requests')->count(),
        ];
    }

    private function logReviewEvent(int $orderRequestId, string $type, string $title, string $body, mixed $occurredAt): void
    {
        DB::table('activity_logs')->insert([
            'subject_type' => 'order_request',
            'subject_id' => $orderRequestId,
            'type' => $type,
            'is_pinned' => 0,
            'title' => $title,
            'body' => $body,
            'occurred_at' => $occurredAt,
            'created_by_user_id' => auth()->id(),
            'updated_by_user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function humanStatus(string $status): string
    {
        return str($status)->replace('_', ' ')->title()->toString();
    }
}
