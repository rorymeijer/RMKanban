import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/layouts/AppLayout';
import { useTranslation } from '@/lib/i18n';

interface WorkCard {
    id: number;
    title: string;
    due_date: string | null;
    board: { id: number | null; name: string | null; slug: string | null };
}

export default function MyWork({ cards }: { cards: WorkCard[] }) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={t('dashboard.title')} />
            <div className="mx-auto max-w-3xl p-6">
                <h1 className="mb-6 text-xl font-semibold">{t('dashboard.title')}</h1>

                {cards.length === 0 ? (
                    <p className="text-sm text-muted-foreground">Geen toegewezen of aangemaakte kaarten.</p>
                ) : (
                    <ul className="divide-y rounded-xl border bg-card">
                        {cards.map((card) => (
                            <li key={card.id} className="flex items-center justify-between px-4 py-3">
                                <div>
                                    <div className="text-sm">{card.title}</div>
                                    {card.board.slug && (
                                        <Link
                                            href={`/boards/${card.board.slug}`}
                                            className="text-xs text-muted-foreground hover:text-primary"
                                        >
                                            {card.board.name}
                                        </Link>
                                    )}
                                </div>
                                {card.due_date && (
                                    <span className="text-xs text-muted-foreground">{card.due_date}</span>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
