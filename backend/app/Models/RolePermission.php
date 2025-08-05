<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'role_permissions';
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'permission_id'
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    // Helper methods
    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->role_name : null;
    }

    public function getPermissionNameAttribute()
    {
        return $this->permission ? $this->permission->permission_name : null;
    }

    public function getResourceAttribute()
    {
        return $this->permission ? $this->permission->resource : null;
    }

    public function getActionAttribute()
    {
        return $this->permission ? $this->permission->action : null;
    }

    public function isSystemRole()
    {
        return $this->role && $this->role->is_system_role;
    }

    public function canBeRemoved()
    {
        // System role permissions cannot be removed
        return !$this->isSystemRole();
    }

    public function getPermissionDescriptionAttribute()
    {
        return $this->permission ? $this->permission->description : null;
    }

    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeByPermission($query, $permissionId)
    {
        return $query->where('permission_id', $permissionId);
    }

    public function scopeByResource($query, $resource)
    {
        return $query->whereHas('permission', function($q) use ($resource) {
            $q->where('resource', $resource);
        });
    }

    public function scopeByAction($query, $action)
    {
        return $query->whereHas('permission', function($q) use ($action) {
            $q->where('action', $action);
        });
    }

    public function scopeSystemRoles($query)
    {
        return $query->whereHas('role', function($q) {
            $q->where('is_system_role', true);
        });
    }

    public function scopeCustomRoles($query)
    {
        return $query->whereHas('role', function($q) {
            $q->where('is_system_role', false);
        });
    }
}