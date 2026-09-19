<x-user-layout>
    <x-slot name="header">{{ $title }}</x-slot>
    @include('market-instruments._registry', ['admin' => false])
</x-user-layout>
