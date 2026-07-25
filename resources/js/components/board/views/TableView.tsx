import { useMemo, useState } from 'react';
import type { BoardList } from '@/types';

interface Row {
    id: number;
    title: string;
    list: string;
    due_date: string | null;
    labels: { id: number; color: string; name: string | null }[];
}

type SortKey = 'title' | 'list' | 'due_date';

export function TableView({ lists, onOpen }: { lists: BoardList[]; onOpen: (id: number) => void }) {
    const [sort, setSort] = useState<SortKey>('list');
    const [dir, setDir] = useState<1 | -1>(1);

    const rows = useMemo<Row[]>(() => {
        const flat: Row[] = [];
        for (const list of lists) {
            for (const card of list.cards) {
                flat.push({ id: card.id, title: card.title, list: list.name, due_date: card.due_date, labels: card.labels });
            }
        }
        return flat.sort((a, b) => {
            const av = (a[sort] ?? '') as string;
            const bv = (b[sort] ?? '') as string;
            return av.localeCompare(bv) * dir;
        });
    }, [lists, sort, dir]);

    const header = (key: SortKey, label: string) => (
        <th
            className="cursor-pointer px-4 py-2 text-left font-medium hover:text-foreground"
            onClick={() => {
                if (sort === key) setDir((d) => (d === 1 ? -1 : 1));
                else setSort(key);
            }}
        >
            {label} {sort === key ? (dir === 1 ? '↑' : '↓') : ''}
        </th>
    );

    return (
        <div className="overflow-x-auto rounded-xl border bg-card">
            <table className="w-full text-sm">
                <thead className="text-muted-foreground">
                    <tr className="border-b">
                        {header('title', 'Kaart')}
                        {header('list', 'Lijst')}
                        <th className="px-4 py-2 text-left font-medium">Labels</th>
                        {header('due_date', 'Deadline')}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr
                            key={row.id}
                            className="cursor-pointer border-t hover:bg-muted/50"
                            onClick={() => onOpen(row.id)}
                        >
                            <td className="px-4 py-2">{row.title}</td>
                            <td className="px-4 py-2 text-muted-foreground">{row.list}</td>
                            <td className="px-4 py-2">
                                <div className="flex gap-1">
                                    {row.labels.map((l) => (
                                        <span key={l.id} className="h-2 w-6 rounded-full" style={{ backgroundColor: l.color }} />
                                    ))}
                                </div>
                            </td>
                            <td className="px-4 py-2 text-muted-foreground">{row.due_date ?? '—'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
