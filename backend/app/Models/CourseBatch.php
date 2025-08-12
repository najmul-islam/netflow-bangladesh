<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CourseBatch extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'course_batches';
    protected $primaryKey = 'batch_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'course_id',
        'batch_name',
        'batch_code',
        'description',
        'max_students',
        'current_students',
        'start_date',
        'end_date',
        'enrollment_start_date',
        'enrollment_end_date',
        'status',
        'batch_type',
        'zoom_link',
        'timezone',
        'is_featured',
        'auto_generated',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enrollment_start_date' => 'datetime',
        'enrollment_end_date' => 'datetime',
        'is_featured' => 'boolean',
        'auto_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function enrollments()
    {
        return $this->hasMany(BatchEnrollment::class, 'batch_id');
    }

    public function activeEnrollments()
    {
        return $this->hasMany(BatchEnrollment::class, 'batch_id')->where('status', 'active');
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'batch_instructors', 'batch_id', 'user_id')
                    ->withPivot('role', 'assigned_at', 'assigned_by', 'is_active');
    }

    public function schedules()
    {
        return $this->hasMany(BatchSchedule::class, 'batch_id')->orderBy('start_datetime');
    }

    public function assessments()
    {
        return $this->hasMany(BatchAssessment::class, 'batch_id');
    }

    public function forums()
    {
        return $this->hasMany(BatchForum::class, 'batch_id');
    }

    public function statistics()
    {
        return $this->hasOne(BatchStatistics::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods
    public function isOpenForEnrollment()
    {
        return $this->status === 'open_for_enrollment';
    }

    public function hasSpace()
    {
        return $this->max_students === null || $this->current_students < $this->max_students;
    }

    public function isEnrollmentActive()
    {
        $now = now();
        return $this->isOpenForEnrollment() && 
               ($this->enrollment_start_date === null || $now >= $this->enrollment_start_date) &&
               ($this->enrollment_end_date === null || $now <= $this->enrollment_end_date);
    }
}