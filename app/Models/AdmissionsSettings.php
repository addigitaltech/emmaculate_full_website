<?php

namespace App\Models;

use App\Domain\Website\Support\SafePublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AdmissionsSettings extends Model
{
    protected $fillable = ['status', 'intro', 'eligibility', 'process_steps', 'requirements', 'important_dates', 'screening_information', 'image_id', 'cta_label', 'cta_url'];
    protected function casts(): array { return ['process_steps' => 'array', 'requirements' => 'array', 'important_dates' => 'array']; }

    protected static function booted(): void
    {
        static::saving(function (self $settings): void {
            if (! in_array($settings->status, ['unconfirmed', 'closed', 'open', 'coming_soon'], true)) {
                throw ValidationException::withMessages(['status' => 'Choose a valid admissions status.']);
            }
            if (! SafePublicUrl::allows($settings->cta_url)) {
                throw ValidationException::withMessages(['cta_url' => 'Use an internal path or an HTTPS URL without credentials.']);
            }
        });
    }

    public static function current(): self { return static::query()->firstOrCreate([], ['status' => 'closed']); }
    public function image() { return $this->belongsTo(MediaAsset::class, 'image_id'); }
}
