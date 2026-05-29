<x-app-layout>
    <x-slot name="header">Edit customer #{{ $customer->id }}</x-slot>
    <form method="POST" action="{{ route('customers.update', $customer->id) }}">
        @csrf
        @method('PATCH')
        @include('customers._form')
    </form>
</x-app-layout>
