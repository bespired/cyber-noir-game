<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dialoog extends Model
{
    use HasFactory;

    protected $table = 'dialogen';

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
