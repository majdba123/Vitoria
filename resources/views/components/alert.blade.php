@props([
    'type' => 'error',
    'id' => 'alert-box',
])

@php
    $colors = match($type) {
        'success' => 'border-emerald-500/20 bg-emerald-50/90 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200',
        'error' => 'border-rose-500/20 bg-rose-50/90 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200',
        'warning' => 'border-amber-500/20 bg-amber-50/90 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
        default => 'border-blue-500/20 bg-blue-50/90 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200',
    };

    $icon = match($type) {
        'success' => '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'error' => '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>',
        'warning' => '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>',
        default => '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>',
    };
@endphp

<div id="{{ $id }}"
     class="alert-shell hidden {{ $colors }}"
     role="alert">
    {!! $icon !!}
    <p id="{{ $id }}-message" class="flex-1"></p>
</div>
