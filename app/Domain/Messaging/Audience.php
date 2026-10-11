<?php

namespace App\Domain\Messaging;

use App\Models\AdmissionApplication;
use App\Models\NewsletterSubscriber;
use App\Models\ParentProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/** Works out who should receive a school email. Placeholder addresses (students without email, demo accounts) are skipped. */
final class Audience
{
    public const OPTIONS = [
        'everyone' => 'Everyone with an email (parents, staff, students, subscribers)',
        'parents' => 'All parents',
        'staff' => 'All teachers and staff',
        'students' => 'Students who have an email',
        'subscribers' => 'Newsletter subscribers',
        'applicants' => 'Admission applicants',
    ];

    /** @return array<int, array{email: string, name: string, unsubscribe: ?string}> */
    public static function recipients(string $audience): array
    {
        $rows = [];
        $add = function (?string $email, string $name = '', ?string $unsubscribe = null) use (&$rows): void {
            $email = Str::lower(trim((string) $email));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || self::isPlaceholder($email)) {
                return;
            }
            if (! isset($rows[$email])) {
                $rows[$email] = ['email' => $email, 'name' => $name, 'unsubscribe' => $unsubscribe];
            } elseif ($unsubscribe !== null && $rows[$email]['unsubscribe'] === null) {
                $rows[$email]['unsubscribe'] = $unsubscribe;
            }
        };

        if (in_array($audience, ['everyone', 'subscribers'], true)) {
            foreach (NewsletterSubscriber::query()->where('status', 'subscribed')->get() as $subscriber) {
                $add($subscriber->email, '', URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]));
            }
        }
        if (in_array($audience, ['everyone', 'parents'], true)) {
            foreach (ParentProfile::query()->whereNotNull('email')->get() as $parent) {
                $add($parent->email, (string) $parent->full_name);
            }
            foreach (User::role('Parent')->get() as $user) {
                $add($user->email, $user->name);
            }
        }
        if (in_array($audience, ['everyone', 'staff'], true)) {
            foreach (User::query()->whereHas('roles', fn ($query) => $query->whereNotIn('name', ['Student', 'Parent']))->get() as $user) {
                $add($user->email, $user->name);
            }
            foreach (Teacher::query()->whereNotNull('email')->get() as $teacher) {
                $add($teacher->email, $teacher->first_name.' '.$teacher->last_name);
            }
        }
        if (in_array($audience, ['everyone', 'students'], true)) {
            foreach (User::role('Student')->get() as $user) {
                $add($user->email, $user->name);
            }
        }
        if (in_array($audience, ['everyone', 'applicants'], true)) {
            foreach (AdmissionApplication::query()->whereNotNull('guardian_email')->get() as $application) {
                $add($application->guardian_email, (string) $application->guardian_name);
            }
        }

        return array_values($rows);
    }

    private static function isPlaceholder(string $email): bool
    {
        return str_ends_with($email, '.invalid') || str_ends_with($email, '.test') || str_ends_with($email, '.local') || str_contains($email, '@demo.');
    }
}
