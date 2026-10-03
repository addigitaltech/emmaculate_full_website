<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AffectiveRating extends Model
{
    protected $fillable = ['student_id', 'trait_id', 'academic_session_id', 'academic_term_id', 'rating'];
    protected function casts(): array { return ['rating' => 'integer']; }

    protected static function booted(): void
    {
        static::saving(function (self $rating): void {
            if ($rating->rating < 1 || $rating->rating > 5) {
                throw ValidationException::withMessages(['rating' => 'Trait ratings must be between 1 and 5.']);
            }
            if (! AcademicTerm::query()->whereKey($rating->academic_term_id)->where('academic_session_id', $rating->academic_session_id)->exists()) {
                throw ValidationException::withMessages(['academic_term_id' => 'The term must belong to the selected session.']);
            }
            if (! AffectiveTrait::query()->whereKey($rating->trait_id)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['trait_id' => 'Choose an active rating trait.']);
            }
            if (Auth::id()) $rating->rated_by = Auth::id();
        });
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function trait() { return $this->belongsTo(AffectiveTrait::class, 'trait_id'); }
    public function session() { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function term() { return $this->belongsTo(AcademicTerm::class, 'academic_term_id'); }
    public function rater() { return $this->belongsTo(User::class, 'rated_by'); }
}
