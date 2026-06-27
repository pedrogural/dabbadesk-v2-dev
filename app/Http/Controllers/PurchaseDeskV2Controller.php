<?php

namespace App\Http\Controllers;

use App\Services\Purchasing\PurchaseDeskV2Service;
use Illuminate\Http\Request;

class PurchaseDeskV2Controller extends Controller
{
    public function index(Request $request, PurchaseDeskV2Service $service)
    {
        return view('purchase-desk-v2.index', $service->index([
            'q' => $request->query('q', ''),
            'payment' => $request->query('payment', 'paid_or_part'),
        ]));
    }

    public function show(Request $request, int $order, PurchaseDeskV2Service $service)
    {
        $workspace = $service->orderWorkspace($order, [
            'q' => $request->query('q', ''),
            'view' => $request->query('view', 'all'),
        ]);

        abort_if(! $workspace, 404);

        return view('purchase-desk-v2.order', $workspace);
    }
}
