<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
  use HasFactory;

  protected $fillable = [
    'quiz_id',
    'student_id',
    'attempt_number',
    'started_at',
    'submitted_at',
    'score',
    'percentage',
    'status',
    'feedback',
  ];

  protected $casts = [
    'attempt_number' => 'integer',
    'score' => 'decimal:2',
    'percentage' => 'decimal:2',
    'started_at' => 'datetime',
    'submitted_at' => 'datetime',
  ];

  // Relationships
  public function quiz(): BelongsTo
  {
    return $this->belongsTo(Quiz::class, 'quiz_id');
  }

  public function student(): BelongsTo
  {
    return $this->belongsTo(User::class, 'student_id');
  }

  public function answers(): HasMany
  {
    return $this->hasMany(QuizAttemptAnswer::class, 'attempt_id');
  }

  // Scopes
  public function scopeSubmitted($query)
  {
    return $query->where('status', 'submitted');
  }

  public function scopeGraded($query)
  {
    return $query->where('status', 'graded');
  }

  // Methods
  public function isTimedOut(): bool
  {
    if (!$this->quiz->time_limit) return false;

    return $this->started_at->addMinutes($this->quiz->time_limit) < now();
  }

  public function calculatePercentage(): float
  {
    $totalPoints = $this->quiz->getTotalPoints();
    if ($totalPoints == 0) return 0;

    return ($this->score / $totalPoints) * 100;
  }
}
