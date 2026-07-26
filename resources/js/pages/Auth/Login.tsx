import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ThemeToggle } from '@/components/ThemeToggle';
import { useTranslation } from '@/lib/i18n';
import type { SharedProps } from '@/types';

export default function Login({ registrationOpen: _registrationOpen }: { registrationOpen: boolean }) {
    const { t } = useTranslation();
    const appName = usePage<SharedProps>().props.app.name;
    const form = useForm({ login: '', password: '', remember: false });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/login');
    }

    return (
        <div className="flex min-h-full items-center justify-center bg-muted/40 p-4">
            <Head title={t('auth.login.title')} />
            <div className="absolute right-4 top-4">
                <ThemeToggle />
            </div>

            <form onSubmit={submit} className="w-full max-w-sm rounded-xl border bg-card p-6 shadow-sm">
                <div className="mb-6 text-center">
                    <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-xl bg-primary text-primary-foreground text-xl font-bold uppercase">
                        {appName.charAt(0)}
                    </div>
                    <h1 className="text-xl font-semibold">{t('auth.login.title')}</h1>
                </div>

                <div className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="login">{t('auth.login.field')}</Label>
                        <Input
                            id="login"
                            value={form.data.login}
                            autoComplete="username"
                            autoFocus
                            onChange={(e) => form.setData('login', e.target.value)}
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="password">{t('auth.login.password')}</Label>
                        <Input
                            id="password"
                            type="password"
                            value={form.data.password}
                            autoComplete="current-password"
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                    </div>
                    {form.errors.login && <p className="text-sm text-destructive">{form.errors.login}</p>}

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.remember}
                            onChange={(e) => form.setData('remember', e.target.checked)}
                        />
                        {t('auth.login.remember')}
                    </label>

                    <Button type="submit" className="w-full" disabled={form.processing}>
                        {t('auth.login.submit')}
                    </Button>
                </div>
            </form>
        </div>
    );
}
