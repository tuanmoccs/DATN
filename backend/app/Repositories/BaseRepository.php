<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
  protected Model $model;

  public function __construct()
  {
    $this->model = app($this->getModelClass());
  }

  abstract protected function getModelClass(): string;

  public function all(array $columns = ['*']): Collection
  {
    return $this->model->newQuery()->get($columns);
  }

  public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
  {
    return $this->model->newQuery()->paginate($perPage, $columns);
  }

  public function find(int $id, array $columns = ['*']): ?Model
  {
    return $this->model->newQuery()->find($id, $columns);
  }

  public function findOrFail(int $id, array $columns = ['*']): Model
  {
    return $this->model->newQuery()->findOrFail($id, $columns);
  }

  public function findByField(string $field, mixed $value, array $columns = ['*']): Collection
  {
    return $this->model->newQuery()->where($field, $value)->get($columns);
  }

  public function findOneByField(string $field, mixed $value, array $columns = ['*']): ?Model
  {
    return $this->model->newQuery()->where($field, $value)->first($columns);
  }

  public function findWhere(array $where, array $columns = ['*']): Collection
  {
    $query = $this->model->newQuery();

    foreach ($where as $field => $value) {
      if (is_array($value)) {
        [$field, $operator, $val] = $value;
        $query->where($field, $operator, $val);
      } else {
        $query->where($field, $value);
      }
    }

    return $query->get($columns);
  }

  public function create(array $data): Model
  {
    return $this->model->newQuery()->create($data);
  }

  public function update(int $id, array $data): Model
  {
    $record = $this->findOrFail($id);
    $record->update($data);
    return $record->fresh();
  }

  public function delete(int $id): bool
  {
    $record = $this->findOrFail($id);
    return $record->delete();
  }

  public function count(array $where = []): int
  {
    $query = $this->model->newQuery();

    foreach ($where as $field => $value) {
      $query->where($field, $value);
    }

    return $query->count();
  }

  public function exists(array $where): bool
  {
    $query = $this->model->newQuery();

    foreach ($where as $field => $value) {
      $query->where($field, $value);
    }

    return $query->exists();
  }

  /**
   * Get the underlying model query builder
   */
  public function query()
  {
    return $this->model->newQuery();
  }
}
