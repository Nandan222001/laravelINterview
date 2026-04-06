<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DifficultyLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'color',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'level' => 'integer',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
