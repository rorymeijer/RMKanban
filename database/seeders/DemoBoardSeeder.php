<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use App\Support\LexoRank;
use Illuminate\Support\Str;

/**
 * Seedt een demo-board zodat een verse installatie meteen bruikbaar is.
 */
class DemoBoardSeeder
{
    public function seed(Workspace $workspace, User $owner): Board
    {
        $boardName = 'Welkom bij '.$workspace->name;

        $board = Board::create([
            'workspace_id' => $workspace->id,
            'name' => $boardName,
            'slug' => Str::slug($boardName),
            'description' => 'Een voorbeeldbord om mee te starten. Sleep kaarten, '
                .'maak lijsten en pas alles naar wens aan.',
            'color' => '#6366f1',
            'visibility' => 'workspace',
            'position' => LexoRank::first(),
            'created_by' => $owner->id,
        ]);

        $board->members()->attach($owner->id, ['role' => 'admin']);

        $labels = collect([
            ['name' => 'Bug', 'color' => '#ef4444'],
            ['name' => 'Feature', 'color' => '#22c55e'],
            ['name' => 'Belangrijk', 'color' => '#f59e0b'],
        ])->map(fn (array $l): Label => Label::create([...$l, 'board_id' => $board->id]));

        $columns = [
            'Te doen' => [
                'Sleep deze kaart naar "Bezig"',
                'Klik op een kaart om details te openen',
                'Maak een nieuwe lijst met de knop rechts',
            ],
            'Bezig' => [
                'Board installeren 🎉',
            ],
            'Klaar' => [
                'Docker compose gestart',
                'Beheerdersaccount aangemaakt',
            ],
        ];

        $listRank = null;
        foreach ($columns as $listName => $cards) {
            $listRank = LexoRank::between($listRank, null);
            $list = BoardList::create([
                'board_id' => $board->id,
                'name' => $listName,
                'position' => $listRank,
            ]);

            $cardRank = null;
            foreach ($cards as $title) {
                $cardRank = LexoRank::between($cardRank, null);
                $card = Card::create([
                    'board_id' => $board->id,
                    'list_id' => $list->id,
                    'title' => $title,
                    'position' => $cardRank,
                    'created_by' => $owner->id,
                ]);

                if ($listName === 'Klaar') {
                    $card->labels()->attach($labels->firstWhere('name', 'Feature'));
                }
            }
        }

        return $board;
    }
}
