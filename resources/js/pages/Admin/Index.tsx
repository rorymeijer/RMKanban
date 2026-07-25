import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/layouts/AppLayout';

interface Stats {
    users: number;
    workspaces: number;
    boards: number;
    registration_open: boolean;
}

interface RecentUser {
    id: number;
    name: string;
    username: string;
    email: string;
    is_admin: boolean;
    created_at: string | null;
}

export default function AdminIndex({ stats, recentUsers }: { stats: Stats; recentUsers: RecentUser[] }) {
    return (
        <AppLayout>
            <Head title="Beheer" />
            <div className="mx-auto max-w-5xl p-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Beheer</h1>
                    <Link href="/admin/license" className="text-sm text-primary hover:underline">
                        Licentie beheren →
                    </Link>
                </div>

                <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <StatTile label="Gebruikers" value={stats.users} />
                    <StatTile label="Workspaces" value={stats.workspaces} />
                    <StatTile label="Boards" value={stats.boards} />
                </div>

                <p className="mb-6 text-sm text-muted-foreground">
                    Zelfregistratie:{' '}
                    <span className={stats.registration_open ? 'text-primary' : ''}>
                        {stats.registration_open ? 'open' : 'gesloten'}
                    </span>{' '}
                    (instelbaar via <code>REGISTRATION_OPEN</code>).
                </p>

                <div className="rounded-xl border bg-card">
                    <div className="border-b px-4 py-3 text-sm font-medium">Recente gebruikers</div>
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">Naam</th>
                                <th className="px-4 py-2 font-medium">E-mail</th>
                                <th className="px-4 py-2 font-medium">Rol</th>
                                <th className="px-4 py-2 font-medium">Aangemaakt</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentUsers.map((user) => (
                                <tr key={user.id} className="border-t">
                                    <td className="px-4 py-2">{user.name}</td>
                                    <td className="px-4 py-2 text-muted-foreground">{user.email}</td>
                                    <td className="px-4 py-2">{user.is_admin ? 'Beheerder' : 'Lid'}</td>
                                    <td className="px-4 py-2 text-muted-foreground">{user.created_at}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

function StatTile({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl border bg-card p-4">
            <div className="text-2xl font-semibold tabular-nums">{value}</div>
            <div className="text-sm text-muted-foreground">{label}</div>
        </div>
    );
}
