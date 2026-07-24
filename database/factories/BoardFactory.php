<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\Workspace;
use App\Support\LexoRank;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    protected $model = Board::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'workspace_id' => Workspace::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'color' => fake()->hexColor(),
            'visibility' => 'workspace',
            'position' => LexoRank::first(),
        ];
    }
}
