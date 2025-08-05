<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchClassScheduleView extends Model
{
    use HasFactory;

    protected $table = 'batch_class_schedule_view';
    public $timestamps = false;

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'attendance_percentage' => 'decimal:2',
    ];

    // Helper methods
    public function isScheduled()
    {
        return $this->class_status === 'scheduled';
    }

    public function isInProgress()
    {
        return $this->class_status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->class_status === 'completed';
    }

    public function isCancelled()
    {
        return $this->class_status === 'cancelled';
    }

    public function isPostponed()
    {
        return $this->class_status === 'postponed';
    }

    public function getSessionTypeDisplayAttribute()
    {
        return match($this->session_type) {
            'live_class' => 'Live Class',
            'lab_session' => 'Lab Session',
            'exam' => 'Exam',
            'workshop' => 'Workshop',
            'review' => 'Review Session',
            'project_presentation' => 'Project Presentation',
            'guest_lecture' => 'Guest Lecture',
            default => ucfirst(str_replace('_', ' ', $this->session_type))
        };
    }

    public function getClassStatusDisplayAttribute()
    {
        return match($this->class_status) {
            'scheduled' => 'Scheduled',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'postponed' => 'Postponed',
            default => ucfirst(str_replace('_', ' ', $this->class_status))
        };
    }

    public function getMeetingPlatformDisplayAttribute()
    {
        return match($this->meeting_platform) {
            'zoom' => 'Zoom',
            'google_meet' => 'Google Meet',
            'microsoft_teams' => 'Microsoft Teams',
            'webex' => 'Webex',
            'custom' => 'Custom Platform',
            'offline' => 'Offline',
            default => ucfirst(str_replace('_', ' ', $this->meeting_platform))
        };
    }

    public function getFormattedDurationAttribute()
    {
        if ($this->duration_minutes < 60) {
            return $this->duration_minutes . ' minutes';
        }
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
    }

    public function getInstructorFullNameAttribute()
    {
        if (!$this->instructor_first_name || !$this->instructor_last_name) {
            return 'TBD';
        }
        
        return $this->instructor_first_name . ' ' . $this->instructor_last_name;
    }

    public function getAttendanceRateDisplayAttribute()
    {
        return round($this->attendance_percentage, 1) . '%';
    }

    public function hasGoodAttendance($threshold = 75)
    {
        return $this->attendance_percentage >= $threshold;
    }

    public function hasPoorAttendance($threshold = 50)
    {
        return $this->attendance_percentage < $threshold;
    }

    public function hasStarted()
    {
        return now() >= $this->start_datetime;
    }

    public function hasEnded()
    {
        return now() >= $this->end_datetime;
    }

    public function isLive()
    {
        return $this->hasStarted() && !$this->hasEnded() && $this->isInProgress();
    }

    public function isUpcoming($hours = 24)
    {
        return $this->start_datetime->isFuture() && 
               $this->start_datetime->diffInHours(now()) <= $hours;
    }

    public function isPast()
    {
        return $this->end_datetime->isPast();
    }

    public function canJoin()
    {
        return $this->isScheduled() || $this->isInProgress();
    }

    public function getJoinUrl()
    {
        return $this->meeting_url;
    }

    public function getMeetingCredentials()
    {
        return [
            'url' => $this->meeting_url,
            'id' => $this->meeting_id,
            'password' => $this->meeting_password,
            'platform' => $this->meeting_platform
        ];
    }

    public function scopeScheduled($query)
    {
        return $query->where('class_status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('class_status', 'completed');
    }

    public function scopeUpcoming($query, $hours = 24)
    {
        return $query->where('start_datetime', '>', now())
                    ->where('start_datetime', '<=', now()->addHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_datetime', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('start_datetime', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeBySessionType($query, $type)
    {
        return $query->where('session_type', $type);
    }

    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeLive($query)
    {
        return $query->where('start_datetime', '<=', now())
                    ->where('end_datetime', '>=', now())
                    ->where('class_status', 'in_progress');
    }

    public function scopeWithGoodAttendance($query, $threshold = 75)
    {
        return $query->where('attendance_percentage', '>=', $threshold);
    }

    public function scopeWithPoorAttendance($query, $threshold = 50)
    {
        return $query->where('attendance_percentage', '<', $threshold);
    }
}