<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardList;
use App\Support\LexoRank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardList>
 */
class BoardListFactory extends Factory
{
    protected $model = BoardList::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'name' => fake()->randomElement(['Te doen', 'Bezig', 'Klaar', 'Backlog']),
            'position' => LexoRank::first(),
        ];
    }
}
