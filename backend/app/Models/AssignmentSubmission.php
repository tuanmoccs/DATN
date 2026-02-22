<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignmentSubmission extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'assignment_id',
    'student_id',
    'submitted_at',
    'is_late',
    'status',
  ];

  protected $casts = [
    'is_late' => 'boolean',
    'submitted_at' => 'datetime',
  ];

  // Relationships
  public function assignment(): BelongsTo
  {
    return $this->belongsTo(Assignment::class, 'assignment_id');
  }

  public function student(): BelongsTo
  {
    return $this->belongsTo(User::class, 'student_id');
  }

  public function attachments(): HasMany
  {
    return $this->hasMany(SubmissionAttachment::class, 'submission_id');
  }

  public function grading(): HasOne
  {
    return $this->hasOne(Grading::class, 'submission_id');
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

  public function scopeLate($query)
  {
    return $query->where('is_late', true);
  }

  // Methods
  public function getScore()
  {
    return $this->grading?->score ?? null;
  }

  public function getPercentage()
  {
    return $this->grading?->percentage ?? null;
  }
}
