<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Board;
use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use App\Support\LexoRank;
use Illuminate\Support\Str;

/**
 * Importeert een Trello JSON-export naar een nieuw board.
 */
class TrelloImporter
{
    /**
     * @param  array<string, mixed>  $data  De gedecodeerde Trello-export.
     */
    public function import(array $data, Workspace $workspace, User $owner): Board
    {
        $name = (string) ($data['name'] ?? 'Geïmporteerd board');

        $board = $workspace->boards()->create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'visibility' => 'workspace',
            'position' => LexoRank::first(),
            'created_by' => $owner->id,
        ]);
        $board->members()->attach($owner->id, ['role' => 'admin']);

        // Labels.
        $labelMap = [];
        foreach ($data['labels'] ?? [] as $trelloLabel) {
            if (! is_array($trelloLabel)) {
                continue;
            }
            $label = Label::create([
                'board_id' => $board->id,
                'name' => $trelloLabel['name'] ?? null,
                'color' => $this->mapColor((string) ($trelloLabel['color'] ?? 'gray')),
            ]);
            $labelMap[(string) ($trelloLabel['id'] ?? '')] = $label->id;
        }

        // Lijsten (Trello: "lists"), overslaan indien gesloten.
        $listMap = [];
        $listRank = null;
        foreach ($data['lists'] ?? [] as $trelloList) {
            if (! is_array($trelloList) || ($trelloList['closed'] ?? false) === true) {
                continue;
            }
            $listRank = LexoRank::between($listRank, null);
            $list = $board->lists()->create([
                'name' => (string) ($trelloList['name'] ?? 'Lijst'),
                'position' => $listRank,
            ]);
            $listMap[(string) ($trelloList['id'] ?? '')] = $list->id;
        }

        // Kaarten.
        $cardRanks = [];
        foreach ($data['cards'] ?? [] as $trelloCard) {
            if (! is_array($trelloCard) || ($trelloCard['closed'] ?? false) === true) {
                continue;
            }
            $listId = $listMap[(string) ($trelloCard['idList'] ?? '')] ?? null;
            if ($listId === null) {
                continue;
            }
            $cardRanks[$listId] = LexoRank::between($cardRanks[$listId] ?? null, null);

            $card = $board->cards()->create([
                'list_id' => $listId,
                'title' => (string) ($trelloCard['name'] ?? 'Kaart'),
                'description' => $trelloCard['desc'] ?? null,
                'due_date' => isset($trelloCard['due']) ? substr((string) $trelloCard['due'], 0, 10) : null,
                'position' => $cardRanks[$listId],
                'created_by' => $owner->id,
            ]);

            foreach ($trelloCard['idLabels'] ?? [] as $labelId) {
                if (isset($labelMap[(string) $labelId])) {
                    $card->labels()->syncWithoutDetaching([$labelMap[(string) $labelId]]);
                }
            }
        }

        return $board;
    }

    private function mapColor(string $trelloColor): string
    {
        return match ($trelloColor) {
            'red' => '#ef4444',
            'green' => '#22c55e',
            'yellow' => '#eab308',
            'orange' => '#f97316',
            'purple' => '#a855f7',
            'blue' => '#3b82f6',
            default => '#9ca3af',
        };
    }
}
