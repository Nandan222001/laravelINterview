<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'learning_objectives',
        'order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_topic')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
