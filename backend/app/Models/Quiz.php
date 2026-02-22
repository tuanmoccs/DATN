<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'lesson_id',
    'title',
    'description',
    'quiz_type',
    'auto_generated',
    'ai_prompt',
    'start_time',
    'end_time',
    'time_limit',
    'shuffle_questions',
    'shuffle_options',
    'show_answers_after',
    'max_attempts',
    'status',
    'created_by',
  ];

  protected $casts = [
    'auto_generated' => 'boolean',
    'shuffle_questions' => 'boolean',
    'shuffle_options' => 'boolean',
    'show_answers_after' => 'boolean',
    'time_limit' => 'integer',
    'max_attempts' => 'integer',
    'start_time' => 'datetime',
    'end_time' => 'datetime',
  ];

  // Relationships
  public function lesson(): BelongsTo
  {
    return $this->belongsTo(Lesson::class, 'lesson_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function questions(): HasMany
  {
    return $this->hasMany(QuizQuestion::class, 'quiz_id')->orderBy('order');
  }

  public function attempts(): HasMany
  {
    return $this->hasMany(QuizAttempt::class, 'quiz_id');
  }

  // Scopes
  public function scopePublished($query)
  {
    return $query->where('status', 'published');
  }

  public function scopeOnline($query)
  {
    return $query->where('quiz_type', 'online');
  }

  public function scopeOffline($query)
  {
    return $query->where('quiz_type', 'offline');
  }

  public function scopeActive($query)
  {
    return $query->where('status', 'published')
      ->where('start_time', '<=', now())
      ->where('end_time', '>=', now());
  }

  // Methods
  public function isActive(): bool
  {
    return $this->status === 'published' &&
      $this->start_time <= now() &&
      $this->end_time >= now();
  }

  public function getTotalPoints(): int
  {
    return $this->questions()->sum('points');
  }
}
