<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmissionAttachment extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'submission_id',
    'file_path',
    'file_size',
    'mime_type',
    'file_name',
    'file_type',
    'uploaded_at',
  ];

  protected $casts = [
    'file_size' => 'integer',
    'uploaded_at' => 'datetime',
  ];

  // Relationships
  public function submission(): BelongsTo
  {
    return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
  }

  // Scopes
  public function scopeImages($query)
  {
    return $query->where('file_type', 'image');
  }

  public function scopeDocuments($query)
  {
    return $query->where('file_type', 'document');
  }
}
