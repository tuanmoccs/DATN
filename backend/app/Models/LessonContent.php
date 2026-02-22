<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonContent extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'lesson_content';

  protected $fillable = [
    'lesson_id',
    'content_type',
    'content_text',
    'file_path',
    'file_size',
    'mime_type',
    'is_primary',
  ];

  protected $casts = [
    'file_size' => 'integer',
    'is_primary' => 'boolean',
  ];

  // Relationships
  public function lesson(): BelongsTo
  {
    return $this->belongsTo(Lesson::class, 'lesson_id');
  }

  // Scopes
  public function scopePrimary($query)
  {
    return $query->where('is_primary', true);
  }

  public function scopeByType($query, $type)
  {
    return $query->where('content_type', $type);
  }
}
