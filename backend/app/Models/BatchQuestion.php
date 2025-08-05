<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_questions';
    protected $primaryKey = 'question_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'assessment_id',
        'question_text',
        'question_type',
        'points',
        'explanation',
        'media_url',
        'sort_order',
        'is_required',
        'difficulty_level',
        'tags'
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'is_required' => 'boolean',
        'tags' => 'json',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function assessment()
    {
        return $this->belongsTo(BatchAssessment::class, 'assessment_id');
    }

    public function options()
    {
        return $this->hasMany(BatchQuestionOption::class, 'question_id')->orderBy('sort_order');
    }

    public function responses()
    {
        return $this->hasMany(BatchQuestionResponse::class, 'question_id');
    }

    public function correctOptions()
    {
        return $this->hasMany(BatchQuestionOption::class, 'question_id')->where('is_correct', true);
    }

    // Helper methods
    public function isMultipleChoice()
    {
        return $this->question_type === 'multiple_choice';
    }

    public function isSingleChoice()
    {
        return $this->question_type === 'single_choice';
    }

    public function isTrueFalse()
    {
        return $this->question_type === 'true_false';
    }

    public function isFillBlank()
    {
        return $this->question_type === 'fill_blank';
    }

    public function isEssay()
    {
        return $this->question_type === 'essay';
    }

    public function isMatching()
    {
        return $this->question_type === 'matching';
    }

    public function isCoding()
    {
        return $this->question_type === 'coding';
    }

    public function isFileUpload()
    {
        return $this->question_type === 'file_upload';
    }

    public function isRequired()
    {
        return $this->is_required;
    }

    public function isEasy()
    {
        return $this->difficulty_level === 'easy';
    }

    public function isMedium()
    {
        return $this->difficulty_level === 'medium';
    }

    public function isHard()
    {
        return $this->difficulty_level === 'hard';
    }

    public function hasMedia()
    {
        return !empty($this->media_url);
    }

    public function hasExplanation()
    {
        return !empty($this->explanation);
    }

    public function hasTags()
    {
        return !empty($this->tags);
    }

    public function getTypeDisplayAttribute()
    {
        return match($this->question_type) {
            'multiple_choice' => 'Multiple Choice',
            'single_choice' => 'Single Choice',
            'true_false' => 'True/False',
            'fill_blank' => 'Fill in the Blank',
            'essay' => 'Essay',
            'matching' => 'Matching',
            'coding' => 'Coding',
            'file_upload' => 'File Upload',
            default => ucfirst(str_replace('_', ' ', $this->question_type))
        };
    }

    public function getDifficultyDisplayAttribute()
    {
        return match($this->difficulty_level) {
            'easy' => 'Easy',
            'medium' => 'Medium',
            'hard' => 'Hard',
            default => ucfirst($this->difficulty_level)
        };
    }

    public function getDifficultyColorAttribute()
    {
        return match($this->difficulty_level) {
            'easy' => 'green',
            'medium' => 'yellow',
            'hard' => 'red',
            default => 'gray'
        };
    }

    public function getCorrectAnswersCount()
    {
        return $this->correctOptions()->count();
    }

    public function getTotalOptionsCount()
    {
        return $this->options()->count();
    }

    public function getResponsesCount()
    {
        return $this->responses()->count();
    }

    public function getCorrectResponsesCount()
    {
        return $this->responses()->where('is_correct', true)->count();
    }

    public function getSuccessRate()
    {
        $total = $this->getResponsesCount();
        if ($total === 0) return 0;
        
        $correct = $this->getCorrectResponsesCount();
        return round(($correct / $total) * 100, 2);
    }

    public function needsManualGrading()
    {
        return in_array($this->question_type, ['essay', 'file_upload', 'coding']);
    }

    public function canAutoGrade()
    {
        return !$this->needsManualGrading();
    }

    public function validateResponse($response)
    {
        if ($this->isRequired() && empty($response)) {
            return false;
        }

        if ($this->isTrueFalse()) {
            return in_array($response, ['true', 'false', true, false]);
        }

        if ($this->isSingleChoice() || $this->isMultipleChoice()) {
            $optionIds = $this->options()->pluck('option_id')->toArray();
            $selectedOptions = is_array($response) ? $response : [$response];
            
            foreach ($selectedOptions as $optionId) {
                if (!in_array($optionId, $optionIds)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function gradeResponse($response)
    {
        if ($this->needsManualGrading()) {
            return null; // Manual grading required
        }

        if (!$this->validateResponse($response)) {
            return 0;
        }

        if ($this->isTrueFalse()) {
            $correctAnswer = $this->correctOptions()->first();
            if (!$correctAnswer) return 0;
            
            $isCorrect = ($response === 'true' || $response === true) === 
                        ($correctAnswer->option_text === 'true' || $correctAnswer->option_text === 'True');
            
            return $isCorrect ? $this->points : 0;
        }

        if ($this->isSingleChoice()) {
            $correctOption = $this->correctOptions()->first();
            return $correctOption && $correctOption->option_id === $response ? $this->points : 0;
        }

        if ($this->isMultipleChoice()) {
            $correctOptionIds = $this->correctOptions()->pluck('option_id')->toArray();
            $selectedOptions = is_array($response) ? $response : [$response];
            
            sort($correctOptionIds);
            sort($selectedOptions);
            
            return $correctOptionIds === $selectedOptions ? $this->points : 0;
        }

        return 0;
    }

    public function scopeByAssessment($query, $assessmentId)
    {
        return $query->where('assessment_id', $assessmentId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('question_type', $type);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    public function scopeWithMedia($query)
    {
        return $query->whereNotNull('media_url');
    }

    public function scopeOrderedBySortOrder($query)
    {
        return $query->orderBy('sort_order');
    }
}