<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PresentationSlide extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'presentation_slides';

  protected $fillable = [
    'presentation_id',
    'order',
    'title',
    'content',
    'notes',
    'image_url',
    'layout',
  ];

  protected $casts = [
    'order' => 'integer',
  ];

  // Relationships
  public function presentation(): BelongsTo
  {
    return $this->belongsTo(Presentation::class, 'presentation_id');
  }

  // Scopes
  public function scopeOrdered($query)
  {
    return $query->orderBy('order');
  }

  public function scopeByLayout($query, string $layout)
  {
    return $query->where('layout', $layout);
  }
}
