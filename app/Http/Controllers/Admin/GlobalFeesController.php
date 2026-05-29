<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GlobalFeeSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalFeesController extends Controller
{
    public function index(GlobalFeeSettingsService $fees)
    {
        $this->ensureAdmin();

        return view('admin.fees.index', [
            'activeFee' => $fees->active(),
            'fees' => $fees->all(),
        ]);
    }

    public function store(Request $request, GlobalFeeSettingsService $fees)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'dabba_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'dabba_fee_min' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);

        $fees->create((float) $data['dabba_fee_rate'], (float) $data['dabba_fee_min'], (int) Auth::id());

        return redirect()->route('admin.fees.index')->with('success', 'Global Dabba fee updated. New drafts will use this unless a customer override applies.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::user() && Auth::user()->role === 'admin', 403);
    }
}
