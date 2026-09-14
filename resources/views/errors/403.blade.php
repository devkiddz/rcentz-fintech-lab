@extends('layouts.error')

@section('title', '403 - Forbidden')

@section('content')
<div class="min-h-screen bg-white">
  <header class="relative overflow-hidden bg-gradient-to-br from-black via-gray-900 to-black text-white">
    <div class="relative z-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14 lg:py-20 text-center">
        <p class="text-sm uppercase tracking-widest text-white/70">Error</p>
        <h1 class="mt-2 text-5xl font-semibold">403</h1>
        <p class="mt-3 text-lg text-white/80">Access Denied</p>
      </div>
    </div>
  </header>

  <section class="py-16 bg-white">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
      <div class="rounded-2xl border border-border bg-white p-8 shadow-sm text-center">
        <p class="text-gray-700">You do not have permission to access this resource.</p>
        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <a href="{{ url()->previous() }}" class="rounded-md border border-gray-300 px-6 py-3 text-sm font-medium text-black transition hover:bg-gray-50">Go Back</a>
          <a href="{{ route('home') }}" class="rounded-md bg-black px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800">Go Home</a>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection


