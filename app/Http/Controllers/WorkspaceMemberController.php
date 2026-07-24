<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceMemberController extends Controller
{
    /**
     * Nodig een gebruiker uit voor een workspace.
     */
    public function invite(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('manageMembers', $workspace);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::cases()))],
        ]);

        $invitation = WorkspaceInvitation::updateOrCreate(
            ['workspace_id' => $workspace->id, 'email' => $data['email']],
            [
                'role' => $data['role'],
                'token' => Str::random(48),
                'invited_by' => $request->user()?->id,
                'accepted_at' => null,
                'expires_at' => now()->addDays(14),
            ],
        );

        // De daadwerkelijke uitnodigingsmail volgt in Fase 4 (notificaties).
        return back()->with('status', "Uitnodiging aangemaakt voor {$invitation->email}.");
    }

    /**
     * Accepteer een uitnodiging via de token (vereist ingelogde gebruiker).
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $invitation = WorkspaceInvitation::query()->where('token', $token)->firstOrFail();

        abort_unless($invitation->isPending(), 410, 'Deze uitnodiging is niet meer geldig.');
        abort_unless(
            strcasecmp($invitation->email, $user->email) === 0,
            403,
            'Deze uitnodiging is voor een ander e-mailadres.',
        );

        $workspace = $invitation->workspace()->firstOrFail();
        $workspace->members()->syncWithoutDetaching([
            $user->id => ['role' => $invitation->role],
        ]);

        $invitation->forceFill(['accepted_at' => now()])->save();

        return redirect()->route('dashboard')->with('status', 'Je bent lid geworden van de workspace.');
    }

    /**
     * Verwijder een lid uit de workspace.
     */
    public function destroy(Request $request, Workspace $workspace, User $member): RedirectResponse
    {
        $this->authorize('manageMembers', $workspace);

        abort_if($member->id === $workspace->owner_id, 403, 'De eigenaar kan niet verwijderd worden.');

        $workspace->members()->detach($member->id);

        return back()->with('status', 'Lid verwijderd.');
    }
}
