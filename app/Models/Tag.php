<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'usage_count',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'usage_count' => 'integer',
    ];

    public function questions(): MorphToMany
    {
        return $this->morphedByMany(Question::class, 'taggable');
    }

    public function categories(): MorphToMany
    {
        return $this->morphedByMany(Category::class, 'taggable');
    }

    public function topics(): MorphToMany
    {
        return $this->morphedByMany(Topic::class, 'taggable');
    }

    public function codeSnippets(): MorphToMany
    {
        return $this->morphedByMany(CodeSnippet::class, 'taggable');
    }
}
