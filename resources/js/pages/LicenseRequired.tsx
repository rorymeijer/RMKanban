import { Head, router } from '@inertiajs/react';
import { KeyRound, LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ThemeToggle } from '@/components/ThemeToggle';

export default function LicenseRequired({ expired }: { expired: boolean }) {
    return (
        <div className="flex min-h-full items-center justify-center bg-muted/40 p-4">
            <Head title="Licentie vereist" />
            <div className="absolute right-4 top-4">
                <ThemeToggle />
            </div>

            <div className="w-full max-w-md rounded-xl border bg-card p-8 text-center shadow-sm">
                <div className="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <KeyRound className="size-6" />
                </div>
                <h1 className="text-xl font-semibold">Licentie vereist</h1>
                <p className="mt-2 text-sm text-muted-foreground">
                    {expired
                        ? 'De licentie van deze installatie is verlopen. Neem contact op met je beheerder om te verlengen.'
                        : 'Deze installatie is nog niet geactiveerd. Neem contact op met je beheerder om een geldige licentiesleutel in te voeren.'}
                </p>

                <Button
                    variant="outline"
                    className="mt-6"
                    onClick={() => router.post('/logout')}
                >
                    <LogOut className="size-4" /> Uitloggen
                </Button>
            </div>
        </div>
    );
}
