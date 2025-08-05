<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseInstructor extends Model
{
    use HasFactory;

    protected $table = 'course_instructors';
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'user_id',
        'role',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function batches()
    {
        return $this->hasMany(CourseBatch::class, 'course_id', 'course_id');
    }

    public function batchInstructions()
    {
        return $this->hasMany(BatchInstructor::class, 'user_id', 'user_id')
                    ->whereHas('batch', function($q) {
                        $q->where('course_id', $this->course_id);
                    });
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

    public function getInstructorNameAttribute()
    {
        return $this->user ? $this->user->getFullNameAttribute() : 'Unknown';
    }

    public function getCourseNameAttribute()
    {
        return $this->course ? $this->course->title : 'Unknown';
    }

    public function getRoleDisplayAttribute()
    {
        return match($this->role) {
            'primary' => 'Primary Instructor',
            'secondary' => 'Secondary Instructor',
            'assistant' => 'Assistant Instructor',
            default => ucfirst($this->role)
        };
    }

    public function canManageCourse()
    {
        return $this->isPrimaryInstructor();
    }

    public function canCreateBatches()
    {
        return in_array($this->role, ['primary', 'secondary']);
    }

    public function canModifyContent()
    {
        return in_array($this->role, ['primary', 'secondary']);
    }

    public function canViewAnalytics()
    {
        return in_array($this->role, ['primary', 'secondary']);
    }

    public function getActiveBatchesCount()
    {
        return $this->batchInstructions()
                    ->whereHas('batch', function($q) {
                        $q->whereIn('status', ['open_for_enrollment', 'in_progress']);
                    })
                    ->count();
    }

    public function getTotalStudentsCount()
    {
        return BatchEnrollment::whereHas('batch', function($q) {
            $q->where('course_id', $this->course_id);
        })->whereHas('batch.instructors', function($q) {
            $q->where('user_id', $this->user_id);
        })->where('status', 'active')->count();
    }

    public function getAssignmentDurationAttribute()
    {
        return $this->assigned_at->diffForHumans();
    }

    public function hasPermissionForBatch($batchId)
    {
        return BatchInstructor::where('user_id', $this->user_id)
                             ->where('batch_id', $batchId)
                             ->where('is_active', true)
                             ->exists();
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopePrimary($query)
    {
        return $query->where('role', 'primary');
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithActiveBatches($query)
    {
        return $query->whereHas('batches', function($q) {
            $q->whereIn('status', ['open_for_enrollment', 'in_progress']);
        });
    }
}