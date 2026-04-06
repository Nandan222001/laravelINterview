<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasNestedSet
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('order');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeLeaves(Builder $query): Builder
    {
        return $query->whereRaw('(_rgt - _lft) = 1');
    }

    public function scopeWithDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', $depth);
    }

    public function scopeWithMaxDepth(Builder $query, int $maxDepth): Builder
    {
        return $query->where('depth', '<=', $maxDepth);
    }

    public function getDescendantsAndSelf(): Collection
    {
        return static::whereBetween('_lft', [$this->_lft, $this->_rgt])
            ->orderBy('_lft')
            ->get();
    }

    public function getDescendantsOnly(): Collection
    {
        return static::whereBetween('_lft', [$this->_lft + 1, $this->_rgt - 1])
            ->orderBy('_lft')
            ->get();
    }

    public function getAncestorsAndSelf(): Collection
    {
        return static::where('_lft', '<=', $this->_lft)
            ->where('_rgt', '>=', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    public function getAncestorsOnly(): Collection
    {
        return static::where('_lft', '<', $this->_lft)
            ->where('_rgt', '>', $this->_rgt)
            ->orderBy('_lft')
            ->get();
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return ($this->_rgt - $this->_lft) === 1;
    }

    public function hasChildren(): bool
    {
        return ! $this->isLeaf();
    }

    public function isDescendantOf(self $other): bool
    {
        return $this->_lft > $other->_lft && $this->_rgt < $other->_rgt;
    }

    public function isAncestorOf(self $other): bool
    {
        return $this->_lft < $other->_lft && $this->_rgt > $other->_rgt;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getLevel(): int
    {
        return $this->depth + 1;
    }
}
