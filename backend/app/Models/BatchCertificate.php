<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchCertificate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_certificates';
    protected $primaryKey = 'certificate_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'batch_id',
        'template_id',
        'certificate_number',
        'issued_date',
        'expiry_date',
        'verification_code',
        'certificate_url',
        'metadata',
        'is_revoked',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'batch_completion_date',
        'final_grade',
        'attendance_percentage',
        'class_rank',
        'total_students',
        'special_achievements'
    ];

    protected $casts = [
        'issued_date' => 'datetime',
        'expiry_date' => 'datetime',
        'revoked_at' => 'datetime',
        'batch_completion_date' => 'date',
        'final_grade' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'is_revoked' => 'boolean',
        'metadata' => 'json',
        'special_achievements' => 'json',
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

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // Helper methods
    public function isRevoked()
    {
        return $this->is_revoked;
    }

    public function isActive()
    {
        return !$this->is_revoked;
    }

    public function hasExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isValid()
    {
        return $this->isActive() && !$this->hasExpired();
    }

    public function revoke($reason, $revokedBy)
    {
        $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
            'revocation_reason' => $reason
        ]);
    }

    public function restore()
    {
        $this->update([
            'is_revoked' => false,
            'revoked_at' => null,
            'revoked_by' => null,
            'revocation_reason' => null
        ]);
    }

    public function getStudentNameAttribute()
    {
        return $this->user ? $this->user->getFullNameAttribute() : 'Unknown';
    }

    public function getBatchNameAttribute()
    {
        return $this->batch ? $this->batch->batch_name : 'Unknown';
    }

    public function getCourseNameAttribute()
    {
        return $this->batch && $this->batch->course ? $this->batch->course->title : 'Unknown';
    }

    public function getMetadataValue($key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    public function setMetadataValue($key, $value)
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->update(['metadata' => $metadata]);
    }

    public function getSpecialAchievements()
    {
        return $this->special_achievements ?? [];
    }

    public function addSpecialAchievement($achievement)
    {
        $achievements = $this->getSpecialAchievements();
        $achievements[] = $achievement;
        $this->update(['special_achievements' => $achievements]);
    }

    public function getFormattedFinalGradeAttribute()
    {
        return $this->final_grade ? $this->final_grade . '%' : 'N/A';
    }

    public function getFormattedAttendanceAttribute()
    {
        return $this->attendance_percentage ? $this->attendance_percentage . '%' : 'N/A';
    }

    public function getRankDisplayAttribute()
    {
        if (!$this->class_rank || !$this->total_students) return 'N/A';
        return $this->class_rank . ' of ' . $this->total_students;
    }

    public function getVerificationUrl()
    {
        return route('certificates.verify', ['code' => $this->verification_code]);
    }

    public function getDownloadUrl()
    {
        return route('certificates.download', ['certificate' => $this->certificate_id]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_revoked', false);
    }

    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('is_revoked', false)
                    ->where(function($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', now());
                    });
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByVerificationCode($query, $code)
    {
        return $query->where('verification_code', $code);
    }
}