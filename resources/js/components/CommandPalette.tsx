import { useEffect, useState } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { router } from '@inertiajs/react';
import { LayoutDashboard, ListChecks, Search } from 'lucide-react';

interface Result {
    id: number;
    title: string;
    board_id: number;
    board_slug: string | null;
}

const NAV = [
    { label: 'Dashboard', href: '/', icon: LayoutDashboard },
    { label: 'Mijn werk', href: '/my-work', icon: ListChecks },
];

export function CommandPalette() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Result[]>([]);

    // ⌘K / Ctrl+K opent het palet; "?" en Escape sluiten.
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setOpen((v) => !v);
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    useEffect(() => {
        if (!open || query.trim() === '') {
            setResults([]);
            return;
        }
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                const res = await fetch(`/search?q=${encodeURIComponent(query)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (res.ok) setResults((await res.json()).results ?? []);
            } catch {
                /* afgebroken */
            }
        }, 200);
        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [query, open]);

    const go = (href: string) => {
        setOpen(false);
        setQuery('');
        router.visit(href);
    };

    const filteredNav = NAV.filter((n) => n.label.toLowerCase().includes(query.toLowerCase()));

    return (
        <Dialog.Root open={open} onOpenChange={setOpen}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm" />
                <Dialog.Content className="fixed left-1/2 top-24 z-50 w-[95vw] max-w-lg -translate-x-1/2 overflow-hidden rounded-xl border bg-card shadow-lg focus:outline-none">
                    <Dialog.Title className="sr-only">Command palette</Dialog.Title>
                    <div className="flex items-center gap-2 border-b px-4">
                        <Search className="size-4 text-muted-foreground" />
                        <input
                            autoFocus
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Zoek kaarten of navigeer…"
                            className="h-12 w-full bg-transparent text-sm focus:outline-none"
                        />
                    </div>
                    <ul className="max-h-80 overflow-y-auto p-2 text-sm">
                        {filteredNav.map((n) => (
                            <li key={n.href}>
                                <button
                                    onClick={() => go(n.href)}
                                    className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left hover:bg-muted"
                                >
                                    <n.icon className="size-4 text-muted-foreground" /> {n.label}
                                </button>
                            </li>
                        ))}
                        {results.map((r) => (
                            <li key={r.id}>
                                <button
                                    onClick={() => r.board_slug && go(`/boards/${r.board_slug}`)}
                                    className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left hover:bg-muted"
                                >
                                    <Search className="size-4 text-muted-foreground" /> {r.title}
                                </button>
                            </li>
                        ))}
                        {query && filteredNav.length === 0 && results.length === 0 && (
                            <li className="px-3 py-2 text-muted-foreground">Geen resultaten.</li>
                        )}
                    </ul>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
