<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scene extends Model
{
    use HasFactory;

    protected $fillable = [
        'locatie_id',
        'titel',
        'type',
        'beschrijving',
        'entry_point',
        'exit_point',
        'status',
    ];

    public function locatie()
    {
        return $this->belongsTo(Locatie::class);
    }
}
