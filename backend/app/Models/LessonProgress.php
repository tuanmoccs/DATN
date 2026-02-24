<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
  use HasFactory;

  protected $table = 'lesson_progress';

  protected $fillable = [
    'student_id',
    'lesson_id',
    'status',
    'slides_viewed',
    'total_slides',
    'time_spent',
    'started_at',
    'completed_at',
  ];

  protected $casts = [
    'slides_viewed' => 'integer',
    'total_slides' => 'integer',
    'time_spent' => 'integer',
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
  ];

  // Relationships
  public function student(): BelongsTo
  {
    return $this->belongsTo(User::class, 'student_id');
  }

  public function lesson(): BelongsTo
  {
    return $this->belongsTo(Lesson::class, 'lesson_id');
  }

  // Scopes
  public function scopeCompleted($query)
  {
    return $query->where('status', 'completed');
  }

  public function scopeInProgress($query)
  {
    return $query->where('status', 'in_progress');
  }

  public function scopeByStudent($query, int $studentId)
  {
    return $query->where('student_id', $studentId);
  }

  // Methods
  public function getProgressPercentage(): float
  {
    if ($this->total_slides === 0) return 0;
    return round(($this->slides_viewed / $this->total_slides) * 100, 2);
  }

  public function markCompleted(): void
  {
    $this->update([
      'status' => 'completed',
      'completed_at' => now(),
    ]);
  }
}
