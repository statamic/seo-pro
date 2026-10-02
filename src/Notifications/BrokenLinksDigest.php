<?php

namespace Statamic\SeoPro\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Statamic\SeoPro\BrokenLinks\Reference;

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
            ->subject(trans_choice('seo-pro::messages.broken_links_notification_subject', $count, ['count' => $count]))
            ->line(trans_choice('seo-pro::messages.broken_links_notification_body', $count, ['count' => $count]));

        foreach ($this->links as $link) {
            $message->line($link->url().' — '.$link->response());

            $references = $link->references()
                ->map(fn (Reference $reference) => $reference->title)
                ->unique()
                ->implode(', ');

            if ($references) {
                $message->line(__('seo-pro::messages.found_in').': '.$references);
            }
        }

        return $message->action(__('seo-pro::messages.view_broken_links'), cp_route('seo-pro.broken-links.index'));
    }
}
