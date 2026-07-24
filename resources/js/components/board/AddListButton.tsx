import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/lib/i18n';

export function AddListButton({ boardId }: { boardId: number }) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);
    const [name, setName] = useState('');

    function submit() {
        const value = name.trim();
        if (value) {
            router.post(`/boards/${boardId}/lists`, { name: value }, { preserveScroll: true });
            setName('');
        }
        setAdding(false);
    }

    return (
        <div className="w-72 shrink-0">
            {adding ? (
                <Input
                    autoFocus
                    value={name}
                    placeholder="Naam van de lijst…"
                    onChange={(e) => setName(e.target.value)}
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
                    className="flex w-full items-center gap-1.5 rounded-xl border border-dashed px-3 py-2.5 text-left text-sm text-muted-foreground hover:bg-muted"
                >
                    <Plus className="size-4" aria-hidden /> {t('board.add_list')}
                </button>
            )}
        </div>
    );
}
