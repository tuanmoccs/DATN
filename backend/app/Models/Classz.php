<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classz extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'classes';

  protected $fillable = [
    'code',
    'name',
    'description',
    'teacher_id',
    'semester',
    'max_students',
    'status',
  ];
  protected $casts = [
    'max_students' => 'integer',
  ];

  // Relationships
  public function teacher(): BelongsTo
  {
    return $this->belongsTo(User::class, 'teacher_id');
  }

  public function enrollments(): HasMany
  {
    return $this->hasMany(Enrollment::class, 'class_id');
  }

  public function students()
  {
    return $this->belongsToMany(User::class, 'enrollments', 'class_id', 'user_id')
      ->withTimestamps()
      ->withPivot('status');
  }

  public function lessons(): HasMany
  {
    return $this->hasMany(Lesson::class, 'class_id');
  }

  public function assignments(): HasMany
  {
    return $this->hasMany(Assignment::class, 'class_id');
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  public function scopeByTeacher($query, $teacherId)
  {
    return $query->where('teacher_id', $teacherId);
  }

  // Methods
  public function enrollmentCount(): int
  {
    return $this->enrollments()->where('status', 'active')->count();
  }
}
