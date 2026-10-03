<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    }

    public function session() { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function results() { return $this->hasMany(Result::class); }
}
