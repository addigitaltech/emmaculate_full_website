<?php

namespace App\Models;

use App\Domain\Website\Support\SafePublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SchoolSettings extends Model
{
    protected $fillable = ['school_name', 'short_name', 'motto', 'description', 'logo_path', 'favicon_path', 'address', 'phone_primary', 'phone_secondary', 'email', 'whatsapp', 'office_hours', 'social_links', 'theme_tokens', 'verified_stats', 'ca1_max_score', 'ca2_max_score', 'ca3_max_score', 'exam_max_score', 'pass_percentage', 'highlight_fail_grade', 'payment_gate_results', 'result_access_mode', 'student_portal_enabled', 'parent_portal_enabled', 'public_result_check_enabled', 'canonical_base_url', 'global_settings'];

    protected function casts(): array
    {
        return ['social_links' => 'array', 'theme_tokens' => 'array', 'verified_stats' => 'array', 'global_settings' => 'array', 'highlight_fail_grade' => 'boolean', 'payment_gate_results' => 'boolean', 'student_portal_enabled' => 'boolean', 'parent_portal_enabled' => 'boolean', 'public_result_check_enabled' => 'boolean', 'ca1_max_score' => 'integer', 'ca2_max_score' => 'integer', 'ca3_max_score' => 'integer', 'exam_max_score' => 'integer', 'pass_percentage' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $settings): void {
            $total = $settings->ca1_max_score + $settings->ca2_max_score + $settings->ca3_max_score + $settings->exam_max_score;
            if ($total !== 100) {
                throw ValidationException::withMessages(['exam_max_score' => 'Assessment maxima must add up to 100.']);
            }
            if ($settings->pass_percentage < 0 || $settings->pass_percentage > 100) {
                throw ValidationException::withMessages(['pass_percentage' => 'Pass percentage must be between 0 and 100.']);
            }
            foreach ($settings->social_links ?? [] as $network => $url) {
                if (! SafePublicUrl::allows((string) $url)) {
                    throw ValidationException::withMessages(['social_links.'.$network => 'Social links must use HTTPS and cannot contain credentials.']);
                }
            }
            if (filled($settings->canonical_base_url) && ! SafePublicUrl::allows($settings->canonical_base_url)) {
                throw ValidationException::withMessages(['canonical_base_url' => 'Use a valid HTTPS canonical base URL.']);
            }
        });
    }

    public static function current(): self
    {
        return \App\Support\SiteCache::remember('settings', 600, fn () => static::query()->orderBy('id')->first() ?? static::query()->create(['school_name' => 'Emmaculate Academy', 'short_name' => 'Emmaculate Academy', 'motto' => 'Determined to make a difference', 'ca1_max_score' => 40, 'ca2_max_score' => 0, 'ca3_max_score' => 0, 'exam_max_score' => 60, 'pass_percentage' => 40, 'highlight_fail_grade' => true, 'payment_gate_results' => false, 'result_access_mode' => 'portal_only']));
    }
}
