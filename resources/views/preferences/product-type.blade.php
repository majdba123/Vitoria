@extends('layouts.app')

@section('title', 'اختيار نوع المنتجات - Vetora')

@section('content')
<div class="bg-white py-10 dark:bg-gray-950 sm:py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 text-center">
            <span class="inline-flex rounded-full bg-brand-50 px-4 py-1.5 text-xs font-black text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">تفضيل التصفح</span>
            <h1 class="mt-4 text-3xl font-black text-gray-900 dark:text-white">اختر نوع المنتجات</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">سيتم عرض التصنيفات والمنتجات المناسبة لاختيارك فقط. يمكنك تعديل الاختيار لاحقاً من الملف الشخصي.</p>
        </div>

        @if(session('error'))
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('product-type.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            @foreach($types as $value => $type)
                <label class="group cursor-pointer rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:border-gray-800 dark:bg-gray-900 dark:has-[:checked]:bg-brand-500/10">
                    <input type="radio" name="preferred_product_type" value="{{ $value }}" class="sr-only" @checked(old('preferred_product_type') === $value)>
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                            <i class="{{ $type['icon'] }} text-2xl" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-gray-900 dark:text-white">{{ $type['label'] }}</h2>
                            <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $type['description'] }}</p>
                        </div>
                    </div>
                </label>
            @endforeach

            @error('preferred_product_type')
                <p class="sm:col-span-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">{{ $message }}</p>
            @enderror

            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="rounded-xl bg-gray-900 px-6 py-3 text-sm font-black text-white transition hover:bg-brand-600 active:scale-[.98] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">
                    متابعة
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
