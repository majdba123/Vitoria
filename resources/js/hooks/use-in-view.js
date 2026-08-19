import { useEffect, useRef, useState } from 'react';

// Fires once, the first time the element crosses into the viewport, then disconnects -
// a one-shot scroll-entrance trigger rather than a continuous visibility tracker.
export function useInView({ rootMargin = '0px 0px -10% 0px', skip = false } = {}) {
    const ref = useRef(null);
    const [inView, setInView] = useState(skip);

    useEffect(() => {
        if (skip) {
            setInView(true);
            return;
        }

        const el = ref.current;
        if (!el || typeof IntersectionObserver === 'undefined') {
            setInView(true);
            return;
        }

        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                setInView(true);
                observer.disconnect();
            }
        }, { rootMargin });

        observer.observe(el);
        return () => observer.disconnect();
    }, [skip, rootMargin]);

    return [ref, inView];
}
