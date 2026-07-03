<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model — extended from original user_login table.
 *
 * Original columns: username, name, department (Medical/Administration/Security/IT/Trg/Mess)
 *
 * Added: password (hashed — original was plain text, fixed here),
 *        remember_token for Laravel session auth.
 *
 * Migration note: rename user_login → users, or set $table = 'user_login'
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users'; // change to 'user_login' if keeping original table name

    protected $fillable = [
        'username',
        'name',
        'department',   // Medical | Administration | Security | IT | Trg | Mess
        'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['password' => 'hashed'];

    /**
     * The field used for login (original used 'username' not 'email').
     * Set AUTH_USERNAME=username in .env or override in config/auth.php.
     */
    public function getAuthIdentifierName(): string
    {
        return 'username';
    }
}
