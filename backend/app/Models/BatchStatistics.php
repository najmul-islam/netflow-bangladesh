<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchStatistics extends Model
{
    use HasFactory;

    protected $table = 'batch_statistics';
    protected $primaryKey = 'batch_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'total_enrollments',
        'active_enrollments',
        'completed_enrollments',
        'dropped_enrollments',
        'completion_rate',
        'average_rating',
        'total_ratings',
        'average_attendance',
        'total_classes_held',
        'certificates_issued',
        'average_final_score',
        'forum_posts_count',
        'total_assignments',
        'submitted_assignments'
    ];

    protected $casts = [
        'completion_rate' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'average_attendance' => 'decimal:2',
        'average_final_score' => 'decimal:2',
        'last_updated' => 'datetime',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    // Helper methods
    public function getCompletionRatePercentage()
    {
        return round($this->completion_rate, 2);
    }

    public function getAverageRatingStars()
    {
        return round($this->average_rating, 1);
    }

    public function getAttendanceRatePercentage()
    {
        return round($this->average_attendance, 2);
    }

    public function hasGoodPerformance()
    {
        return $this->completion_rate >= 80 && $this->average_attendance >= 75;
    }

    public function getDropoutRate()
    {
        if ($this->total_enrollments == 0) return 0;
        return round(($this->dropped_enrollments / $this->total_enrollments) * 100, 2);
    }

    public function getAssignmentSubmissionRate()
    {
        if ($this->total_assignments == 0) return 0;
        return round(($this->submitted_assignments / $this->total_assignments) * 100, 2);
    }

    public function getCertificationRate()
    {
        if ($this->total_enrollments == 0) return 0;
        return round(($this->certificates_issued / $this->total_enrollments) * 100, 2);
    }

    public function getPerformanceGrade()
    {
        $score = 0;
        
        // Completion rate (40% weight)
        $score += ($this->completion_rate / 100) * 40;
        
        // Attendance rate (30% weight)
        $score += ($this->average_attendance / 100) * 30;
        
        // Average rating (20% weight)
        $score += ($this->average_rating / 5) * 20;
        
        // Final score (10% weight)
        $score += ($this->average_final_score / 100) * 10;
        
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D';
        
        return 'F';
    }

    public function getEngagementScore()
    {
        $engagementFactors = [
            'forum_participation' => $this->forum_posts_count > 0 ? min(100, $this->forum_posts_count * 10) : 0,
            'assignment_submission' => $this->getAssignmentSubmissionRate(),
            'attendance' => $this->average_attendance,
            'completion' => $this->completion_rate
        ];

        return round(array_sum($engagementFactors) / count($engagementFactors), 2);
    }
}