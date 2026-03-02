<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
  protected $table = 'enrollment';
  protected $fillable = ['class_id', 'user_id', 'joined_at', 'status'];

  protected $casts = [
    'joined_at' => 'datetime',
  ];

  // Relationships
  public function class(): BelongsTo
  {
    return $this->belongsTo(Classz::class, 'class_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }
}
