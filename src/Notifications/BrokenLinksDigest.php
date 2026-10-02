<?php

namespace Statamic\SeoPro\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class BrokenLinksDigest extends Notification
{
    use Queueable;

    public function __construct(public Collection $links) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $count = $this->links->count();

        $message = (new MailMessage)
            ->subject(trans_choice('seo-pro::messages.broken_links_digest_subject', $count, ['count' => $count]))
            ->line(trans_choice('seo-pro::messages.broken_links_digest_intro', $count, ['count' => $count]));

        foreach ($this->links as $link) {
            $message->line($link->url().' — '.$link->response());

            $references = $link->references()
                ->map(fn ($reference) => $reference['title'] ?? $reference['subject_id'])
                ->unique()
                ->implode(', ');

            if ($references) {
                $message->line(__('seo-pro::messages.found_in').': '.$references);
            }
        }

        return $message;
    }
}
