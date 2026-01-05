<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scene extends Model
{
    use HasFactory;

    protected $fillable = [
        'locatie_id',
        'sector_id',
        'titel',
        'type',
        'beschrijving',
        'status',
        'gateways',
        'data',
    ];

    public function locatie()
    {
        return $this->belongsTo(Locatie::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function artwork()
    {
        return $this->morphMany(Afbeelding::class, 'imageable');
    }

    public function scenePersonages()
    {
        return $this->hasMany(ScenePersonage::class);
    }

    protected $casts = [
        'gateways' => 'array',
        'data' => 'array',
    ];
}
