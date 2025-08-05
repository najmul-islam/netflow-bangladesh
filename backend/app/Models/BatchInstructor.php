<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchInstructor extends Model
{
    use HasFactory;

    protected $table = 'batch_instructors';
    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'user_id',
        'role',
        'assigned_at',
        'assigned_by',
        'is_active'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function course()
    {
        return $this->hasOneThrough(Course::class, CourseBatch::class, 'batch_id', 'course_id', 'batch_id', 'course_id');
    }

    // Helper methods
    public function isPrimaryInstructor()
    {
        return $this->role === 'primary';
    }

    public function isSecondaryInstructor()
    {
        return $this->role === 'secondary';
    }

    public function isAssistantInstructor()
    {
        return $this->role === 'assistant';
    }

    public function isGuestInstructor()
    {
        return $this->role === 'guest';
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function getInstructorNameAttribute()
    {
        return $this->user ? $this->user->getFullNameAttribute() : 'Unknown';
    }

    public function getBatchNameAttribute()
    {
        return $this->batch ? $this->batch->batch_name : 'Unknown';
    }

    public function getRoleDisplayAttribute()
    {
        return match($this->role) {
            'primary' => 'Primary Instructor',
            'secondary' => 'Secondary Instructor',
            'assistant' => 'Assistant Instructor',
            'guest' => 'Guest Instructor',
            default => ucfirst($this->role)
        };
    }

    public function getAssignedByDisplayAttribute()
    {
        if (!$this->assigned_by) {
            return 'System';
        }
        
        return $this->assignedBy ? $this->assignedBy->getFullNameAttribute() : 'Unknown';
    }

    public function canManageBatch()
    {
        return $this->isPrimaryInstructor() && $this->isActive();
    }

    public function canGradeAssessments()
    {
        return in_array($this->role, ['primary', 'secondary']) && $this->isActive();
    }

    public function canTakeAttendance()
    {
        return in_array($this->role, ['primary', 'secondary', 'assistant']) && $this->isActive();
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function getAssignmentDurationAttribute()
    {
        return $this->assigned_at->diffForHumans();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopePrimary($query)
    {
        return $query->where('role', 'primary');
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}