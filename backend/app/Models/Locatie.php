<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locatie extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_id',
        'naam',
        'beschrijving',
        'notities',
    ];

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

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
