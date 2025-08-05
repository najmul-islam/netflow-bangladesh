<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchAssessmentAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_assessment_attempts';
    protected $primaryKey = 'attempt_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'batch_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'time_spent_minutes',
        'score',
        'max_score',
        'percentage',
        'passed',
        'status',
        'is_late',
        'late_penalty_applied',
        'graded_by',
        'graded_at',
        'feedback',
        'plagiarism_score',
        'proctoring_violations'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'late_penalty_applied' => 'decimal:2',
        'plagiarism_score' => 'decimal:2',
        'passed' => 'boolean',
        'is_late' => 'boolean',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'proctoring_violations' => 'json',
    ];

    // Relationships
    public function assessment()
    {
        return $this->belongsTo(BatchAssessment::class, 'assessment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function responses()
    {
        return $this->hasMany(BatchQuestionResponse::class, 'attempt_id');
    }

    // Helper methods
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isSubmitted()
    {
        return $this->status === 'submitted';
    }

    public function isGraded()
    {
        return $this->status === 'graded';
    }

    public function hasExpired()
    {
        return $this->status === 'expired';
    }

    public function isLateSubmission()
    {
        return $this->status === 'late_submission';
    }

    public function hasPassed()
    {
        return $this->passed;
    }

    public function isLate()
    {
        return $this->is_late;
    }

    public function hasLatePenalty()
    {
        return $this->late_penalty_applied > 0;
    }

    public function hasPlagiarismIssues()
    {
        return $this->plagiarism_score && $this->plagiarism_score > 50;
    }

    public function hasProctoringViolations()
    {
        return !empty($this->proctoring_violations);
    }

    public function hasFeedback()
    {
        return !empty($this->feedback);
    }

    public function needsGrading()
    {
        return $this->isSubmitted() && !$this->isGraded();
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'in_progress' => 'In Progress',
            'submitted' => 'Submitted',
            'graded' => 'Graded',
            'expired' => 'Expired',
            'late_submission' => 'Late Submission',
            default => ucfirst(str_replace('_', ' ', $this->status))
        };
    }

    public function getGradeLetterAttribute()
    {
        if (!$this->percentage) return null;
        
        $grade = $this->percentage;
        
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

    public function getFormattedTimeSpentAttribute()
    {
        if ($this->time_spent_minutes < 60) {
            return $this->time_spent_minutes . ' minutes';
        }
        
        $hours = floor($this->time_spent_minutes / 60);
        $minutes = $this->time_spent_minutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    public function getScoreDisplayAttribute()
    {
        return $this->score . ' / ' . $this->max_score . ' (' . $this->percentage . '%)';
    }

    public function calculateTimeSpent()
    {
        if (!$this->started_at || !$this->submitted_at) return 0;
        
        return $this->started_at->diffInMinutes($this->submitted_at);
    }

    public function markAsSubmitted()
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'time_spent_minutes' => $this->calculateTimeSpent()
        ]);
    }

    public function markAsGraded($score, $gradedBy, $feedback = null)
    {
        $percentage = $this->max_score > 0 ? ($score / $this->max_score) * 100 : 0;
        $passed = $percentage >= $this->assessment->passing_score;
        
        $this->update([
            'status' => 'graded',
            'score' => $score,
            'percentage' => $percentage,
            'passed' => $passed,
            'graded_by' => $gradedBy,
            'graded_at' => now(),
            'feedback' => $feedback
        ]);
    }

    public function applyLatePenalty()
    {
        if ($this->assessment->allowsLateSubmission() && $this->assessment->late_penalty_percent > 0) {
            $penalty = ($this->assessment->late_penalty_percent / 100) * $this->percentage;
            $newPercentage = max(0, $this->percentage - $penalty);
            
            $this->update([
                'percentage' => $newPercentage,
                'late_penalty_applied' => $penalty,
                'is_late' => true
            ]);
        }
    }

    public function addProctoringViolation($type, $description, $timestamp = null)
    {
        $violations = $this->proctoring_violations ?? [];
        
        $violations[] = [
            'type' => $type,
            'description' => $description,
            'timestamp' => $timestamp ?? now()->toISOString(),
            'severity' => $this->getViolationSeverity($type)
        ];
        
        $this->update(['proctoring_violations' => $violations]);
    }

    private function getViolationSeverity($type)
    {
        return match($type) {
            'tab_switch' => 'low',
            'window_blur' => 'low',
            'full_screen_exit' => 'medium',
            'copy_paste' => 'high',
            'multiple_faces' => 'high',
            'no_face_detected' => 'medium',
            default => 'medium'
        };
    }

    public function getViolationCount($severity = null)
    {
        if (!$this->proctoring_violations) return 0;
        
        if (!$severity) return count($this->proctoring_violations);
        
        return collect($this->proctoring_violations)
                ->where('severity', $severity)
                ->count();
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    public function scopeNeedsGrading($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAssessment($query, $assessmentId)
    {
        return $query->where('assessment_id', $assessmentId);
    }

    public function scopeLateSubmissions($query)
    {
        return $query->where('is_late', true);
    }

    public function scopeWithViolations($query)
    {
        return $query->whereNotNull('proctoring_violations');
    }
}