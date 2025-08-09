<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchForum;
use App\Models\BatchForumTopic;
use App\Models\BatchForumReply;
use App\Models\BatchEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ForumController extends Controller
{
    /**
     * Get forums for user's enrolled batches
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForums()
    {
        try {
            $user = Auth::user();
            
            // Get user's enrolled batches
            $enrolledBatchIds = BatchEnrollment::where('user_id', $user->user_id)
                ->where('status', 'active')
                ->pluck('batch_id');
            
            $forums = BatchForum::whereIn('batch_id', $enrolledBatchIds)
                ->where('is_active', true)
                ->with(['batch.course'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $forums,
                'message' => 'Forums retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve forums',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get forum topics
     *
     * @param string $forumId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForumTopics($forumId)
    {
        try {
            $user = Auth::user();
            
            // Verify user has access to this forum
            $forum = BatchForum::findOrFail($forumId);
            $hasAccess = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $forum->batch_id)
                ->where('status', 'active')
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this forum'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $topics = BatchForumTopic::where('forum_id', $forumId)
                ->with(['user', 'replies.user'])
                ->orderBy('is_pinned', 'desc')
                ->orderBy('last_activity_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $topics,
                'message' => 'Forum topics retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve forum topics',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new forum topic
     *
     * @param \Illuminate\Http\Request $request
     * @param string $forumId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTopic(Request $request, $forumId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string|min:10',
                'is_pinned' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();
            
            // Verify user has access to this forum
            $forum = BatchForum::findOrFail($forumId);
            $hasAccess = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $forum->batch_id)
                ->where('status', 'active')
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this forum'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $topic = BatchForumTopic::create([
                'forum_id' => $forumId,
                'user_id' => $user->user_id,
                'title' => $request->title,
                'content' => $request->content,
                'is_pinned' => $request->is_pinned ?? false,
                'last_activity_at' => now()
            ]);
            
            $topic->load(['user', 'replies.user']);
            
            return response()->json([
                'success' => true,
                'data' => $topic,
                'message' => 'Forum topic created successfully'
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create forum topic',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get topic details with replies
     *
     * @param string $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopic($topicId)
    {
        try {
            $user = Auth::user();
            
            $topic = BatchForumTopic::with(['user', 'forum', 'replies.user'])
                ->findOrFail($topicId);
            
            // Verify user has access to this topic
            $hasAccess = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $topic->forum->batch_id)
                ->where('status', 'active')
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this topic'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Increment view count
            $topic->increment('views_count');
            
            return response()->json([
                'success' => true,
                'data' => $topic,
                'message' => 'Topic retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve topic',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reply to a forum topic
     *
     * @param \Illuminate\Http\Request $request
     * @param string $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function replyToTopic(Request $request, $topicId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:5',
                'parent_reply_id' => 'nullable|exists:batch_forum_replies,reply_id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();
            
            $topic = BatchForumTopic::with('forum')->findOrFail($topicId);
            
            // Verify user has access to this topic
            $hasAccess = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $topic->forum->batch_id)
                ->where('status', 'active')
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this topic'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $reply = BatchForumReply::create([
                'topic_id' => $topicId,
                'user_id' => $user->user_id,
                'content' => $request->content,
                'parent_reply_id' => $request->parent_reply_id
            ]);
            
            // Update topic last activity
            $topic->update(['last_activity_at' => now()]);
            
            $reply->load('user');
            
            return response()->json([
                'success' => true,
                'data' => $reply,
                'message' => 'Reply posted successfully'
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to post reply',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update forum topic (only by topic creator)
     *
     * @param \Illuminate\Http\Request $request
     * @param string $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTopic(Request $request, $topicId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string|min:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();
            $topic = BatchForumTopic::findOrFail($topicId);
            
            // Only topic creator can update
            if ($topic->user_id !== $user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only topic creator can update this topic'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $topic->update($request->only(['title', 'content']));
            $topic->load(['user', 'replies.user']);
            
            return response()->json([
                'success' => true,
                'data' => $topic,
                'message' => 'Topic updated successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update topic',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete forum topic (only by topic creator)
     *
     * @param string $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTopic($topicId)
    {
        try {
            $user = Auth::user();
            $topic = BatchForumTopic::findOrFail($topicId);
            
            // Only topic creator can delete
            if ($topic->user_id !== $user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only topic creator can delete this topic'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $topic->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Topic deleted successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete topic',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update forum reply (only by reply creator)
     *
     * @param \Illuminate\Http\Request $request
     * @param string $replyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateReply(Request $request, $replyId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:5'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();
            $reply = BatchForumReply::findOrFail($replyId);
            
            // Only reply creator can update
            if ($reply->user_id !== $user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only reply creator can update this reply'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $reply->update(['content' => $request->content]);
            $reply->load('user');
            
            return response()->json([
                'success' => true,
                'data' => $reply,
                'message' => 'Reply updated successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reply',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete forum reply (only by reply creator)
     *
     * @param string $replyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteReply($replyId)
    {
        try {
            $user = Auth::user();
            $reply = BatchForumReply::findOrFail($replyId);
            
            // Only reply creator can delete
            if ($reply->user_id !== $user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only reply creator can delete this reply'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $reply->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Reply deleted successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reply',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
