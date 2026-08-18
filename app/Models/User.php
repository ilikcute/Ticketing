<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'counter_number',
        'is_active',
        'custom_permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'custom_permissions' => 'array',
        ];
    }

    /**
     * Memeriksa apakah user memiliki hak akses fitur tertentu.
     */
    public function hasPermission(string $permission): bool
    {
        $roleValue = is_object($this->role) ? $this->role->value : $this->role;

        // Admin selalu memiliki akses penuh ke semua modul
        if ($roleValue === 'admin') {
            return true;
        }

        // Cek jika ada custom permission override khusus per user
        // Jika custom_permissions diset sebagai array kosong, anggap bukan override
        if (is_array($this->custom_permissions) && count($this->custom_permissions) > 0) {
            return in_array($permission, $this->custom_permissions, true);
        }

        // Mengambil permission default bawaan role
        $rolePermissions = RolePermission::getPermissionsForRole($roleValue);

        return in_array($permission, $rolePermissions, true);
    }

    /**
     * Mengembalikan daftar semua kode hak akses user
     */
    public function getAllPermissions(): array
    {
        $roleValue = is_object($this->role) ? $this->role->value : $this->role;

        if ($roleValue === 'admin') {
            return [
                'access-dashboard',
                'access-loket',
                'access-bib-check',
                'access-import',
                'access-users',
                'access-reset-claim',
            ];
        }

        // Jika custom_permissions ada dan tidak kosong, kembalikan override.
        if (is_array($this->custom_permissions) && count($this->custom_permissions) > 0) {
            return $this->custom_permissions;
        }

        return RolePermission::getPermissionsForRole($roleValue);
    }
}
