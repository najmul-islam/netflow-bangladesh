<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchStudentPerformance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_student_performance';
    protected $primaryKey = 'performance_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'batch_id',
        'total_classes',
        'attended_classes',
        'attendance_percentage',
        'assignments_submitted',
        'assignments_total',
        'average_assignment_score',
        'quiz_attempts',
        'average_quiz_score',
        'final_exam_score',
        'overall_grade',
        'grade_letter',
        'class_rank',
        'participation_score'
    ];

    protected $casts = [
        'attendance_percentage' => 'decimal:2',
        'average_assignment_score' => 'decimal:2',
        'average_quiz_score' => 'decimal:2',
        'final_exam_score' => 'decimal:2',
        'overall_grade' => 'decimal:2',
        'participation_score' => 'decimal:2',
        'last_updated' => 'datetime',
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

    public function enrollment()
    {
        return $this->hasOne(BatchEnrollment::class, 'user_id', 'user_id')
                    ->where('batch_id', $this->batch_id);
    }

    public function lessonProgress()
    {
        return $this->hasMany(BatchLessonProgress::class, 'user_id', 'user_id')
                    ->where('batch_id', $this->batch_id);
    }

    public function assessmentAttempts()
    {
        return $this->hasMany(BatchAssessmentAttempt::class, 'user_id', 'user_id')
                    ->where('batch_id', $this->batch_id);
    }

    // Helper methods
    public function hasPassedFinalExam()
    {
        return $this->final_exam_score && $this->final_exam_score >= 70;
    }

    public function hasGoodAttendance($threshold = 75)
    {
        return $this->attendance_percentage >= $threshold;
    }

    public function hasGoodGrade($threshold = 70)
    {
        return $this->overall_grade >= $threshold;
    }

    public function isTopPerformer($topPercent = 20)
    {
        if (!$this->class_rank) return false;
        
        $totalStudents = BatchStudentPerformance::where('batch_id', $this->batch_id)->count();
        $topRank = ceil($totalStudents * ($topPercent / 100));
        
        return $this->class_rank <= $topRank;
    }

    public function getStudentNameAttribute()
    {
        return $this->user ? $this->user->getFullNameAttribute() : 'Unknown';
    }

    public function getBatchNameAttribute()
    {
        return $this->batch ? $this->batch->batch_name : 'Unknown';
    }

    public function getFormattedOverallGradeAttribute()
    {
        return $this->overall_grade ? $this->overall_grade . '%' : 'N/A';
    }

    public function getFormattedAttendanceAttribute()
    {
        return $this->attendance_percentage ? $this->attendance_percentage . '%' : 'N/A';
    }

    public function getAttendanceRateAttribute()
    {
        if ($this->total_classes == 0) return 0;
        return round(($this->attended_classes / $this->total_classes) * 100, 2);
    }

    public function getAssignmentSubmissionRateAttribute()
    {
        if ($this->assignments_total == 0) return 0;
        return round(($this->assignments_submitted / $this->assignments_total) * 100, 2);
    }

    public function getPerformanceStatusAttribute()
    {
        if ($this->overall_grade >= 85) return 'excellent';
        if ($this->overall_grade >= 70) return 'good';
        if ($this->overall_grade >= 60) return 'satisfactory';
        if ($this->overall_grade >= 50) return 'needs_improvement';
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
        
        $totalStudents = BatchStudentPerformance::where('batch_id', $this->batch_id)->count();
        return $this->class_rank . ' of ' . $totalStudents;
    }

    public function calculateOverallGrade()
    {
        $weights = [
            'attendance' => 20,
            'assignments' => 30,
            'quizzes' => 25,
            'final_exam' => 20,
            'participation' => 5
        ];

        $totalGrade = 0;
        
        // Attendance component
        $totalGrade += ($this->attendance_percentage * $weights['attendance']) / 100;
        
        // Assignment component
        $totalGrade += ($this->average_assignment_score * $weights['assignments']) / 100;
        
        // Quiz component
        $totalGrade += ($this->average_quiz_score * $weights['quizzes']) / 100;
        
        // Final exam component
        $totalGrade += ($this->final_exam_score * $weights['final_exam']) / 100;
        
        // Participation component
        $totalGrade += ($this->participation_score * $weights['participation']) / 100;

        return round($totalGrade, 2);
    }

    public function updateClassRank()
    {
        $rank = BatchStudentPerformance::where('batch_id', $this->batch_id)
                                     ->where('overall_grade', '>', $this->overall_grade)
                                     ->count() + 1;
        
        $this->update(['class_rank' => $rank]);
    }

    public function getGradeLetterAttribute($value)
    {
        if ($value) return $value;
        
        return $this->calculateGradeLetter($this->overall_grade);
    }

    private function calculateGradeLetter($grade)
    {
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

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->orderBy('overall_grade', 'desc')->limit($limit);
    }

    public function scopeByGradeRange($query, $min, $max)
    {
        return $query->whereBetween('overall_grade', [$min, $max]);
    }

    public function scopePassedFinalExam($query)
    {
        return $query->where('final_exam_score', '>=', 70);
    }

    public function scopeGoodAttendance($query, $threshold = 75)
    {
        return $query->where('attendance_percentage', '>=', $threshold);
    }

    public function scopePoorAttendance($query, $threshold = 60)
    {
        return $query->where('attendance_percentage', '<', $threshold);
    }

    public function scopeByGradeLetter($query, $letter)
    {
        return $query->where('grade_letter', $letter);
    }

    public function scopeRankedTop($query, $topCount = 10)
    {
        return $query->where('class_rank', '<=', $topCount);
    }
}