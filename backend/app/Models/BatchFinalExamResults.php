<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchFinalExamResults extends Model
{
    use HasFactory;

    protected $table = 'batch_final_exam_results';
    public $timestamps = false;

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'overall_grade' => 'decimal:2',
        'passed' => 'boolean',
        'certificate_issued' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    // Note: This is a view, so no relationships to other models
    // But we can define accessor methods for data manipulation

    // Helper methods
    public function getStudentFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function hasPassed()
    {
        return $this->passed;
    }

    public function hasFailed()
    {
        return !$this->passed;
    }

    public function hasCertificate()
    {
        return $this->certificate_issued;
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

    public function getScoreDisplayAttribute()
    {
        return $this->score . ' / ' . $this->max_score . ' (' . $this->percentage . '%)';
    }

    public function getFormattedScoreAttribute()
    {
        return number_format($this->score, 2);
    }

    public function getFormattedPercentageAttribute()
    {
        return number_format($this->percentage, 2) . '%';
    }

    public function getFormattedOverallGradeAttribute()
    {
        return $this->overall_grade ? number_format($this->overall_grade, 2) . '%' : 'N/A';
    }

    public function getPassStatusDisplayAttribute()
    {
        return $this->passed ? 'Passed' : 'Failed';
    }

    public function getPassStatusColorAttribute()
    {
        return $this->passed ? 'green' : 'red';
    }

    public function getCertificateStatusDisplayAttribute()
    {
        return $this->certificate_issued ? 'Issued' : 'Not Issued';
    }

    public function getCertificateStatusColorAttribute()
    {
        return $this->certificate_issued ? 'blue' : 'gray';
    }

    public function getRankDisplayAttribute()
    {
        return $this->class_rank ? 'Rank #' . $this->class_rank : 'No rank';
    }

    public function getSubmittedTimeAttribute()
    {
        return $this->submitted_at ? $this->submitted_at->format('M j, Y g:i A') : 'Not submitted';
    }

    public function getTimeAgoAttribute()
    {
        return $this->submitted_at ? $this->submitted_at->diffForHumans() : 'Not submitted';
    }

    public function isTopPerformer($topPercent = 10)
    {
        if (!$this->class_rank) return false;
        
        // Estimate total students from the view data
        $totalStudents = self::where('batch_id', $this->batch_id)->count();
        $topRank = ceil($totalStudents * ($topPercent / 100));
        
        return $this->class_rank <= $topRank;
    }

    public function isExcellentPerformance($threshold = 90)
    {
        return $this->percentage >= $threshold;
    }

    public function isGoodPerformance($threshold = 80)
    {
        return $this->percentage >= $threshold;
    }

    public function isSatisfactoryPerformance($threshold = 70)
    {
        return $this->percentage >= $threshold;
    }

    public function needsImprovement($threshold = 60)
    {
        return $this->percentage < $threshold;
    }

    public function getPerformanceLevelAttribute()
    {
        if ($this->isExcellentPerformance()) return 'excellent';
        if ($this->isGoodPerformance()) return 'good';
        if ($this->isSatisfactoryPerformance()) return 'satisfactory';
        if ($this->needsImprovement()) return 'needs_improvement';
        return 'poor';
    }

    public function getPerformanceColorAttribute()
    {
        return match($this->getPerformanceLevelAttribute()) {
            'excellent' => 'green',
            'good' => 'blue',
            'satisfactory' => 'yellow',
            'needs_improvement' => 'orange',
            'poor' => 'red',
            default => 'gray'
        };
    }

    public function getExamResultSummaryAttribute()
    {
        return [
            'student_name' => $this->getStudentFullNameAttribute(),
            'exam_title' => $this->exam_title,
            'batch_info' => $this->batch_name . ' (' . $this->batch_code . ')',
            'score' => $this->getScoreDisplayAttribute(),
            'grade_letter' => $this->getGradeLetterAttribute(),
            'pass_status' => $this->getPassStatusDisplayAttribute(),
            'rank' => $this->getRankDisplayAttribute(),
            'certificate_status' => $this->getCertificateStatusDisplayAttribute(),
            'submitted_at' => $this->getSubmittedTimeAttribute(),
            'performance_level' => $this->getPerformanceLevelAttribute()
        ];
    }

    // Scoping methods for filtering
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    public function scopeWithCertificate($query)
    {
        return $query->where('certificate_issued', true);
    }

    public function scopeWithoutCertificate($query)
    {
        return $query->where('certificate_issued', false);
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

    public function scopeByScoreRange($query, $min, $max)
    {
        return $query->whereBetween('percentage', [$min, $max]);
    }

    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->orderBy('percentage', 'desc')->limit($limit);
    }

    public function scopeBottomPerformers($query, $limit = 10)
    {
        return $query->orderBy('percentage', 'asc')->limit($limit);
    }

    public function scopeExcellentPerformance($query, $threshold = 90)
    {
        return $query->where('percentage', '>=', $threshold);
    }

    public function scopeGoodPerformance($query, $threshold = 80)
    {
        return $query->where('percentage', '>=', $threshold);
    }

    public function scopePoorPerformance($query, $threshold = 60)
    {
        return $query->where('percentage', '<', $threshold);
    }

    public function scopeByGradeLetter($query, $letter)
    {
        return $query->whereRaw('
            CASE 
                WHEN percentage >= 97 THEN "A+"
                WHEN percentage >= 93 THEN "A"
                WHEN percentage >= 90 THEN "A-"
                WHEN percentage >= 87 THEN "B+"
                WHEN percentage >= 83 THEN "B"
                WHEN percentage >= 80 THEN "B-"
                WHEN percentage >= 77 THEN "C+"
                WHEN percentage >= 73 THEN "C"
                WHEN percentage >= 70 THEN "C-"
                WHEN percentage >= 67 THEN "D+"
                WHEN percentage >= 65 THEN "D"
                ELSE "F"
            END = ?
        ', [$letter]);
    }

    public function scopeRecentSubmissions($query, $days = 30)
    {
        return $query->where('submitted_at', '>=', now()->subDays($days));
    }

    public function scopeByRankRange($query, $minRank, $maxRank)
    {
        return $query->whereBetween('class_rank', [$minRank, $maxRank]);
    }

    public function scopeTopRanked($query, $topCount = 10)
    {
        return $query->where('class_rank', '<=', $topCount);
    }

    public function scopeWithOverallGrade($query)
    {
        return $query->whereNotNull('overall_grade');
    }

    public function scopeOrderByRank($query, $direction = 'asc')
    {
        return $query->orderBy('class_rank', $direction);
    }

    public function scopeOrderByScore($query, $direction = 'desc')
    {
        return $query->orderBy('percentage', $direction);
    }

    public function scopeOrderBySubmissionDate($query, $direction = 'desc')
    {
        return $query->orderBy('submitted_at', $direction);
    }
}