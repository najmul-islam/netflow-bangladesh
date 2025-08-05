<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchQuestionResponse extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_question_responses';
    protected $primaryKey = 'response_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_options',
        'text_response',
        'file_uploads',
        'points_earned',
        'is_correct',
        'feedback',
        'time_spent_seconds'
    ];

    protected $casts = [
        'selected_options' => 'json',
        'file_uploads' => 'json',
        'points_earned' => 'decimal:2',
        'is_correct' => 'boolean',
    ];

    // Relationships
    public function attempt()
    {
        return $this->belongsTo(BatchAssessmentAttempt::class, 'attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(BatchQuestion::class, 'question_id');
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, BatchAssessmentAttempt::class, 'attempt_id', 'user_id', 'attempt_id', 'user_id');
    }

    // Helper methods
    public function isCorrect()
    {
        return $this->is_correct;
    }

    public function hasTextResponse()
    {
        return !empty($this->text_response);
    }

    public function hasSelectedOptions()
    {
        return !empty($this->selected_options);
    }

    public function hasFileUploads()
    {
        return !empty($this->file_uploads);
    }

    public function hasFeedback()
    {
        return !empty($this->feedback);
    }

    public function isGraded()
    {
        return !is_null($this->points_earned);
    }

    public function needsManualGrading()
    {
        return $this->question && $this->question->needsManualGrading() && !$this->isGraded();
    }

    public function getSelectedOptionsArray()
    {
        return $this->selected_options ?? [];
    }

    public function getFileUploadsArray()
    {
        return $this->file_uploads ?? [];
    }

    public function getFormattedTimeSpentAttribute()
    {
        if ($this->time_spent_seconds < 60) {
            return $this->time_spent_seconds . ' seconds';
        }
        
        $minutes = floor($this->time_spent_seconds / 60);
        $seconds = $this->time_spent_seconds % 60;
        
        return $minutes . 'm ' . $seconds . 's';
    }

    public function getScorePercentageAttribute()
    {
        if (!$this->question || $this->question->points == 0) return 0;
        
        return round(($this->points_earned / $this->question->points) * 100, 2);
    }

    public function getResponseDisplayAttribute()
    {
        if ($this->hasTextResponse()) {
            return $this->text_response;
        }
        
        if ($this->hasSelectedOptions()) {
            $options = BatchQuestionOption::whereIn('option_id', $this->getSelectedOptionsArray())
                                         ->pluck('option_text')
                                         ->toArray();
            return implode(', ', $options);
        }
        
        if ($this->hasFileUploads()) {
            $files = $this->getFileUploadsArray();
            return count($files) . ' file(s) uploaded';
        }
        
        return 'No response';
    }

    public function getQuestionTypeAttribute()
    {
        return $this->question ? $this->question->question_type : null;
    }

    public function getMaxPointsAttribute()
    {
        return $this->question ? $this->question->points : 0;
    }

    public function autoGrade()
    {
        if (!$this->question || $this->question->needsManualGrading()) {
            return false;
        }

        $response = $this->hasSelectedOptions() ? $this->getSelectedOptionsArray() : $this->text_response;
        $points = $this->question->gradeResponse($response);
        
        $this->update([
            'points_earned' => $points,
            'is_correct' => $points > 0
        ]);
        
        return true;
    }

    public function grade($points, $feedback = null, $isCorrect = null)
    {
        $updateData = [
            'points_earned' => min($points, $this->getMaxPointsAttribute()),
            'feedback' => $feedback
        ];
        
        if ($isCorrect !== null) {
            $updateData['is_correct'] = $isCorrect;
        } else {
            $updateData['is_correct'] = $points > 0;
        }
        
        $this->update($updateData);
    }

    public function addFileUpload($filename, $url, $size = null, $mimeType = null)
    {
        $uploads = $this->getFileUploadsArray();
        
        $uploads[] = [
            'filename' => $filename,
            'url' => $url,
            'size' => $size,
            'mime_type' => $mimeType,
            'uploaded_at' => now()->toISOString()
        ];
        
        $this->update(['file_uploads' => $uploads]);
    }

    public function removeFileUpload($filename)
    {
        $uploads = $this->getFileUploadsArray();
        $uploads = array_filter($uploads, function($upload) use ($filename) {
            return $upload['filename'] !== $filename;
        });
        
        $this->update(['file_uploads' => array_values($uploads)]);
    }

    public function getUploadedFileByName($filename)
    {
        $uploads = $this->getFileUploadsArray();
        
        foreach ($uploads as $upload) {
            if ($upload['filename'] === $filename) {
                return $upload;
            }
        }
        
        return null;
    }

    public function getTotalUploadSize()
    {
        $uploads = $this->getFileUploadsArray();
        return array_sum(array_column($uploads, 'size'));
    }

    public function scopeByAttempt($query, $attemptId)
    {
        return $query->where('attempt_id', $attemptId);
    }

    public function scopeByQuestion($query, $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    public function scopeGraded($query)
    {
        return $query->whereNotNull('points_earned');
    }

    public function scopeUngraded($query)
    {
        return $query->whereNull('points_earned');
    }

    public function scopeNeedsManualGrading($query)
    {
        return $query->whereHas('question', function($q) {
            $q->whereIn('question_type', ['essay', 'file_upload', 'coding']);
        })->whereNull('points_earned');
    }

    public function scopeWithFeedback($query)
    {
        return $query->whereNotNull('feedback');
    }

    public function scopeWithFileUploads($query)
    {
        return $query->whereNotNull('file_uploads');
    }

    public function scopeByQuestionType($query, $type)
    {
        return $query->whereHas('question', function($q) use ($type) {
            $q->where('question_type', $type);
        });
    }
}