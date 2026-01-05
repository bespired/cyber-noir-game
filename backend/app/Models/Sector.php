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
        'kaart_coordinaten', // Deprecated but kept for now
        'is_ontdekt',
        'x',
        'y',
        'width',
        'height',
    ];

    protected $casts = [
        'is_ontdekt' => 'boolean',
        'x' => 'integer',
        'y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    protected $with = ['artwork'];

    public function scenes()
    {
        return $this->hasMany(Scene::class);
    }

    public function artwork()
    {
        return $this->morphMany(Afbeelding::class, 'imageable');
    }
}
