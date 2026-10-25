<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class AgendaModule
{
    private ?bool $schemaReady = null;

    public function ready(): bool
    {
        if (config('organization.id') !== 'servicios-legales-integrados' || !config('agenda.enabled')) {
            return false;
        }
        return $this->schemaReady ??= collect(['agenda_locations', 'agenda_settings', 'agenda_manual_routes',
            'agenda_events', 'agenda_conflicts', 'reschedule_proposals', 'reschedule_options',
            'agenda_client_availability', 'agenda_notifications', 'agenda_audits'])
            ->every(fn ($table) => Schema::hasTable($table))
            && Schema::hasColumn('citas', 'agenda_location_id');
    }
}
