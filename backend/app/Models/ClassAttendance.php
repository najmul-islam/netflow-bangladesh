<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ClassAttendance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'class_attendance';
    protected $primaryKey = 'attendance_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'user_id',
        'status',
        'join_time',
        'leave_time',
        'duration_minutes',
        'ip_address',
        'user_agent',
        'notes',
        'marked_by',
        'marked_at',
        'auto_marked'
    ];

    protected $casts = [
        'join_time' => 'datetime',
        'leave_time' => 'datetime',
        'marked_at' => 'datetime',
        'auto_marked' => 'boolean',
    ];

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(BatchSchedule::class, 'schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Helper methods
    public function isPresent()
    {
        return $this->status === 'present';
    }

    public function isAbsent()
    {
        return $this->status === 'absent';
    }

    public function isLate()
    {
        return $this->status === 'late';
    }

    public function isExcused()
    {
        return $this->status === 'excused';
    }

    public function isPartial()
    {
        return $this->status === 'partial';
    }

    public function getAttendanceDurationAttribute()
    {
        if ($this->join_time && $this->leave_time) {
            return $this->join_time->diffInMinutes($this->leave_time);
        }
        return $this->duration_minutes;
    }

    public function getAttendancePercentageAttribute()
    {
        if (!$this->schedule || !$this->join_time || !$this->leave_time) {
            return 0;
        }

        $totalDuration = $this->schedule->start_datetime->diffInMinutes($this->schedule->end_datetime);
        $attendedDuration = $this->getAttendanceDurationAttribute();

        if ($totalDuration <= 0) {
            return 0;
        }

        return min(100, round(($attendedDuration / $totalDuration) * 100, 2));
    }

    public function wasAutoMarked()
    {
        return $this->auto_marked;
    }
}