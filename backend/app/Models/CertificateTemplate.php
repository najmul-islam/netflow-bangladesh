<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CertificateTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'certificate_templates';
    protected $primaryKey = 'template_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'template_html',
        'template_css',
        'variables',
        'is_active',
        'is_batch_specific',
        'created_by'
    ];

    protected $casts = [
        'variables' => 'json',
        'is_active' => 'boolean',
        'is_batch_specific' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'certificate_template_id');
    }

    public function certificates()
    {
        return $this->hasMany(BatchCertificate::class, 'template_id');
    }

    public function activeCourses()
    {
        return $this->hasMany(Course::class, 'certificate_template_id')
                    ->where('status', 'published');
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isBatchSpecific()
    {
        return $this->is_batch_specific;
    }

    public function getAvailableVariables()
    {
        return $this->variables ?? [];
    }

    public function hasVariable($variable)
    {
        return in_array($variable, $this->getAvailableVariables());
    }

    public function getRequiredVariables()
    {
        $requiredVars = [
            'student_name',
            'course_title',
            'completion_date',
            'certificate_number',
            'verification_code'
        ];

        if ($this->isBatchSpecific()) {
            $requiredVars = array_merge($requiredVars, [
                'batch_name',
                'batch_code',
                'final_grade',
                'class_rank',
                'total_students'
            ]);
        }

        return $requiredVars;
    }

    public function validateTemplate()
    {
        $errors = [];
        $requiredVars = $this->getRequiredVariables();
        
        foreach ($requiredVars as $var) {
            if (!str_contains($this->template_html, '{{'.$var.'}}')) {
                $errors[] = "Missing required variable: {$var}";
            }
        }

        return empty($errors) ? true : $errors;
    }

    public function renderCertificate($data)
    {
        $html = $this->template_html;
        $css = $this->template_css;

        // Replace variables in HTML
        foreach ($data as $key => $value) {
            $html = str_replace('{{'.$key.'}}', $value, $html);
        }

        // Combine HTML with CSS
        $fullHtml = "<style>{$css}</style>" . $html;

        return $fullHtml;
    }

    public function getUsageCount()
    {
        return $this->certificates()->count();
    }

    public function getActiveCourseCount()
    {
        return $this->activeCourses()->count();
    }

    public function canBeDeleted()
    {
        return $this->getUsageCount() === 0 && $this->getActiveCourseCount() === 0;
    }

    public function duplicate($newName = null)
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName ?? $this->name . ' (Copy)';
        $newTemplate->save();
        
        return $newTemplate;
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function getPreviewData()
    {
        $sampleData = [
            'student_name' => 'John Doe',
            'course_title' => 'Sample Course Title',
            'completion_date' => now()->format('F j, Y'),
            'certificate_number' => 'CERT-2025-123456',
            'verification_code' => 'VERIFY123456',
        ];

        if ($this->isBatchSpecific()) {
            $sampleData = array_merge($sampleData, [
                'batch_name' => 'Sample Batch January 2025',
                'batch_code' => 'BATCH-JAN-2025',
                'final_grade' => '85.50',
                'class_rank' => '5',
                'total_students' => '25',
                'attendance_percentage' => '92.5'
            ]);
        }

        return $sampleData;
    }

    public function getCreatorNameAttribute()
    {
        return $this->creator ? $this->creator->getFullNameAttribute() : 'Unknown';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeBatchSpecific($query)
    {
        return $query->where('is_batch_specific', true);
    }

    public function scopeGeneral($query)
    {
        return $query->where('is_batch_specific', false);
    }

    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopePopular($query, $threshold = 10)
    {
        return $query->whereHas('certificates', function($q) use ($threshold) {
            $q->selectRaw('COUNT(*)')
              ->havingRaw('COUNT(*) >= ?', [$threshold]);
        });
    }
}