import { RefreshCw } from 'lucide-react';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Shared status-branching wrapper for public storefront sections that fetch
 * their own data client-side (Categories/Products index, Home's product
 * grids). Mirrors the loading/error/empty/ready contract already used by
 * the Vendor dashboard's InsightPanel/StatCard, minus the card chrome, so
 * pages that render bare grids (not cards) can reuse the same behavior.
 *
 * - loading: renders `loadingSkeleton`
 * - error: renders `errorMessage` plus a retry button (only if `onRetry` is provided)
 * - ready + empty: renders `emptyContent`
 * - ready + not empty: renders `children`
 */
export function DataState({ status, onRetry, loadingSkeleton, isEmpty = false, emptyContent, errorMessage, children }) {
    const { common } = useI18n();

    if (status === 'loading') return loadingSkeleton ?? null;

    if (status === 'error') {
        return (
            <div className="empty-state py-10 sm:py-12">
                {errorMessage}
                {onRetry && (
                    <button type="button" onClick={onRetry} className="mx-auto mt-3 flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                        <RefreshCw className="size-3.5" />
                        {common.refresh ?? 'Retry'}
                    </button>
                )}
            </div>
        );
    }

    if (status === 'ready' && isEmpty) return emptyContent ?? null;

    if (status === 'ready') return children;

    return null;
}
