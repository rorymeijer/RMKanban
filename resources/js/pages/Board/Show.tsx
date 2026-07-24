import { Head } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { AppLayout } from '@/layouts/AppLayout';
import { useTranslation } from '@/lib/i18n';
import type { BoardList, Card } from '@/types';

interface BoardData {
    id: number;
    name: string;
    description: string | null;
    color: string | null;
    lists: BoardList[];
}

export default function BoardShow({ board }: { board: BoardData }) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={board.name} />
            <div className="p-6">
                <div className="mb-6 flex items-center gap-3">
                    <span
                        className="h-6 w-1.5 rounded-full"
                        style={{ backgroundColor: board.color ?? 'hsl(var(--primary))' }}
                    />
                    <h1 className="text-xl font-semibold">{board.name}</h1>
                </div>

                {/* Op mobiel horizontaal scrollen tussen lijsten. */}
                <div className="flex gap-4 overflow-x-auto pb-4">
                    {board.lists.map((list) => {
                        const overLimit = list.wip_limit !== null && list.cards.length > list.wip_limit;
                        return (
                            <div key={list.id} className="w-72 shrink-0">
                                <div className="mb-2 flex items-center justify-between px-1">
                                    <h2 className="text-sm font-medium">
                                        {list.name}
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            {list.cards.length}
                                            {list.wip_limit !== null && ` / ${list.wip_limit}`}
                                        </span>
                                    </h2>
                                    {overLimit && (
                                        <span
                                            className="flex items-center gap-1 text-xs text-destructive"
                                            title={t('board.wip_exceeded')}
                                        >
                                            <AlertTriangle className="size-3.5" />
                                        </span>
                                    )}
                                </div>
                                <div className="space-y-2 rounded-xl bg-muted/50 p-2">
                                    {list.cards.map((card) => (
                                        <CardItem key={card.id} card={card} />
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}

function CardItem({ card }: { card: Card }) {
    return (
        <div className="rounded-lg border bg-card p-3 shadow-sm">
            {card.labels.length > 0 && (
                <div className="mb-2 flex flex-wrap gap-1">
                    {card.labels.map((label) => (
                        <span
                            key={label.id}
                            className="h-1.5 w-8 rounded-full"
                            style={{ backgroundColor: label.color }}
                            title={label.name ?? ''}
                        />
                    ))}
                </div>
            )}
            <div className="text-sm">{card.title}</div>
            {card.due_date && (
                <div className="mt-2 text-xs text-muted-foreground">{card.due_date}</div>
            )}
        </div>
    );
}
