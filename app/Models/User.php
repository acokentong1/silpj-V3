<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    /**
     * Safe defaults for newly-created user model instances.
     *
     * Keep role/is_active out of $fillable so they cannot be assigned
     * from ordinary registration/profile requests. These defaults also
     * ensure factory-created users and freshly-created users have the
     * same in-memory state as the database defaults.
     */
    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
    ];

    protected $hidden = ['password', 'remember_token'];

    public function schoolProfile()
    {
        return $this->belongsTo(SchoolProfile::class);
    }

    public function isSystemAdmin(): bool
    {
        return (bool) $this->is_system_admin;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBendahara(): bool
    {
        return in_array($this->role, ['bendahara', 'admin']);
    }

    public function isKepalaSekolah(): bool
    {
        return $this->role === 'kepala_sekolah';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_system_admin' => 'boolean',
        ];
    }
}
