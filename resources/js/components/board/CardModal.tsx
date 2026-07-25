import { useState } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { router } from '@inertiajs/react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Calendar, Check, MessageSquare, Tag, Users, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { renderMarkdown } from '@/lib/markdown';

interface CardDetails {
    id: number;
    title: string;
    description: string | null;
    start_date: string | null;
    due_date: string | null;
    labels: { id: number; name: string | null; color: string }[];
    assignees: { id: number; name: string; username: string }[];
    checklists: {
        id: number;
        title: string;
        progress: number;
        items: { id: number; content: string; completed: boolean }[];
    }[];
    comments: { id: number; body: string; author: string | null; created_at: string | null }[];
    links: { id: number; type: string; card: { id: number | null; title: string | null } }[];
    board: {
        id: number | null;
        labels: { id: number; name: string | null; color: string }[];
        members: { id: number; name: string; username: string }[];
    };
}

const mutationOpts = (onSuccess: () => void) => ({
    preserveScroll: true,
    preserveState: true,
    only: [] as string[],
    onSuccess,
});

export function CardModal({ cardId, onClose }: { cardId: number; onClose: () => void }) {
    const queryClient = useQueryClient();
    const queryKey = ['card', cardId];

    const { data: card, isLoading } = useQuery<CardDetails>({
        queryKey,
        queryFn: async () => {
            const res = await fetch(`/cards/${cardId}/details`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Kon kaart niet laden');
            return res.json();
        },
    });

    const refetch = () => queryClient.invalidateQueries({ queryKey });

    return (
        <Dialog.Root open onOpenChange={(open) => !open && onClose()}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm" />
                <Dialog.Content className="fixed left-1/2 top-1/2 z-50 grid max-h-[90vh] w-[95vw] max-w-2xl -translate-x-1/2 -translate-y-1/2 gap-4 overflow-y-auto rounded-xl border bg-card p-6 shadow-lg focus:outline-none">
                    <Dialog.Close asChild>
                        <button
                            className="absolute right-4 top-4 rounded-md p-1 text-muted-foreground hover:bg-muted"
                            aria-label="Sluiten"
                        >
                            <X className="size-5" />
                        </button>
                    </Dialog.Close>

                    {isLoading || !card ? (
                        <div className="py-10 text-center text-sm text-muted-foreground">Laden…</div>
                    ) : (
                        <CardBody card={card} refetch={refetch} />
                    )}
                    <Dialog.Title className="sr-only">Kaartdetails</Dialog.Title>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}

function CardBody({ card, refetch }: { card: CardDetails; refetch: () => void }) {
    const [title, setTitle] = useState(card.title);
    const [description, setDescription] = useState(card.description ?? '');
    const [editingDesc, setEditingDesc] = useState(false);
    const [comment, setComment] = useState('');

    const patch = (data: Record<string, string | number | boolean | null>) =>
        router.patch(`/cards/${card.id}`, data, mutationOpts(refetch));

    const labelIds = new Set(card.labels.map((l) => l.id));
    const assigneeIds = new Set(card.assignees.map((a) => a.id));

    return (
        <div className="space-y-6 pr-6">
            <input
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                onBlur={() => title !== card.title && patch({ title })}
                className="w-full bg-transparent text-lg font-semibold focus:outline-none"
                aria-label="Titel"
            />

            {/* Meta: deadline */}
            <div className="flex flex-wrap gap-4 text-sm">
                <label className="flex items-center gap-2">
                    <Calendar className="size-4 text-muted-foreground" />
                    <input
                        type="date"
                        defaultValue={card.due_date ?? ''}
                        onChange={(e) => patch({ due_date: e.target.value || null })}
                        className="rounded-md border border-input bg-transparent px-2 py-1"
                        aria-label="Deadline"
                    />
                </label>
            </div>

            {/* Labels */}
            <section>
                <h3 className="mb-2 flex items-center gap-2 text-sm font-medium">
                    <Tag className="size-4" /> Labels
                </h3>
                <div className="flex flex-wrap gap-2">
                    {card.board.labels.map((label) => {
                        const active = labelIds.has(label.id);
                        return (
                            <button
                                key={label.id}
                                onClick={() =>
                                    active
                                        ? router.delete(`/cards/${card.id}/labels/${label.id}`, mutationOpts(refetch))
                                        : router.post(`/cards/${card.id}/labels`, { label_id: label.id }, mutationOpts(refetch))
                                }
                                className={`rounded-full px-3 py-1 text-xs font-medium transition ${active ? 'text-white' : 'opacity-50'}`}
                                style={{ backgroundColor: label.color }}
                            >
                                {label.name ?? '—'}
                            </button>
                        );
                    })}
                </div>
            </section>

            {/* Assignees */}
            <section>
                <h3 className="mb-2 flex items-center gap-2 text-sm font-medium">
                    <Users className="size-4" /> Toegewezen
                </h3>
                <div className="flex flex-wrap gap-2">
                    {card.board.members.map((member) => {
                        const active = assigneeIds.has(member.id);
                        return (
                            <button
                                key={member.id}
                                onClick={() =>
                                    active
                                        ? router.delete(`/cards/${card.id}/assignees/${member.id}`, mutationOpts(refetch))
                                        : router.post(`/cards/${card.id}/assignees`, { user_id: member.id }, mutationOpts(refetch))
                                }
                                className={`rounded-full border px-3 py-1 text-xs transition ${active ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground'}`}
                            >
                                {member.name}
                            </button>
                        );
                    })}
                </div>
            </section>

            {/* Beschrijving met live preview */}
            <section>
                <h3 className="mb-2 text-sm font-medium">Beschrijving</h3>
                {editingDesc ? (
                    <div className="space-y-2">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <textarea
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                rows={8}
                                className="rounded-md border border-input bg-transparent p-2 text-sm"
                                placeholder="Markdown ondersteund…"
                            />
                            <div
                                className="prose-sm rounded-md border border-dashed p-2 text-sm [&_a]:text-primary [&_code]:rounded [&_code]:bg-muted [&_code]:px-1"
                                dangerouslySetInnerHTML={{ __html: renderMarkdown(description) }}
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button size="sm" onClick={() => { patch({ description }); setEditingDesc(false); }}>
                                Opslaan
                            </Button>
                            <Button size="sm" variant="ghost" onClick={() => setEditingDesc(false)}>
                                Annuleren
                            </Button>
                        </div>
                    </div>
                ) : (
                    <button
                        onClick={() => setEditingDesc(true)}
                        className="w-full rounded-md border border-dashed p-3 text-left text-sm [&_a]:text-primary [&_code]:rounded [&_code]:bg-muted [&_code]:px-1"
                    >
                        {description ? (
                            <span dangerouslySetInnerHTML={{ __html: renderMarkdown(description) }} />
                        ) : (
                            <span className="text-muted-foreground">Klik om een beschrijving toe te voegen…</span>
                        )}
                    </button>
                )}
            </section>

            {/* Checklists */}
            {card.checklists.map((checklist) => (
                <section key={checklist.id}>
                    <div className="mb-1 flex items-center justify-between">
                        <h3 className="text-sm font-medium">{checklist.title}</h3>
                        <span className="text-xs text-muted-foreground">{checklist.progress}%</span>
                    </div>
                    <div className="mb-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div className="h-full bg-primary transition-all" style={{ width: `${checklist.progress}%` }} />
                    </div>
                    <ul className="space-y-1">
                        {checklist.items.map((item) => (
                            <li key={item.id}>
                                <button
                                    onClick={() => router.post(`/checklist-items/${item.id}/toggle`, {}, mutationOpts(refetch))}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <span className={`flex size-4 items-center justify-center rounded border ${item.completed ? 'border-primary bg-primary text-primary-foreground' : ''}`}>
                                        {item.completed && <Check className="size-3" />}
                                    </span>
                                    <span className={item.completed ? 'text-muted-foreground line-through' : ''}>
                                        {item.content}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                    <AddChecklistItem checklistId={checklist.id} onDone={refetch} />
                </section>
            ))}
            <AddChecklist cardId={card.id} onDone={refetch} />

            {/* Reacties */}
            <section>
                <h3 className="mb-2 flex items-center gap-2 text-sm font-medium">
                    <MessageSquare className="size-4" /> Reacties
                </h3>
                <ul className="space-y-3">
                    {card.comments.map((c) => (
                        <li key={c.id} className="rounded-md bg-muted/50 p-3 text-sm">
                            <div className="mb-1 text-xs text-muted-foreground">
                                {c.author ?? 'Systeem'} · {c.created_at}
                            </div>
                            <div dangerouslySetInnerHTML={{ __html: renderMarkdown(c.body) }} />
                        </li>
                    ))}
                </ul>
                <div className="mt-2 flex gap-2">
                    <Input
                        value={comment}
                        onChange={(e) => setComment(e.target.value)}
                        placeholder="Schrijf een reactie… (@gebruiker om te noemen)"
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && comment.trim()) {
                                router.post(`/cards/${card.id}/comments`, { body: comment }, mutationOpts(() => { setComment(''); refetch(); }));
                            }
                        }}
                    />
                </div>
            </section>
        </div>
    );
}

function AddChecklistItem({ checklistId, onDone }: { checklistId: number; onDone: () => void }) {
    const [content, setContent] = useState('');
    return (
        <input
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder="+ Item toevoegen"
            className="mt-2 w-full bg-transparent text-sm text-muted-foreground focus:outline-none"
            onKeyDown={(e) => {
                if (e.key === 'Enter' && content.trim()) {
                    router.post(`/checklists/${checklistId}/items`, { content }, mutationOpts(() => { setContent(''); onDone(); }));
                }
            }}
        />
    );
}

function AddChecklist({ cardId, onDone }: { cardId: number; onDone: () => void }) {
    const [title, setTitle] = useState('');
    return (
        <input
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="+ Checklist toevoegen"
            className="w-full bg-transparent text-sm text-muted-foreground focus:outline-none"
            onKeyDown={(e) => {
                if (e.key === 'Enter' && title.trim()) {
                    router.post(`/cards/${cardId}/checklists`, { title }, mutationOpts(() => { setTitle(''); onDone(); }));
                }
            }}
        />
    );
}
