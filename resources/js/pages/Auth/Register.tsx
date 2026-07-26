import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ThemeToggle } from '@/components/ThemeToggle';

export default function Register() {
    const form = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/register');
    }

    return (
        <div className="flex min-h-full items-center justify-center bg-muted/40 p-4">
            <Head title="Registreren" />
            <div className="absolute right-4 top-4">
                <ThemeToggle />
            </div>
            <form onSubmit={submit} className="w-full max-w-sm rounded-xl border bg-card p-6 shadow-sm">
                <h1 className="mb-6 text-center text-xl font-semibold">Account aanmaken</h1>
                <div className="space-y-4">
                    {(
                        [
                            ['name', 'Naam', 'text', 'name'],
                            ['username', 'Gebruikersnaam', 'text', 'username'],
                            ['email', 'E-mailadres', 'email', 'email'],
                            ['password', 'Wachtwoord', 'password', 'new-password'],
                            ['password_confirmation', 'Wachtwoord bevestigen', 'password', 'new-password'],
                        ] as const
                    ).map(([field, label, type, autoComplete]) => (
                        <div key={field} className="space-y-1.5">
                            <Label htmlFor={field}>{label}</Label>
                            <Input
                                id={field}
                                type={type}
                                autoComplete={autoComplete}
                                value={form.data[field]}
                                onChange={(e) => form.setData(field, e.target.value)}
                            />
                            {form.errors[field] && (
                                <p className="text-sm text-destructive">{form.errors[field]}</p>
                            )}
                        </div>
                    ))}
                    <Button type="submit" className="w-full" disabled={form.processing}>
                        Registreren
                    </Button>
                </div>
            </form>
        </div>
    );
}
