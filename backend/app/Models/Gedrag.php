<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gedrag extends Model
{
    use HasFactory;

    protected $table = 'gedragingen';

    protected $fillable = [
        'naam',
        'beschrijving',
        'acties',
    ];

    protected $casts = [
        'acties' => 'array',
    ];

    public function scenePersonages()
    {
        return $this->hasMany(ScenePersonage::class, 'gedrag_id');
    }
}
