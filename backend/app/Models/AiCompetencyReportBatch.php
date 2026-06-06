<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCompetencyReportBatch extends Model
{
  use HasFactory;

  protected $fillable = [
    'class_id',
    'teacher_id',
    'status',
    'total_students',
    'processed',
    'generated',
    'skipped',
    'failed',
    'results',
    'error_message',
    'started_at',
    'finished_at',
  ];

  protected $casts = [
    'total_students' => 'integer',
    'processed' => 'integer',
    'generated' => 'integer',
    'skipped' => 'integer',
    'failed' => 'integer',
    'results' => 'array',
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
  ];

  public function class(): BelongsTo
  {
    return $this->belongsTo(Classz::class, 'class_id');
  }

  public function teacher(): BelongsTo
  {
    return $this->belongsTo(User::class, 'teacher_id');
  }
}
