<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public function auditLog(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        mixed $oldValues = null,
        mixed $newValues = null,
        ?string $ipAddress = null,
        ?string $deviceId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip(),
            'device_id' => $deviceId ?? request()->header('X-Device-Id'),
        ]);
    }
}
