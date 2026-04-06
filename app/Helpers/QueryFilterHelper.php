<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class QueryFilterHelper
{
    public static function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            match ($key) {
                'search' => self::applySearch($query, $value),
                'sort' => self::applySort($query, $value),
                'order' => null,
                'per_page' => null,
                'page' => null,
                default => self::applyCustomFilter($query, $key, $value),
            };
        }

        return $query;
    }

    protected static function applySearch(Builder $query, string $term): void
    {
        $model = $query->getModel();

        if (method_exists($model, 'scopeSearch')) {
            $query->search($term);
        }
    }

    protected static function applySort(Builder $query, string|array $sort): void
    {
        if (is_string($sort)) {
            $parts = explode(':', $sort);
            $column = $parts[0];
            $direction = $parts[1] ?? 'asc';
            $query->orderBy($column, $direction);
        } elseif (is_array($sort)) {
            foreach ($sort as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }
    }

    protected static function applyCustomFilter(Builder $query, string $key, mixed $value): void
    {
        $model = $query->getModel();
        $scopeMethod = 'scope'.str_replace('_', '', ucwords($key, '_'));

        if (method_exists($model, $scopeMethod)) {
            $methodName = lcfirst(str_replace('scope', '', $scopeMethod));
            $query->$methodName($value);
        }
    }

    public static function buildWhereConditions(array $conditions): array
    {
        $where = [];

        foreach ($conditions as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $where[] = [$key, 'in', $value];
            } else {
                $where[] = [$key, '=', $value];
            }
        }

        return $where;
    }
}
