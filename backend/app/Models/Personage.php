<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personage extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'naam',
        'rol',
        'beschrijving',
        'menselijke_status',
        'replicant_status',
        'motief',
        'is_replicant',
        'is_playable',
    ];

    protected $casts = [
        'is_replicant' => 'boolean',
        'is_playable' => 'boolean',
    ];

    public function aanwijzingen()
    {
        return $this->hasMany(Aanwijzing::class);
    }

    public function artwork()
    {
        return $this->morphMany(Afbeelding::class, 'imageable');
    }

    public function scenePersonages()
    {
        return $this->hasMany(ScenePersonage::class);
    }
}
