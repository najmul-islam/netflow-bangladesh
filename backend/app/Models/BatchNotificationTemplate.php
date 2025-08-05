<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchNotificationTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_notification_templates';
    protected $primaryKey = 'template_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'subject',
        'content_html',
        'content_text',
        'template_type',
        'variables',
        'is_active'
    ];

    protected $casts = [
        'variables' => 'json',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function emailQueue()
    {
        return $this->hasMany(BatchEmailQueue::class, 'template_id');
    }

    public function sentEmails()
    {
        return $this->hasMany(BatchEmailQueue::class, 'template_id')
                    ->where('status', 'sent');
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isBatchEnrollmentTemplate()
    {
        return $this->template_type === 'batch_enrollment';
    }

    public function isClassReminderTemplate()
    {
        return $this->template_type === 'class_reminder';
    }

    public function isAssignmentDueTemplate()
    {
        return $this->template_type === 'assignment_due';
    }

    public function isGradeReleasedTemplate()
    {
        return $this->template_type === 'grade_released';
    }

    public function isCertificateIssuedTemplate()
    {
        return $this->template_type === 'certificate_issued';
    }

    public function isBatchAnnouncementTemplate()
    {
        return $this->template_type === 'batch_announcement';
    }

    public function isAttendanceWarningTemplate()
    {
        return $this->template_type === 'attendance_warning';
    }

    public function getAvailableVariables()
    {
        return $this->variables ?? [];
    }

    public function hasVariable($variable)
    {
        return in_array($variable, $this->getAvailableVariables());
    }

    public function getTemplateTypeDisplayAttribute()
    {
        return match($this->template_type) {
            'batch_enrollment' => 'Batch Enrollment',
            'class_reminder' => 'Class Reminder',
            'assignment_due' => 'Assignment Due',
            'grade_released' => 'Grade Released',
            'certificate_issued' => 'Certificate Issued',
            'batch_announcement' => 'Batch Announcement',
            'attendance_warning' => 'Attendance Warning',
            default => ucfirst(str_replace('_', ' ', $this->template_type))
        };
    }

    public function renderContent($data, $format = 'html')
    {
        $content = $format === 'html' ? $this->content_html : $this->content_text;
        
        foreach ($data as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }
        
        return $content;
    }

    public function renderSubject($data)
    {
        $subject = $this->subject;
        
        foreach ($data as $key => $value) {
            $subject = str_replace('{{'.$key.'}}', $value, $subject);
        }
        
        return $subject;
    }

    public function validateTemplate()
    {
        $errors = [];
        $requiredVars = $this->getRequiredVariables();
        
        foreach ($requiredVars as $var) {
            if (!str_contains($this->content_html, '{{'.$var.'}}')) {
                $errors[] = "Missing required variable in HTML content: {$var}";
            }
            
            if (!str_contains($this->content_text, '{{'.$var.'}}')) {
                $errors[] = "Missing required variable in text content: {$var}";
            }
        }

        return empty($errors) ? true : $errors;
    }

    public function getRequiredVariables()
    {
        return match($this->template_type) {
            'batch_enrollment' => ['batch_name', 'batch_code', 'course_title', 'student_name', 'start_date'],
            'class_reminder' => ['student_name', 'class_title', 'time_until', 'class_date', 'meeting_url'],
            'assignment_due' => ['student_name', 'assignment_title', 'due_date', 'batch_name'],
            'grade_released' => ['student_name', 'assessment_title', 'grade', 'batch_name'],
            'certificate_issued' => ['student_name', 'batch_name', 'course_title', 'final_grade', 'certificate_url'],
            'batch_announcement' => ['student_name', 'batch_name', 'announcement_title', 'announcement_content'],
            'attendance_warning' => ['student_name', 'batch_name', 'attendance_percentage', 'required_percentage'],
            default => ['student_name', 'batch_name']
        };
    }

    public function getUsageCount()
    {
        return $this->emailQueue()->count();
    }

    public function getSentCount()
    {
        return $this->sentEmails()->count();
    }

    public function getSuccessRate()
    {
        $total = $this->getUsageCount();
        if ($total === 0) return 0;
        
        $sent = $this->getSentCount();
        return round(($sent / $total) * 100, 2);
    }

    public function duplicate($newName = null)
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName ?? $this->name . ' (Copy)';
        $newTemplate->save();
        
        return $newTemplate;
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopePopular($query, $threshold = 10)
    {
        return $query->whereHas('emailQueue', function($q) use ($threshold) {
            $q->selectRaw('COUNT(*)')
              ->havingRaw('COUNT(*) >= ?', [$threshold]);
        });
    }
}