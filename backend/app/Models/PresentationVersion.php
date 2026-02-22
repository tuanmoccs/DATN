<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PresentationVersion extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'presentation_id',
    'version_number',
    'file_path',
    'file_size',
    'mime_type',
    'uploaded_by',
    'change_log',
    'is_active',
  ];

  protected $casts = [
    'version_number' => 'integer',
    'file_size' => 'integer',
    'is_active' => 'boolean',
  ];

  // Relationships
  public function presentation(): BelongsTo
  {
    return $this->belongsTo(Presentation::class, 'presentation_id');
  }

  public function uploadedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'uploaded_by');
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  // Methods
  public function activate()
  {
    // Deactivate all other versions
    $this->presentation->versions()->update(['is_active' => false]);
    $this->update(['is_active' => true]);
    $this->presentation->update(['current_version' => $this->version_number]);
  }
}
