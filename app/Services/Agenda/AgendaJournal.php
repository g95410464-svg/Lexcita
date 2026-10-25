<?php

namespace App\Services\Agenda;

use Illuminate\Support\Facades\DB;

class AgendaJournal
{
    public function notify(int $user, string $key, string $message, string $url): void
    {
        // Stored in the same transaction. Invisible to other requests until
        // commit; no email/push can escape a rolled-back agenda operation.
        DB::table('agenda_notifications')->insertOrIgnore([
            'usuario_id' => $user, 'dedupe_key' => $key, 'message' => $message,
            'url' => $url, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function audit(int $lawyer, ?int $actor, string $action, string $type, int $id, ?array $before, ?array $after): void
    {
        DB::table('agenda_audits')->insert([
            'abogado_id' => $lawyer, 'actor_id' => $actor, 'action' => $action,
            'subject_type' => $type, 'subject_id' => $id,
            'before' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
