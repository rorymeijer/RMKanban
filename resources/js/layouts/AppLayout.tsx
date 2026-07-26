import type { ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ThemeToggle } from '@/components/ThemeToggle';
import { CommandPalette } from '@/components/CommandPalette';
import { useTranslation } from '@/lib/i18n';
import type { SharedProps } from '@/types';

export function AppLayout({ children }: { children: ReactNode }) {
    const { t } = useTranslation();
    const { props } = usePage<SharedProps>();
    const user = props.auth.user;
    const appName = props.app.name;

    return (
        <div className="flex min-h-full flex-col">
            <CommandPalette />
            <header className="sticky top-0 z-10 flex h-14 items-center justify-between border-b bg-background/80 px-4 backdrop-blur">
                <Link href="/" className="flex items-center gap-2 font-semibold">
                    <span className="flex size-7 items-center justify-center rounded-lg bg-primary text-primary-foreground text-sm font-bold uppercase">
                        {appName.charAt(0)}
                    </span>
                    {appName}
                </Link>
                <div className="flex items-center gap-1">
                    <ThemeToggle />
                    {user && (
                        <>
                            <span className="mx-2 hidden text-sm text-muted-foreground sm:inline">
                                {user.name}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={t('common.logout')}
                                onClick={() => router.post('/logout')}
                            >
                                <LogOut className="size-5" />
                            </Button>
                        </>
                    )}
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t px-4 py-3 text-center text-xs text-muted-foreground">
                {appName} {props.app.version}
            </footer>
        </div>
    );
}
