<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'class_id',
    'title',
    'description',
    'objectives',
    'order',
    'status',
    'created_by',
  ];

  protected $casts = [
    'order' => 'integer',
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

  public function content(): HasMany
  {
    return $this->hasMany(LessonContent::class, 'lesson_id');
  }

  public function presentation(): HasOne
  {
    return $this->hasOne(Presentation::class, 'lesson_id');
  }

  public function quizzes(): HasMany
  {
    return $this->hasMany(Quiz::class, 'lesson_id');
  }

  // Scopes
  public function scopePublished($query)
  {
    return $query->where('status', 'published');
  }

  public function scopeForClass($query, $classId)
  {
    return $query->where('class_id', $classId)->orderBy('order');
  }

  // Methods
  public function getPrimaryContent()
  {
    return $this->content()->where('is_primary', true)->first();
  }
}
