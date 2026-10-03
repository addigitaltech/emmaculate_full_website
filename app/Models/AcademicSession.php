<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AcademicSession extends Model
{
    protected $fillable = ['name', 'is_active', 'starts_on', 'ends_on'];
    protected function casts(): array { return ['is_active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date']; }

    protected static function booted(): void
    {
        static::saving(function (self $session): void {
            if ($session->starts_on && $session->ends_on && $session->ends_on->lt($session->starts_on)) {
                throw ValidationException::withMessages(['ends_on' => 'The session end date must be on or after its start date.']);
            }
            if ($session->is_active && static::query()->where('is_active', true)->when($session->exists, fn ($query) => $query->whereKeyNot($session->id))->exists()) {
                throw ValidationException::withMessages(['is_active' => 'Only one academic session may be active at a time.']);
            }
        });
    }

    public function terms() { return $this->hasMany(AcademicTerm::class)->orderBy('sequence'); }
    public function results() { return $this->hasMany(Result::class); }
}
