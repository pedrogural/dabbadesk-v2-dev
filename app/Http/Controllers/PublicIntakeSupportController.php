<?php

namespace App\Http\Controllers;

use App\Services\Intake\FeePolicyLookupService;
use App\Services\Intake\PublicOrderRequestNotificationService;
use App\Services\Intake\RetailerLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Intake\CreateOrderRequestService;
use Illuminate\Support\Facades\DB;


class PublicIntakeSupportController extends Controller
{
    public function detectRetailer(Request $request, RetailerLookupService $retailers): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['nullable', 'string', 'max:4000'],
            'product_code' => ['nullable', 'string', 'max:255'],
            'retailer_name' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'ok' => true,
            'retailer' => $retailers->detect(
                url: (string) ($validated['url'] ?? ''),
                productCode: (string) ($validated['product_code'] ?? ''),
                retailerName: (string) ($validated['retailer_name'] ?? ''),
            ),
        ]);
    }

    public function countries(): JsonResponse
    {
        $countries = \Illuminate\Support\Facades\DB::table('countries')
            ->where('is_active', 1)
            ->orderByRaw("FIELD(iso2, 'GI', 'ES', 'GB') DESC")
            ->orderBy('name')
            ->get(['name', 'iso2', 'iso3', 'phone_code'])
            ->map(fn($country) => [
                'name' => $country->name,
                'iso2' => $country->iso2,
                'iso3' => $country->iso3,
                'phone_code' => $country->phone_code,
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'countries' => $countries,
        ]);
    }

    public function feePolicy(FeePolicyLookupService $fees): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'fee_policy' => $fees->activePolicy(),
        ]);
    }

    public function submit(
        Request $request,
        PublicOrderRequestNotificationService $notifications,
        CreateOrderRequestService $creator,
        \App\Services\Notifications\CustomerNotificationService $customerNotifications
    ): JsonResponse {


        $wrapper = $request->validate([
            'hp_field' => ['nullable', 'string', 'max:255'],
            'payload' => ['required', 'string'],
        ]);

        if (!empty($wrapper['hp_field'])) {
            return response()->json([
                'ok' => true,
                'message' => 'Public order request received.',
            ]);
        }

        $payload = json_decode($wrapper['payload'], true);

        if (!is_array($payload)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid intake payload.',
            ], 422);
        }

        validator($payload, [
            'source' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_company_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:100'],
            'customer_phone_country' => ['nullable', 'string', 'max:10'],
            'address_line1' => ['nullable', 'string', 'max:1000'],
            'address_line2' => ['nullable', 'string', 'max:1000'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_postcode' => ['nullable', 'string', 'max:50'],
            'address_country' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'submission_uuid' => ['nullable', 'string', 'max:100'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.qty' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.estimated_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.retailer_name' => ['nullable', 'string', 'max:120'],
            'items.*.retailer_url' => ['nullable', 'string', 'max:2048'],
            'items.*.product_code' => ['nullable', 'string', 'max:120'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $created = $creator->create($payload, $request);

        $storedAttachmentCount = 0;

        if (! $created['existing']) {
            $incomingAttachments = $request->file('attachments', []);

            if ($incomingAttachments instanceof \Illuminate\Http\UploadedFile) {
                $incomingAttachments = [$incomingAttachments];
            }

            $storedAttachmentCount = app(
                \App\Services\Intake\OrderRequestAttachmentService::class
            )->storeForRequest(
                (int) $created['id'],
                (string) $created['request_ref'],
                array_filter($incomingAttachments)
            );
        }

        $payload['_attachment_count'] = $storedAttachmentCount;
        $payload['_attachments'] = DB::table('order_request_attachments')
            ->where('order_request_id', $created['id'])
            ->select('original_name', 'size')
            ->get()
            ->map(fn($row) => [
                'original_name' => $row->original_name,
                'size' => $row->size,
            ])
            ->all();

        $notifications->notifyStaff($payload, $created['request_ref']);
        if (!empty($payload['customer_email'])) {
            $customerNotifications->sendOrderRequestConfirmation(
                (string) $payload['customer_email'],
                (string) $created['request_ref'],
                $payload
            );
        }


        return response()->json([
            'ok' => true,
            'message' => 'Public order request received.',
            'reference' => $created['request_ref'],
            'order_request_ref' => $created['request_ref'],
            'order_request_id' => $created['id'],
            'existing' => $created['existing'],
            'attachments_saved' => $storedAttachmentCount,
        ]);
    }
}