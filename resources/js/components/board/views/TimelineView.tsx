import { useMemo } from 'react';
import type { BoardList } from '@/types';

interface Bar {
    id: number;
    title: string;
    start: number;
    end: number;
}

const DAY = 86_400_000;

export function TimelineView({ lists, onOpen }: { lists: BoardList[]; onOpen: (id: number) => void }) {
    const { bars, min, span } = useMemo(() => {
        const items: Bar[] = [];
        for (const list of lists) {
            for (const card of list.cards) {
                const due = card.due_date;
                if (!due) continue;
                const start = card.start_date ?? due;
                items.push({
                    id: card.id,
                    title: card.title,
                    start: new Date(start + 'T00:00:00').getTime(),
                    end: new Date(due + 'T00:00:00').getTime() + DAY,
                });
            }
        }
        if (items.length === 0) return { bars: [], min: 0, span: 1 };
        const min = Math.min(...items.map((i) => i.start));
        const max = Math.max(...items.map((i) => i.end));
        return { bars: items, min, span: Math.max(max - min, DAY) };
    }, [lists]);

    if (bars.length === 0) {
        return <p className="text-sm text-muted-foreground">Nog geen kaarten met een deadline om op de tijdlijn te tonen.</p>;
    }

    return (
        <div className="space-y-2 rounded-xl border bg-card p-4">
            {bars.map((bar) => {
                const left = ((bar.start - min) / span) * 100;
                const width = Math.max(((bar.end - bar.start) / span) * 100, 4);
                return (
                    <div key={bar.id} className="relative h-8">
                        <div className="absolute inset-y-0 left-0 flex w-40 items-center truncate pr-2 text-sm">
                            {bar.title}
                        </div>
                        <div className="absolute inset-y-1 left-40 right-0">
                            <button
                                onClick={() => onOpen(bar.id)}
                                className="absolute inset-y-0 rounded-md bg-primary/70 hover:bg-primary"
                                style={{ left: `${left}%`, width: `${width}%` }}
                                aria-label={bar.title}
                            />
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
