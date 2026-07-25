import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import { Check, ChevronLeft, ChevronRight, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ThemeToggle } from '@/components/ThemeToggle';
import { useTranslation } from '@/lib/i18n';

interface Props {
    locales: string[];
    defaultLocale: string;
    timezones: string[];
    defaultTimezone: string;
    version: string;
    licensing: boolean;
}

type StepKey = 'app' | 'admin' | 'license' | 'smtp';

export default function Wizard({ locales, defaultLocale, timezones, defaultTimezone, version, licensing }: Props) {
    const { t } = useTranslation();
    const [step, setStep] = useState(0);

    const STEPS: StepKey[] = ['app', 'admin', ...(licensing ? (['license'] as StepKey[]) : []), 'smtp'];

    const form = useForm({
        app_name: 'Board',
        locale: defaultLocale,
        timezone: defaultTimezone,
        admin_name: '',
        admin_username: '',
        admin_email: '',
        admin_password: '',
        admin_password_confirmation: '',
        license_key: '',
    });

    const stepKey = STEPS[step];

    function next() {
        setStep((s) => Math.min(s + 1, STEPS.length - 1));
    }
    function back() {
        setStep((s) => Math.max(s - 1, 0));
    }

    function submit() {
        form.post('/install');
    }

    return (
        <div className="flex min-h-full items-center justify-center bg-muted/40 p-4">
            <Head title={t('install.title')} />
            <div className="absolute right-4 top-4">
                <ThemeToggle />
            </div>

            <div className="w-full max-w-lg">
                <div className="mb-8 text-center">
                    <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-xl bg-primary text-primary-foreground text-xl font-bold">
                        B
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight">{t('install.title')}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">{t('app.tagline')}</p>
                </div>

                {/* Stap-indicator */}
                <ol className="mb-6 flex items-center justify-center gap-2" aria-label="Voortgang">
                    {STEPS.map((key, i) => (
                        <li key={key} className="flex items-center gap-2">
                            <span
                                className={`flex size-8 items-center justify-center rounded-full text-sm font-medium transition-colors ${
                                    i < step
                                        ? 'bg-primary text-primary-foreground'
                                        : i === step
                                          ? 'bg-primary/15 text-primary ring-2 ring-primary'
                                          : 'bg-muted text-muted-foreground'
                                }`}
                                aria-current={i === step ? 'step' : undefined}
                            >
                                {i < step ? <Check className="size-4" /> : i + 1}
                            </span>
                            {i < STEPS.length - 1 && <span className="h-px w-6 bg-border" />}
                        </li>
                    ))}
                </ol>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-medium">{t(`install.step.${stepKey}`)}</h2>

                    <AnimatePresence mode="wait">
                        <motion.div
                            key={stepKey}
                            initial={{ opacity: 0, x: 8 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -8 }}
                            transition={{ duration: 0.15 }}
                        >
                            {stepKey === 'app' && (
                                <div className="space-y-4">
                                    <Field label={t('install.app.name')} error={form.errors.app_name}>
                                        <Input
                                            value={form.data.app_name}
                                            onChange={(e) => form.setData('app_name', e.target.value)}
                                        />
                                    </Field>
                                    <Field label={t('install.app.locale')} error={form.errors.locale}>
                                        <select
                                            className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                            value={form.data.locale}
                                            onChange={(e) => form.setData('locale', e.target.value)}
                                        >
                                            {locales.map((l) => (
                                                <option key={l} value={l}>
                                                    {l === 'nl' ? 'Nederlands' : 'English'}
                                                </option>
                                            ))}
                                        </select>
                                    </Field>
                                    <Field label={t('install.app.timezone')} error={form.errors.timezone}>
                                        <select
                                            className="flex h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                            value={form.data.timezone}
                                            onChange={(e) => form.setData('timezone', e.target.value)}
                                        >
                                            {timezones.map((tz) => (
                                                <option key={tz} value={tz}>
                                                    {tz}
                                                </option>
                                            ))}
                                        </select>
                                    </Field>
                                </div>
                            )}

                            {stepKey === 'admin' && (
                                <div className="space-y-4">
                                    <Field label={t('install.admin.name')} error={form.errors.admin_name}>
                                        <Input
                                            value={form.data.admin_name}
                                            autoComplete="name"
                                            onChange={(e) => form.setData('admin_name', e.target.value)}
                                        />
                                    </Field>
                                    <Field label={t('install.admin.username')} error={form.errors.admin_username}>
                                        <Input
                                            value={form.data.admin_username}
                                            autoComplete="username"
                                            onChange={(e) => form.setData('admin_username', e.target.value)}
                                        />
                                    </Field>
                                    <Field label={t('install.admin.email')} error={form.errors.admin_email}>
                                        <Input
                                            type="email"
                                            value={form.data.admin_email}
                                            autoComplete="email"
                                            onChange={(e) => form.setData('admin_email', e.target.value)}
                                        />
                                    </Field>
                                    <Field label={t('install.admin.password')} error={form.errors.admin_password}>
                                        <Input
                                            type="password"
                                            value={form.data.admin_password}
                                            autoComplete="new-password"
                                            onChange={(e) => form.setData('admin_password', e.target.value)}
                                        />
                                    </Field>
                                    <Field label={t('install.admin.password_confirmation')}>
                                        <Input
                                            type="password"
                                            value={form.data.admin_password_confirmation}
                                            autoComplete="new-password"
                                            onChange={(e) =>
                                                form.setData('admin_password_confirmation', e.target.value)
                                            }
                                        />
                                    </Field>
                                </div>
                            )}

                            {stepKey === 'license' && (
                                <div className="space-y-4">
                                    <p className="text-sm text-muted-foreground">{t('install.license.hint')}</p>
                                    <Field label={t('install.license.key')} error={form.errors.license_key}>
                                        <textarea
                                            value={form.data.license_key}
                                            onChange={(e) => form.setData('license_key', e.target.value)}
                                            rows={4}
                                            placeholder={t('install.license.placeholder')}
                                            className="w-full rounded-md border border-input bg-transparent p-2 font-mono text-xs"
                                        />
                                    </Field>
                                    <p className="text-xs text-muted-foreground">{t('install.license.skip_note')}</p>
                                </div>
                            )}

                            {stepKey === 'smtp' && (
                                <div className="space-y-4">
                                    <p className="text-sm text-muted-foreground">{t('install.smtp.hint')}</p>
                                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                        Geen database- of Redis-velden nodig — die zijn al geconfigureerd.
                                    </div>
                                </div>
                            )}
                        </motion.div>
                    </AnimatePresence>

                    <div className="mt-6 flex items-center justify-between">
                        <Button variant="ghost" onClick={back} disabled={step === 0}>
                            <ChevronLeft className="size-4" /> {t('install.back')}
                        </Button>

                        {step < STEPS.length - 1 ? (
                            <Button onClick={next}>
                                {t('install.next')} <ChevronRight className="size-4" />
                            </Button>
                        ) : (
                            <Button onClick={submit} disabled={form.processing}>
                                {form.processing && <Loader2 className="size-4 animate-spin" />}
                                {t('install.finish')}
                            </Button>
                        )}
                    </div>
                </div>

                <p className="mt-4 text-center text-xs text-muted-foreground">Board {version}</p>
            </div>
        </div>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
