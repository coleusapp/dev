<?php

namespace Coleus\Notes\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Checklist items coming due (O9.8): one message per run listing them,
 * with a link to the Tasks view.
 */
class TasksDueNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<array{note_id: int, note_title: string, text: string, due: string}>  $items
     */
    public function __construct(public readonly array $items)
    {
        //
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return (array) config('notes.reminders.channels', ['mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(count($this->items) === 1 ? 'A task is due: '.$this->items[0]['text'] : count($this->items).' tasks are due')
            ->line('From your notes:');

        foreach ($this->items as $item) {
            $message->line("• {$item['text']} (due {$item['due']}, in “{$item['note_title']}”)");
        }

        return $message->action('Open Tasks', route('notes.tasks.index', ['state' => 'due']));
    }

    /**
     * For a "database" channel, if the host adds one.
     *
     * @return array{items: list<array{note_id: int, note_title: string, text: string, due: string}>}
     */
    public function toArray(object $notifiable): array
    {
        return ['items' => $this->items];
    }
}
