<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'class_id',
    'title',
    'description',
    'instructions',
    'file_path',
    'file_size',
    'mime_type',
    'due_date',
    'max_score',
    'allow_late_submission',
    'late_penalty',
    'submission_type',
    'status',
    'created_by',
  ];

  protected $casts = [
    'file_size' => 'integer',
    'max_score' => 'integer',
    'allow_late_submission' => 'boolean',
    'late_penalty' => 'integer',
    'due_date' => 'datetime',
  ];

  // Relationships
  public function class(): BelongsTo
  {
    return $this->belongsTo(Classz::class, 'class_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function files(): HasMany
  {
    return $this->hasMany(AssignmentFile::class, 'assignment_id');
  }

  public function submissions(): HasMany
  {
    return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
  }

  // Scopes
  public function scopePublished($query)
  {
    return $query->where('status', 'published');
  }

  public function scopeUpcoming($query)
  {
    return $query->where('due_date', '>', now());
  }

  public function scopeOverdue($query)
  {
    return $query->where('due_date', '<', now())
      ->where('status', '!=', 'closed');
  }

  // Methods
  public function isOverdue(): bool
  {
    return $this->due_date && $this->due_date < now();
  }

  public function calculateLateSubmissionPenalty($score): float
  {
    if (!$this->allow_late_submission || !$this->isOverdue()) {
      return $score;
    }

    $penalty = ($score / 100) * $this->late_penalty;
    return max(0, $score - $penalty);
  }
}
