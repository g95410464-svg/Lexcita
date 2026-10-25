<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaLocation extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['latitude' => 'float', 'longitude' => 'float', 'active' => 'boolean'];
}
