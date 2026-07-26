<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Card;
use Illuminate\Console\Command;

/**
 * Verwijdert soft-deleted items definitief na de retentieperiode (30 dagen).
 */
class PruneTrash extends Command
{
    protected $signature = 'board:prune-trash {--days=30}';

    protected $description = 'Verwijder items uit de prullenbak die ouder zijn dan de retentieperiode.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = Card::onlyTrashed()->where('deleted_at', '<', $cutoff)->forceDelete();

        $this->info("Prullenbak opgeschoond: {$count} kaart(en) definitief verwijderd (ouder dan {$days} dagen).");

        return self::SUCCESS;
    }
}
