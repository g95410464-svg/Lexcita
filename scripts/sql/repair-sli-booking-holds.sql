-- Run with psql against lexcita_sli as its database administrator.
-- This is equivalent to 2026_10_22_120000_add_temporary_booking_holds,
-- with explicit runtime grants and safe repetition after a partial deployment.
\set ON_ERROR_STOP on
BEGIN;
DO $$
BEGIN
    IF current_database() <> 'lexcita_sli' THEN
        RAISE EXCEPTION 'Esta reparación solo se puede ejecutar en lexcita_sli.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM public.lexcita_organization WHERE id = 'servicios-legales-integrados') THEN
        RAISE EXCEPTION 'La identidad de la base no corresponde a SLI.';
    END IF;
END $$;
SET LOCAL ROLE lexcita_sli_owner;
SELECT pg_advisory_xact_lock(hashtext('sli-booking-schema-repair'));

ALTER TABLE public.citas ADD COLUMN IF NOT EXISTS hold_expires_at timestamp(0) without time zone NULL;
CREATE INDEX IF NOT EXISTS citas_hold_expires_at_index ON public.citas (hold_expires_at);
UPDATE public.citas
SET hold_expires_at = COALESCE(created_at, LOCALTIMESTAMP) + INTERVAL '15 minutes'
WHERE estado = 'pendiente_pago' AND payment_status IS DISTINCT FROM 'completed'
    AND hold_expires_at IS NULL;

CREATE TABLE IF NOT EXISTS public.booking_holds (
    id bigserial PRIMARY KEY,
    token uuid NOT NULL CONSTRAINT booking_holds_token_unique UNIQUE,
    cliente_id bigint NOT NULL CONSTRAINT booking_holds_cliente_id_foreign REFERENCES public.usuarios(id) ON DELETE CASCADE,
    abogado_id bigint NOT NULL CONSTRAINT booking_holds_abogado_id_foreign REFERENCES public.usuarios(id) ON DELETE CASCADE,
    fecha date NOT NULL,
    hora_inicio time(0) without time zone NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL,
    CONSTRAINT booking_holds_abogado_id_fecha_hora_inicio_unique UNIQUE (abogado_id, fecha, hora_inicio)
);
CREATE INDEX IF NOT EXISTS booking_holds_expires_at_index ON public.booking_holds (expires_at);
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE public.booking_holds TO lexcita_sli_app;
GRANT USAGE, SELECT ON SEQUENCE public.booking_holds_id_seq TO lexcita_sli_app;

INSERT INTO public.migrations (migration, batch)
SELECT '2026_10_22_120000_add_temporary_booking_holds', COALESCE(MAX(batch), 0) + 1
FROM public.migrations
HAVING NOT EXISTS (
    SELECT 1 FROM public.migrations
    WHERE migration = '2026_10_22_120000_add_temporary_booking_holds'
);
COMMIT;
SELECT 'Apartados SLI: esquema, permisos y registro de migración completados.' AS resultado;
