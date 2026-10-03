<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'event', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'safe_metadata', 'created_at'];

    protected function casts(): array
    {
        return ['safe_metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(?User $actor, string $event, ?Model $subject = null, array $metadata = []): self
    {
        $safe = self::redact($metadata);
        return static::query()->create([
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey() === null ? null : (string) $subject->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 500),
            'safe_metadata' => $safe,
            'created_at' => now(),
        ]);
    }

    private static function redact(array $data): array
    {
        $out = [];
        foreach (Arr::dot($data) as $key => $value) {
            if (preg_match('/password|secret|token|authorization|cvv|cvc|pan|card.?number|private.?key/i', (string) $key)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                Arr::set($out, (string) $key, $value);
            }
        }
        return $out;
    }
}
