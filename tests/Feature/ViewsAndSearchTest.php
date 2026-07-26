<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\SavedFilter;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    clearInstallState();
    installApp();

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => Role::Owner->value]);
    $this->board = Board::factory()->create(['workspace_id' => $this->workspace->id, 'visibility' => 'workspace']);
    $this->list = BoardList::factory()->create(['board_id' => $this->board->id]);
});

it('doorzoekt kaarten binnen toegankelijke boards', function (): void {
    Card::factory()->create([
        'board_id' => $this->board->id,
        'list_id' => $this->list->id,
        'title' => 'Betaalintegratie afronden',
    ]);

    $response = $this->actingAs($this->user)->getJson('/search?q=Betaalintegratie');

    $response->assertOk();
    expect($response->json('results'))->toHaveCount(1);
});

it('vindt geen kaarten uit boards zonder toegang', function (): void {
    $otherWorkspace = Workspace::factory()->create();
    $otherBoard = Board::factory()->create(['workspace_id' => $otherWorkspace->id]);
    $otherList = BoardList::factory()->create(['board_id' => $otherBoard->id]);
    Card::factory()->create([
        'board_id' => $otherBoard->id,
        'list_id' => $otherList->id,
        'title' => 'Geheimprojectxyz',
    ]);

    $response = $this->actingAs($this->user)->getJson('/search?q=Geheimprojectxyz');

    expect($response->json('results'))->toHaveCount(0);
});

it('toont Mijn werk met toegewezen en aangemaakte kaarten', function (): void {
    $assigned = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $this->list->id]);
    $assigned->assignees()->attach($this->user->id);
    Card::factory()->create([
        'board_id' => $this->board->id,
        'list_id' => $this->list->id,
        'created_by' => $this->user->id,
    ]);
    // Niet van de gebruiker → mag niet verschijnen.
    Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $this->list->id]);

    $this->actingAs($this->user)
        ->get('/my-work')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MyWork')
            ->has('cards', 2));
});

it('wijst een kaart toe aan een board-lid', function (): void {
    $member = User::factory()->create();
    $this->workspace->members()->attach($member->id, ['role' => Role::Member->value]);
    $card = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $this->list->id]);

    $this->actingAs($this->user)
        ->post("/cards/{$card->id}/assignees", ['user_id' => $member->id])
        ->assertRedirect();

    expect($card->assignees()->count())->toBe(1);
});

it('bewaart een gedeelde weergave en haalt hem op', function (): void {
    $this->actingAs($this->user)
        ->post("/boards/{$this->board->id}/filters", [
            'name' => 'Openstaand',
            'view' => 'table',
            'criteria' => ['label' => 'bug'],
            'shared' => true,
        ])->assertRedirect();

    expect(SavedFilter::where('board_id', $this->board->id)->count())->toBe(1);

    $this->actingAs($this->user)
        ->getJson("/boards/{$this->board->id}/filters")
        ->assertOk()
        ->assertJsonPath('filters.0.name', 'Openstaand');
});
