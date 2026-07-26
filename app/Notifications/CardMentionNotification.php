<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Card;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificatie wanneer een gebruiker in een reactie genoemd wordt.
 */
class CardMentionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Card $card,
        public Comment $comment,
        public User $author,
    ) {}

    /**
     * Kanalen: altijd in-app (database); e-mail alleen als de gebruiker dat wil.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $prefs = $notifiable instanceof User ? ($notifiable->notification_preferences ?? []) : [];
        if (($prefs['email_mentions'] ?? true) === true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->author->name} noemde je in een reactie")
            ->line("{$this->author->name} noemde je op de kaart \"{$this->card->title}\".")
            ->line($this->comment->body);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'card.mention',
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->board_id,
            'comment_id' => $this->comment->id,
            'author' => $this->author->name,
        ];
    }
}
