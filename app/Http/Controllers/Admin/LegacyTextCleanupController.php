<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\LegacyTextCleanupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegacyTextCleanupController extends Controller
{
    public function index(Request $request, LegacyTextCleanupService $cleanup)
    {
        $this->ensureAdmin();

        $target = $request->string('target')->toString() ?: null;
        $search = $request->string('search')->toString() ?: null;

        return view('admin.text-cleanup.index', [
            'targets' => $cleanup->flattenedTargets(),
            'selectedTarget' => $target,
            'search' => $search,
            'results' => $cleanup->scan($target, $search, 250),
            'badNeedles' => $cleanup->badNeedles(),
        ]);
    }

    public function update(Request $request, LegacyTextCleanupService $cleanup)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'table' => ['required', 'string', 'max:120'],
            'field' => ['required', 'string', 'max:120'],
            'record_id' => ['required', 'integer', 'min:1'],
            'original' => ['required', 'string'],
            'replacement' => ['required', 'string'],
        ]);

        $cleanup->updateText(
            $data['table'],
            $data['field'],
            (int) $data['record_id'],
            $data['original'],
            $data['replacement']
        );

        return back()->with('success', 'Legacy text corrected.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::user() && Auth::user()->role === 'admin', 403);
    }
}
