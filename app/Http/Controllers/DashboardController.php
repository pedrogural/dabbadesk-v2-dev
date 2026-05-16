<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardSummaryService;

class DashboardController extends Controller
{
    public function index(DashboardSummaryService $dashboard)
    {
        return view('dashboard', [
            'today' => $dashboard->today(),
            'operations' => $dashboard->operations(),
            'finance' => $dashboard->finance(),
            'alerts' => $dashboard->alerts(),
            'recentOrders' => $dashboard->recentOrders(),
            'recentPayments' => $dashboard->recentPayments(),
        ]);
    }
}