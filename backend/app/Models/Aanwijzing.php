<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aanwijzing extends Model
{
    use HasFactory;

    protected $table = 'aanwijzingen';

    protected $fillable = [
        'titel',
        'beschrijving',
        'type',
        'data',
        'personage_id',
        'locatie_id',
        'is_kritisch',
    ];

    protected $casts = [
        'is_kritisch' => 'boolean',
        'data' => 'array',
    ];

    public function personage()
    {
        return $this->belongsTo(Personage::class);
    }

    public function locatie()
    {
        return $this->belongsTo(Locatie::class);
    }

    public function artwork()
    {
        return $this->morphMany(Afbeelding::class, 'imageable');
    }
}
