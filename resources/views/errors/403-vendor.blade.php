@extends('layouts.app')

@section('title', __('Forbidden') . ' — Vetora')

@section('content')
<div class="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-12">
    <div class="auth-shell w-full max-w-md">
        <div class="text-center">
            <p class="text-6xl font-bold" style="color: var(--color-warning-500)">403</p>
            <h1 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">{{ __('Forbidden') }}</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ $message ?? __('Your vendor account is inactive or not set up. Please contact support.') }}
            </p>
            <a href="{{ route('login') }}" class="btn-primary mt-6 inline-flex">
                {{ __('Back to login') }}
            </a>
        </div>
    </div>
</div>
@endsection
