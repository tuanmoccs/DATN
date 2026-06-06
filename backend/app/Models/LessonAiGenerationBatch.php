<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAiGenerationBatch extends Model
{
  use HasFactory;

  protected $fillable = [
    'lesson_id',
    'teacher_id',
    'type',
    'status',
    'progress',
    'slide_count',
    'question_count',
    'options',
    'result',
    'message',
    'error_message',
    'started_at',
    'finished_at',
  ];

  protected $casts = [
    'progress' => 'integer',
    'slide_count' => 'integer',
    'question_count' => 'integer',
    'options' => 'array',
    'result' => 'array',
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
  ];

  public function lesson(): BelongsTo
  {
    return $this->belongsTo(Lesson::class);
  }

  public function teacher(): BelongsTo
  {
    return $this->belongsTo(User::class, 'teacher_id');
  }
}
