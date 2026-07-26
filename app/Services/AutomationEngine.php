<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Automation;
use App\Models\Card;
use App\Models\Label;
use App\Support\LexoRank;
use Throwable;

/**
 * Butler-achtige regel-uitvoerder. Draait de acties van een automation op een
 * kaart en logt elke run.
 */
class AutomationEngine
{
    /**
     * Draai alle actieve automations voor een trigger op een board.
     */
    public function dispatch(string $trigger, Card $card): void
    {
        Automation::query()
            ->where('board_id', $card->board_id)
            ->where('trigger', $trigger)
            ->where('active', true)
            ->get()
            ->each(fn (Automation $automation) => $this->run($automation, $card));
    }

    public function run(Automation $automation, Card $card): void
    {
        if (! $this->conditionsMatch($automation, $card)) {
            return;
        }

        try {
            $applied = [];
            foreach ($automation->actions as $action) {
                $applied[] = $this->applyAction($action, $card);
            }

            $automation->runs()->create([
                'card_id' => $card->id,
                'status' => 'success',
                'result' => $applied,
            ]);
        } catch (Throwable $e) {
            $automation->runs()->create([
                'card_id' => $card->id,
                'status' => 'failed',
                'result' => ['error' => $e->getMessage()],
            ]);
        }
    }

    private function conditionsMatch(Automation $automation, Card $card): bool
    {
        $conditions = $automation->conditions ?? [];

        if (isset($conditions['list_id']) && $card->list_id !== (int) $conditions['list_id']) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>
     */
    private function applyAction(array $action, Card $card): array
    {
        $type = (string) ($action['type'] ?? '');

        return match ($type) {
            'assign' => $this->assign($card, (int) ($action['user_id'] ?? 0)),
            'add_label' => $this->addLabel($card, (int) ($action['label_id'] ?? 0)),
            'move_to_list' => $this->moveToList($card, (int) ($action['list_id'] ?? 0)),
            'archive' => $this->archive($card),
            'comment' => $this->comment($card, (string) ($action['body'] ?? '')),
            default => ['type' => $type, 'skipped' => true],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function assign(Card $card, int $userId): array
    {
        $card->assignees()->syncWithoutDetaching([$userId]);

        return ['type' => 'assign', 'user_id' => $userId];
    }

    /**
     * @return array<string, mixed>
     */
    private function addLabel(Card $card, int $labelId): array
    {
        if (Label::query()->whereKey($labelId)->where('board_id', $card->board_id)->exists()) {
            $card->labels()->syncWithoutDetaching([$labelId]);
        }

        return ['type' => 'add_label', 'label_id' => $labelId];
    }

    /**
     * @return array<string, mixed>
     */
    private function moveToList(Card $card, int $listId): array
    {
        $last = $card->newQuery()->where('list_id', $listId)->orderByDesc('position')->first();
        $card->update(['list_id' => $listId, 'position' => LexoRank::between($last?->position, null)]);

        return ['type' => 'move_to_list', 'list_id' => $listId];
    }

    /**
     * @return array<string, mixed>
     */
    private function archive(Card $card): array
    {
        $card->update(['archived_at' => now()]);

        return ['type' => 'archive'];
    }

    /**
     * @return array<string, mixed>
     */
    private function comment(Card $card, string $body): array
    {
        $card->comments()->create(['user_id' => null, 'body' => $body, 'mentions' => []]);

        return ['type' => 'comment'];
    }
}
