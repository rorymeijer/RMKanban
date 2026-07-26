<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CardMentionNotification;
use App\Services\ActivityLogger;
use App\Support\MentionParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CommentController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(Request $request, Card $card): RedirectResponse
    {
        $this->authorize('write', $card->board);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        /** @var User $author */
        $author = $request->user();

        $mentionIds = MentionParser::resolve($data['body']);

        $comment = $card->comments()->create([
            'user_id' => $author->id,
            'body' => $data['body'],
            'mentions' => $mentionIds,
        ]);

        $this->activity->log('comment.created', $card, ['comment_id' => $comment->id], $card->board);

        // Notificeer genoemde gebruikers (behalve de auteur zelf).
        $recipients = User::query()
            ->whereIn('id', $mentionIds)
            ->where('id', '!=', $author->id)
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new CardMentionNotification($card, $comment, $author));
        }

        return back();
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $card = $comment->card()->firstOrFail();
        $board = $card->board()->firstOrFail();
        $this->authorize('write', $board);

        // Alleen de auteur of een beheerder mag verwijderen.
        abort_unless(
            $comment->user_id === $user->id || (bool) $board->roleFor($user)?->canManage(),
            403,
        );

        $comment->delete();
        $this->activity->log('comment.deleted', $card, ['comment_id' => $comment->id], $board);

        return back();
    }
}
