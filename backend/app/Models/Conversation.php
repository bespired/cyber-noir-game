<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'personage_id',
        'titel',
        'tree',
        'is_active',
    ];

    protected $casts = [
        'tree' => 'array',
        'is_active' => 'boolean',
    ];

    public function personage()
    {
        return $this->belongsTo(Personage::class);
    }
}
