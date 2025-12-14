<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sectoren';

    protected $fillable = [
        'naam',
        'beschrijving',
        'kaart_coordinaten',
        'is_ontdekt',
    ];

    protected $casts = [
        'is_ontdekt' => 'boolean',
    ];

    public function locaties()
    {
        return $this->hasMany(Locatie::class);
    }
}
