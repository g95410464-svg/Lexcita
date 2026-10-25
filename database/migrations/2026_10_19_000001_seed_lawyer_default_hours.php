<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DAYS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

    public function up(): void
    {
        if (config('organization.id') !== 'servicios-legales-integrados') {
            return;
        }

        if (!Schema::hasTable('usuarios') || !Schema::hasTable('horarios_disponibles')) {
            return;
        }

        // Repair lawyers already inserted directly into the database.
        foreach (DB::table('usuarios')->where('rol', 'abogado')->pluck('id') as $lawyerId) {
            $this->createMissingDefaults((int) $lawyerId);
        }

        // Keep direct SQL inserts and role changes consistent on PostgreSQL (Railway).
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION lexcita_seed_lawyer_hours() RETURNS trigger AS $$
                DECLARE day_name text;
                DECLARE should_seed boolean := false;
                BEGIN
                    IF TG_OP = 'INSERT' THEN
                        should_seed := NEW.rol = 'abogado';
                    ELSIF TG_OP = 'UPDATE' THEN
                        should_seed := NEW.rol = 'abogado' AND OLD.rol IS DISTINCT FROM NEW.rol;
                    END IF;

                    IF should_seed THEN
                        FOREACH day_name IN ARRAY ARRAY['lunes', 'martes', 'miercoles', 'jueves', 'viernes'] LOOP
                            IF NOT EXISTS (
                                SELECT 1 FROM horarios_disponibles
                                WHERE abogado_id = NEW.id AND dia_semana = day_name
                            ) THEN
                                INSERT INTO horarios_disponibles
                                    (abogado_id, dia_semana, hora_inicio, hora_fin, activo, created_at, updated_at)
                                VALUES (NEW.id, day_name, '08:00:00', '17:00:00', TRUE, NOW(), NOW());
                            END IF;
                        END LOOP;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER usuarios_seed_lawyer_hours
                AFTER INSERT OR UPDATE ON usuarios
                FOR EACH ROW EXECUTE FUNCTION lexcita_seed_lawyer_hours();
                SQL);
        }
    }

    public function down(): void
    {
        if (config('organization.id') === 'servicios-legales-integrados' && DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS usuarios_seed_lawyer_hours ON usuarios');
            DB::unprepared('DROP FUNCTION IF EXISTS lexcita_seed_lawyer_hours()');
        }

        // Keep generated hours: they may have been customized after this migration.
    }

    private function createMissingDefaults(int $lawyerId): void
    {
        foreach (self::DAYS as $day) {
            $exists = DB::table('horarios_disponibles')
                ->where('abogado_id', $lawyerId)
                ->where('dia_semana', $day)
                ->exists();

            if (!$exists) {
                DB::table('horarios_disponibles')->insert([
                    'abogado_id' => $lawyerId,
                    'dia_semana' => $day,
                    'hora_inicio' => '08:00:00',
                    'hora_fin' => '17:00:00',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
