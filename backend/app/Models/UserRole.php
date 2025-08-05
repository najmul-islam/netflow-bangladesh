<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $table = 'user_roles';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id',
        'assigned_at',
        'assigned_by'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Helper methods
    public function isSystemRole()
    {
        return $this->role && $this->role->is_system_role;
    }

    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->role_name : null;
    }

    public function getRoleDescriptionAttribute()
    {
        return $this->role ? $this->role->description : null;
    }

    public function hasPermission($permissionName)
    {
        return $this->role && $this->role->hasPermission($permissionName);
    }

    public function canBeRemoved()
    {
        // System roles assigned to system users cannot be removed
        if ($this->isSystemRole() && $this->user && $this->user->user_id === '00000000-0000-0000-0000-000000000001') {
            return false;
        }
        return true;
    }

    public function getAssignedByDisplayAttribute()
    {
        if (!$this->assigned_by) {
            return 'System';
        }
        
        return $this->assignedBy ? $this->assignedBy->getFullNameAttribute() : 'Unknown';
    }

    public function getAssignmentDurationAttribute()
    {
        return $this->assigned_at->diffForHumans();
    }

    public function isRecentlyAssigned($days = 7)
    {
        return $this->assigned_at && $this->assigned_at->diffInDays(now()) <= $days;
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('role_name', $roleName);
        });
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
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

    public function scopeAssignedBy($query, $assignerId)
    {
        return $query->where('assigned_by', $assignerId);
    }
}