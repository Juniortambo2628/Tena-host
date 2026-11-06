import { useCallback, useEffect, useState } from 'react';

const KEY = 'tenafi-theme';
export const THEMES = ['light', 'dark', 'system'];

const stored = () => {
    try {
        const value = window.localStorage.getItem(KEY);
        return THEMES.includes(value) ? value : 'light';
    } catch {
        return 'light';
    }
};

const prefersDark = () => typeof window !== 'undefined' && window.matchMedia?.('(prefers-color-scheme: dark)').matches;

/**
 * The dashboard theme the user picked on this device: light (the default),
 * dark, or system (follow the device). While the dashboard is open it puts
 * .dark on <html>, so dialogs rendered outside the layout are dark too; the
 * class comes off again on public pages.
 */
export function useTheme() {
    const [theme, setThemeState] = useState(() => (typeof window === 'undefined' ? 'light' : stored()));
    const [systemDark, setSystemDark] = useState(prefersDark);

    useEffect(() => {
        const media = window.matchMedia?.('(prefers-color-scheme: dark)');
        if (!media) return undefined;
        const onChange = (e) => setSystemDark(e.matches);
        media.addEventListener?.('change', onChange);
        return () => media.removeEventListener?.('change', onChange);
    }, []);

    const dark = theme === 'dark' || (theme === 'system' && systemDark);

    useEffect(() => {
        const root = document.documentElement;
        root.classList.toggle('dark', dark);
        return () => root.classList.remove('dark');
    }, [dark]);

    const setTheme = useCallback((next) => {
        setThemeState(next);
        try {
            window.localStorage.setItem(KEY, next);
        } catch {
            // Private mode: the choice lasts until the page is closed.
        }
    }, []);

    return { theme, setTheme, dark };
}
