<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizOption extends Model
{
  use HasFactory;

  protected $table = 'quiz_options';

  protected $fillable = [
    'question_id',
    'option_text',
    'is_correct',
    'order',
    'explanation',
  ];

  protected $casts = [
    'order' => 'integer',
    'is_correct' => 'boolean',
  ];

  // Relationships
  public function question(): BelongsTo
  {
    return $this->belongsTo(QuizQuestion::class, 'question_id');
  }

  // Scopes
  public function scopeCorrect($query)
  {
    return $query->where('is_correct', true);
  }
}
