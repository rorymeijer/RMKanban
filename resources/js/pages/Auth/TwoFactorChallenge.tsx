import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ThemeToggle } from '@/components/ThemeToggle';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const form = useForm({ code: '', recovery_code: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/two-factor-challenge');
    }

    return (
        <div className="flex min-h-full items-center justify-center bg-muted/40 p-4">
            <Head title="Tweestapsverificatie" />
            <div className="absolute right-4 top-4">
                <ThemeToggle />
            </div>
            <form onSubmit={submit} className="w-full max-w-sm rounded-xl border bg-card p-6 shadow-sm">
                <h1 className="mb-2 text-center text-xl font-semibold">Tweestapsverificatie</h1>
                <p className="mb-6 text-center text-sm text-muted-foreground">
                    {useRecovery
                        ? 'Voer een van je herstelcodes in.'
                        : 'Voer de code uit je authenticator-app in.'}
                </p>

                {useRecovery ? (
                    <div className="space-y-1.5">
                        <Label htmlFor="recovery_code">Herstelcode</Label>
                        <Input
                            id="recovery_code"
                            autoComplete="one-time-code"
                            value={form.data.recovery_code}
                            onChange={(e) => form.setData('recovery_code', e.target.value)}
                        />
                    </div>
                ) : (
                    <div className="space-y-1.5">
                        <Label htmlFor="code">Code</Label>
                        <Input
                            id="code"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            autoFocus
                            value={form.data.code}
                            onChange={(e) => form.setData('code', e.target.value)}
                        />
                    </div>
                )}
                {form.errors.code && <p className="mt-2 text-sm text-destructive">{form.errors.code}</p>}

                <Button type="submit" className="mt-4 w-full" disabled={form.processing}>
                    Verifiëren
                </Button>
                <button
                    type="button"
                    className="mt-4 w-full text-center text-sm text-muted-foreground hover:text-foreground"
                    onClick={() => setUseRecovery((v) => !v)}
                >
                    {useRecovery ? 'Gebruik een app-code' : 'Gebruik een herstelcode'}
                </button>
            </form>
        </div>
    );
}
