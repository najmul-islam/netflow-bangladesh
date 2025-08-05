<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchLessonProgress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_lesson_progress';
    protected $primaryKey = 'progress_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'batch_id',
        'lesson_id',
        'status',
        'progress_percentage',
        'time_spent_minutes',
        'first_accessed',
        'last_accessed',
        'completed_at',
        'notes',
        'instructor_feedback',
        'grade'
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'grade' => 'decimal:2',
        'first_accessed' => 'datetime',
        'last_accessed' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function enrollment()
    {
        return $this->hasOne(BatchEnrollment::class, 'user_id', 'user_id')
                    ->where('batch_id', $this->batch_id);
    }

    // Helper methods
    public function isNotStarted()
    {
        return $this->status === 'not_started';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isSkipped()
    {
        return $this->status === 'skipped';
    }

    public function hasGrade()
    {
        return !is_null($this->grade);
    }

    public function hasInstructorFeedback()
    {
        return !empty($this->instructor_feedback);
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'skipped' => 'Skipped',
            default => ucfirst(str_replace('_', ' ', $this->status))
        };
    }

    public function getFormattedTimeSpentAttribute()
    {
        if ($this->time_spent_minutes < 60) {
            return $this->time_spent_minutes . ' minutes';
        }
        
        $hours = floor($this->time_spent_minutes / 60);
        $minutes = $this->time_spent_minutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    public function getGradeLetterAttribute()
    {
        if (!$this->hasGrade()) return null;
        
        $grade = $this->grade;
        
        if ($grade >= 97) return 'A+';
        if ($grade >= 93) return 'A';
        if ($grade >= 90) return 'A-';
        if ($grade >= 87) return 'B+';
        if ($grade >= 83) return 'B';
        if ($grade >= 80) return 'B-';
        if ($grade >= 77) return 'C+';
        if ($grade >= 73) return 'C';
        if ($grade >= 70) return 'C-';
        if ($grade >= 67) return 'D+';
        if ($grade >= 65) return 'D';
        
        return 'F';
    }

    public function markAsStarted()
    {
        $updateData = [
            'status' => 'in_progress',
            'first_accessed' => $this->first_accessed ?? now(),
            'last_accessed' => now()
        ];

        $this->update($updateData);
    }

    public function markAsCompleted()
    {
        $updateData = [
            'status' => 'completed',
            'progress_percentage' => 100.00,
            'completed_at' => now(),
            'last_accessed' => now()
        ];

        $this->update($updateData);
    }

    public function updateProgress($percentage)
    {
        $updateData = [
            'progress_percentage' => min(100, max(0, $percentage)),
            'last_accessed' => now()
        ];

        if ($percentage >= 100) {
            $updateData['status'] = 'completed';
            $updateData['completed_at'] = now();
        } elseif ($this->isNotStarted() && $percentage > 0) {
            $updateData['status'] = 'in_progress';
            $updateData['first_accessed'] = now();
        }

        $this->update($updateData);
    }

    public function addTimeSpent($minutes)
    {
        $this->increment('time_spent_minutes', $minutes);
        $this->update(['last_accessed' => now()]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByLesson($query, $lessonId)
    {
        return $query->where('lesson_id', $lessonId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeWithGrade($query)
    {
        return $query->whereNotNull('grade');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}