<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grading extends Model
{
  use HasFactory;

  protected $fillable = [
    'submission_id',
    'graded_by',
    'score',
    'max_score',
    'percentage',
    'feedback',
    'graded_at',
  ];

  protected $casts = [
    'score' => 'decimal:2',
    'max_score' => 'integer',
    'percentage' => 'decimal:2',
    'graded_at' => 'datetime',
  ];

  // Relationships
  public function submission(): BelongsTo
  {
    return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
  }

  public function gradedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'graded_by');
  }

  // Methods
  public function calculatePercentage(): float
  {
    if ($this->max_score == 0) return 0;
    return ($this->score / $this->max_score) * 100;
  }

  public function getGrade(): string
  {
    $percentage = $this->percentage ?? $this->calculatePercentage();

    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
  }
}
