import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/layouts/AppLayout';
import { useTranslation } from '@/lib/i18n';
import type { BoardSummary } from '@/types';

interface Workspace {
    id: number;
    name: string;
    role: string;
    boards: BoardSummary[];
}

export default function Dashboard({ workspaces }: { workspaces: Workspace[] }) {
    const { t } = useTranslation();

    return (
        <AppLayout>
            <Head title={t('dashboard.title')} />
            <div className="mx-auto max-w-5xl p-6">
                {workspaces.map((workspace) => (
                    <section key={workspace.id} className="mb-10">
                        <h2 className="mb-4 text-lg font-semibold">{workspace.name}</h2>
                        {workspace.boards.length === 0 ? (
                            <p className="text-sm text-muted-foreground">{t('dashboard.empty')}</p>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {workspace.boards.map((board) => (
                                    <Link
                                        key={board.id}
                                        href={`/boards/${board.slug}`}
                                        className="group rounded-xl border bg-card p-4 shadow-sm transition-shadow hover:shadow-md"
                                    >
                                        <div
                                            className="mb-3 h-2 w-12 rounded-full"
                                            style={{ backgroundColor: board.color ?? 'hsl(var(--primary))' }}
                                        />
                                        <div className="font-medium group-hover:text-primary">{board.name}</div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
