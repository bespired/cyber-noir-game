<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instelling extends Model
{
    protected $table = 'instellingen';

    protected $fillable = [
        'sleutel',
        'waarde',
    ];

    public static function getWaarde($sleutel, $default = null)
    {
        $instelling = self::where('sleutel', $sleutel)->first();
        return $instelling ? $instelling->waarde : $default;
    }
}
