<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchReview extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_reviews';
    protected $primaryKey = 'review_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'batch_id',
        'user_id',
        'rating',
        'title',
        'content',
        'review_categories',
        'is_approved',
        'is_anonymous',
        'instructor_response',
        'instructor_responded_at',
        'helpful_count'
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_anonymous' => 'boolean',
        'instructor_responded_at' => 'datetime',
        'review_categories' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->hasOneThrough(Course::class, CourseBatch::class, 'batch_id', 'course_id', 'batch_id', 'course_id');
    }

    // Helper methods
    public function isApproved()
    {
        return $this->is_approved;
    }

    public function isPending()
    {
        return !$this->is_approved;
    }

    public function isAnonymous()
    {
        return $this->is_anonymous;
    }

    public function hasInstructorResponse()
    {
        return !empty($this->instructor_response);
    }

    public function approve()
    {
        $this->update(['is_approved' => true]);
    }

    public function reject()
    {
        $this->update(['is_approved' => false]);
    }

    public function addInstructorResponse($response, $instructorId)
    {
        $this->update([
            'instructor_response' => $response,
            'instructor_responded_at' => now(),
            'instructor_responded_by' => $instructorId
        ]);
    }

    public function incrementHelpfulCount()
    {
        $this->increment('helpful_count');
    }

    public function decrementHelpfulCount()
    {
        $this->decrement('helpful_count');
    }

    public function getReviewerNameAttribute()
    {
        if ($this->isAnonymous()) {
            return 'Anonymous';
        }
        
        return $this->user ? $this->user->getFullNameAttribute() : 'Unknown';
    }

    public function getBatchNameAttribute()
    {
        return $this->batch ? $this->batch->batch_name : 'Unknown';
    }

    public function getCourseNameAttribute()
    {
        return $this->course ? $this->course->title : 'Unknown';
    }

    public function getRatingStarsAttribute()
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $this->rating ? '★' : '☆';
        }
        return $stars;
    }

    public function getReviewCategoriesAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setReviewCategoriesAttribute($value)
    {
        $this->attributes['review_categories'] = json_encode($value);
    }

    public function getCategoryRating($category)
    {
        $categories = $this->getReviewCategoriesAttribute($this->attributes['review_categories'] ?? '[]');
        return $categories[$category] ?? null;
    }

    public function setCategoryRating($category, $rating)
    {
        $categories = $this->getReviewCategoriesAttribute($this->attributes['review_categories'] ?? '[]');
        $categories[$category] = $rating;
        $this->setReviewCategoriesAttribute($categories);
    }

    public function getAverageRatingForCategories()
    {
        $categories = $this->getReviewCategoriesAttribute($this->attributes['review_categories'] ?? '[]');
        if (empty($categories)) return $this->rating;
        
        return round(array_sum($categories) / count($categories), 1);
    }

    public function isRecent($days = 7)
    {
        return $this->created_at->diffInDays(now()) <= $days;
    }

    public function isHelpful($threshold = 5)
    {
        return $this->helpful_count >= $threshold;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHighRated($query, $threshold = 4)
    {
        return $query->where('rating', '>=', $threshold);
    }

    public function scopeLowRated($query, $threshold = 3)
    {
        return $query->where('rating', '<=', $threshold);
    }

    public function scopeWithInstructorResponse($query)
    {
        return $query->whereNotNull('instructor_response');
    }

    public function scopeWithoutInstructorResponse($query)
    {
        return $query->whereNull('instructor_response');
    }

    public function scopeAnonymous($query)
    {
        return $query->where('is_anonymous', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_anonymous', false);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeHelpful($query, $threshold = 5)
    {
        return $query->where('helpful_count', '>=', $threshold);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}