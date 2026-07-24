<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rollen binnen een workspace/board, van veel naar weinig rechten.
 */
enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';
    case Guest = 'guest';

    /**
     * Hiërarchische rangorde; hoger = meer rechten.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Owner => 50,
            self::Admin => 40,
            self::Member => 30,
            self::Viewer => 20,
            self::Guest => 10,
        };
    }

    public function atLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }

    /**
     * Mag deze rol inhoud aanmaken/bewerken (kaarten, lijsten, reacties)?
     */
    public function canWrite(): bool
    {
        return $this->atLeast(self::Member);
    }

    /**
     * Mag deze rol de workspace/het board beheren (leden, instellingen)?
     */
    public function canManage(): bool
    {
        return $this->atLeast(self::Admin);
    }
}
