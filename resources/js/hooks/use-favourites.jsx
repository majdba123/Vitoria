import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { useAuthUser, useI18n } from '@/hooks/use-i18n';

const FavouritesContext = createContext(null);

export function FavouritesProvider({ children }) {
    const { common } = useI18n();
    const user = useAuthUser();
    const [ids, setIds] = useState(new Set());
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        if (!user) return;
        window.axios.get('/api/favourites/ids', { silent: true }).then((res) => {
            setIds(new Set(res.data?.data ?? []));
            setLoaded(true);
        }).catch(() => {});
    }, [user]);

    const toggle = useCallback((productId) => {
        if (!user) {
            window.location.href = '/login';
            return Promise.resolve();
        }
        return window.axios.post(`/api/favourites/${productId}`, {}, { silent: true }).then((res) => {
            const isFav = res.data?.favourited;
            setIds((prev) => {
                const next = new Set(prev);
                if (isFav) next.add(productId); else next.delete(productId);
                return next;
            });
            window.AppToast?.show(isFav ? (common.added_to_favourites || '') : (common.removed_from_favourites || ''), 'success');
        }).catch(() => {});
    }, [user, common.added_to_favourites, common.removed_from_favourites]);

    return (
        <FavouritesContext.Provider value={{ ids, loaded, toggle }}>
            {children}
        </FavouritesContext.Provider>
    );
}

export function useFavourites() {
    const ctx = useContext(FavouritesContext);
    if (!ctx) throw new Error('useFavourites must be used within a FavouritesProvider');
    return ctx;
}
