<?php

namespace App\Models;

use App\Domain\Backups\BackupArchiveService;
use App\Mail\BackupArchiveMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AcademicTerm extends Model
{
    protected $fillable = ['academic_session_id', 'name', 'sequence', 'is_current', 'starts_on', 'ends_on'];
    protected function casts(): array { return ['sequence' => 'integer', 'is_current' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date']; }

    protected static function booted(): void
    {
        static::saving(function (self $term): void {
            if (! in_array((int) $term->sequence, [1, 2, 3], true)) {
                throw ValidationException::withMessages(['sequence' => 'The source result model supports first, second and third terms.']);
            }
            if ($term->starts_on && $term->ends_on && $term->ends_on->lt($term->starts_on)) {
                throw ValidationException::withMessages(['ends_on' => 'The term end date must be on or after its start date.']);
            }
            if ($term->is_current) {
                $session = AcademicSession::query()->find($term->academic_session_id);
                if (! $session?->is_active) {
                    throw ValidationException::withMessages(['is_current' => 'The current term must belong to the active academic session.']);
                }
                if (static::query()->where('academic_session_id', $term->academic_session_id)->where('is_current', true)->when($term->exists, fn ($query) => $query->whereKeyNot($term->id))->exists()) {
                    throw ValidationException::withMessages(['is_current' => 'Only one term per session may be current.']);
                }
            }
        });

        static::saved(function (self $term): void {
            if (! $term->wasChanged('is_current') || ! $term->is_current) {
                return;
            }

            $schoolEmail = SchoolSettings::query()->value('email');
            if (! filled($schoolEmail)) {
                return;
            }

            $backupTerm = static::query()
                ->where('academic_session_id', $term->academic_session_id)
                ->where('sequence', '<', $term->sequence)
                ->orderByDesc('sequence')
                ->first() ?: $term;
            $service = app(BackupArchiveService::class);
            $resultsPath = $service->resultsZip($backupTerm);
            $peoplePath = $service->peopleZip();

            try {
                Mail::to($schoolEmail)->send(new BackupArchiveMail($backupTerm->session?->name.' — '.$backupTerm->name, $resultsPath, $peoplePath));
            } finally {
                @unlink($resultsPath);
                @unlink($peoplePath);
            }
        });
    }

    public function session() { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function results() { return $this->hasMany(Result::class); }
}
