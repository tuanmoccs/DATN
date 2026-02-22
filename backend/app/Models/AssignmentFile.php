<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignmentFile extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'assignment_id',
    'file_path',
    'file_size',
    'mime_type',
    'file_name',
    'uploaded_by',
    'is_primary',
  ];

  protected $casts = [
    'file_size' => 'integer',
    'is_primary' => 'boolean',
  ];

  // Relationships
  public function assignment(): BelongsTo
  {
    return $this->belongsTo(Assignment::class, 'assignment_id');
  }

  public function uploadedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'uploaded_by');
  }
}
