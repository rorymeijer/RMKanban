<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Support\LexoRank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'list_id' => BoardList::factory(),
            'title' => fake()->sentence(4),
            'position' => LexoRank::first(),
        ];
    }
}
