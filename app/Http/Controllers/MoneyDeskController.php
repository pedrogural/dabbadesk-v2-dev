<?php

namespace App\Http\Controllers;

use App\Services\Finance\FinancialAnomalyService;
use App\Services\Finance\MoneyDeskSummaryService;
use App\Services\Finance\OrderFinanceLookupService;
use Illuminate\Http\Request;

class MoneyDeskController extends Controller
{
    public function index(MoneyDeskSummaryService $moneyDesk)
    {
        return view('money-desk.index', [
            'stats' => $moneyDesk->getStats(),
            'recentLedgerEntries' => $moneyDesk->recentLedgerEntries(),
            'recentOrderTransactions' => $moneyDesk->recentOrderTransactions(),
            'openWalletCredits' => $moneyDesk->openWalletCredits(),
        ]);
    }

    public function customerSearch(Request $request, MoneyDeskSummaryService $moneyDesk)
    {
        return response()->json([
            'results' => $moneyDesk->customerSearch(
                $request->query('q'),
                $request->query('filter', 'all')
            ),
        ]);
    }

    public function orderSearch(Request $request, OrderFinanceLookupService $orders)
    {
        return response()->json([
            'results' => $orders->search(
                $request->query('q'),
                $request->query('filter', 'order_number')
            ),
        ]);
    }

    public function anomalies(FinancialAnomalyService $anomalies)
    {
        return view('money-desk.anomalies', [
            'summary' => $anomalies->summary(),
            'overSettledOrders' => $anomalies->overSettledOrders(),
            'paidButDueOrders' => $anomalies->paidButDueOrders(),
            'ordersWithNoTransactions' => $anomalies->ordersWithNoTransactions(),
            'walletProblems' => $anomalies->walletProblems(),
            'refundProblems' => $anomalies->refundProblems(),
            'orphanLedgerEntries' => $anomalies->orphanLedgerEntries(),
        ]);
    }

    public function customerShow(int $customer, MoneyDeskSummaryService $moneyDesk)
    {
        $customerProfile = $moneyDesk->customerProfile($customer);

        abort_if(! $customerProfile, 404);

        return view('money-desk.customer-show', [
            'customer' => $customerProfile,
            'summary' => $moneyDesk->customerFinanceSummary($customer),
            'timeline' => $moneyDesk->customerFinanceTimeline($customer),
            'orders' => $moneyDesk->customerOrderFinance($customer),
            'walletCredits' => $moneyDesk->customerWalletCredits($customer),
        ]);
    }

    public function orderShow(int $order, OrderFinanceLookupService $orders)
    {
        $orderProfile = $orders->findOrder($order);

        abort_if(! $orderProfile, 404);

        return view('money-desk.order-show', [
            'order' => $orderProfile,
            'summary' => $orders->summary($order),
            'timeline' => $orders->timeline($order),
            'walletApplications' => $orders->walletApplications($order),
            'warnings' => $orders->warnings($order),
        ]);
    }
}