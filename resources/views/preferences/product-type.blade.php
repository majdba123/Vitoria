@extends('layouts.app')

@section('title', __('preferences.title'))

@section('content')
<div class="page-shell min-h-[calc(100vh-5rem)] py-10 sm:py-16">
    <div class="mx-auto max-w-6xl border-y-2 py-8 sm:py-10" style="border-color: var(--color-text);">
        <div class="max-w-2xl text-start">
            <span class="eyebrow">{{ __('preferences.start_browsing') }}</span>
            <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl" style="color: var(--color-text);">{{ __('preferences.heading') }}</h1>
            <p class="mt-3 text-sm leading-7" style="color: var(--color-text-secondary);">
                {{ __('preferences.description') }}
            </p>
        </div>

        @if (session('success'))
            <div class="alert-shell alert-success mx-auto mt-8 max-w-2xl">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-shell alert-error mx-auto mt-8 max-w-2xl">
                {{ session('error') }}
            </div>
        @endif

        @error('preferred_product_type')
            <div class="alert-shell alert-error mx-auto mt-8 max-w-2xl">{{ $message }}</div>
        @enderror

        <div class="home-type-grid mt-10">
            @foreach ($types as $value => $type)
                @php
                    $isSelected = old('preferred_product_type', $selectedType) === $value;
                @endphp
                <a
                    href="{{ route('product-type.select', ['preferred_product_type' => $value, 'redirect_to' => 'categories']) }}"
                    class="home-type-card {{ $isSelected ? 'is-selected' : '' }}"
                    aria-label="{{ $type['button'] }}"
                    @if ($isSelected) aria-current="true" @endif
                >
                    <span class="flex items-start justify-between gap-4">
                        <span class="icon-chip flex h-12 w-12 text-xl">
                            <i class="{{ $type['icon'] }} text-3xl" aria-hidden="true"></i>
                        </span>
                        <span class="badge {{ $isSelected ? 'badge-brand' : '' }}">
                            {{ $isSelected ? __('preferences.currently_selected') : __('preferences.choose') }}
                        </span>
                    </span>

                    <span class="mt-6 block">
                        <span class="block text-2xl font-bold text-gray-900 dark:text-white">{{ $type['label'] }}</span>
                        <span class="mt-3 block text-sm leading-7 text-gray-600 dark:text-slate-200">{{ $type['description'] }}</span>
                    </span>

                    <span class="mt-6 block space-y-3 text-sm text-gray-600 dark:text-slate-200">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            <span>{{ __('preferences.matching_categories_only') }}</span>
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            <span>{{ __('preferences.filter_by_selected_section') }}</span>
                        </span>
                    </span>

                    <span class="home-type-action mt-8">
                        {{ $type['button'] }}
                        <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
