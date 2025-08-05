<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchEmailQueue extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_email_queue';
    protected $primaryKey = 'queue_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'recipient_email',
        'recipient_user_id',
        'subject',
        'content_html',
        'content_text',
        'template_id',
        'template_data',
        'email_type',
        'status',
        'attempts',
        'max_attempts',
        'scheduled_at',
        'sent_at',
        'error_message'
    ];

    protected $casts = [
        'template_data' => 'json',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function template()
    {
        return $this->belongsTo(BatchNotificationTemplate::class, 'template_id');
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canRetry()
    {
        return $this->attempts < $this->max_attempts && $this->hasFailed();
    }

    public function isScheduled()
    {
        return $this->scheduled_at && $this->scheduled_at > now();
    }

    public function isReadyToSend()
    {
        return $this->isPending() && 
               (!$this->scheduled_at || $this->scheduled_at <= now()) &&
               $this->attempts < $this->max_attempts;
    }

    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1
        ]);
    }

    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    public function getEmailTypeDisplayAttribute()
    {
        return match($this->email_type) {
            'individual' => 'Individual Email',
            'batch_broadcast' => 'Batch Broadcast',
            'class_reminder' => 'Class Reminder',
            'assessment_notification' => 'Assessment Notification',
            default => ucfirst(str_replace('_', ' ', $this->email_type))
        };
    }

    public function getTemplateDataValue($key, $default = null)
    {
        return data_get($this->template_data, $key, $default);
    }
}