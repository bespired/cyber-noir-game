<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScenePersonage extends Model
{
    use HasFactory;

    protected $fillable = [
        'scene_id',
        'personage_id',
        'spawn_point_name',
        'spawn_condition',
        'gedrag_id',
        'dialoog_id',
    ];

    protected $casts = [
        'spawn_condition' => 'array',
    ];

    public function scene()
    {
        return $this->belongsTo(Scene::class);
    }

    public function personage()
    {
        return $this->belongsTo(Personage::class);
    }

    public function gedrag()
    {
        return $this->belongsTo(Gedrag::class, 'gedrag_id');
    }

    public function dialoog()
    {
        return $this->belongsTo(Dialoog::class, 'dialoog_id');
    }
}
