<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function find(int|string $id, array $columns = ['*']): ?Model;

    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model;

    public function findWhere(array $where, array $columns = ['*']): Collection;

    public function create(array $attributes): Model;

    public function update(int|string $id, array $attributes): bool;

    public function delete(int|string $id): bool;

    public function updateOrCreate(array $attributes, array $values = []): Model;

    public function firstOrCreate(array $attributes, array $values = []): Model;

    public function count(): int;

    public function exists(int|string $id): bool;
}
