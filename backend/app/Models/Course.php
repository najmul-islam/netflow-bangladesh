<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Course extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'courses';
    protected $primaryKey = 'course_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'thumbnail_url',
        'trailer_video_url',
        'category_id',
        'difficulty_level',
        'estimated_duration_hours',
        'language',
        'price',
        'currency',
        'is_free',
        'max_enrollments',
        'prerequisites',
        'learning_objectives',
        'status',
        'featured',
        'has_certificate',
        'certificate_template_id',
        'enable_batches',
        'max_batch_size',
        'min_batch_size',
        'auto_create_batches',
        'batch_creation_criteria',
        'batch_start_interval_days',
        'created_by',
        'published_at'
    ];

    protected $casts = [
        'estimated_duration_hours' => 'decimal:2',
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'learning_objectives' => 'json',
        'featured' => 'boolean',
        'has_certificate' => 'boolean',
        'enable_batches' => 'boolean',
        'auto_create_batches' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function batches()
    {
        return $this->hasMany(CourseBatch::class, 'course_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class, 'course_id')->orderBy('sort_order');
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'course_instructors', 'course_id', 'user_id')
                    ->withPivot('role', 'assigned_at');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'course_tags', 'course_id', 'tag_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function certificateTemplate()
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }

    // Helper methods
    public function isPublished()
    {
        return $this->status === 'published';
    }

    public function isPaid()
    {
        return !$this->is_free && $this->price > 0;
    }

    public function hasBatchesEnabled()
    {
        return $this->enable_batches;
    }
}