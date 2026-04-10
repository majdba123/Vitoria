@extends('layouts.admin')

@section('title', 'Product Reviews — SyriaZone Admin')
@section('page-title', 'Product Reviews')

@section('content')
<div class="mx-auto max-w-5xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('admin.products.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Products</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <a href="{{ route('admin.products.show', $product->id) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $product->name }}</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900 dark:text-white">Reviews</span>
    </nav>

    <div class="card overflow-hidden border border-gray-200 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="card-body border-b border-gray-100 dark:border-gray-800">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">All reviews for: {{ $product->name }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $reviews->total() }} {{ $reviews->total() === 1 ? 'review' : 'reviews' }} total</p>
        </div>

        @if($reviews->isEmpty())
            <div class="px-4 py-14 text-center">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">No reviews for this product yet.</p>
                <a href="{{ route('admin.products.index') }}" class="btn-secondary btn-sm mt-4 inline-block">Back to Products</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">User</th>
                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Rating</th>
                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Comment</th>
                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Date</th>
                            <th class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($reviews as $review)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $review->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-amber-400">{{ str_repeat('★', (int) $review->rating) }}</span><span class="text-gray-300 dark:text-gray-600">{{ str_repeat('★', 5 - (int) $review->rating) }}</span>
                                </td>
                                <td class="max-w-xs px-4 py-3 text-gray-600 dark:text-gray-400">{{ $review->body ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $review->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('admin.products.reviews.destroy', [$product->id, $review->id]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this review?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Page {{ $reviews->currentPage() }} of {{ $reviews->lastPage() }} · {{ $reviews->total() }} total
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    @if($reviews->onFirstPage())
                        <span class="btn-secondary btn-xs cursor-not-allowed opacity-50">Prev</span>
                    @else
                        <a href="{{ $reviews->previousPageUrl() }}" class="btn-secondary btn-xs">Prev</a>
                    @endif
                    @php $pStart = max(1, $reviews->currentPage() - 2); $pEnd = min($reviews->lastPage(), $reviews->currentPage() + 2); @endphp
                    @foreach($reviews->getUrlRange($pStart, $pEnd) as $page => $url)
                        @if($page == $reviews->currentPage())
                            <span class="btn-primary btn-xs">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn-secondary btn-xs">{{ $page }}</a>
                        @endif
                    @endforeach
                    @if($reviews->hasMorePages())
                        <a href="{{ $reviews->nextPageUrl() }}" class="btn-secondary btn-xs">Next</a>
                    @else
                        <span class="btn-secondary btn-xs cursor-not-allowed opacity-50">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="mt-4 flex gap-2">
        <a href="{{ route('admin.products.show', $product->id) }}" class="btn-secondary btn-sm">View Product</a>
        <a href="{{ route('admin.products.index') }}" class="btn-secondary btn-sm">Back to Products</a>
    </div>
</div>
@endsection
