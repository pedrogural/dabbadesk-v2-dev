<?php

namespace App\Http\Controllers;

use App\Services\Intake\FeePolicyLookupService;
use App\Services\Intake\RetailerLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function feePolicy(FeePolicyLookupService $fees): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'fee_policy' => $fees->activePolicy(),
        ]);
    }
}
