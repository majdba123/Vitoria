@extends('layouts.app')

@section('title', __('Forbidden') . ' — SyriaZone')

@section('content')
<div class="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-12">
    <div class="w-full max-w-md rounded-xl bg-white p-8 shadow-xl ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-gray-800">
        <div class="text-center">
            <p class="text-6xl font-bold text-amber-500">403</p>
            <h1 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">{{ __('Forbidden') }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ $message ?? __('Your vendor account is inactive or not set up. Please contact support.') }}
            </p>
            <a href="{{ route('login') }}" class="mt-6 inline-block rounded-lg bg-gray-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                {{ __('Back to login') }}
            </a>
        </div>
    </div>
</div>
@endsection
