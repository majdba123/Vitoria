@props(['color' => 'brand'])

@php
    $hoverBorder = $color === 'brand' ? 'hover:border-brand-400' : 'hover:border-emerald-400';
    $hoverBg = $color === 'brand' ? 'hover:bg-brand-50/30' : 'hover:bg-emerald-50/30';
    $bgColor = $color === 'brand' ? 'bg-brand-100' : 'bg-emerald-100';
    $textColor = $color === 'brand' ? 'text-brand-600' : 'text-emerald-600';
    $spanColor = $color === 'brand' ? 'text-brand-600' : 'text-emerald-600';
@endphp

{{-- Photos Card --}}
<div class="card">
    <div class="card-body border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">Product Photos</h2>
        <p class="mt-0.5 text-sm text-gray-500">Upload up to 10 images (JPEG, PNG, GIF, WebP · max 5 MB each).</p>
    </div>

    <div class="card-body">
        <p class="form-error" id="photos-error"></p>
        <p class="form-error" id="photos.0-error"></p>

        {{-- Drop Zone --}}
        <div id="drop-zone" class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 px-6 py-10 text-center transition-colors {{ $hoverBorder }} {{ $hoverBg }}">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full {{ $bgColor }} {{ $textColor }} transition-transform group-hover:scale-110">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Drag & drop images here, or <span class="{{ $spanColor }} underline">browse</span></p>
            <p class="mt-1 text-xs text-gray-400">JPEG, PNG, GIF, WebP · Max 5 MB each</p>
            <input type="file" id="photo-input" multiple accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
        </div>

        {{-- Preview Grid --}}
        <div id="photo-preview" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"></div>
        <p id="photo-count" class="mt-2 hidden text-xs text-gray-500"></p>
    </div>
</div>

