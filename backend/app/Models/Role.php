<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_name',
        'description',
        'is_system_role'
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
                    ->withPivot('assigned_at', 'assigned_by');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    // Helper methods
    public function hasPermission($permissionName)
    {
        return $this->permissions()->where('permission_name', $permissionName)->exists();
    }

    public function isSystemRole()
    {
        return $this->is_system_role;
    }
}