<?php

namespace Statamic\SeoPro\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DeadLinksDigest extends Notification
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
            ->subject(trans_choice(
                '{1} 1 broken link found|[2,*] :count broken links found',
                $count,
                ['count' => $count]
            ))
            ->line(trans_choice(
                '{1} 1 external link is currently broken.|[2,*] :count external links are currently broken.',
                $count,
                ['count' => $count]
            ));

        foreach ($this->links as $link) {
            $message->line($link->url().' — '.($link->statusCode() ?? $link->error() ?? __('seo-pro::messages.unreachable')));

            $references = $link->references()
                ->map(fn ($reference) => $reference['title'] ?? $reference['subject_id'])
                ->implode(', ');

            if ($references) {
                $message->line(__('seo-pro::messages.found_in').': '.$references);
            }
        }

        return $message;
    }
}
