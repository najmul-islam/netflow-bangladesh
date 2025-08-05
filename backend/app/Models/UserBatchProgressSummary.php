<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBatchProgressSummary extends Model
{
    use HasFactory;

    protected $table = 'user_batch_progress_summary';
    public $timestamps = false;

    protected $casts = [
        'enrollment_date' => 'datetime',
        'progress_percentage' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'last_accessed' => 'datetime',
        'final_exam_passed' => 'boolean',
        'certificate_issued' => 'boolean',
        'overall_grade' => 'decimal:2',
    ];

    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isActive()
    {
        return $this->enrollment_status === 'active';
    }

    public function isCompleted()
    {
        return $this->enrollment_status === 'completed';
    }

    public function isDropped()
    {
        return $this->enrollment_status === 'dropped';
    }

    public function isSuspended()
    {
        return $this->enrollment_status === 'suspended';
    }

    public function isTransferred()
    {
        return $this->enrollment_status === 'transferred';
    }

    public function hasPassedFinalExam()
    {
        return $this->final_exam_passed;
    }

    public function hasCertificate()
    {
        return $this->certificate_issued;
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->enrollment_status) {
            'active' => 'Active',
            'completed' => 'Completed',
            'dropped' => 'Dropped',
            'suspended' => 'Suspended',
            'transferred' => 'Transferred',
            default => ucfirst($this->enrollment_status)
        };
    }

    public function getLessonCompletionRateAttribute()
    {
        if ($this->total_lessons == 0) return 0;
        return round(($this->completed_lessons / $this->total_lessons) * 100, 2);
    }

    public function getClassAttendanceRateAttribute()
    {
        if ($this->total_classes == 0) return 0;
        return round(($this->attended_classes / $this->total_classes) * 100, 2);
    }

    public function getOverallProgressAttribute()
    {
        $factors = [];
        
        // Lesson progress (40% weight)
        if ($this->total_lessons > 0) {
            $factors['lessons'] = [
                'weight' => 0.4,
                'value' => $this->getLessonCompletionRateAttribute()
            ];
        }
        
        // Attendance (30% weight)
        if ($this->total_classes > 0) {
            $factors['attendance'] = [
                'weight' => 0.3,
                'value' => $this->getClassAttendanceRateAttribute()
            ];
        }
        
        // Overall grade (30% weight)
        if ($this->overall_grade) {
            $factors['grade'] = [
                'weight' => 0.3,
                'value' => $this->overall_grade
            ];
        }
        
        if (empty($factors)) return 0;
        
        $totalWeight = array_sum(array_column($factors, 'weight'));
        $weightedSum = 0;
        
        foreach ($factors as $factor) {
            $weightedSum += $factor['value'] * $factor['weight'];
        }
        
        return round($weightedSum / $totalWeight, 2);
    }

    public function getPerformanceStatusAttribute()
    {
        $progress = $this->getOverallProgressAttribute();
        
        if ($progress >= 90) return 'excellent';
        if ($progress >= 80) return 'good';
        if ($progress >= 70) return 'satisfactory';
        if ($progress >= 60) return 'needs_improvement';
        return 'poor';
    }

    public function getPerformanceColorAttribute()
    {
        return match($this->getPerformanceStatusAttribute()) {
            'excellent' => 'green',
            'good' => 'blue',
            'satisfactory' => 'yellow',
            'needs_improvement' => 'orange',
            'poor' => 'red',
            default => 'gray'
        };
    }

    public function getRankDisplayAttribute()
    {
        if (!$this->class_rank) return 'N/A';
        
        // Calculate total students in batch (approximation)
        $totalInBatch = self::where('batch_id', $this->batch_id)
                           ->whereNotNull('class_rank')
                           ->max('class_rank');
        
        return $this->class_rank . ' of ' . $totalInBatch;
    }

    public function getGradeLetterAttribute()
    {
        if (!$this->overall_grade) return null;
        
        $grade = $this->overall_grade;
        
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

    public function getEnrollmentDurationAttribute()
    {
        return $this->enrollment_date->diffForHumans();
    }

    public function getLastAccessDisplayAttribute()
    {
        return $this->last_accessed ? $this->last_accessed->diffForHumans() : 'Never';
    }

    public function isRecentlyActive($days = 7)
    {
        return $this->last_accessed && $this->last_accessed->diffInDays(now()) <= $days;
    }

    public function hasGoodAttendance($threshold = 75)
    {
        return $this->attendance_percentage >= $threshold;
    }

    public function hasGoodProgress($threshold = 70)
    {
        return $this->progress_percentage >= $threshold;
    }

    public function isAtRisk()
    {
        return ($this->attendance_percentage < 60) || 
               ($this->progress_percentage < 50) ||
               (!$this->last_accessed || $this->last_accessed->diffInDays(now()) > 14);
    }

    public function getProgressSummaryAttribute()
    {
        return [
            'lessons' => [
                'completed' => $this->completed_lessons,
                'total' => $this->total_lessons,
                'percentage' => $this->getLessonCompletionRateAttribute()
            ],
            'classes' => [
                'attended' => $this->attended_classes,
                'total' => $this->total_classes,
                'percentage' => $this->getClassAttendanceRateAttribute()
            ],
            'overall' => [
                'progress' => $this->progress_percentage,
                'grade' => $this->overall_grade,
                'rank' => $this->class_rank
            ]
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('enrollment_status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('enrollment_status', 'completed');
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithCertificate($query)
    {
        return $query->where('certificate_issued', true);
    }

    public function scopePassedFinalExam($query)
    {
        return $query->where('final_exam_passed', true);
    }

    public function scopeAtRisk($query)
    {
        return $query->where(function($q) {
            $q->where('attendance_percentage', '<', 60)
              ->orWhere('progress_percentage', '<', 50)
              ->orWhere('last_accessed', '<', now()->subDays(14))
              ->orWhereNull('last_accessed');
        });
    }

    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->whereNotNull('overall_grade')
                    ->orderBy('overall_grade', 'desc')
                    ->limit($limit);
    }

    public function scopeRecentlyActive($query, $days = 7)
    {
        return $query->where('last_accessed', '>=', now()->subDays($days));
    }

    public function scopeInactive($query, $days = 14)
    {
        return $query->where(function($q) use ($days) {
            $q->where('last_accessed', '<', now()->subDays($days))
              ->orWhereNull('last_accessed');
        });
    }

    public function scopeByProgressRange($query, $min, $max)
    {
        return $query->whereBetween('progress_percentage', [$min, $max]);
    }

    public function scopeByAttendanceRange($query, $min, $max)
    {
        return $query->whereBetween('attendance_percentage', [$min, $max]);
    }

    public function scopeByGradeRange($query, $min, $max)
    {
        return $query->whereBetween('overall_grade', [$min, $max]);
    }

    public function scopeByRankRange($query, $min, $max)
    {
        return $query->whereBetween('class_rank', [$min, $max]);
    }
}