<x-app-layout>
    <x-slot name="header">{{ $title }}</x-slot>
    @include('market-instruments._registry', ['admin' => false])
</x-app-layout>
