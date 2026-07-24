<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id', 'name', 'slug', 'description', 'color',
        'visibility', 'position', 'created_by', 'archived_at',
    ];

    protected $casts = ['archived_at' => 'datetime'];

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<BoardList, $this>
     */
    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class)->orderBy('position');
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    /**
     * @return HasMany<Label, $this>
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * De effectieve rol van een gebruiker op dit board: expliciet board-lidmaatschap
     * wint; anders valt hij terug op de workspace-rol (owners/admins zien alles).
     */
    public function roleFor(User $user): ?Role
    {
        $member = $this->members()->where('users.id', $user->id)->first();

        if ($member !== null) {
            $pivot = $member->getRelation('pivot');
            $role = $pivot instanceof Pivot ? (string) $pivot->getAttribute('role') : '';

            return Role::tryFrom($role);
        }

        // Geen expliciet board-lid: erf de workspace-rol, maar alleen voor
        // niet-privé boards (privé vereist expliciet lidmaatschap).
        if ($this->visibility !== 'private') {
            return $this->workspace?->roleFor($user);
        }

        return null;
    }
}
