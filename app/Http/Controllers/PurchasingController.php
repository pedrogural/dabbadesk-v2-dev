<?php

namespace App\Http\Controllers;

use App\Services\Purchasing\PurchasingQueueService;
use Illuminate\Http\Request;

class PurchasingController extends Controller
{
    public function index(Request $request, PurchasingQueueService $service)
    {
        return view('purchasing.index', $service->queue([
            'tab' => $request->query('tab', 'to_buy'),
            'payment' => $request->query('payment', 'paid'),
            'q' => $request->query('q', ''),
        ]));
    }

    public function show(int $order, Request $request, PurchasingQueueService $service)
    {
        $workspace = $service->workspace($order);

        abort_if(! $workspace, 404);

        $workspace['activeTab'] = in_array($request->query('tab'), array_keys($workspace['tabs']), true)
            ? $request->query('tab')
            : 'overview';

        return view('purchasing.show', $workspace);
    }
}
