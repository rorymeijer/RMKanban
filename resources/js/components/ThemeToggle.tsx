import { useEffect, useState } from 'react';
import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';

type Theme = 'light' | 'dark';

function currentTheme(): Theme {
    if (typeof document === 'undefined') return 'light';
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

export function ThemeToggle() {
    const { t } = useTranslation();
    const [theme, setTheme] = useState<Theme>(currentTheme);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        try {
            localStorage.setItem('board.theme', theme);
        } catch {
            /* localStorage kan geblokkeerd zijn */
        }
    }, [theme]);

    return (
        <Button
            variant="ghost"
            size="icon"
            aria-label={t('common.theme')}
            onClick={() => setTheme((prev) => (prev === 'dark' ? 'light' : 'dark'))}
        >
            {theme === 'dark' ? <Sun className="size-5" /> : <Moon className="size-5" />}
        </Button>
    );
}
