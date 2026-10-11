<?php

namespace App\Console\Commands;

use App\Domain\Messaging\Audience;
use App\Mail\SchoolUpdateMail;
use App\Models\NewsPost;
use App\Models\SchoolEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendNewsNotifications extends Command
{
    protected $signature = 'school:send-notifications';
    protected $description = 'Email everyone once about news and events that have just been published.';

    public function handle(): int
    {
        $recipients = null;
        $sent = 0;

        foreach ([[NewsPost::class, 'news.show', 'New from the school'], [SchoolEvent::class, 'events.show', 'Upcoming event']] as [$model, $route, $prefix]) {
            foreach ($model::published()->where('send_email', true)->whereNull('notified_at')->get() as $item) {
                // Mark first: at worst someone misses an email, nobody gets it twice.
                $item->forceFill(['notified_at' => now()])->saveQuietly();
                $recipients ??= Audience::recipients('everyone');
                $body = $item instanceof NewsPost ? ($item->excerpt ?: Str::limit(strip_tags((string) $item->content), 400)) : Str::limit(strip_tags((string) $item->description), 400);
                if ($item instanceof SchoolEvent && $item->starts_at) {
                    $body = $item->starts_at->format('l, j F Y \a\t g:i A').($item->location ? "\n".$item->location : '')."\n\n".$body;
                }
                $imageUrl = ($item instanceof NewsPost ? $item->coverImage : $item->image)?->url();
                foreach ($recipients as $recipient) {
                    Mail::to($recipient['email'])->send(new SchoolUpdateMail(
                        subjectLine: $prefix.': '.$item->title,
                        heading: $item->title,
                        bodyText: $body,
                        url: route($route, $item->slug),
                        buttonLabel: 'Read more',
                        imageUrl: $imageUrl,
                        unsubscribeUrl: $recipient['unsubscribe'],
                    ));
                    $sent++;
                }
            }
        }

        $this->info($sent.' email(s) queued.');

        return self::SUCCESS;
    }
}
