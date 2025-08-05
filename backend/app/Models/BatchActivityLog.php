<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_activity_logs';
    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'batch_id',
        'lesson_id',
        'schedule_id',
        'activity_type',
        'ip_address',
        'user_agent',
        'session_id',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
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

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function schedule()
    {
        return $this->belongsTo(BatchSchedule::class, 'schedule_id');
    }

    // Helper methods
    public function isLoginActivity()
    {
        return $this->activity_type === 'login';
    }

    public function isLogoutActivity()
    {
        return $this->activity_type === 'logout';
    }

    public function isBatchAccessActivity()
    {
        return $this->activity_type === 'batch_access';
    }

    public function isLessonActivity()
    {
        return in_array($this->activity_type, ['lesson_start', 'lesson_complete']);
    }

    public function isAssessmentActivity()
    {
        return in_array($this->activity_type, ['assessment_start', 'assessment_submit']);
    }

    public function isForumActivity()
    {
        return $this->activity_type === 'forum_post';
    }

    public function isDownloadActivity()
    {
        return $this->activity_type === 'download';
    }

    public function isCertificateDownload()
    {
        return $this->activity_type === 'certificate_download';
    }

    public function isClassActivity()
    {
        return in_array($this->activity_type, ['class_join', 'class_leave']);
    }

    public function isMessageActivity()
    {
        return $this->activity_type === 'message_send';
    }

    public function hasMetadata()
    {
        return !empty($this->metadata);
    }

    public function getActivityTypeDisplayAttribute()
    {
        return match($this->activity_type) {
            'login' => 'User Login',
            'logout' => 'User Logout',
            'batch_access' => 'Batch Access',
            'lesson_start' => 'Lesson Started',
            'lesson_complete' => 'Lesson Completed',
            'assessment_start' => 'Assessment Started',
            'assessment_submit' => 'Assessment Submitted',
            'forum_post' => 'Forum Post',
            'download' => 'File Download',
            'certificate_download' => 'Certificate Download',
            'class_join' => 'Joined Class',
            'class_leave' => 'Left Class',
            'message_send' => 'Message Sent',
            default => ucfirst(str_replace('_', ' ', $this->activity_type))
        };
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->getFullNameAttribute() : 'Unknown User';
    }

    public function getBatchNameAttribute()
    {
        return $this->batch ? $this->batch->batch_name : 'Unknown Batch';
    }

    public function getLessonTitleAttribute()
    {
        return $this->lesson ? $this->lesson->title : null;
    }

    public function getScheduleTitleAttribute()
    {
        return $this->schedule ? $this->schedule->title : null;
    }

    public function getMetadataValue($key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    public function getBrowserInfo()
    {
        if (!$this->user_agent) return 'Unknown';
        
        // Simple browser detection
        if (str_contains($this->user_agent, 'Chrome')) return 'Chrome';
        if (str_contains($this->user_agent, 'Firefox')) return 'Firefox';
        if (str_contains($this->user_agent, 'Safari')) return 'Safari';
        if (str_contains($this->user_agent, 'Edge')) return 'Edge';
        
        return 'Other';
    }

    public function getOsInfo()
    {
        if (!$this->user_agent) return 'Unknown';
        
        // Simple OS detection
        if (str_contains($this->user_agent, 'Windows')) return 'Windows';
        if (str_contains($this->user_agent, 'Mac')) return 'macOS';
        if (str_contains($this->user_agent, 'Linux')) return 'Linux';
        if (str_contains($this->user_agent, 'Android')) return 'Android';
        if (str_contains($this->user_agent, 'iOS')) return 'iOS';
        
        return 'Other';
    }

    public function isRecentActivity($minutes = 30)
    {
        return $this->created_at && $this->created_at->diffInMinutes(now()) <= $minutes;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : 'Unknown';
    }

    public function getActivityDuration()
    {
        // Calculate duration for paired activities (like class_join/class_leave)
        if ($this->activity_type === 'class_leave') {
            $joinLog = self::where('user_id', $this->user_id)
                          ->where('schedule_id', $this->schedule_id)
                          ->where('activity_type', 'class_join')
                          ->where('created_at', '<', $this->created_at)
                          ->orderBy('created_at', 'desc')
                          ->first();
            
            if ($joinLog) {
                return $this->created_at->diffInMinutes($joinLog->created_at);
            }
        }
        
        return null;
    }

    public function getLocationInfo()
    {
        // This would typically integrate with IP geolocation service
        return $this->getMetadataValue('location', 'Unknown Location');
    }

    public function isSecuritySensitive()
    {
        return in_array($this->activity_type, [
            'login', 'logout', 'certificate_download', 'assessment_submit'
        ]);
    }

    public function getIconAttribute()
    {
        return match($this->activity_type) {
            'login' => 'sign-in-alt',
            'logout' => 'sign-out-alt',
            'batch_access' => 'users',
            'lesson_start', 'lesson_complete' => 'play-circle',
            'assessment_start', 'assessment_submit' => 'clipboard-check',
            'forum_post' => 'comments',
            'download' => 'download',
            'certificate_download' => 'award',
            'class_join', 'class_leave' => 'video',
            'message_send' => 'envelope',
            default => 'info-circle'
        };
    }

    public function getColorClassAttribute()
    {
        return match($this->activity_type) {
            'login' => 'green',
            'logout' => 'red',
            'lesson_complete', 'assessment_submit' => 'blue',
            'certificate_download' => 'yellow',
            'download' => 'purple',
            default => 'gray'
        };
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByActivityType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeRecent($query, $minutes = 30)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeLoginActivities($query)
    {
        return $query->whereIn('activity_type', ['login', 'logout']);
    }

    public function scopeLearningActivities($query)
    {
        return $query->whereIn('activity_type', [
            'lesson_start', 'lesson_complete', 'assessment_start', 'assessment_submit'
        ]);
    }

    public function scopeClassActivities($query)
    {
        return $query->whereIn('activity_type', ['class_join', 'class_leave']);
    }

    public function scopeSecuritySensitive($query)
    {
        return $query->whereIn('activity_type', [
            'login', 'logout', 'certificate_download', 'assessment_submit'
        ]);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeWithUserAndBatch($query)
    {
        return $query->with(['user', 'batch']);
    }
}