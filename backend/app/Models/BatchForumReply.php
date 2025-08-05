<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchForumReply extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_forum_replies';
    protected $primaryKey = 'reply_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'topic_id',
        'batch_id',
        'parent_reply_id',
        'content',
        'is_solution',
        'like_count',
        'attachment_urls',
        'is_instructor_reply',
        'created_by'
    ];

    protected $casts = [
        'is_solution' => 'boolean',
        'attachment_urls' => 'json',
        'is_instructor_reply' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function topic()
    {
        return $this->belongsTo(BatchForumTopic::class, 'topic_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentReply()
    {
        return $this->belongsTo(BatchForumReply::class, 'parent_reply_id');
    }

    public function childReplies()
    {
        return $this->hasMany(BatchForumReply::class, 'parent_reply_id');
    }

    // Helper methods
    public function isSolution()
    {
        return $this->is_solution;
    }

    public function isInstructorReply()
    {
        return $this->is_instructor_reply;
    }

    public function hasParent()
    {
        return !is_null($this->parent_reply_id);
    }

    public function hasChildren()
    {
        return $this->childReplies()->exists();
    }

    public function hasAttachments()
    {
        return !empty($this->attachment_urls);
    }

    public function getAttachments()
    {
        return $this->attachment_urls ?? [];
    }

    public function incrementLikeCount()
    {
        $this->increment('like_count');
    }

    public function decrementLikeCount()
    {
        $this->decrement('like_count');
    }

    public function markAsSolution()
    {
        // Unmark other solutions in the same topic
        self::where('topic_id', $this->topic_id)
            ->where('reply_id', '!=', $this->reply_id)
            ->update(['is_solution' => false]);

        // Mark this as solution
        $this->update(['is_solution' => true]);

        // Mark topic as solved
        $this->topic->update(['is_solved' => true]);
    }

    public function unmarkAsSolution()
    {
        $this->update(['is_solution' => false]);
        
        // Check if there are other solutions
        $hasSolution = self::where('topic_id', $this->topic_id)
                          ->where('is_solution', true)
                          ->exists();
        
        if (!$hasSolution) {
            $this->topic->update(['is_solved' => false]);
        }
    }

    public function getDepthLevel()
    {
        $depth = 0;
        $parent = $this->parentReply;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parentReply;
        }
        
        return $depth;
    }
}