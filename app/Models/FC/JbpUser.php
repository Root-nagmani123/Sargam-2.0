<?php

namespace App\Models\FC;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class JbpUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'jbp_users';

    protected $fillable = [
        'username',
        'password',
        'email',
        'role',
        'enabled',
        'login_attempts',
        'last_login',
        'session_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'enabled'       => 'boolean',
        'last_login'    => 'datetime',
        'login_attempts'=> 'integer',
    ];

    /**
     * The username field is used instead of 'email' for auth.
     */
    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function studentMasterFirst()
    {
        return $this->hasOne(StudentMasterFirst::class, 'username', 'username');
    }

    public function studentMasterSecond()
    {
        return $this->hasOne(StudentMasterSecond::class, 'username', 'username');
    }

    public function studentMaster()
    {
        return $this->hasOne(StudentMaster::class, 'username', 'username');
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return in_array($this->role, ['ADMIN', 'SUPERADMIN']);
    }

    public function isFC(): bool
    {
        return $this->role === 'FC';
    }

    public function isLocked(): bool
    {
        return $this->login_attempts >= 5;
    }

    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
    }

    public function resetLoginAttempts(): void
    {
        $this->update(['login_attempts' => 0, 'last_login' => now()]);
    }
}
