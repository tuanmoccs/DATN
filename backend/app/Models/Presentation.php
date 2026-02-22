<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentation extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'lesson_id',
    'current_version',
    'status',
    'generated_by',
    'ai_prompt',
  ];

  protected $casts = [
    'current_version' => 'integer',
  ];

  // Relationships
  public function lesson(): BelongsTo
  {
    return $this->belongsTo(Lesson::class, 'lesson_id');
  }

  public function versions(): HasMany
  {
    return $this->hasMany(PresentationVersion::class, 'presentation_id');
  }

  public function activeVersion()
  {
    return $this->versions()->where('is_active', true)->first();
  }

  // Scopes
  public function scopePublished($query)
  {
    return $query->where('status', 'published');
  }

  public function scopeGeneratedByAI($query)
  {
    return $query->where('generated_by', 'ai');
  }
}
