<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    /**
     * Exporteer een board als JSON.
     */
    public function json(Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $board->load(['lists.cards.labels', 'labels']);

        return response()->json([
            'board' => [
                'name' => $board->name,
                'description' => $board->description,
                'labels' => $board->labels->map(fn ($l): array => ['name' => $l->name, 'color' => $l->color])->all(),
                'lists' => $board->lists->map(fn (BoardList $list): array => [
                    'name' => $list->name,
                    'cards' => $list->cards->map(fn (Card $card): array => [
                        'title' => $card->title,
                        'description' => $card->description,
                        'due_date' => $card->due_date?->toDateString(),
                        'labels' => $card->labels->pluck('name')->all(),
                    ])->all(),
                ])->all(),
            ],
        ]);
    }

    /**
     * iCal-feed met de deadlines van een board.
     */
    public function ical(Board $board): Response
    {
        $this->authorize('view', $board);

        $cards = $board->cards()->whereNotNull('due_date')->whereNull('archived_at')->get();

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//rmboard//NL'];
        foreach ($cards as $card) {
            $date = $card->due_date?->format('Ymd');
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = "UID:card-{$card->id}@board";
            $lines[] = "DTSTART;VALUE=DATE:{$date}";
            $lines[] = 'SUMMARY:'.$this->escape($card->title);
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"board-{$board->id}.ics\"",
        ]);
    }

    private function escape(string $text): string
    {
        return str_replace([',', ';', "\n"], ['\\,', '\\;', '\\n'], $text);
    }
}
