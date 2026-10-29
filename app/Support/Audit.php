<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class Audit
{
    /** Konteks tambahan (mis. nomor batch import) yang ikut dicatat selama proses berjalan. */
    public static array $context = [];

    public static function log(string $entityType, ?int $entityId, string $action, ?array $fields = null, ?int $actorId = null, array $extra = []): void
    {
        $payload = array_filter([
            'fields' => $fields,
            ...self::$context,
            ...$extra,
        ], fn ($v) => $v !== null && $v !== []);

        AuditLog::create([
            'actor_user_id' => $actorId ?? Auth::id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'changed_fields' => $payload ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    public static function security(string $event, array $extra = [], ?int $actorId = null): void
    {
        self::log('security', null, 'Login-security-event', null, $actorId, ['event' => $event, ...$extra]);
    }

    public static function withContext(array $context, callable $callback): mixed
    {
        $previous = self::$context;
        self::$context = $context;
        try {
            return $callback();
        } finally {
            self::$context = $previous;
        }
    }
}
