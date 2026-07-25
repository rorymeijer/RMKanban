<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use App\Services\License\LicenseService;
use Database\Seeders\DemoBoardSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Voert de eerste installatie uit: beheerder + workspace + demo-board, en zet de
 * installatievlag. Beschermd tegen races zodat niet twee mensen tegelijk een
 * beheerder aanmaken.
 */
class InstallService
{
    public function __construct(private readonly LicenseService $license) {}

    /**
     * @param  array{
     *     app_name: string,
     *     locale: string,
     *     timezone: string,
     *     admin_name: string,
     *     admin_username: string,
     *     admin_email: string,
     *     admin_password: string,
     *     license_key?: string|null
     * }  $data
     */
    public function install(array $data): User
    {
        if (Setting::isInstalled()) {
            throw new RuntimeException('De applicatie is al geïnstalleerd.');
        }

        // Atomic lock: slechts één installatie tegelijk. De unieke constraints op
        // users + de installatievlag vangen de rest af.
        $lock = Cache::lock('board:install', 15);

        if (! $lock->get()) {
            throw new RuntimeException('Er wordt al een installatie uitgevoerd.');
        }

        try {
            // Verse controle binnen de lock (race-conditie): kijk direct in de db.
            $alreadyInstalled = Setting::query()
                ->where('key', Setting::INSTALLED_KEY)
                ->exists();

            if ($alreadyInstalled) {
                throw new RuntimeException('De applicatie is al geïnstalleerd.');
            }

            $admin = DB::transaction(function () use ($data): User {
                $admin = User::create([
                    'name' => $data['admin_name'],
                    'username' => $data['admin_username'],
                    'email' => $data['admin_email'],
                    'password' => Hash::make($data['admin_password']),
                    'locale' => $data['locale'],
                    'timezone' => $data['timezone'],
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ]);

                $workspace = Workspace::create([
                    'name' => $data['app_name'],
                    'slug' => $this->uniqueSlug($data['app_name']),
                    'owner_id' => $admin->id,
                ]);

                $workspace->members()->attach($admin->id, ['role' => 'owner']);

                app(DemoBoardSeeder::class)->seed($workspace, $admin);

                Setting::put('app_name', $data['app_name']);
                Setting::put('default_locale', $data['locale']);
                Setting::put('default_timezone', $data['timezone']);
                Setting::put(Setting::INSTALLED_KEY, [
                    'at' => now()->toIso8601String(),
                    'version' => config('board.version'),
                ]);

                // Snelle offline-vlag zodat de installer niet opnieuw verschijnt.
                @file_put_contents(storage_path('app/installed.lock'), (string) now());
                Cache::forget('board.installed');

                return $admin;
            });

            // Optioneel: activeer de meegegeven licentiesleutel (al gevalideerd
            // in het formulier). Bij een probleem blijft de installatie staan en
            // valt de app terug op de community-tier.
            $key = $data['license_key'] ?? null;
            if (is_string($key) && trim($key) !== '') {
                $this->license->activate(trim($key));
            }

            return $admin;
        } finally {
            $lock->release();
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $i = 1;

        while (Workspace::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
