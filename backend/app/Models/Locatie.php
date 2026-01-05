<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locatie extends Model
{
    use HasFactory;

    protected $fillable = [
        'naam',
        'beschrijving',
        'notities',
        'volgorde',
        'spawn_points',
    ];

    protected $casts = [
        'volgorde' => 'integer',
        'spawn_points' => 'array',
    ];

    public function scenes()
    {
        return $this->hasMany(Scene::class);
    }

    public function aanwijzingen()
    {
        return $this->hasMany(Aanwijzing::class);
    }

    public function artwork()
    {
        return $this->morphMany(Afbeelding::class, 'imageable');
    }
}
