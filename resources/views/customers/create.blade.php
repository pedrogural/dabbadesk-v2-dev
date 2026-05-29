<x-app-layout>
    <x-slot name="header">New customer</x-slot>
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        @include('customers._form', ['customer' => null, 'details' => ['email' => '', 'phone' => '', 'address' => null]])
    </form>
</x-app-layout>
