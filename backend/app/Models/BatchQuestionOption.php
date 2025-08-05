<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchQuestionOption extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'batch_question_options';
    protected $primaryKey = 'option_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
        'sort_order',
        'explanation'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    // Relationships
    public function question()
    {
        return $this->belongsTo(BatchQuestion::class, 'question_id');
    }

    public function responses()
    {
        return $this->hasMany(BatchQuestionResponse::class, 'selected_options');
    }

    // Helper methods
    public function isCorrect()
    {
        return $this->is_correct;
    }

    public function hasExplanation()
    {
        return !empty($this->explanation);
    }

    public function getFormattedTextAttribute()
    {
        return strip_tags($this->option_text);
    }

    public function getStatusDisplayAttribute()
    {
        return $this->isCorrect() ? 'Correct' : 'Incorrect';
    }

    public function getStatusColorAttribute()
    {
        return $this->isCorrect() ? 'green' : 'red';
    }

    public function getSelectionCount()
    {
        // This would need custom logic to count selections from JSON fields
        return BatchQuestionResponse::whereJsonContains('selected_options', $this->option_id)->count();
    }

    public function getSelectionPercentage()
    {
        $totalResponses = $this->question->getResponsesCount();
        if ($totalResponses === 0) return 0;
        
        $selections = $this->getSelectionCount();
        return round(($selections / $totalResponses) * 100, 2);
    }

    public function isPopularChoice()
    {
        return $this->getSelectionPercentage() > 50;
    }

    public function isDistractorEffective()
    {
        // For incorrect options, effective if selected by some but not majority
        if ($this->isCorrect()) return true;
        
        $percentage = $this->getSelectionPercentage();
        return $percentage > 5 && $percentage < 30; // Good distractor range
    }

    public function getQuestionTypeAttribute()
    {
        return $this->question ? $this->question->question_type : null;
    }

    public function getQuestionPointsAttribute()
    {
        return $this->question ? $this->question->points : 0;
    }

    public function canBeDeleted()
    {
        // Can't delete if it's the only correct option for single choice
        if ($this->isCorrect() && $this->question && $this->question->isSingleChoice()) {
            return $this->question->correctOptions()->count() > 1;
        }
        
        // Can't delete if responses exist
        return $this->getSelectionCount() === 0;
    }

    public function duplicate($newQuestionId = null)
    {
        $newOption = $this->replicate();
        if ($newQuestionId) {
            $newOption->question_id = $newQuestionId;
        }
        $newOption->save();
        
        return $newOption;
    }

    public function moveUp()
    {
        $prevOption = $this->question->options()
                          ->where('sort_order', '<', $this->sort_order)
                          ->orderBy('sort_order', 'desc')
                          ->first();
        
        if ($prevOption) {
            $tempOrder = $this->sort_order;
            $this->update(['sort_order' => $prevOption->sort_order]);
            $prevOption->update(['sort_order' => $tempOrder]);
        }
    }

    public function moveDown()
    {
        $nextOption = $this->question->options()
                          ->where('sort_order', '>', $this->sort_order)
                          ->orderBy('sort_order', 'asc')
                          ->first();
        
        if ($nextOption) {
            $tempOrder = $this->sort_order;
            $this->update(['sort_order' => $nextOption->sort_order]);
            $nextOption->update(['sort_order' => $tempOrder]);
        }
    }

    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    public function scopeByQuestion($query, $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    public function scopeOrderedBySortOrder($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeWithExplanation($query)
    {
        return $query->whereNotNull('explanation');
    }

    public function scopePopular($query, $threshold = 50)
    {
        // This would need a custom scope implementation for JSON queries
        return $query;
    }
}