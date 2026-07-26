import { useState, type ReactNode } from 'react';
import { useDroppable } from '@dnd-kit/core';
import { AlertTriangle, Plus } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/lib/i18n';
import type { BoardList } from '@/types';

interface Props {
    list: BoardList;
    onAddCard: (listId: number, title: string) => void;
    children: ReactNode;
}

export function ListColumn({ list, onAddCard, children }: Props) {
    const { t } = useTranslation();
    const { setNodeRef, isOver } = useDroppable({ id: list.id });
    const [adding, setAdding] = useState(false);
    const [title, setTitle] = useState('');

    const overLimit = list.wip_limit !== null && list.cards.length > list.wip_limit;

    function submit() {
        const value = title.trim();
        if (value) {
            onAddCard(list.id, value);
            setTitle('');
        }
        setAdding(false);
    }

    return (
        <section className="w-72 shrink-0" aria-label={list.name}>
            <div className="mb-2 flex items-center justify-between px-1">
                <h2 className="text-sm font-medium">
                    {list.name}
                    <span className="ml-2 text-xs text-muted-foreground">
                        {list.cards.length}
                        {list.wip_limit !== null && ` / ${list.wip_limit}`}
                    </span>
                </h2>
                {overLimit && (
                    <span className="flex items-center gap-1 text-xs text-destructive" title={t('board.wip_exceeded')}>
                        <AlertTriangle className="size-3.5" aria-hidden />
                        <span className="sr-only">{t('board.wip_exceeded')}</span>
                    </span>
                )}
            </div>

            <div
                ref={setNodeRef}
                className={`min-h-2 space-y-2 rounded-xl p-2 transition-colors ${
                    isOver ? 'bg-primary/10' : 'bg-muted/50'
                }`}
            >
                {children}

                {adding ? (
                    <Input
                        autoFocus
                        value={title}
                        placeholder="Titel van de kaart…"
                        onChange={(e) => setTitle(e.target.value)}
                        onBlur={submit}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') submit();
                            if (e.key === 'Escape') setAdding(false);
                        }}
                    />
                ) : (
                    <button
                        type="button"
                        onClick={() => setAdding(true)}
                        className="flex w-full items-center gap-1.5 rounded-lg px-2 py-1.5 text-left text-sm text-muted-foreground hover:bg-muted"
                    >
                        <Plus className="size-4" aria-hidden /> {t('board.add_card')}
                    </button>
                )}
            </div>
        </section>
    );
}
