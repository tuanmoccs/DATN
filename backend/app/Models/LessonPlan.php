<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'title',
    'subject',
    'grade_level',
    'content',
    'status',
    'created_by',
  ];

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function scopeDraft($query)
  {
    return $query->where('status', 'draft');
  }

  public function scopeCompleted($query)
  {
    return $query->where('status', 'completed');
  }
}
