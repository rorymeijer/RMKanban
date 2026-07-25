<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\License\LicenseService;
use Illuminate\Console\Command;

/**
 * Controleert online op licentie-upgrades of intrekking.
 */
class RefreshLicense extends Command
{
    protected $signature = 'license:refresh';

    protected $description = 'Vernieuw de licentiestatus bij de licentieserver (upgrades/intrekking).';

    public function handle(LicenseService $license): int
    {
        $license->refresh();
        $current = $license->current();

        $this->info("Licentie: {$current->package}".($current->unlicensed ? ' (geen actieve licentie)' : ''));

        return self::SUCCESS;
    }
}
