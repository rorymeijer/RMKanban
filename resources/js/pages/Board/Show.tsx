import { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    closestCorners,
    useSensor,
    useSensors,
    type DragEndEvent,
    type DragStartEvent,
} from '@dnd-kit/core';
import { SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { AppLayout } from '@/layouts/AppLayout';
import { useTranslation } from '@/lib/i18n';
import { ListColumn } from '@/components/board/ListColumn';
import { CardItem } from '@/components/board/CardItem';
import { AddListButton } from '@/components/board/AddListButton';
import { CardModal } from '@/components/board/CardModal';
import { TableView } from '@/components/board/views/TableView';
import { CalendarView } from '@/components/board/views/CalendarView';
import { TimelineView } from '@/components/board/views/TimelineView';
import type { BoardList, Card } from '@/types';

type ViewMode = 'board' | 'table' | 'calendar' | 'timeline';
const VIEWS: { key: ViewMode; label: string }[] = [
    { key: 'board', label: 'Bord' },
    { key: 'table', label: 'Tabel' },
    { key: 'calendar', label: 'Kalender' },
    { key: 'timeline', label: 'Tijdlijn' },
];

interface BoardData {
    id: number;
    name: string;
    description: string | null;
    color: string | null;
    lists: BoardList[];
}

export default function BoardShow({ board }: { board: BoardData }) {
    const { t } = useTranslation();
    const [lists, setLists] = useState<BoardList[]>(board.lists);
    const [activeCard, setActiveCard] = useState<Card | null>(null);
    const [openCardId, setOpenCardId] = useState<number | null>(null);
    const [view, setView] = useState<ViewMode>('board');

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const cardIndex = useMemo(() => {
        const map = new Map<number, { listId: number; card: Card }>();
        for (const list of lists) {
            for (const card of list.cards) {
                map.set(card.id, { listId: list.id, card });
            }
        }
        return map;
    }, [lists]);

    function findListId(id: number): number | null {
        if (lists.some((l) => l.id === id)) return id; // dropped on a list container
        return cardIndex.get(id)?.listId ?? null;
    }

    function onDragStart(event: DragStartEvent) {
        const entry = cardIndex.get(Number(event.active.id));
        setActiveCard(entry?.card ?? null);
    }

    function onDragEnd(event: DragEndEvent) {
        setActiveCard(null);
        const { active, over } = event;
        if (!over) return;

        const cardId = Number(active.id);
        const fromListId = cardIndex.get(cardId)?.listId;
        const toListId = findListId(Number(over.id));
        if (fromListId == null || toListId == null) return;

        const next = structuredClone(lists) as BoardList[];
        const from = next.find((l) => l.id === fromListId)!;
        const to = next.find((l) => l.id === toListId)!;
        const movingIdx = from.cards.findIndex((c) => c.id === cardId);
        if (movingIdx === -1) return;
        const [moving] = from.cards.splice(movingIdx, 1);

        // Bepaal doelpositie: vóór de kaart waarop gedropt is, anders achteraan.
        let insertAt = to.cards.length;
        const overIdx = to.cards.findIndex((c) => c.id === Number(over.id));
        if (overIdx !== -1) insertAt = overIdx;
        to.cards.splice(insertAt, 0, moving);

        setLists(next);

        const before = to.cards[insertAt - 1]?.id ?? null;
        const after = to.cards[insertAt + 1]?.id ?? null;

        // Optimistisch: verstuur de verplaatsing zonder de props te herladen.
        router.post(
            `/cards/${cardId}/move`,
            { list_id: toListId, before_id: before, after_id: after },
            { preserveScroll: true, preserveState: true, only: [], onError: () => setLists(board.lists) },
        );
    }

    function addCard(listId: number, title: string) {
        router.post(`/lists/${listId}/cards`, { title }, { preserveScroll: true });
    }

    return (
        <AppLayout>
            <Head title={board.name} />
            <div className="p-6">
                <div className="mb-4 flex flex-wrap items-center gap-3">
                    <span
                        className="h-6 w-1.5 rounded-full"
                        style={{ backgroundColor: board.color ?? 'hsl(var(--primary))' }}
                    />
                    <h1 className="text-xl font-semibold">{board.name}</h1>

                    <div className="ml-auto flex gap-1 rounded-lg border bg-card p-1" role="tablist" aria-label="Weergave">
                        {VIEWS.map((v) => (
                            <button
                                key={v.key}
                                role="tab"
                                aria-selected={view === v.key}
                                onClick={() => setView(v.key)}
                                className={`rounded-md px-3 py-1 text-sm transition ${
                                    view === v.key ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {v.label}
                            </button>
                        ))}
                    </div>
                </div>

                {view === 'board' && (
                    <DndContext
                        sensors={sensors}
                        collisionDetection={closestCorners}
                        onDragStart={onDragStart}
                        onDragEnd={onDragEnd}
                    >
                        <div className="flex gap-4 overflow-x-auto pb-4">
                            {lists.map((list) => (
                                <ListColumn key={list.id} list={list} onAddCard={addCard}>
                                    <SortableContext
                                        items={list.cards.map((c) => c.id)}
                                        strategy={verticalListSortingStrategy}
                                    >
                                        {list.cards.map((card) => (
                                            <CardItem key={card.id} card={card} onOpen={setOpenCardId} />
                                        ))}
                                    </SortableContext>
                                </ListColumn>
                            ))}
                            <AddListButton boardId={board.id} />
                        </div>

                        <DragOverlay>
                            {activeCard ? <CardItem card={activeCard} overlay /> : null}
                        </DragOverlay>
                    </DndContext>
                )}

                {view === 'table' && <TableView lists={lists} onOpen={setOpenCardId} />}
                {view === 'calendar' && <CalendarView lists={lists} onOpen={setOpenCardId} />}
                {view === 'timeline' && <TimelineView lists={lists} onOpen={setOpenCardId} />}

                <p className="sr-only" aria-live="polite">
                    {t('board.add_card')}
                </p>

                {openCardId !== null && (
                    <CardModal cardId={openCardId} onClose={() => setOpenCardId(null)} />
                )}
            </div>
        </AppLayout>
    );
}
