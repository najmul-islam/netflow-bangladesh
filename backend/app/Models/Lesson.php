<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Lesson extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lessons';
    protected $primaryKey = 'lesson_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'module_id',
        'batch_id',
        'title',
        'content_type',
        'content_url',
        'content_text',
        'duration_minutes',
        'sort_order',
        'is_free_preview',
        'is_published',
        'is_batch_specific',
        'scheduled_date',
        'scheduled_time',
        'availability_start',
        'availability_end',
        'settings'
    ];

    protected $casts = [
        'is_free_preview' => 'boolean',
        'is_published' => 'boolean',
        'is_batch_specific' => 'boolean',
        'scheduled_date' => 'date',
        'scheduled_time' => 'time',
        'availability_start' => 'datetime',
        'availability_end' => 'datetime',
        'settings' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function resources()
    {
        return $this->hasMany(LessonResource::class, 'lesson_id');
    }

    public function progress()
    {
        return $this->hasMany(BatchLessonProgress::class, 'lesson_id');
    }

    public function assessments()
    {
        return $this->hasMany(BatchAssessment::class, 'lesson_id');
    }

    // Helper methods
    public function isPublished()
    {
        return $this->is_published;
    }

    public function isFreePreview()
    {
        return $this->is_free_preview;
    }

    public function isBatchSpecific()
    {
        return $this->is_batch_specific;
    }

    public function isAvailable()
    {
        $now = now();
        
        if ($this->availability_start && $now < $this->availability_start) {
            return false;
        }
        
        if ($this->availability_end && $now > $this->availability_end) {
            return false;
        }
        
        return true;
    }

    public function canUserAccess($userId, $batchId = null)
    {
        // Free preview lessons are always accessible
        if ($this->isFreePreview()) {
            return true;
        }

        // Check if user is enrolled in the batch
        if ($batchId) {
            $enrollment = BatchEnrollment::where('user_id', $userId)
                ->where('batch_id', $batchId)
                ->where('status', 'active')
                ->exists();
            
            return $enrollment;
        }

        return false;
    }

    public function getUserProgress($userId, $batchId)
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('batch_id', $batchId)
            ->first();
    }

    public function isCompletedByUser($userId, $batchId)
    {
        $progress = $this->getUserProgress($userId, $batchId);
        return $progress && $progress->status === 'completed';
    }

    public function getFormattedDuration()
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        
        return $minutes . 'm';
    }
}