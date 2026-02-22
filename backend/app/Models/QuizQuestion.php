<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestion extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'quiz_questions';

  protected $fillable = [
    'quiz_id',
    'question_type',
    'content',
    'explanation',
    'order',
    'points',
  ];

  protected $casts = [
    'order' => 'integer',
    'points' => 'integer',
  ];

  // Relationships
  public function quiz(): BelongsTo
  {
    return $this->belongsTo(Quiz::class, 'quiz_id');
  }

  public function options(): HasMany
  {
    return $this->hasMany(QuizOption::class, 'question_id')->orderBy('order');
  }

  public function attemptAnswers(): HasMany
  {
    return $this->hasMany(QuizAttemptAnswer::class, 'question_id');
  }

  // Scopes
  public function scopeMultipleChoice($query)
  {
    return $query->where('question_type', 'multiple_choice');
  }

  public function scopeShortAnswer($query)
  {
    return $query->where('question_type', 'short_answer');
  }

  public function scopeEssay($query)
  {
    return $query->where('question_type', 'essay');
  }
}
