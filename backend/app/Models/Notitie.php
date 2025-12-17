<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notitie extends Model
{
    use HasFactory;

    protected $table = 'notities';

    protected $fillable = ['titel', 'inhoud', 'is_afgerond'];
}
