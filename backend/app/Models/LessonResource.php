<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LessonResource extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lesson_resources';
    protected $primaryKey = 'resource_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'lesson_id',
        'batch_id',
        'title',
        'file_url',
        'file_type',
        'file_size',
        'is_free',
        'is_batch_specific',
        'download_count',
        'access_restrictions'
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_batch_specific' => 'boolean',
        'access_restrictions' => 'json',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    // Helper methods
    public function isFree()
    {
        return $this->is_free;
    }

    public function isBatchSpecific()
    {
        return $this->is_batch_specific;
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    public function getFileSizeFormatted()
    {
        if (!$this->file_size) return 'Unknown';

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function canUserAccess($userId, $batchId = null)
    {
        // Free resources are always accessible
        if ($this->isFree()) {
            return true;
        }

        // Check batch-specific access
        if ($this->isBatchSpecific() && $batchId) {
            $enrollment = BatchEnrollment::where('user_id', $userId)
                ->where('batch_id', $batchId)
                ->where('status', 'active')
                ->exists();
            
            return $enrollment;
        }

        // Check access restrictions
        if ($this->access_restrictions) {
            // Implement custom access logic based on restrictions
            return true; // Placeholder
        }

        return true;
    }

    public function getFileExtension()
    {
        return strtoupper(pathinfo($this->file_url, PATHINFO_EXTENSION));
    }

    public function isVideo()
    {
        return in_array($this->file_type, ['video/mp4', 'video/avi', 'video/mov']);
    }

    public function isDocument()
    {
        return in_array($this->file_type, ['application/pdf', 'application/msword', 'text/plain']);
    }

    public function isImage()
    {
        return str_starts_with($this->file_type, 'image/');
    }
}