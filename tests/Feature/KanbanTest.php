<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use App\Support\LexoRank;

beforeEach(function (): void {
    clearInstallState();
    installApp();

    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->members()->attach($this->owner->id, ['role' => Role::Owner->value]);

    $this->member = User::factory()->create();
    $this->workspace->members()->attach($this->member->id, ['role' => Role::Member->value]);

    $this->viewer = User::factory()->create();
    $this->workspace->members()->attach($this->viewer->id, ['role' => Role::Viewer->value]);

    $this->board = Board::factory()->create([
        'workspace_id' => $this->workspace->id,
        'visibility' => 'workspace',
    ]);
});

it('laat een lid een lijst en kaart aanmaken en logt de activiteit', function (): void {
    $this->actingAs($this->member)
        ->post("/boards/{$this->board->id}/lists", ['name' => 'Backlog'])
        ->assertRedirect();

    $list = BoardList::firstWhere('name', 'Backlog');
    expect($list)->not->toBeNull();

    $this->actingAs($this->member)
        ->post("/lists/{$list->id}/cards", ['title' => 'Eerste kaart'])
        ->assertRedirect();

    expect($list->cards()->count())->toBe(1);
    $this->assertDatabaseHas('activities', ['action' => 'card.created', 'user_id' => $this->member->id]);
});

it('verbiedt een viewer om een kaart te maken', function (): void {
    $list = BoardList::factory()->create(['board_id' => $this->board->id]);

    $this->actingAs($this->viewer)
        ->post("/lists/{$list->id}/cards", ['title' => 'Mag niet'])
        ->assertForbidden();
});

it('verplaatst een kaart binnen dezelfde lijst tussen twee buren', function (): void {
    $list = BoardList::factory()->create(['board_id' => $this->board->id]);
    [$a, $b, $c] = collect(['A', 'B', 'C'])->map(fn (string $t, int $i) => Card::factory()->create([
        'board_id' => $this->board->id,
        'list_id' => $list->id,
        'title' => $t,
        'position' => LexoRank::initial(3)[$i],
    ]))->all();

    // Verplaats C tussen A en B.
    $this->actingAs($this->member)
        ->post("/cards/{$c->id}/move", [
            'list_id' => $list->id,
            'before_id' => $a->id,
            'after_id' => $b->id,
        ])->assertRedirect();

    $ordered = $list->cards()->orderBy('position')->pluck('title')->all();
    expect($ordered)->toBe(['A', 'C', 'B']);
});

it('verplaatst een kaart naar een andere lijst', function (): void {
    $from = BoardList::factory()->create(['board_id' => $this->board->id]);
    $to = BoardList::factory()->create(['board_id' => $this->board->id]);
    $card = Card::factory()->create([
        'board_id' => $this->board->id,
        'list_id' => $from->id,
        'position' => LexoRank::first(),
    ]);

    $this->actingAs($this->member)
        ->post("/cards/{$card->id}/move", ['list_id' => $to->id])
        ->assertRedirect();

    expect($card->fresh()->list_id)->toBe($to->id);
    $this->assertDatabaseHas('activities', ['action' => 'card.moved']);
});

it('weigert een verplaatsing naar een lijst van een ander board', function (): void {
    $list = BoardList::factory()->create(['board_id' => $this->board->id]);
    $card = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $list->id]);

    $otherBoard = Board::factory()->create(['workspace_id' => $this->workspace->id]);
    $otherList = BoardList::factory()->create(['board_id' => $otherBoard->id]);

    $this->actingAs($this->member)
        ->post("/cards/{$card->id}/move", ['list_id' => $otherList->id])
        ->assertStatus(422);
});

it('archiveert en herstelt een kaart', function (): void {
    $list = BoardList::factory()->create(['board_id' => $this->board->id]);
    $card = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $list->id]);

    $this->actingAs($this->member)->post("/cards/{$card->id}/archive")->assertRedirect();
    expect($card->fresh()->archived_at)->not->toBeNull();

    $this->actingAs($this->member)->post("/cards/{$card->id}/restore")->assertRedirect();
    expect($card->fresh()->archived_at)->toBeNull();
});

it('verplaatst een kaart naar de prullenbak en herstelt hem', function (): void {
    $list = BoardList::factory()->create(['board_id' => $this->board->id]);
    $card = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $list->id]);

    $this->actingAs($this->member)->delete("/cards/{$card->id}")->assertRedirect();
    expect(Card::withTrashed()->find($card->id)->trashed())->toBeTrue();

    $this->actingAs($this->member)
        ->post("/boards/{$this->board->id}/trash/cards/{$card->id}/restore")
        ->assertRedirect();
    expect(Card::find($card->id))->not->toBeNull();
});

it('ruimt de prullenbak op na de retentieperiode', function (): void {
    $list = BoardList::factory()->create(['board_id' => $this->board->id]);
    $old = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $list->id]);
    $recent = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $list->id]);

    $old->delete();
    $old->forceFill(['deleted_at' => now()->subDays(40)])->saveQuietly();
    $recent->delete();

    $this->artisan('board:prune-trash')->assertSuccessful();

    expect(Card::withTrashed()->find($old->id))->toBeNull();          // ouder dan 30 dagen → weg
    expect(Card::withTrashed()->find($recent->id))->not->toBeNull();  // recent → blijft
});

it('maakt een board met standaardlijsten aan', function (): void {
    $this->actingAs($this->owner)
        ->post("/workspaces/{$this->workspace->id}/boards", ['name' => 'Nieuw board'])
        ->assertRedirect();

    $board = Board::firstWhere('name', 'Nieuw board');
    expect($board)->not->toBeNull();
    expect($board->lists()->count())->toBe(3);
});
