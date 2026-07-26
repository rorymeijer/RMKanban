import { useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { BoardList } from '@/types';

interface DueCard {
    id: number;
    title: string;
    due_date: string;
}

const MONTHS = [
    'januari', 'februari', 'maart', 'april', 'mei', 'juni',
    'juli', 'augustus', 'september', 'oktober', 'november', 'december',
];
const DAYS = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

export function CalendarView({ lists, onOpen }: { lists: BoardList[]; onOpen: (id: number) => void }) {
    const [cursor, setCursor] = useState(() => {
        const first = lists.flatMap((l) => l.cards).find((c) => c.due_date)?.due_date;
        return first ? new Date(first + 'T00:00:00') : new Date(2026, 6, 1);
    });

    const byDate = useMemo(() => {
        const map = new Map<string, DueCard[]>();
        for (const list of lists) {
            for (const card of list.cards) {
                if (!card.due_date) continue;
                const arr = map.get(card.due_date) ?? [];
                arr.push({ id: card.id, title: card.title, due_date: card.due_date });
                map.set(card.due_date, arr);
            }
        }
        return map;
    }, [lists]);

    const year = cursor.getFullYear();
    const month = cursor.getMonth();
    const firstDay = new Date(year, month, 1);
    const startOffset = (firstDay.getDay() + 6) % 7; // maandag = 0
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const cells: (number | null)[] = [];
    for (let i = 0; i < startOffset; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) cells.push(d);

    const iso = (d: number) => `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

    return (
        <div className="rounded-xl border bg-card p-4">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="text-sm font-medium">
                    {MONTHS[month]} {year}
                </h2>
                <div className="flex gap-1">
                    <Button variant="ghost" size="icon" aria-label="Vorige maand" onClick={() => setCursor(new Date(year, month - 1, 1))}>
                        <ChevronLeft className="size-4" />
                    </Button>
                    <Button variant="ghost" size="icon" aria-label="Volgende maand" onClick={() => setCursor(new Date(year, month + 1, 1))}>
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>
            <div className="grid grid-cols-7 gap-px text-xs">
                {DAYS.map((d) => (
                    <div key={d} className="px-2 py-1 text-center text-muted-foreground">{d}</div>
                ))}
                {cells.map((day, i) => (
                    <div key={i} className="min-h-20 rounded-md border p-1">
                        {day && (
                            <>
                                <div className="mb-1 text-right text-muted-foreground">{day}</div>
                                {(byDate.get(iso(day)) ?? []).map((card) => (
                                    <button
                                        key={card.id}
                                        onClick={() => onOpen(card.id)}
                                        className="mb-1 block w-full truncate rounded bg-primary/10 px-1 py-0.5 text-left text-primary hover:bg-primary/20"
                                    >
                                        {card.title}
                                    </button>
                                ))}
                            </>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
