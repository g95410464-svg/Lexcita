<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class AgendaEvent extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['blocking' => 'boolean', 'cancelled_at' => 'datetime'];

    public function start(): CarbonImmutable { return CarbonImmutable::parse($this->starts_at, 'UTC'); }
    public function end(): CarbonImmutable { return CarbonImmutable::parse($this->ends_at, 'UTC'); }
    public function location() { return $this->belongsTo(AgendaLocation::class); }
}
