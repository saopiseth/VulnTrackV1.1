<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'mfa_enabled',
        'password',
        'dashboard_layout',
    ];

    public function isAdministrator(): bool
    {
        return $this->role === 'administrator';
    }

    public function isAssessor(): bool
    {
        return $this->role === 'assessor';
    }

    public function isPatchAdministrator(): bool
    {
        return $this->role === 'patch_administrator';
    }

    /** True for any role that may only read data and cannot perform write actions. */
    public function isViewOnly(): bool
    {
        return $this->role === 'patch_administrator';
    }

    /** Groups this user belongs to (many-to-many via user_group_members). */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_members', 'user_id', 'user_group_id')
                    ->withTimestamps();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'mfa_enabled'        => 'boolean',
            'dashboard_layout'   => 'array',
        ];
    }
}
