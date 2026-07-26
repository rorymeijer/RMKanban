<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\LicenseToken;
use Illuminate\Console\Command;

class GenerateKeypair extends Command
{
    protected $signature = 'license:keygen';

    protected $description = 'Genereer een Ed25519-sleutelpaar voor het tekenen/verifiëren van licenties.';

    public function handle(): int
    {
        $keys = LicenseToken::generateKeypair();

        $this->line('');
        $this->info('Zet dit op de licentieserver (.env):');
        $this->line('LICENSE_PRIVATE_KEY='.$keys['private']);
        $this->line('LICENSE_PUBLIC_KEY='.$keys['public']);
        $this->line('');
        $this->info('Zet dit in Board (.env) zodat het licenties kan verifiëren:');
        $this->line('LICENSE_PUBLIC_KEY='.$keys['public']);
        $this->line('');

        return self::SUCCESS;
    }
}
