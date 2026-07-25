<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\CardLink;
use App\Models\Checklist;
use App\Models\CustomField;
use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use App\Support\LexoRank;

beforeEach(function (): void {
    clearInstallState();
    installApp();

    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->members()->attach($this->owner->id, ['role' => Role::Owner->value]);
    $this->board = Board::factory()->create(['workspace_id' => $this->workspace->id, 'visibility' => 'workspace']);
    $this->list = BoardList::factory()->create(['board_id' => $this->board->id]);
    $this->card = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $this->list->id]);
});

it('plaatst een reactie en herkent @mentions', function (): void {
    $mentioned = User::factory()->create(['username' => 'jan']);
    $this->workspace->members()->attach($mentioned->id, ['role' => Role::Member->value]);

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/comments", ['body' => 'Hoi @jan, kijk hier eens naar'])
        ->assertRedirect();

    $comment = $this->card->comments()->first();
    expect($comment)->not->toBeNull();
    expect($comment->mentions)->toContain($mentioned->id);
});

it('berekent checklist-voortgang', function (): void {
    $checklist = Checklist::create([
        'card_id' => $this->card->id,
        'title' => 'Taken',
        'position' => LexoRank::first(),
    ]);

    $this->actingAs($this->owner)->post("/checklists/{$checklist->id}/items", ['content' => 'A']);
    $this->actingAs($this->owner)->post("/checklists/{$checklist->id}/items", ['content' => 'B']);

    expect($checklist->progress())->toBe(0);

    $first = $checklist->items()->first();
    $this->actingAs($this->owner)->post("/checklist-items/{$first->id}/toggle")->assertRedirect();

    expect($checklist->fresh()->progress())->toBe(50);
});

it('koppelt en ontkoppelt een label', function (): void {
    $label = Label::factory()->create(['board_id' => $this->board->id]);

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/labels", ['label_id' => $label->id])
        ->assertRedirect();
    expect($this->card->labels()->count())->toBe(1);

    $this->actingAs($this->owner)
        ->delete("/cards/{$this->card->id}/labels/{$label->id}")
        ->assertRedirect();
    expect($this->card->labels()->count())->toBe(0);
});

it('weigert een label van een ander board', function (): void {
    $otherBoard = Board::factory()->create(['workspace_id' => $this->workspace->id]);
    $label = Label::factory()->create(['board_id' => $otherBoard->id]);

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/labels", ['label_id' => $label->id])
        ->assertStatus(422);
});

it('legt een kaartrelatie en verwijdert die', function (): void {
    $other = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $this->list->id]);

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/links", ['linked_card_id' => $other->id, 'type' => 'blocks'])
        ->assertRedirect();

    $link = CardLink::firstWhere('card_id', $this->card->id);
    expect($link)->not->toBeNull();
    expect($link->type)->toBe('blocks');

    $this->actingAs($this->owner)
        ->delete("/cards/{$this->card->id}/links/{$link->id}")
        ->assertRedirect();
    expect(CardLink::count())->toBe(0);
});

it('verbiedt een relatie van een kaart naar zichzelf', function (): void {
    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/links", ['linked_card_id' => $this->card->id, 'type' => 'related'])
        ->assertStatus(422);
});

it('levert volledige kaartdetails als JSON voor de modal', function (): void {
    Checklist::create(['card_id' => $this->card->id, 'title' => 'Taken', 'position' => LexoRank::first()]);

    $this->actingAs($this->owner)
        ->getJson("/cards/{$this->card->id}/details")
        ->assertOk()
        ->assertJsonStructure([
            'id', 'title', 'description', 'labels', 'assignees',
            'checklists', 'comments', 'links',
            'board' => ['id', 'labels', 'members'],
        ])
        ->assertJsonPath('checklists.0.title', 'Taken');
});

it('definieert een custom field en zet een waarde', function (): void {
    $this->actingAs($this->owner)
        ->post("/boards/{$this->board->id}/custom-fields", ['name' => 'Prioriteit', 'type' => 'number'])
        ->assertRedirect();

    $field = CustomField::firstWhere('name', 'Prioriteit');
    expect($field)->not->toBeNull();

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/custom-fields/{$field->id}", ['value' => 3])
        ->assertRedirect();

    $value = $this->card->customFieldValues()->where('custom_field_id', $field->id)->first();
    expect($value?->value)->toBe(3);
});
