import { useEffect } from 'react';

const FOCUSABLE_SELECTOR = '[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

/**
 * Shared focus-management behaviour for modal/dropdown-style panels:
 * - closes the panel on Escape (via onEscape)
 * - traps Tab/Shift+Tab focus cycling within the panel's own focusable elements
 *
 * `panelRef` should point to the DOM node that contains the panel's focusable
 * content. `active` gates the listeners so they only run while the panel is open.
 */
export function useFocusTrap(panelRef, active, onEscape) {
    useEffect(() => {
        if (!active) return undefined;

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                onEscape?.();
                return;
            }

            if (event.key !== 'Tab' || !panelRef.current) return;

            const focusable = Array.from(panelRef.current.querySelectorAll(FOCUSABLE_SELECTOR)).filter(
                (el) => el.offsetParent !== null,
            );
            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [active, panelRef, onEscape]);
}
