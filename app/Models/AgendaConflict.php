<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaConflict extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['resolved_at' => 'datetime'];
    public function cita() { return $this->belongsTo(Cita::class); }
    public function event() { return $this->belongsTo(AgendaEvent::class); }
    public function proposals() { return $this->hasMany(RescheduleProposal::class, 'conflict_id'); }
}
