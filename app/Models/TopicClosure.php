<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicClosure extends Model
{
    use HasFactory;

    protected $table = 'topic_closure';

    protected $fillable = [
        'ancestor_id',
        'descendant_id',
        'depth',
    ];

    protected $casts = [
        'depth' => 'integer',
    ];

    public function ancestor(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'ancestor_id');
    }

    public function descendant(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'descendant_id');
    }
}
