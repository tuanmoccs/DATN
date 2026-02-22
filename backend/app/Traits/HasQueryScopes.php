<?php

namespace App\Traits;

trait HasQueryScopes
{
  public function scopeTrashed($query)
  {
    return $query->onlyTrashed();
  }

  public function scopeWithTrashed($query)
  {
    return $query->withTrashed();
  }

  public function scopeOrdered($query, $column = 'created_at', $direction = 'desc')
  {
    return $query->orderBy($column, $direction);
  }

  public function scopePaginated($query, $perPage = 15)
  {
    return $query->paginate($perPage);
  }
}
