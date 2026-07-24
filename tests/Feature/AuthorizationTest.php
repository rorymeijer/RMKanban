<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function (): void {
    clearInstallState();
    installApp();
});

function workspaceWithMember(Role $role): array
{
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $workspace->members()->attach($owner->id, ['role' => Role::Owner->value]);

    $member = User::factory()->create();
    $workspace->members()->attach($member->id, ['role' => $role->value]);

    return [$workspace, $member, $owner];
}

it('geeft de juiste workspace-rol terug', function (): void {
    [$workspace, $member] = workspaceWithMember(Role::Member);

    expect($workspace->roleFor($member))->toBe(Role::Member);
    expect($workspace->roleFor(User::factory()->create()))->toBeNull();
});

it('laat een admin de workspace beheren maar een viewer niet', function (): void {
    [$workspace, $viewer] = workspaceWithMember(Role::Viewer);
    [$workspace2, $admin] = workspaceWithMember(Role::Admin);

    expect($viewer->can('update', $workspace))->toBeFalse();
    expect($admin->can('update', $workspace2))->toBeTrue();
});

it('laat alleen de eigenaar de workspace verwijderen', function (): void {
    [$workspace, $admin, $owner] = workspaceWithMember(Role::Admin);

    expect($admin->can('delete', $workspace))->toBeFalse();
    expect($owner->can('delete', $workspace))->toBeTrue();
});

it('laat een globale beheerder alles', function (): void {
    [$workspace] = workspaceWithMember(Role::Member);
    $superadmin = User::factory()->admin()->create();

    expect($superadmin->can('update', $workspace))->toBeTrue();
    expect($superadmin->can('delete', $workspace))->toBeTrue();
});

it('erft board-toegang van de workspace voor niet-privé boards', function (): void {
    [$workspace, $member] = workspaceWithMember(Role::Member);
    $board = Board::factory()->create([
        'workspace_id' => $workspace->id,
        'visibility' => 'workspace',
    ]);

    expect($member->can('view', $board))->toBeTrue();
    expect($member->can('write', $board))->toBeTrue();
    expect($member->can('update', $board))->toBeFalse(); // member != admin
});

it('vereist expliciet lidmaatschap voor privé-boards', function (): void {
    [$workspace, $member] = workspaceWithMember(Role::Member);
    $board = Board::factory()->create([
        'workspace_id' => $workspace->id,
        'visibility' => 'private',
    ]);

    expect($member->can('view', $board))->toBeFalse();

    $board->members()->attach($member->id, ['role' => Role::Member->value]);
    expect($board->fresh()->roleFor($member))->toBe(Role::Member);
});

it('blokkeert een board-route voor niet-leden', function (): void {
    [$workspace] = workspaceWithMember(Role::Member);
    $board = Board::factory()->create(['workspace_id' => $workspace->id, 'visibility' => 'private']);
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get("/boards/{$board->slug}")->assertForbidden();
});
