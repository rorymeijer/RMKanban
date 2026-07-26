import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { cn } from '@/lib/utils';
import type { Card } from '@/types';

export function CardItem({
    card,
    overlay = false,
    onOpen,
}: {
    card: Card;
    overlay?: boolean;
    onOpen?: (id: number) => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: card.id,
        disabled: overlay,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition: transition ?? undefined,
    };

    return (
        <div
            ref={overlay ? undefined : setNodeRef}
            style={overlay ? undefined : style}
            {...(overlay ? {} : attributes)}
            {...(overlay ? {} : listeners)}
            onClick={() => onOpen?.(card.id)}
            className={cn(
                'cursor-grab rounded-lg border bg-card p-3 text-sm shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                isDragging && 'opacity-40',
                overlay && 'rotate-1 shadow-lg',
            )}
        >
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
            <div>{card.title}</div>
            {card.due_date && <div className="mt-2 text-xs text-muted-foreground">{card.due_date}</div>}
        </div>
    );
}
