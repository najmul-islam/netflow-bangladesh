<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchEnrollment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_enrollments';
    protected $primaryKey = 'enrollment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'batch_id',
        'enrollment_date',
        'completion_date',
        'progress_percentage',
        'last_accessed',
        'status',
        'attendance_percentage',
        'final_exam_passed',
        'final_exam_score',
        'certificate_issued',
        'certificate_issued_at',
        'enrolled_by',
        'transfer_from_batch_id',
        'transfer_reason',
        'transfer_date',
        'payment_status'
    ];

    protected $casts = [
        'enrollment_date' => 'datetime',
        'completion_date' => 'datetime',
        'progress_percentage' => 'decimal:2',
        'last_accessed' => 'datetime',
        'attendance_percentage' => 'decimal:2',
        'final_exam_passed' => 'boolean',
        'final_exam_score' => 'decimal:2',
        'certificate_issued' => 'boolean',
        'certificate_issued_at' => 'datetime',
        'transfer_date' => 'datetime',
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

    public function enrolledBy()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function transferFromBatch()
    {
        return $this->belongsTo(CourseBatch::class, 'transfer_from_batch_id');
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

    public function performance()
    {
        return $this->hasOne(BatchStudentPerformance::class, 'user_id', 'user_id')
                    ->where('batch_id', $this->batch_id);
    }

    public function certificate()
    {
        return $this->hasOne(BatchCertificate::class, 'user_id', 'user_id')
                    ->where('batch_id', $this->batch_id);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function hasPassedFinalExam()
    {
        return $this->final_exam_passed;
    }

    public function hasCertificate()
    {
        return $this->certificate_issued;
    }

    public function getProgressPercentageAttribute($value)
    {
        return round($value, 2);
    }

    public function isTransferred()
    {
        return $this->status === 'transferred';
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }
}