<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'type',
    'title',
    'content',
    'action_url',
    'metadata',
    'is_read',
    'read_at',
  ];

  protected $casts = [
    'metadata' => 'array',
    'is_read' => 'boolean',
    'read_at' => 'datetime',
  ];

  // Relationships
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  // Scopes
  public function scopeUnread($query)
  {
    return $query->where('is_read', false);
  }

  public function scopeRead($query)
  {
    return $query->where('is_read', true);
  }

  public function scopeByType($query, string $type)
  {
    return $query->where('type', $type);
  }

  public function scopeForUser($query, int $userId)
  {
    return $query->where('user_id', $userId)->orderBy('created_at', 'desc');
  }

  // Methods
  public function markAsRead(): void
  {
    $this->update([
      'is_read' => true,
      'read_at' => now(),
    ]);
  }

  public function markAsUnread(): void
  {
    $this->update([
      'is_read' => false,
      'read_at' => null,
    ]);
  }
}
