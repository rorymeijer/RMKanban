import { Head, useForm, router, usePage } from '@inertiajs/react';
import { AppLayout } from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { SharedProps } from '@/types';

interface LicenseInfo {
    package: string;
    unlicensed: boolean;
    expires_at: string | null;
    in_grace: boolean;
    limits: Record<string, number | null>;
    features: string[];
}

const LIMIT_LABELS: Record<string, string> = {
    users: 'Gebruikers',
    workspaces: 'Workspaces',
    boards: 'Boards',
    storage_gb: 'Opslag (GB)',
};

export default function AdminLicense({ license, enforce }: { license: LicenseInfo; enforce: boolean }) {
    const { props } = usePage<SharedProps>();
    const form = useForm({ key: '' });
    const flash = props.flash.status;

    return (
        <AppLayout>
            <Head title="Licentie" />
            <div className="mx-auto max-w-2xl p-6">
                <h1 className="mb-6 text-xl font-semibold">Licentie</h1>

                {flash && <div className="mb-4 rounded-md bg-primary/10 px-4 py-2 text-sm text-primary">{flash}</div>}

                <div className="mb-6 rounded-xl border bg-card p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <div className="text-sm text-muted-foreground">Huidig pakket</div>
                            <div className="text-lg font-semibold">
                                {license.package}
                                {license.unlicensed && ' (geen actieve licentie)'}
                            </div>
                        </div>
                        {license.expires_at && (
                            <div className="text-right text-sm">
                                <div className="text-muted-foreground">Verloopt</div>
                                <div className={license.in_grace ? 'text-destructive' : ''}>
                                    {license.expires_at}
                                    {license.in_grace && ' — respijtperiode'}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {Object.entries(license.limits).map(([key, value]) => (
                            <div key={key} className="rounded-lg bg-muted/50 p-3">
                                <div className="text-lg font-semibold tabular-nums">{value ?? '∞'}</div>
                                <div className="text-xs text-muted-foreground">{LIMIT_LABELS[key] ?? key}</div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4">
                        <div className="mb-1 text-sm text-muted-foreground">Features</div>
                        <div className="flex flex-wrap gap-2">
                            {license.features.length === 0 && <span className="text-sm text-muted-foreground">—</span>}
                            {license.features.map((f) => (
                                <span key={f} className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                                    {f}
                                </span>
                            ))}
                        </div>
                    </div>

                    {!enforce && (
                        <p className="mt-4 text-xs text-muted-foreground">
                            Licentiehandhaving staat uit (<code>LICENSE_ENFORCE=false</code>): alle limieten zijn
                            onbeperkt.
                        </p>
                    )}
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/admin/license');
                    }}
                    className="rounded-xl border bg-card p-5"
                >
                    <Label htmlFor="key">Licentiesleutel invoeren</Label>
                    <textarea
                        id="key"
                        value={form.data.key}
                        onChange={(e) => form.setData('key', e.target.value)}
                        rows={4}
                        placeholder="Plak hier je licentiesleutel…"
                        className="mt-1 w-full rounded-md border border-input bg-transparent p-2 font-mono text-xs"
                    />
                    {form.errors.key && <p className="mt-1 text-sm text-destructive">{form.errors.key}</p>}
                    <div className="mt-3 flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Activeren
                        </Button>
                        <Button type="button" variant="outline" onClick={() => router.post('/admin/license/refresh')}>
                            Online vernieuwen
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
