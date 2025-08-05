<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_schedule';
    protected $primaryKey = 'schedule_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'batch_id',
        'title',
        'description',
        'session_type',
        'start_datetime',
        'end_datetime',
        'timezone',
        'is_mandatory',
        'max_attendees',
        'status',
        'meeting_platform',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'dial_in_number',
        'meeting_room',
        'backup_meeting_url',
        'agenda',
        'prerequisites',
        'materials_needed',
        'recording_url',
        'recording_password',
        'auto_record',
        'send_reminder',
        'reminder_minutes',
        'created_by'
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_mandatory' => 'boolean',
        'auto_record' => 'boolean',
        'send_reminder' => 'boolean',
        'reminder_minutes' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(ClassAttendance::class, 'schedule_id');
    }

    public function presentAttendances()
    {
        return $this->hasMany(ClassAttendance::class, 'schedule_id')
                    ->where('status', 'present');
    }

    public function absentAttendances()
    {
        return $this->hasMany(ClassAttendance::class, 'schedule_id')
                    ->where('status', 'absent');
    }

    public function notifications()
    {
        return $this->hasMany(BatchNotification::class, 'schedule_id');
    }

    // Helper methods
    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function getDurationMinutesAttribute()
    {
        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    public function getAttendancePercentageAttribute()
    {
        $totalAttendees = $this->attendances()->count();
        if ($totalAttendees === 0) return 0;
        
        $presentCount = $this->presentAttendances()->count();
        return round(($presentCount / $totalAttendees) * 100, 2);
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

    public function canJoin()
    {
        return $this->isScheduled() || $this->isInProgress();
    }
}