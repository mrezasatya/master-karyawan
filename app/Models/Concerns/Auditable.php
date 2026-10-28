<?php

namespace App\Models\Concerns;

use App\Support\Audit;

/**
 * Mencatat Create/Update/Deactivate/SoftDelete ke audit_logs secara otomatis.
 * Hanya NAMA field yang dicatat, bukan nilainya, agar data sensitif tidak tersalin ke log.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            Audit::log($model->auditEntityType(), $model->getKey(), 'Create', $model->auditFieldNames(array_keys($model->getAttributes())));
        });

        static::updated(function ($model) {
            $fields = $model->auditFieldNames(array_keys($model->getChanges()));
            if ($fields === []) {
                return;
            }
            Audit::log($model->auditEntityType(), $model->getKey(), $model->auditActionFor($fields), $fields);
        });

        static::deleted(function ($model) {
            $action = method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting() ? 'SoftDelete' : 'Delete';
            Audit::log($model->auditEntityType(), $model->getKey(), $action, null);
        });
    }

    public function auditEntityType(): string
    {
        return property_exists($this, 'auditEntity') ? $this->auditEntity : $this->getTable();
    }

    protected function auditFieldNames(array $fields): array
    {
        $ignored = ['created_at', 'updated_at', 'password_hash', 'remember_token', $this->getKeyName(), 'created_by', 'updated_by'];

        return array_values(array_diff($fields, $ignored));
    }

    protected function auditActionFor(array $fields): string
    {
        foreach (['status_karyawan', 'account_status', 'status'] as $statusField) {
            if (in_array($statusField, $fields, true) && $this->getAttribute($statusField) === 'Nonaktif') {
                return 'Deactivate';
            }
        }

        return 'Update';
    }
}
