<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchForumTopic extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_forum_topics';
    protected $primaryKey = 'topic_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'forum_id',
        'batch_id',
        'title',
        'content',
        'topic_type',
        'is_pinned',
        'is_locked',
        'is_solved',
        'view_count',
        'reply_count',
        'like_count',
        'last_reply_at',
        'last_reply_by',
        'tags',
        'created_by'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_solved' => 'boolean',
        'last_reply_at' => 'datetime',
        'tags' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function forum()
    {
        return $this->belongsTo(BatchForum::class, 'forum_id');
    }

    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastReplyBy()
    {
        return $this->belongsTo(User::class, 'last_reply_by');
    }

    public function replies()
    {
        return $this->hasMany(BatchForumReply::class, 'topic_id')->orderBy('created_at');
    }

    public function solutionReply()
    {
        return $this->hasOne(BatchForumReply::class, 'topic_id')->where('is_solution', true);
    }

    // Helper methods
    public function isPinned()
    {
        return $this->is_pinned;
    }

    public function isLocked()
    {
        return $this->is_locked;
    }

    public function isSolved()
    {
        return $this->is_solved;
    }

    public function isQuestion()
    {
        return $this->topic_type === 'question';
    }

    public function isDiscussion()
    {
        return $this->topic_type === 'discussion';
    }

    public function isAnnouncement()
    {
        return $this->topic_type === 'announcement';
    }

    public function isPoll()
    {
        return $this->topic_type === 'poll';
    }

    public function canUserReply($user)
    {
        return !$this->isLocked();
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function markAsSolved($replyId = null)
    {
        $this->update(['is_solved' => true]);
        
        if ($replyId) {
            BatchForumReply::where('topic_id', $this->topic_id)
                          ->update(['is_solution' => false]);
            
            BatchForumReply::where('reply_id', $replyId)
                          ->update(['is_solution' => true]);
        }
    }

    public function getStatusAttribute()
    {
        if ($this->isPinned()) return 'pinned';
        if ($this->isLocked()) return 'locked';
        if ($this->isSolved()) return 'solved';
        return 'open';
    }

    public function getLastActivityAttribute()
    {
        return $this->last_reply_at ?? $this->created_at;
    }
}