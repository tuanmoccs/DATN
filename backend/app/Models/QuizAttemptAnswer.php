<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
  use HasFactory;

  protected $table = 'quiz_attempt_answers';

  protected $fillable = [
    'attempt_id',
    'question_id',
    'answer_type',
    'answer_option_id',
    'answer_text',
    'answer_file_path',
    'is_correct',
    'points_earned',
    'teacher_feedback',
    'submitted_at',
    'graded_at',
    'graded_by',
  ];

  protected $casts = [
    'is_correct' => 'boolean',
    'points_earned' => 'decimal:2',
    'submitted_at' => 'datetime',
    'graded_at' => 'datetime',
  ];

  // Relationships
  public function attempt(): BelongsTo
  {
    return $this->belongsTo(QuizAttempt::class, 'attempt_id');
  }

  public function question(): BelongsTo
  {
    return $this->belongsTo(QuizQuestion::class, 'question_id');
  }

  public function selectedOption(): BelongsTo
  {
    return $this->belongsTo(QuizOption::class, 'answer_option_id');
  }

  public function gradedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'graded_by');
  }

  // Scopes
  public function scopeGraded($query)
  {
    return $query->whereNotNull('graded_at');
  }

  public function scopeCorrect($query)
  {
    return $query->where('is_correct', true);
  }
}
