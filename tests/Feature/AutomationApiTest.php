<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\DeliverWebhook;
use App\Models\Automation;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;
use App\Models\User;
use App\Models\Webhook;
use App\Models\Workspace;
use App\Services\TrelloImporter;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    clearInstallState();
    installApp();

    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->members()->attach($this->owner->id, ['role' => Role::Owner->value]);
    $this->board = Board::factory()->create(['workspace_id' => $this->workspace->id, 'visibility' => 'workspace']);
    $this->from = BoardList::factory()->create(['board_id' => $this->board->id]);
    $this->to = BoardList::factory()->create(['board_id' => $this->board->id]);
});

it('voert een automation uit wanneer een kaart wordt verplaatst', function (): void {
    $label = Label::factory()->create(['board_id' => $this->board->id]);
    $automation = Automation::create([
        'board_id' => $this->board->id,
        'name' => 'Label bij verplaatsen',
        'trigger' => 'card_moved',
        'conditions' => ['list_id' => $this->to->id],
        'actions' => [['type' => 'add_label', 'label_id' => $label->id]],
        'active' => true,
    ]);

    $card = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $this->from->id]);

    $this->actingAs($this->owner)
        ->post("/cards/{$card->id}/move", ['list_id' => $this->to->id])
        ->assertRedirect();

    expect($card->fresh()->labels()->count())->toBe(1);
    expect($automation->runs()->where('status', 'success')->count())->toBe(1);
});

it('signeert een webhook met HMAC-SHA256', function (): void {
    $signature = DeliverWebhook::sign('{"a":1}', 'geheim');

    expect($signature)->toBe('sha256='.hash_hmac('sha256', '{"a":1}', 'geheim'));
});

it('dispatcht een webhook-job bij een board-event', function (): void {
    Bus::fake();
    Webhook::create([
        'board_id' => $this->board->id,
        'url' => 'https://voorbeeld.nl/hook',
        'secret' => 'geheim',
        'events' => ['card.moved'],
        'active' => true,
    ]);

    $card = Card::factory()->create(['board_id' => $this->board->id, 'list_id' => $this->from->id]);
    $this->actingAs($this->owner)->post("/cards/{$card->id}/move", ['list_id' => $this->to->id]);

    Bus::assertDispatched(DeliverWebhook::class);
});

it('levert een gesigneerde webhook af', function (): void {
    Http::fake();
    $webhook = Webhook::create([
        'board_id' => $this->board->id,
        'url' => 'https://voorbeeld.nl/hook',
        'secret' => 'geheim',
        'events' => null,
        'active' => true,
    ]);

    (new DeliverWebhook($webhook, 'card.moved', ['card_id' => 1]))->handle();

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('X-Board-Signature')
            && str_starts_with($request->header('X-Board-Signature')[0], 'sha256=');
    });
});

it('geeft de REST API toegang met een token', function (): void {
    Sanctum::actingAs($this->owner, ['*']);

    $this->getJson('/api/v1/boards')
        ->assertOk()
        ->assertJsonPath('data.0.id', $this->board->id);

    $this->getJson("/api/v1/boards/{$this->board->id}/cards")->assertOk();
});

it('weigert de REST API zonder token', function (): void {
    $this->getJson('/api/v1/boards')->assertUnauthorized();
});

it('exporteert een board als JSON', function (): void {
    Card::factory()->create([
        'board_id' => $this->board->id,
        'list_id' => $this->from->id,
        'title' => 'Exporteerbaar',
    ]);

    $this->actingAs($this->owner)
        ->getJson("/boards/{$this->board->id}/export.json")
        ->assertOk()
        ->assertJsonPath('board.name', $this->board->name);
});

it('levert een iCal-feed met deadlines', function (): void {
    Card::factory()->create([
        'board_id' => $this->board->id,
        'list_id' => $this->from->id,
        'title' => 'Deadline kaart',
        'due_date' => '2026-08-01',
    ]);

    $response = $this->actingAs($this->owner)->get("/boards/{$this->board->id}/calendar.ics");
    $response->assertOk();
    expect($response->getContent())->toContain('BEGIN:VEVENT')->toContain('Deadline kaart');
});

it('importeert een Trello-export', function (): void {
    $data = [
        'name' => 'Trello Project',
        'labels' => [['id' => 'l1', 'name' => 'Bug', 'color' => 'red']],
        'lists' => [['id' => 'list1', 'name' => 'Te doen', 'closed' => false]],
        'cards' => [[
            'name' => 'Eerste taak', 'idList' => 'list1', 'closed' => false,
            'desc' => 'Beschrijving', 'idLabels' => ['l1'],
        ]],
    ];

    $board = app(TrelloImporter::class)->import($data, $this->workspace, $this->owner);

    expect($board->name)->toBe('Trello Project');
    expect($board->lists()->count())->toBe(1);
    expect($board->cards()->count())->toBe(1);
    expect($board->cards()->first()->labels()->count())->toBe(1);
});
