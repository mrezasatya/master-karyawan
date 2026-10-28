<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_user_id', 'entity_type', 'entity_id', 'action', 'changed_fields', 'ip_address'];

    protected function casts(): array
    {
        return ['changed_fields' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Audit log bersifat append-only.
        static::updating(fn () => throw new LogicException('Audit log tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Audit log tidak dapat dihapus.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'actor_user_id');
    }
}
