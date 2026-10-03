<?php

namespace App\Domain\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    public function log(?User $actor, string $event, ?Model $subject = null, array $metadata = []): AuditLog
    {
        return AuditLog::record($actor, $event, $subject, $metadata);
    }
}
