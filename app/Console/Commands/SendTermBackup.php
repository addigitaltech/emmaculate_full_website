<?php

namespace App\Console\Commands;

use App\Domain\Backups\BackupArchiveService;
use App\Mail\BackupArchiveMail;
use App\Models\AcademicTerm;
use App\Models\SchoolSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTermBackup extends Command
{
    protected $signature = 'school:send-term-backup {--days=7 : Send backups for terms starting within this many days}';
    protected $description = 'Email the previous term results and people backup before an upcoming/current term transition';

    public function handle(): int
    {
        $email = SchoolSettings::query()->value('email');
        if (! filled($email)) {
            $this->warn('No school email is configured; no term backup was sent.');
            return self::SUCCESS;
        }

        $days = max(0, min(30, (int) $this->option('days')));
        $today = now()->startOfDay();
        $cutoff = now()->addDays($days)->endOfDay();
        $terms = AcademicTerm::query()
            ->whereNull('backup_emailed_at')
            ->where(function ($query) use ($today, $cutoff): void {
                $query->where('is_current', true)
                    ->orWhereBetween('starts_on', [$today->toDateString(), $cutoff->toDateString()]);
            })
            ->with('session')
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();

        $sent = 0;
        foreach ($terms as $term) {
            $previous = AcademicTerm::query()
                ->where('academic_session_id', $term->academic_session_id)
                ->where('sequence', '<', $term->sequence)
                ->orderByDesc('sequence')
                ->first() ?: $term;
            $service = app(BackupArchiveService::class);
            $resultsPath = $service->resultsZip($previous);
            $peoplePath = $service->peopleZip();
            try {
                Mail::to($email)->send(new BackupArchiveMail($previous->session?->name.' — '.$previous->name, $resultsPath, $peoplePath));
                $term->forceFill(['backup_emailed_at' => now()])->saveQuietly();
                $sent++;
                $this->info('Sent '.$previous->name.' backup before '.$term->name.'.');
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Could not send backup for '.$term->name.': '.$exception->getMessage());
            } finally {
                @unlink($resultsPath);
                @unlink($peoplePath);
            }
        }

        $this->info($sent.' term backup email(s) sent.');
        return self::SUCCESS;
    }
}
