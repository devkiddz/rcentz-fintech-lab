<x-admin-layout>
    <x-slot name="header">{{ $title }}</x-slot>
    @include('market-instruments._registry', ['admin' => true])
</x-admin-layout>
