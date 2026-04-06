<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $method = 'filter'.str_replace('_', '', ucwords($key, '_'));

            if (method_exists($this, $method)) {
                $this->$method($query, $value);
            }
        }

        return $query;
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        if (property_exists($this, 'searchableColumns')) {
            $query->where(function ($q) use ($term) {
                foreach ($this->searchableColumns as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        return $query;
    }

    public function scopeSort(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        if (property_exists($this, 'sortableColumns')) {
            if (in_array($column, $this->sortableColumns)) {
                return $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    public function scopeWhereDate(Builder $query, string $column, string $operator, $value = null): Builder
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return $query->whereDate($column, $operator, $value);
    }

    public function scopeWhereDateBetween(Builder $query, string $column, $start, $end): Builder
    {
        return $query->whereBetween($column, [$start, $end]);
    }
}
