<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class RescheduleOption extends Model
{
    protected $guarded = ['id'];
    public function start(): CarbonImmutable { return CarbonImmutable::parse($this->starts_at, 'UTC'); }
    public function end(): CarbonImmutable { return CarbonImmutable::parse($this->ends_at, 'UTC'); }
    public function proposal() { return $this->belongsTo(RescheduleProposal::class, 'proposal_id'); }
}
