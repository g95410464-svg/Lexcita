<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class RescheduleProposal extends Model
{
    protected $guarded = ['id'];
    public function expiry(): CarbonImmutable { return CarbonImmutable::parse($this->expires_at, 'UTC'); }
    public function cita() { return $this->belongsTo(Cita::class); }
    public function conflict() { return $this->belongsTo(AgendaConflict::class); }
    public function options() { return $this->hasMany(RescheduleOption::class, 'proposal_id'); }
}
