<x-admin-layout>
    <x-slot name="header">{{ $instrument->display_symbol }}</x-slot>
    @include('market-instruments._show', ['admin' => true])
</x-admin-layout>
