<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Module extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'modules';
    protected $primaryKey = 'module_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'course_id',
        'batch_id',
        'title',
        'description',
        'sort_order',
        'is_published',
        'is_batch_specific'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_batch_specific' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('sort_order');
    }

    public function publishedLessons()
    {
        return $this->hasMany(Lesson::class, 'module_id')
                    ->where('is_published', true)
                    ->orderBy('sort_order');
    }

    // Helper methods
    public function isPublished()
    {
        return $this->is_published;
    }

    public function isBatchSpecific()
    {
        return $this->is_batch_specific;
    }

    public function getTotalDurationMinutes()
    {
        return $this->lessons()->sum('duration_minutes');
    }

    public function getTotalLessonsCount()
    {
        return $this->lessons()->count();
    }

    public function getPublishedLessonsCount()
    {
        return $this->publishedLessons()->count();
    }

    public function hasLessons()
    {
        return $this->lessons()->exists();
    }

    public function getProgressPercentage($userId, $batchId = null)
    {
        $totalLessons = $this->publishedLessons()->count();
        if ($totalLessons === 0) return 0;

        $completedLessons = $this->lessons()
            ->whereHas('progress', function($q) use ($userId, $batchId) {
                $q->where('user_id', $userId)
                  ->where('status', 'completed');
                if ($batchId) {
                    $q->where('batch_id', $batchId);
                }
            })->count();

        return round(($completedLessons / $totalLessons) * 100, 2);
    }
}