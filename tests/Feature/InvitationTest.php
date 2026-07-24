<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;

beforeEach(function (): void {
    clearInstallState();
    installApp();
});

it('laat een admin een lid uitnodigen', function (): void {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $workspace->members()->attach($owner->id, ['role' => Role::Owner->value]);

    $this->actingAs($owner)
        ->post("/workspaces/{$workspace->id}/invitations", [
            'email' => 'gast@example.com',
            'role' => Role::Member->value,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('workspace_invitations', [
        'workspace_id' => $workspace->id,
        'email' => 'gast@example.com',
        'role' => Role::Member->value,
    ]);
});

it('verbiedt een gewoon lid om uit te nodigen', function (): void {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $member = User::factory()->create();
    $workspace->members()->attach($member->id, ['role' => Role::Member->value]);

    $this->actingAs($member)
        ->post("/workspaces/{$workspace->id}/invitations", [
            'email' => 'gast@example.com',
            'role' => Role::Member->value,
        ])
        ->assertForbidden();
});

it('laat de uitgenodigde de uitnodiging accepteren', function (): void {
    $workspace = Workspace::factory()->create();
    $invitee = User::factory()->create(['email' => 'gast@example.com']);

    $invitation = WorkspaceInvitation::create([
        'workspace_id' => $workspace->id,
        'email' => 'gast@example.com',
        'role' => Role::Member->value,
        'token' => 'test-token-123',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($invitee)
        ->get("/invitations/{$invitation->token}/accept")
        ->assertRedirect(route('dashboard'));

    expect($workspace->fresh()->roleFor($invitee))->toBe(Role::Member);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

it('weigert een uitnodiging voor een ander e-mailadres', function (): void {
    $workspace = Workspace::factory()->create();
    $other = User::factory()->create(['email' => 'iemand-anders@example.com']);

    $invitation = WorkspaceInvitation::create([
        'workspace_id' => $workspace->id,
        'email' => 'gast@example.com',
        'role' => Role::Member->value,
        'token' => 'test-token-456',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($other)
        ->get("/invitations/{$invitation->token}/accept")
        ->assertForbidden();
});

it('beschermt het adminpaneel', function (): void {
    $admin = User::factory()->admin()->create();
    $regular = User::factory()->create();

    $this->actingAs($regular)->get('/admin')->assertForbidden();
    $this->actingAs($admin)->get('/admin')->assertOk();
});
