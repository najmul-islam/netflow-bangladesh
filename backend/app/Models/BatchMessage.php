<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchMessage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_messages';
    protected $primaryKey = 'message_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'sender_id',
        'recipient_id',
        'subject',
        'content',
        'message_type',
        'is_read',
        'read_at',
        'parent_message_id',
        'attachment_urls',
        'is_system_message'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'attachment_urls' => 'json',
        'is_system_message' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(CourseBatch::class, 'batch_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function parentMessage()
    {
        return $this->belongsTo(BatchMessage::class, 'parent_message_id');
    }

    public function replies()
    {
        return $this->hasMany(BatchMessage::class, 'parent_message_id')->orderBy('created_at');
    }

    public function allReplies()
    {
        return $this->replies()->with('allReplies');
    }

    // Helper methods
    public function isRead()
    {
        return $this->is_read;
    }

    public function isUnread()
    {
        return !$this->is_read;
    }

    public function isSystemMessage()
    {
        return $this->is_system_message;
    }

    public function isDirectMessage()
    {
        return $this->message_type === 'direct';
    }

    public function isAnnouncement()
    {
        return $this->message_type === 'announcement';
    }

    public function isAssignmentFeedback()
    {
        return $this->message_type === 'assignment_feedback';
    }

    public function isGradeNotification()
    {
        return $this->message_type === 'grade_notification';
    }

    public function hasSubject()
    {
        return !empty($this->subject);
    }

    public function hasParent()
    {
        return !is_null($this->parent_message_id);
    }

    public function hasReplies()
    {
        return $this->replies()->exists();
    }

    public function hasAttachments()
    {
        return !empty($this->attachment_urls);
    }

    public function getTypeDisplayAttribute()
    {
        return match($this->message_type) {
            'direct' => 'Direct Message',
            'announcement' => 'Announcement',
            'assignment_feedback' => 'Assignment Feedback',
            'grade_notification' => 'Grade Notification',
            default => ucfirst(str_replace('_', ' ', $this->message_type))
        };
    }

    public function getSenderNameAttribute()
    {
        if ($this->isSystemMessage()) {
            return 'System';
        }
        
        return $this->sender ? $this->sender->getFullNameAttribute() : 'Unknown';
    }

    public function getRecipientNameAttribute()
    {
        return $this->recipient ? $this->recipient->getFullNameAttribute() : 'Unknown';
    }

    public function getAttachmentsArray()
    {
        return $this->attachment_urls ?? [];
    }

    public function getAttachmentsCount()
    {
        return count($this->getAttachmentsArray());
    }

    public function getThreadDepth()
    {
        $depth = 0;
        $parent = $this->parentMessage;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parentMessage;
        }
        
        return $depth;
    }

    public function getRootMessage()
    {
        $message = $this;
        
        while ($message->hasParent()) {
            $message = $message->parentMessage;
        }
        
        return $message;
    }

    public function getTotalRepliesCount()
    {
        return $this->getAllDescendantsCount();
    }

    private function getAllDescendantsCount()
    {
        $count = $this->replies()->count();
        
        foreach ($this->replies as $reply) {
            $count += $reply->getAllDescendantsCount();
        }
        
        return $count;
    }

    public function getLastReply()
    {
        return $this->replies()->latest('created_at')->first();
    }

    public function getLastActivity()
    {
        $lastReply = $this->getLastReply();
        
        if (!$lastReply) {
            return $this->created_at;
        }
        
        return $lastReply->created_at > $this->created_at ? $lastReply->created_at : $this->created_at;
    }

    public function markAsRead()
    {
        if (!$this->isRead()) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }

    public function markAsUnread()
    {
        if ($this->isRead()) {
            $this->update([
                'is_read' => false,
                'read_at' => null
            ]);
        }
    }

    public function addAttachment($filename, $url, $size = null, $mimeType = null)
    {
        $attachments = $this->getAttachmentsArray();
        
        $attachments[] = [
            'filename' => $filename,
            'url' => $url,
            'size' => $size,
            'mime_type' => $mimeType,
            'uploaded_at' => now()->toISOString()
        ];
        
        $this->update(['attachment_urls' => $attachments]);
    }

    public function removeAttachment($filename)
    {
        $attachments = $this->getAttachmentsArray();
        $attachments = array_filter($attachments, function($attachment) use ($filename) {
            return $attachment['filename'] !== $filename;
        });
        
        $this->update(['attachment_urls' => array_values($attachments)]);
    }

    public function canUserReply($user)
    {
        // System messages usually can't be replied to
        if ($this->isSystemMessage()) {
            return false;
        }
        
        // Announcements might have restricted replies
        if ($this->isAnnouncement()) {
            return $user->hasRole('instructor') || $user->hasRole('admin');
        }
        
        // Check if user is involved in the conversation
        return $user->user_id === $this->sender_id || 
               $user->user_id === $this->recipient_id ||
               $user->hasRole('instructor') || 
               $user->hasRole('admin');
    }

    public function canUserEdit($user)
    {
        // Only sender can edit their own messages
        return $user->user_id === $this->sender_id && !$this->hasReplies();
    }

    public function canUserDelete($user)
    {
        // Sender can delete, or admin/instructor
        return $user->user_id === $this->sender_id || 
               $user->hasRole('instructor') || 
               $user->hasRole('admin');
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeBySender($query, $userId)
    {
        return $query->where('sender_id', $userId);
    }

    public function scopeByRecipient($query, $userId)
    {
        return $query->where('recipient_id', $userId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('sender_id', $userId)->orWhere('recipient_id', $userId);
        });
    }

    public function scopeSystemMessages($query)
    {
        return $query->where('is_system_message', true);
    }

    public function scopeUserMessages($query)
    {
        return $query->where('is_system_message', false);
    }

    public function scopeThreads($query)
    {
        return $query->whereNull('parent_message_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_message_id');
    }

    public function scopeWithAttachments($query)
    {
        return $query->whereNotNull('attachment_urls');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeAnnouncements($query)
    {
        return $query->where('message_type', 'announcement');
    }
}