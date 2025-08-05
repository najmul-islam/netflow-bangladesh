<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchForum extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_forums';
    protected $primaryKey = 'forum_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'title',
        'description',
        'forum_type',
        'sort_order',
        'is_locked',
        'is_announcement_only',
        'auto_subscribe_students',
        'created_by'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_announcement_only' => 'boolean',
        'auto_subscribe_students' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function topics()
    {
        return $this->hasMany(BatchForumTopic::class, 'forum_id');
    }

    public function activeTopics()
    {
        return $this->hasMany(BatchForumTopic::class, 'forum_id')
                    ->where('is_locked', false);
    }

    public function pinnedTopics()
    {
        return $this->hasMany(BatchForumTopic::class, 'forum_id')
                    ->where('is_pinned', true)
                    ->orderBy('created_at', 'desc');
    }

    public function recentTopics()
    {
        return $this->hasMany(BatchForumTopic::class, 'forum_id')
                    ->orderBy('last_reply_at', 'desc');
    }

    // Helper methods
    public function isGeneral()
    {
        return $this->forum_type === 'general';
    }

    public function isAnnouncements()
    {
        return $this->forum_type === 'announcements';
    }

    public function isQAndA()
    {
        return $this->forum_type === 'q_and_a';
    }

    public function isAssignments()
    {
        return $this->forum_type === 'assignments';
    }

    public function isProjects()
    {
        return $this->forum_type === 'projects';
    }

    public function isSocial()
    {
        return $this->forum_type === 'social';
    }

    public function isLocked()
    {
        return $this->is_locked;
    }

    public function isAnnouncementOnly()
    {
        return $this->is_announcement_only;
    }

    public function autoSubscribesStudents()
    {
        return $this->auto_subscribe_students;
    }

    public function getTypeDisplayAttribute()
    {
        return match($this->forum_type) {
            'general' => 'General Discussion',
            'announcements' => 'Announcements',
            'q_and_a' => 'Q&A',
            'assignments' => 'Assignments',
            'projects' => 'Projects',
            'social' => 'Social',
            default => ucfirst(str_replace('_', ' ', $this->forum_type))
        };
    }

    public function getBatchNameAttribute()
    {
        return $this->batch ? $this->batch->batch_name : 'Unknown Batch';
    }

    public function getCreatorNameAttribute()
    {
        return $this->creator ? $this->creator->getFullNameAttribute() : 'Unknown';
    }

    public function getTopicsCount()
    {
        return $this->topics()->count();
    }

    public function getRepliesCount()
    {
        return $this->topics()->sum('reply_count');
    }

    public function getLastActivity()
    {
        return $this->topics()
                    ->orderBy('last_reply_at', 'desc')
                    ->first()?->last_reply_at;
    }

    public function getLastTopic()
    {
        return $this->topics()
                    ->orderBy('created_at', 'desc')
                    ->first();
    }

    public function getMostActiveTopics($limit = 5)
    {
        return $this->topics()
                    ->orderBy('reply_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public function getRecentActivity($days = 7)
    {
        return $this->topics()
                    ->where('created_at', '>=', now()->subDays($days))
                    ->orWhere('last_reply_at', '>=', now()->subDays($days))
                    ->orderBy('last_reply_at', 'desc')
                    ->get();
    }

    public function canUserPost($user)
    {
        if ($this->isLocked()) {
            return $user->hasRole(['instructor', 'admin']);
        }

        if ($this->isAnnouncementOnly()) {
            return $user->hasRole(['instructor', 'admin']);
        }

        // Check if user is enrolled in the batch
        return BatchEnrollment::where('user_id', $user->user_id)
                             ->where('batch_id', $this->batch_id)
                             ->where('status', 'active')
                             ->exists() || 
               $user->hasRole(['instructor', 'admin']);
    }

    public function canUserModerate($user)
    {
        return $user->hasRole(['instructor', 'admin']) ||
               BatchInstructor::where('user_id', $user->user_id)
                              ->where('batch_id', $this->batch_id)
                              ->where('is_active', true)
                              ->exists();
    }

    public function getIconAttribute()
    {
        return match($this->forum_type) {
            'general' => 'comments',
            'announcements' => 'bullhorn',
            'q_and_a' => 'question-circle',
            'assignments' => 'clipboard-list',
            'projects' => 'project-diagram',
            'social' => 'users',
            default => 'forum'
        };
    }

    public function getColorClassAttribute()
    {
        return match($this->forum_type) {
            'general' => 'blue',
            'announcements' => 'red',
            'q_and_a' => 'green',
            'assignments' => 'yellow',
            'projects' => 'purple',
            'social' => 'pink',
            default => 'gray'
        };
    }

    public function lock()
    {
        $this->update(['is_locked' => true]);
    }

    public function unlock()
    {
        $this->update(['is_locked' => false]);
    }

    public function makeAnnouncementOnly()
    {
        $this->update(['is_announcement_only' => true]);
    }

    public function allowGeneralPosts()
    {
        $this->update(['is_announcement_only' => false]);
    }

    public function getActivitySummary($days = 30)
    {
        $topics = $this->topics()
                       ->where('created_at', '>=', now()->subDays($days))
                       ->count();

        $replies = BatchForumReply::whereHas('topic', function($q) {
                        $q->where('forum_id', $this->forum_id);
                    })
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count();

        return [
            'new_topics' => $topics,
            'new_replies' => $replies,
            'total_posts' => $topics + $replies,
            'active_users' => $this->getActiveUsersCount($days)
        ];
    }

    public function getActiveUsersCount($days = 30)
    {
        $topicUsers = $this->topics()
                           ->where('created_at', '>=', now()->subDays($days))
                           ->distinct('created_by')
                           ->count('created_by');

        $replyUsers = BatchForumReply::whereHas('topic', function($q) {
                         $q->where('forum_id', $this->forum_id);
                     })
                     ->where('created_at', '>=', now()->subDays($days))
                     ->distinct('created_by')
                     ->count('created_by');

        return $topicUsers + $replyUsers;
    }

    public function getTopContributors($limit = 5)
    {
        // Get users with most topics + replies
        $users = User::select('users.*')
                     ->selectRaw('COUNT(DISTINCT bft.topic_id) + COUNT(DISTINCT bfr.reply_id) as total_posts')
                     ->leftJoin('batch_forum_topics as bft', function($join) {
                         $join->on('users.user_id', '=', 'bft.created_by')
                              ->where('bft.forum_id', $this->forum_id);
                     })
                     ->leftJoin('batch_forum_replies as bfr', function($join) {
                         $join->on('users.user_id', '=', 'bfr.created_by')
                              ->whereExists(function($query) {
                                  $query->select('1')
                                        ->from('batch_forum_topics as bft2')
                                        ->whereColumn('bft2.topic_id', 'bfr.topic_id')
                                        ->where('bft2.forum_id', $this->forum_id);
                              });
                     })
                     ->groupBy('users.user_id')
                     ->having('total_posts', '>', 0)
                     ->orderBy('total_posts', 'desc')
                     ->limit($limit)
                     ->get();

        return $users;
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('forum_type', $type);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeAnnouncementOnly($query)
    {
        return $query->where('is_announcement_only', true);
    }

    public function scopeOpenForDiscussion($query)
    {
        return $query->where('is_announcement_only', false);
    }

    public function scopeOrderBySort($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeWithActivity($query)
    {
        return $query->withCount(['topics', 'topics as replies_count' => function($q) {
            $q->select(\DB::raw('COALESCE(SUM(reply_count), 0)'));
        }]);
    }
}