<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Events\CardMoved;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\CardMentionNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

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

it('stuurt een notificatie bij een @mention', function (): void {
    Notification::fake();

    $jan = User::factory()->create(['username' => 'jan']);
    $this->workspace->members()->attach($jan->id, ['role' => Role::Member->value]);

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/comments", ['body' => 'Hoi @jan!'])
        ->assertRedirect();

    Notification::assertSentTo($jan, CardMentionNotification::class);
});

it('notificeert de auteur niet bij een zelf-mention', function (): void {
    Notification::fake();
    $this->owner->update(['username' => 'baas']);

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/comments", ['body' => 'Nota voor @baas zelf']);

    Notification::assertNothingSent();
});

it('bewaart de in-app notificatie in de database', function (): void {
    $jan = User::factory()->create(['username' => 'jan']);
    $this->workspace->members()->attach($jan->id, ['role' => Role::Member->value]);

    $this->actingAs($this->owner)->post("/cards/{$this->card->id}/comments", ['body' => 'Ping @jan']);

    expect($jan->notifications()->count())->toBe(1);
    $this->actingAs($jan)->getJson('/notifications')->assertOk()->assertJson(['unread' => 1]);
});

it('zendt een broadcast-event uit bij het verplaatsen van een kaart', function (): void {
    Event::fake([CardMoved::class]);

    $to = BoardList::factory()->create(['board_id' => $this->board->id]);

    $this->actingAs($this->owner)
        ->post("/cards/{$this->card->id}/move", ['list_id' => $to->id])
        ->assertRedirect();

    Event::assertDispatched(CardMoved::class);
});

it('werkt notificatievoorkeuren bij', function (): void {
    $this->actingAs($this->owner)
        ->patch('/user/notification-preferences', ['email_mentions' => false])
        ->assertRedirect();

    expect($this->owner->fresh()->notification_preferences['email_mentions'])->toBeFalse();
});
