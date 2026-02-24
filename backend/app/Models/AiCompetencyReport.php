<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCompetencyReport extends Model
{
  use HasFactory;

  protected $table = 'ai_competency_reports';

  protected $fillable = [
    'student_id',
    'class_id',
    'report_type',
    'lesson_id',
    'average_score',
    'total_quizzes_taken',
    'total_assignments_completed',
    'strengths',
    'weaknesses',
    'recommendations',
    'overall_summary',
    'ai_prompt_used',
    'ai_model_used',
    'generated_by',
    'generated_at',
  ];

  protected $casts = [
    'average_score' => 'decimal:2',
    'total_quizzes_taken' => 'integer',
    'total_assignments_completed' => 'integer',
    'strengths' => 'array',
    'weaknesses' => 'array',
    'recommendations' => 'array',
    'generated_at' => 'datetime',
  ];

  // Relationships
  public function student(): BelongsTo
  {
    return $this->belongsTo(User::class, 'student_id');
  }

  public function class(): BelongsTo
  {
    return $this->belongsTo(Classz::class, 'class_id');
  }

  public function lesson(): BelongsTo
  {
    return $this->belongsTo(Lesson::class, 'lesson_id');
  }

  public function generatedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'generated_by');
  }

  // Scopes
  public function scopeByStudent($query, int $studentId)
  {
    return $query->where('student_id', $studentId);
  }

  public function scopeByClass($query, int $classId)
  {
    return $query->where('class_id', $classId);
  }

  public function scopeByType($query, string $type)
  {
    return $query->where('report_type', $type);
  }

  public function scopeLatest($query)
  {
    return $query->orderBy('generated_at', 'desc');
  }
}
