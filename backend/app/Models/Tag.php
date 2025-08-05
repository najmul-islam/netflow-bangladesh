<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';
    protected $primaryKey = 'tag_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'color'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_tags', 'tag_id', 'course_id');
    }

    // Helper methods
    public function getCoursesCount()
    {
        return $this->courses()->count();
    }

    public function getHexColorAttribute()
    {
        return $this->color ?? '#3B82F6';
    }

    public function isPopular()
    {
        return $this->getCoursesCount() > 10;
    }
}