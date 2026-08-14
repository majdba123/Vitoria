import { useEffect, useState } from 'react';
import { Moon, Sun } from 'lucide-react';
import { Button } from '@/Components/ui/button';

/**
 * Reads/writes the same 'sz_theme' localStorage key the Blade workspace
 * layouts use, so the toggle stays in sync when navigating between an
 * Inertia page and a still-Blade one in the same session.
 */
export function ThemeToggle({ label }) {
    const [isDark, setIsDark] = useState(() => typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));

    useEffect(() => {
        document.documentElement.classList.toggle('dark', isDark);
        localStorage.setItem('sz_theme', isDark ? 'dark' : 'light');
    }, [isDark]);

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            aria-label={label}
            title={label}
            onClick={() => setIsDark((value) => !value)}
        >
            {isDark ? <Sun className="size-4" /> : <Moon className="size-4" />}
        </Button>
    );
}
