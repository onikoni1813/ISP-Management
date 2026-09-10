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
        'phone',
        'password',
        'status',
    ];

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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function hasPermissionTo(string $permissionSlug): bool
    {
        // 1. Direct permission on user
        if ($this->permissions()->where('slug', $permissionSlug)->exists()) {
            return true;
        }

        // 2. Permission inherited from assigned roles
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    public function assignRole(Role|string ...$roles): self
    {
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::where('slug', $role)->firstOrFail();
                $this->roles()->syncWithoutDetaching([$roleModel->id]);
            } else {
                $this->roles()->syncWithoutDetaching([$role->id]);
            }
        }
        return $this;
    }

    public function givePermissionTo(Permission|string ...$permissions): self
    {
        foreach ($permissions as $perm) {
            if (is_string($perm)) {
                $permModel = Permission::where('slug', $perm)->firstOrFail();
                $this->permissions()->syncWithoutDetaching([$permModel->id]);
            } else {
                $this->permissions()->syncWithoutDetaching([$perm->id]);
            }
        }
        return $this;
    }
}
