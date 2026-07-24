<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Services\TrelloImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /**
     * Importeer een Trello JSON-export naar een nieuw board in de workspace.
     */
    public function trello(Request $request, Workspace $workspace, TrelloImporter $importer): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:10240'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $contents = (string) file_get_contents($request->file('file')->getRealPath());
        /** @var array<string, mixed> $data */
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $board = $importer->import($data, $workspace, $user);

        return redirect()->route('boards.show', $board->slug)
            ->with('status', 'Trello-board geïmporteerd.');
    }
}
