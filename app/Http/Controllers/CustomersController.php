<?php

namespace App\Http\Controllers;

use App\Services\Customers\CustomerDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomersController extends Controller
{
    public function index(Request $request, CustomerDeskService $customers)
    {
        $filters = ['q' => trim((string) $request->query('q', ''))];

        return view('customers.index', [
            'filters' => $filters,
            'customers' => $customers->search($filters),
            'initialResults' => $customers->liveSearch($filters['q'], 25),
        ]);
    }

    public function liveSearch(Request $request, CustomerDeskService $customers): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        return response()->json([
            'ok' => true,
            'query' => $q,
            'customers' => $customers->liveSearch($q, 25),
        ]);
    }

    public function create(CustomerDeskService $customers)
    {
        return view('customers.create', [
            'countries' => $customers->countries(),
            'defaults' => $customers->formDefaults(),
        ]);
    }

    public function store(Request $request, CustomerDeskService $customers)
    {
        $id = $customers->create($this->validated($request), (int) Auth::id());

        return redirect()->route('customers.show', $id)->with('success', 'Customer created.');
    }

    public function show(int $customer, CustomerDeskService $customers)
    {
        $record = $customers->find($customer);
        abort_if(! $record, 404);

        return view('customers.show', [
            'customer' => $record,
            'details' => $customers->details($customer),
            'effectiveFee' => $customers->effectiveFeePolicy($customer),
        ]);
    }

    public function edit(int $customer, CustomerDeskService $customers)
    {
        $record = $customers->find($customer);
        abort_if(! $record, 404);

        return view('customers.edit', [
            'customer' => $record,
            'details' => $customers->details($customer),
            'countries' => $customers->countries(),
            'defaults' => $customers->formDefaults(),
        ]);
    }

    public function update(int $customer, Request $request, CustomerDeskService $customers)
    {
        abort_if(! $customers->find($customer), 404);

        $customers->update($customer, $this->validated($request), (int) Auth::id());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:50', "regex:/^[\\pL\\s'’\\-]+$/u"],
            'last_name' => ['required', 'string', 'min:2', 'max:50', "regex:/^[\\pL\\s'’\\-]+$/u"],
            'company_name' => ['nullable', 'string', 'max:150', "regex:/^[\\pL\\pN\\s&.'’\\-]+$/u"],
            'reference' => ['nullable', 'string', 'max:191'],
            'is_active' => ['nullable'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['required', 'string', 'max:64', "regex:/^[0-9+\\s().-]+$/"],
            'phone_country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'line1' => ['required', 'string', 'min:7', 'max:191'],
            'line2' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'region' => ['nullable', 'string', 'max:191'],
            'postcode' => ['nullable', 'string', 'max:32'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'dabba_fee_level' => ['required', 'string', 'in:global,custom'],
            'dabba_fee_rate' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_if:dabba_fee_level,custom'],
            'dabba_fee_min' => ['nullable', 'numeric', 'min:0', 'max:999999', 'required_if:dabba_fee_level,custom'],
            'dabba_fee_is_disabled' => ['nullable'],
        ], [
            'first_name.regex' => 'First name may contain letters, spaces, hyphens and apostrophes only.',
            'last_name.regex' => 'Last name may contain letters, spaces, hyphens and apostrophes only.',
            'company_name.regex' => 'Company name contains characters that are not allowed.',
            'line1.min' => 'Address line 1 must be at least 7 characters.',
            'phone.regex' => 'Phone number may contain digits, +, spaces, brackets, dots and hyphens only.',
        ]);
    }
}
