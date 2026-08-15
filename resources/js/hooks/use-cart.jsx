import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { useI18n } from '@/hooks/use-i18n';

const CartContext = createContext(null);

const emptyState = {
    items: [],
    itemsCount: 0,
    subtotal: 0,
    discount: 0,
    total: 0,
    currency: 'SYP',
    coupon: null,
    loaded: false,
};

export function CartProvider({ children }) {
    const { cart } = useI18n();
    const [state, setState] = useState(emptyState);
    const [isOpen, setIsOpen] = useState(false);
    const [isBusy, setIsBusy] = useState(false);
    const [message, setMessage] = useState(null);
    const triggerRef = useRef(null);

    const applyPayload = useCallback((payload) => {
        if (!payload) return;
        setState({
            items: Array.isArray(payload.items) ? payload.items : [],
            itemsCount: Number(payload.items_count) || 0,
            subtotal: Number(payload.subtotal) || 0,
            discount: Number(payload.discount) || 0,
            total: payload.total !== undefined ? Number(payload.total) : Number(payload.subtotal) || 0,
            currency: payload.currency || 'SYP',
            coupon: payload.coupon || null,
            loaded: true,
        });
    }, []);

    const sync = useCallback(() => {
        return window.axios.get('/api/cart', { silent: true }).then((res) => applyPayload(res.data?.data)).catch(() => {});
    }, [applyPayload]);

    useEffect(() => { sync(); }, [sync]);

    const mutate = useCallback((request, { successMessage = null } = {}) => {
        setIsBusy(true);
        setMessage(null);
        return request()
            .then((response) => {
                applyPayload(response.data?.data);
                (response.data?.notices || []).forEach((notice) => window.AppToast?.show(notice, 'warning'));
                const msg = successMessage ?? response.data?.message;
                if (msg) setMessage({ tone: 'success', text: msg });
                return response.data;
            })
            .catch((error) => {
                const msg = error.response?.data?.message || cart.checkout_failed || '';
                setMessage({ tone: 'error', text: msg });
                window.AppToast?.show(msg, 'error');
                sync();
                throw error;
            })
            .finally(() => setIsBusy(false));
    }, [applyPayload, sync, cart.checkout_failed]);

    const addToCart = useCallback((productId, quantity = 1) => {
        return mutate(() => window.axios.post('/api/cart/items', { product_id: Number(productId), quantity: Math.max(1, Number(quantity) || 1) }, { silent: true }))
            .then(() => window.AppToast?.show(cart.added_to_cart_toast || '', 'success'))
            .catch(() => {});
    }, [mutate, cart.added_to_cart_toast]);

    const updateQty = useCallback((productId, quantity) => {
        return mutate(() => window.axios.patch('/api/cart/items', { product_id: Number(productId), quantity: Math.max(0, Number(quantity) || 0) }, { silent: true })).catch(() => {});
    }, [mutate]);

    const removeFromCart = useCallback((productId) => {
        return mutate(() => window.axios.delete(`/api/cart/items/${Number(productId)}`, { silent: true })).catch(() => {});
    }, [mutate]);

    const applyCoupon = useCallback((code) => {
        return mutate(() => window.axios.post('/api/cart/coupon', { coupon_code: String(code || '').trim() }, { silent: true })).catch(() => {});
    }, [mutate]);

    const clearCart = useCallback(() => {
        return mutate(() => window.axios.delete('/api/cart', { silent: true })).catch(() => {});
    }, [mutate]);

    const openCart = useCallback(() => {
        triggerRef.current = document.activeElement;
        setMessage(null);
        setIsOpen(true);
        sync();
    }, [sync]);

    const closeCart = useCallback(() => {
        setMessage(null);
        setIsOpen(false);
        if (triggerRef.current && typeof triggerRef.current.focus === 'function') {
            triggerRef.current.focus();
        }
        triggerRef.current = null;
    }, []);

    const value = {
        ...state,
        isOpen,
        isBusy,
        message,
        openCart,
        closeCart,
        addToCart,
        updateQty,
        removeFromCart,
        applyCoupon,
        clearCart,
        refreshCart: sync,
    };

    return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() {
    const ctx = useContext(CartContext);
    if (!ctx) throw new Error('useCart must be used within a CartProvider');
    return ctx;
}
