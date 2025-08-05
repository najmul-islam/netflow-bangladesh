<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchAssessment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_assessments';
    protected $primaryKey = 'assessment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'batch_id',
        'lesson_id',
        'title',
        'description',
        'instructions',
        'assessment_type',
        'time_limit_minutes',
        'max_attempts',
        'passing_score',
        'randomize_questions',
        'show_results',
        'due_date',
        'available_from',
        'available_until',
        'weight',
        'is_published',
        'is_final_exam',
        'is_proctored',
        'proctoring_settings',
        'allow_late_submission',
        'late_penalty_percent',
        'group_assessment',
        'max_group_size',
        'created_by'
    ];

    protected $casts = [
        'passing_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'late_penalty_percent' => 'decimal:2',
        'randomize_questions' => 'boolean',
        'is_published' => 'boolean',
        'is_final_exam' => 'boolean',
        'is_proctored' => 'boolean',
        'allow_late_submission' => 'boolean',
        'group_assessment' => 'boolean',
        'proctoring_settings' => 'json',
        'due_date' => 'datetime',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(BatchQuestion::class, 'assessment_id')->orderBy('sort_order');
    }

    public function attempts()
    {
        return $this->hasMany(BatchAssessmentAttempt::class, 'assessment_id');
    }

    public function completedAttempts()
    {
        return $this->hasMany(BatchAssessmentAttempt::class, 'assessment_id')
                    ->whereIn('status', ['submitted', 'graded']);
    }

    public function passedAttempts()
    {
        return $this->hasMany(BatchAssessmentAttempt::class, 'assessment_id')
                    ->where('passed', true);
    }

    public function notifications()
    {
        return $this->hasMany(BatchNotification::class, 'assessment_id');
    }

    // Helper methods
    public function isQuiz()
    {
        return $this->assessment_type === 'quiz';
    }

    public function isAssignment()
    {
        return $this->assessment_type === 'assignment';
    }

    public function isExam()
    {
        return $this->assessment_type === 'exam';
    }

    public function isFinalExam()
    {
        return $this->is_final_exam;
    }

    public function isSurvey()
    {
        return $this->assessment_type === 'survey';
    }

    public function isProject()
    {
        return $this->assessment_type === 'project';
    }

    public function isPresentation()
    {
        return $this->assessment_type === 'presentation';
    }

    public function isPublished()
    {
        return $this->is_published;
    }

    public function isProctored()
    {
        return $this->is_proctored;
    }

    public function isGroupAssessment()
    {
        return $this->group_assessment;
    }

    public function hasTimeLimit()
    {
        return !is_null($this->time_limit_minutes);
    }

    public function allowsLateSubmission()
    {
        return $this->allow_late_submission;
    }

    public function randomizesQuestions()
    {
        return $this->randomize_questions;
    }

    public function showsResultsImmediately()
    {
        return $this->show_results === 'immediately';
    }

    public function showsResultsAfterDueDate()
    {
        return $this->show_results === 'after_due_date';
    }

    public function showsResultsManually()
    {
        return $this->show_results === 'manual';
    }

    public function neverShowsResults()
    {
        return $this->show_results === 'never';
    }

    public function isAvailable()
    {
        $now = now();
        
        if ($this->available_from && $now < $this->available_from) {
            return false;
        }
        
        if ($this->available_until && $now > $this->available_until) {
            return false;
        }
        
        return $this->isPublished();
    }

    public function isDueSoon($hours = 24)
    {
        if (!$this->due_date) return false;
        
        return $this->due_date->diffInHours(now()) <= $hours && $this->due_date->isFuture();
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast();
    }

    public function getMaxScore()
    {
        return $this->questions()->sum('points');
    }

    public function getTotalQuestions()
    {
        return $this->questions()->count();
    }

    public function getAverageScore()
    {
        return $this->completedAttempts()->avg('percentage') ?? 0;
    }

    public function getPassRate()
    {
        $total = $this->completedAttempts()->count();
        if ($total === 0) return 0;
        
        $passed = $this->passedAttempts()->count();
        return round(($passed / $total) * 100, 2);
    }

    public function getAttemptCount()
    {
        return $this->attempts()->count();
    }

    public function getCompletionRate()
    {
        $total = $this->batch->activeEnrollments()->count();
        if ($total === 0) return 0;
        
        $completed = $this->completedAttempts()->distinct('user_id')->count();
        return round(($completed / $total) * 100, 2);
    }

    public function getUserAttempts($userId)
    {
        return $this->attempts()->where('user_id', $userId)->orderBy('attempt_number');
    }

    public function getUserBestAttempt($userId)
    {
        return $this->attempts()
                    ->where('user_id', $userId)
                    ->whereIn('status', ['submitted', 'graded'])
                    ->orderBy('percentage', 'desc')
                    ->first();
    }

    public function canUserAttempt($userId)
    {
        if (!$this->isAvailable()) return false;
        
        $userAttempts = $this->getUserAttempts($userId)->count();
        return $userAttempts < $this->max_attempts;
    }

    public function getTimeLeftForUser($userId)
    {
        $activeAttempt = $this->attempts()
                             ->where('user_id', $userId)
                             ->where('status', 'in_progress')
                             ->first();
        
        if (!$activeAttempt || !$this->hasTimeLimit()) return null;
        
        $elapsed = $activeAttempt->started_at->diffInMinutes(now());
        $remaining = $this->time_limit_minutes - $elapsed;
        
        return max(0, $remaining);
    }

    public function getTypeDisplayAttribute()
    {
        return match($this->assessment_type) {
            'quiz' => 'Quiz',
            'assignment' => 'Assignment',
            'exam' => 'Exam',
            'final_exam' => 'Final Exam',
            'survey' => 'Survey',
            'project' => 'Project',
            'presentation' => 'Presentation',
            default => ucfirst($this->assessment_type)
        };
    }

    public function getFormattedTimeLimitAttribute()
    {
        if (!$this->hasTimeLimit()) return 'No time limit';
        
        if ($this->time_limit_minutes < 60) {
            return $this->time_limit_minutes . ' minutes';
        }
        
        $hours = floor($this->time_limit_minutes / 60);
        $minutes = $this->time_limit_minutes % 60;
        
        return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFinalExams($query)
    {
        return $query->where('is_final_exam', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('assessment_type', $type);
    }

    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where('is_published', true)
                    ->where(function($q) use ($now) {
                        $q->whereNull('available_from')->orWhere('available_from', '<=', $now);
                    })
                    ->where(function($q) use ($now) {
                        $q->whereNull('available_until')->orWhere('available_until', '>=', $now);
                    });
    }

    public function scopeDueSoon($query, $hours = 24)
    {
        return $query->whereNotNull('due_date')
                    ->where('due_date', '>', now())
                    ->where('due_date', '<=', now()->addHours($hours));
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
                    ->where('due_date', '<', now());
    }
}