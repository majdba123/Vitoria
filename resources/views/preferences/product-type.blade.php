@extends('layouts.app')

@section('title', 'اختيار نوع التصفح - Vetora')

@section('content')
<div class="min-h-[calc(100vh-5rem)] bg-gray-50 py-10 dark:bg-gray-950 sm:py-16">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex rounded-full bg-brand-50 px-4 py-1.5 text-xs font-black text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">ابدأ التصفح</span>
            <h1 class="mt-4 text-3xl font-black text-gray-900 dark:text-white sm:text-4xl">اختر نوع المنتجات التي تريد استكشافها</h1>
            <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
                قبل عرض التصنيفات والمنتجات، اختر القسم المناسب لك لنُظهر لك محتوى زراعياً أو بيطرياً فقط.
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

        <form method="POST" action="{{ route('product-type.store') }}" class="mt-10 grid gap-5 lg:grid-cols-2">
            @csrf

            @foreach ($types as $value => $type)
                @php
                    $isSelected = old('preferred_product_type', $selectedType) === $value;
                @endphp
                <label class="group cursor-pointer overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-brand-300 hover:shadow-lg has-[:checked]:border-brand-500 has-[:checked]:shadow-brand-100/80 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-brand-500 dark:has-[:checked]:bg-brand-500/5">
                    <input type="radio" name="preferred_product_type" value="{{ $value }}" class="sr-only" @checked($isSelected)>
                    <div class="flex h-full flex-col p-6 sm:p-7">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                <i class="{{ $type['icon'] }} text-3xl" aria-hidden="true"></i>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black {{ $isSelected ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                {{ $isSelected ? 'محدد الآن' : 'متاح للاختيار' }}
                            </span>
                        </div>

                        <div class="mt-6">
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ $type['label'] }}</h2>
                            <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">{{ $type['description'] }}</p>
                        </div>

                        <div class="mt-6 space-y-3 text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                                <span>عرض التصنيفات المطابقة فقط</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                                <span>تصفية المنتجات والبحث حسب القسم المحدد</span>
                            </div>
                        </div>

                        <div class="mt-8">
                            <span class="inline-flex items-center justify-center rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition group-hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:group-hover:bg-brand-500 dark:group-hover:text-white">
                                {{ $type['button'] }}
                            </span>
                        </div>
                    </div>
                </label>
            @endforeach

            @error('preferred_product_type')
                <p class="lg:col-span-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">{{ $message }}</p>
            @enderror

            <div class="lg:col-span-2 flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-gray-200 bg-white px-5 py-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">يمكنك تعديل نوع التصفح لاحقاً من الصفحة نفسها أو من ملفك الشخصي.</p>
                <button type="submit" class="rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white transition hover:bg-brand-600 active:scale-[.98] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">
                    متابعة
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
