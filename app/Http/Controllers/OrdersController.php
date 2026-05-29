<?php

namespace App\Http\Controllers;

use App\Services\Orders\OrdersReadOnlyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    public function index(Request $request, OrdersReadOnlyService $orders)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'mine' => $request->boolean('mine'),
            'user_id' => Auth::id(),
        ];

        return view('orders.index', [
            'filters' => $filters,
            'statusOptions' => $orders->statusOptions(),
            'orders' => $orders->search($filters),
        ]);
    }

    public function show(int $order, OrdersReadOnlyService $orders)
    {
        $orderProfile = $orders->find($order);

        abort_if(! $orderProfile, 404);

        return view('orders.show', [
            'order' => $orderProfile,
            'finance' => $orders->financeSummary($order),
            'retailerGroups' => $orders->itemsGroupedByRetailer($order),
            'purchases' => $orders->purchases($order),
            'arrivals' => $orders->arrivals($order),
            'notes' => $orders->notes($orderProfile),
            'progress' => $orders->progressSummary($order),
        ]);
    }
}