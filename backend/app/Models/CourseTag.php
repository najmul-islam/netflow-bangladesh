<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTag extends Model
{
    use HasFactory;

    protected $table = 'course_tags';
    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'tag_id'
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    // Helper methods
    public function getCourseNameAttribute()
    {
        return $this->course ? $this->course->title : 'Unknown';
    }

    public function getTagNameAttribute()
    {
        return $this->tag ? $this->tag->name : 'Unknown';
    }

    public function getTagColorAttribute()
    {
        return $this->tag ? $this->tag->color : '#3B82F6';
    }

    public function getTagSlugAttribute()
    {
        return $this->tag ? $this->tag->slug : null;
    }

    public function isCoursePublished()
    {
        return $this->course && $this->course->status === 'published';
    }

    public function isCourseFeatured()
    {
        return $this->course && $this->course->featured;
    }

    public function getCourseStatusAttribute()
    {
        return $this->course ? $this->course->status : null;
    }

    public function getCoursePriceAttribute()
    {
        return $this->course ? $this->course->price : 0;
    }

    public function isCourseFreePaidAttribute()
    {
        return $this->course ? $this->course->is_free : true;
    }

    public function getCourseCategoryAttribute()
    {
        return $this->course && $this->course->category ? $this->course->category->name : null;
    }

    public function getTagUsageCount()
    {
        return self::where('tag_id', $this->tag_id)->count();
    }

    public function getCourseTagsCount()
    {
        return self::where('course_id', $this->course_id)->count();
    }

    public function isPopularTag()
    {
        return $this->getTagUsageCount() > 10;
    }

    public function getTagDisplayAttribute()
    {
        $tag = $this->tag;
        if (!$tag) return 'Unknown';
        
        return [
            'name' => $tag->name,
            'slug' => $tag->slug,
            'color' => $tag->color,
            'usage_count' => $this->getTagUsageCount()
        ];
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByTag($query, $tagId)
    {
        return $query->where('tag_id', $tagId);
    }

    public function scopePublishedCourses($query)
    {
        return $query->whereHas('course', function($q) {
            $q->where('status', 'published');
        });
    }

    public function scopeFeaturedCourses($query)
    {
        return $query->whereHas('course', function($q) {
            $q->where('featured', true);
        });
    }

    public function scopeFreeCourses($query)
    {
        return $query->whereHas('course', function($q) {
            $q->where('is_free', true);
        });
    }

    public function scopePaidCourses($query)
    {
        return $query->whereHas('course', function($q) {
            $q->where('is_free', false);
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->whereHas('course', function($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    public function scopePopularTags($query, $threshold = 5)
    {
        return $query->whereHas('tag', function($q) use ($threshold) {
            $q->whereExists(function($subQuery) use ($threshold) {
                $subQuery->selectRaw('COUNT(*)')
                        ->from('course_tags as ct2')
                        ->whereColumn('ct2.tag_id', 'tags.tag_id')
                        ->havingRaw('COUNT(*) >= ?', [$threshold]);
            });
        });
    }
}