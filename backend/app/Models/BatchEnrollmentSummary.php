<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchEnrollmentSummary extends Model
{
    use HasFactory;

    protected $table = 'batch_enrollment_summary';
    public $timestamps = false;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'has_certificate' => 'boolean',
        'average_rating' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Helper methods
    public function isFree()
    {
        return $this->is_free;
    }

    public function hasCertificate()
    {
        return $this->has_certificate;
    }

    public function isDraft()
    {
        return $this->batch_status === 'draft';
    }

    public function isOpenForEnrollment()
    {
        return $this->batch_status === 'open_for_enrollment';
    }

    public function isInProgress()
    {
        return $this->batch_status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->batch_status === 'completed';
    }

    public function isCancelled()
    {
        return $this->batch_status === 'cancelled';
    }

    public function isSuspended()
    {
        return $this->batch_status === 'suspended';
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->batch_status) {
            'draft' => 'Draft',
            'open_for_enrollment' => 'Open for Enrollment',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'suspended' => 'Suspended',
            default => ucfirst(str_replace('_', ' ', $this->batch_status))
        };
    }

    public function getEnrollmentRateAttribute()
    {
        if ($this->max_students == 0) return 0;
        return round(($this->total_enrollments / $this->max_students) * 100, 2);
    }

    public function getCompletionRateAttribute()
    {
        if ($this->total_enrollments == 0) return 0;
        return round(($this->completed_enrollments / $this->total_enrollments) * 100, 2);
    }

    public function getCertificationRateAttribute()
    {
        if ($this->total_enrollments == 0) return 0;
        return round(($this->certificates_issued / $this->total_enrollments) * 100, 2);
    }

    public function getFormattedPriceAttribute()
    {
        return $this->isFree() ? 'Free' : '$' . number_format($this->price, 2);
    }

    public function getRatingStarsAttribute()
    {
        if (!$this->average_rating) return 'No ratings';
        
        $stars = '';
        $rating = round($this->average_rating);
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $rating ? '★' : '☆';
        }
        return $stars . ' (' . $this->average_rating . ')';
    }

    public function hasGoodRating($threshold = 4.0)
    {
        return $this->average_rating >= $threshold;
    }

    public function isPopular($enrollmentThreshold = 50)
    {
        return $this->total_enrollments >= $enrollmentThreshold;
    }

    public function isFullyBooked()
    {
        return $this->total_enrollments >= $this->max_students;
    }

    public function hasAvailableSpots()
    {
        return $this->total_enrollments < $this->max_students;
    }

    public function getAvailableSpotsAttribute()
    {
        return max(0, $this->max_students - $this->total_enrollments);
    }

    public function getProgressSummaryAttribute()
    {
        return [
            'total_enrollments' => $this->total_enrollments,
            'active_enrollments' => $this->active_enrollments,
            'completed_enrollments' => $this->completed_enrollments,
            'certificates_issued' => $this->certificates_issued,
            'completion_rate' => $this->getCompletionRateAttribute(),
            'certification_rate' => $this->getCertificationRateAttribute()
        ];
    }

    public function getPerformanceGradeAttribute()
    {
        $score = 0;
        $totalWeight = 0;

        // Enrollment rate (30% weight)
        if ($this->max_students > 0) {
            $enrollmentRate = ($this->total_enrollments / $this->max_students) * 100;
            $score += min(100, $enrollmentRate) * 0.3;
            $totalWeight += 0.3;
        }

        // Completion rate (40% weight)
        if ($this->total_enrollments > 0) {
            $completionRate = ($this->completed_enrollments / $this->total_enrollments) * 100;
            $score += $completionRate * 0.4;
            $totalWeight += 0.4;
        }

        // Rating (30% weight)
        if ($this->average_rating > 0) {
            $ratingScore = ($this->average_rating / 5) * 100;
            $score += $ratingScore * 0.3;
            $totalWeight += 0.3;
        }

        if ($totalWeight == 0) return 'N/A';

        $finalScore = $score / $totalWeight;

        if ($finalScore >= 90) return 'A+';
        if ($finalScore >= 85) return 'A';
        if ($finalScore >= 80) return 'A-';
        if ($finalScore >= 75) return 'B+';
        if ($finalScore >= 70) return 'B';
        if ($finalScore >= 65) return 'B-';
        if ($finalScore >= 60) return 'C+';
        if ($finalScore >= 55) return 'C';
        if ($finalScore >= 50) return 'C-';
        if ($finalScore >= 45) return 'D';
        
        return 'F';
    }

    public function scopeActive($query)
    {
        return $query->whereIn('batch_status', ['open_for_enrollment', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('batch_status', 'completed');
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    public function scopeWithCertificate($query)
    {
        return $query->where('has_certificate', true);
    }

    public function scopeHighRated($query, $threshold = 4.0)
    {
        return $query->where('average_rating', '>=', $threshold);
    }

    public function scopePopular($query, $enrollmentThreshold = 50)
    {
        return $query->where('total_enrollments', '>=', $enrollmentThreshold);
    }

    public function scopeFullyBooked($query)
    {
        return $query->whereRaw('total_enrollments >= max_students');
    }

    public function scopeAvailableForEnrollment($query)
    {
        return $query->where('batch_status', 'open_for_enrollment')
                    ->whereRaw('total_enrollments < max_students');
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeStartingSoon($query, $days = 30)
    {
        return $query->where('start_date', '>=', now()->toDateString())
                    ->where('start_date', '<=', now()->addDays($days)->toDateString());
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }
}