<?php

namespace App\Http\Controllers;

use App\Services\Drafts\DraftRetailerDetectionService;
use DabbaDirect\IntakeTools\ProductUrlResolver;
use Illuminate\Http\Request;

class CommerceToolsPublicController extends Controller
{
    public function resolveFinalUrl(Request $request, ProductUrlResolver $resolver)
    {
        $data = $request->validate(['url' => ['required', 'string', 'max:4096']]);
        $resolved = $resolver->resolve($data['url']);

        return response()->json([
            'ok' => $resolved !== null,
            'final_url' => $resolved?->finalUrl,
            'clean_url' => $resolved?->cleanUrl ?: $resolved?->finalUrl,
            'final_host' => $resolved?->finalHost,
            'product_id' => $resolved?->productId,
            'product_id_type' => $resolved?->productIdType,
            'warning' => $resolved?->warning,
        ]);
    }

    public function detectRetailer(Request $request, DraftRetailerDetectionService $detector)
    {
        $data = $request->validate(['url' => ['required', 'string', 'max:4096']]);
        $result = $detector->detect($data['url']);

        return response()->json([
            'ok' => true,
            'retailer' => $result->toArray(),
            'final_url' => $result->finalUrl,
            'final_host' => $result->host,
            'product_id' => $result->productId,
            'product_id_type' => $result->productIdType,
        ]);
    }
}
