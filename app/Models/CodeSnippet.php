<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CodeSnippet extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'title',
        'description',
        'code',
        'language',
        'type',
        'order',
        'is_executable',
        'expected_output',
        'metadata',
    ];

    protected $casts = [
        'is_executable' => 'boolean',
        'metadata' => 'array',
        'order' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
