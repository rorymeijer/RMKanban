import { Head, Link, router } from '@inertiajs/react';
import { RotateCcw, Trash2 } from 'lucide-react';
import { AppLayout } from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';

interface TrashedCard {
    id: number;
    title: string;
    deleted_at: string | null;
}

interface BoardRef {
    id: number;
    name: string;
    slug: string;
}

export default function BoardTrash({ board, cards }: { board: BoardRef; cards: TrashedCard[] }) {
    return (
        <AppLayout>
            <Head title={`Prullenbak — ${board.name}`} />
            <div className="mx-auto max-w-2xl p-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Prullenbak — {board.name}</h1>
                    <Link href={`/boards/${board.slug}`} className="text-sm text-primary hover:underline">
                        Terug naar board
                    </Link>
                </div>

                {cards.length === 0 ? (
                    <p className="text-sm text-muted-foreground">De prullenbak is leeg.</p>
                ) : (
                    <ul className="divide-y rounded-xl border bg-card">
                        {cards.map((card) => (
                            <li key={card.id} className="flex items-center justify-between px-4 py-3">
                                <div>
                                    <div className="text-sm">{card.title}</div>
                                    <div className="text-xs text-muted-foreground">Verwijderd: {card.deleted_at}</div>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.post(`/boards/${board.id}/trash/cards/${card.id}/restore`)
                                        }
                                    >
                                        <RotateCcw className="size-4" /> Herstellen
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(`/boards/${board.id}/trash/cards/${card.id}`)
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <p className="mt-4 text-xs text-muted-foreground">
                    Kaarten in de prullenbak worden na 30 dagen definitief verwijderd.
                </p>
            </div>
        </AppLayout>
    );
}
