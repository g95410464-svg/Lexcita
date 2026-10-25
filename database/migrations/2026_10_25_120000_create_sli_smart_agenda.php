<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_locations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('abogado_id')->constrained('usuarios')->cascadeOnDelete();
            $t->string('name', 120);
            $t->string('address', 255)->nullable();
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['abogado_id', 'active']);
        });
        Schema::create('agenda_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('abogado_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            foreach (['base_location_id', 'day_start_location_id', 'day_end_location_id'] as $field) {
                $t->foreignId($field)->nullable()->constrained('agenda_locations')->nullOnDelete();
            }
            $t->string('transport_mode', 30)->default('driving-car');
            $t->unsignedSmallInteger('margin_minutes')->default(15);
            $t->unsignedSmallInteger('virtual_buffer_minutes')->default(0);
            $t->timestamps();
        });
        Schema::create('agenda_manual_routes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('abogado_id')->constrained('usuarios')->cascadeOnDelete();
            $t->foreignId('origin_id')->constrained('agenda_locations')->cascadeOnDelete();
            $t->foreignId('destination_id')->constrained('agenda_locations')->cascadeOnDelete();
            $t->string('mode', 30);
            $t->unsignedSmallInteger('minutes');
            $t->timestamps();
            $t->unique(['abogado_id', 'origin_id', 'destination_id', 'mode'], 'agenda_manual_route_unique');
            $t->index('destination_id');
        });
        Schema::create('agenda_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('abogado_id')->constrained('usuarios')->cascadeOnDelete();
            $t->string('title', 180);
            $t->string('type', 30);
            // These two instants are always written and interpreted in UTC.
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->foreignId('location_id')->nullable()->constrained('agenda_locations')->nullOnDelete();
            $t->string('modality', 20)->default('virtual');
            $t->boolean('blocking')->default(true);
            $t->string('transport_mode', 30)->default('driving-car');
            $t->unsignedSmallInteger('margin_minutes')->default(15);
            $t->text('private_note')->nullable();
            $t->uuid('series_id')->nullable()->index();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
            $t->index(['abogado_id', 'starts_at']);
            $t->index(['abogado_id', 'ends_at']);
        });
        Schema::table('citas', function (Blueprint $t): void {
            $t->foreignId('agenda_location_id')->nullable()->constrained('agenda_locations')->nullOnDelete();
        });
        Schema::create('agenda_conflicts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('abogado_id')->constrained('usuarios')->cascadeOnDelete();
            $t->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $t->foreignId('event_id')->nullable()->constrained('agenda_events')->nullOnDelete();
            $t->string('cause_key', 100);
            $t->string('reason', 180);
            $t->string('status', 30)->default('abierto');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
            $t->unique(['cita_id', 'cause_key']);
            $t->index(['abogado_id', 'status']);
        });
        Schema::create('reschedule_proposals', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('conflict_id')->constrained('agenda_conflicts')->cascadeOnDelete();
            $t->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('usuarios')->cascadeOnDelete();
            $t->string('status', 30)->default('enviada');
            $t->dateTime('expires_at'); // UTC
            $t->timestamp('responded_at')->nullable();
            $t->timestamps();
            $t->index(['cita_id', 'status']);
            $t->index(['status', 'expires_at']);
        });
        Schema::create('reschedule_options', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('proposal_id')->constrained('reschedule_proposals')->cascadeOnDelete();
            $t->dateTime('starts_at'); // UTC
            $t->dateTime('ends_at');
            $t->foreignId('location_id')->nullable()->constrained('agenda_locations')->nullOnDelete();
            $t->string('status', 20)->default('ofrecida');
            $t->timestamps();
            $t->index(['proposal_id', 'status']);
        });
        Schema::create('agenda_client_availability', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $t->foreignId('cliente_id')->constrained('usuarios')->cascadeOnDelete();
            $t->json('windows');
            $t->string('comment', 500)->nullable();
            $t->boolean('allow_later_dates')->default(false);
            $t->timestamps();
            $t->index(['cita_id', 'created_at']);
        });
        Schema::create('agenda_notifications', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $t->string('dedupe_key', 150)->unique();
            $t->string('message', 180);
            $t->string('url', 255);
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['usuario_id', 'read_at']);
        });
        Schema::create('agenda_audits', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('abogado_id')->constrained('usuarios')->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->string('action', 60);
            $t->string('subject_type', 60);
            $t->unsignedBigInteger('subject_id');
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->timestamp('created_at');
            $t->index(['abogado_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['agenda_audits', 'agenda_notifications', 'agenda_client_availability',
            'reschedule_options', 'reschedule_proposals', 'agenda_conflicts'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('citas', fn (Blueprint $t) => $t->dropConstrainedForeignId('agenda_location_id'));
        foreach (['agenda_events', 'agenda_manual_routes', 'agenda_settings', 'agenda_locations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
