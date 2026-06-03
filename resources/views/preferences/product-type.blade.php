@extends('layouts.app')

@section('title', 'اختيار نوع التصفح - Vetora')

@section('content')
<div class="min-h-[calc(100vh-5rem)] bg-gray-50 py-10 dark:bg-gray-950 sm:py-16">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex rounded-full bg-brand-50 px-4 py-1.5 text-xs font-black text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">ابدأ التصفح</span>
            <h1 class="mt-4 text-3xl font-black text-gray-900 dark:text-white sm:text-4xl">اختر نوع المنتجات التي ترغب في تصفحها</h1>
            <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
                سيتم عرض التصنيفات والمنتجات المناسبة لاختيارك فقط، ويمكنك تغيير هذا الاختيار لاحقا من نفس الصفحة أو من الملف الشخصي.
            </p>
        </div>

        @if (session('success'))
            <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @error('preferred_product_type')
            <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('product-type.store') }}" class="mt-10 grid gap-5 lg:grid-cols-2">
            @csrf

            @foreach ($types as $value => $type)
                @php
                    $isSelected = old('preferred_product_type', $selectedType) === $value;
                @endphp
                <button
                    type="submit"
                    name="preferred_product_type"
                    value="{{ $value }}"
                    class="group h-full rounded-3xl border bg-white p-6 text-start shadow-sm transition hover:-translate-y-1 hover:border-brand-300 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-brand-500/20 sm:p-7 dark:bg-gray-900 {{ $isSelected ? 'border-brand-500 shadow-brand-100/80 dark:bg-brand-500/5' : 'border-gray-200 dark:border-gray-800 dark:hover:border-brand-500' }}"
                    aria-label="{{ $type['button'] }}"
                >
                    <span class="flex items-start justify-between gap-4">
                        <span class="flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                            <i class="{{ $type['icon'] }} text-3xl" aria-hidden="true"></i>
                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black {{ $isSelected ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                            {{ $isSelected ? 'محدد الآن' : 'اختيار' }}
                        </span>
                    </span>

                    <span class="mt-6 block">
                        <span class="block text-2xl font-black text-gray-900 dark:text-white">{{ $type['label'] }}</span>
                        <span class="mt-3 block text-sm leading-7 text-gray-500 dark:text-gray-400">{{ $type['description'] }}</span>
                    </span>

                    <span class="mt-6 block space-y-3 text-sm text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            <span>عرض التصنيفات المطابقة فقط</span>
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            <span>تصفية المنتجات والبحث حسب القسم المحدد</span>
                        </span>
                    </span>

                    <span class="mt-8 inline-flex items-center justify-center rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition group-hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:group-hover:bg-brand-500 dark:group-hover:text-white">
                        {{ $type['button'] }}
                    </span>
                </button>
            @endforeach
        </form>
    </div>
</div>
@endsection
