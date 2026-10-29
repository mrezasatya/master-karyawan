<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserAccount extends Authenticatable
{
    use Auditable;

    protected string $auditEntity = 'user_account';

    protected $authPasswordName = 'password_hash';

    protected $fillable = ['employee_id', 'username', 'display_name', 'role', 'password_hash', 'must_change_password', 'account_status', 'last_login_at'];

    protected $hidden = ['password_hash'];

    // Fitur "ingat saya" sengaja tidak disediakan (komputer bersama).
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function isHr(): bool
    {
        return $this->role === 'HR';
    }

    public function isActive(): bool
    {
        return $this->account_status === 'Aktif';
    }

    public function displayName(): string
    {
        return $this->employee?->nama ?? $this->display_name ?? $this->username;
    }
}
