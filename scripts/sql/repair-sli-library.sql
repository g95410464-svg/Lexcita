-- Execute with psql as the database administrator, connected to lexcita_sli.
-- Equivalent to the two SLI library migrations; never removes existing records.
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
SELECT pg_advisory_xact_lock(hashtext('sli-library-schema-repair'));

CREATE TABLE IF NOT EXISTS public.sli_library_documents (
    id bigserial PRIMARY KEY,
    title varchar(180) NOT NULL,
    category varchar(80) NOT NULL,
    jurisdiction varchar(100) NOT NULL DEFAULT 'El Salvador',
    description text NULL,
    source_url varchar(2048) NULL,
    storage_path varchar(255) NULL,
    created_by bigint NULL CONSTRAINT sli_library_documents_created_by_foreign
        REFERENCES public.usuarios(id) ON DELETE SET NULL,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL
);
CREATE INDEX IF NOT EXISTS sli_library_documents_category_index
    ON public.sli_library_documents (category);

CREATE TABLE IF NOT EXISTS public.sli_library_annotations (
    id bigserial PRIMARY KEY,
    document_id bigint NOT NULL CONSTRAINT sli_library_annotations_document_id_foreign
        REFERENCES public.sli_library_documents(id) ON DELETE CASCADE,
    usuario_id bigint NOT NULL CONSTRAINT sli_library_annotations_usuario_id_foreign
        REFERENCES public.usuarios(id) ON DELETE CASCADE,
    page integer NOT NULL,
    kind varchar(20) NOT NULL,
    quote text NULL,
    note text NULL,
    rects json NULL,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL
);
CREATE INDEX IF NOT EXISTS sli_library_annotations_reader_index
    ON public.sli_library_annotations (document_id, usuario_id, page);

-- Seed only while the catalog migration is pending. An administrator who
-- removed a default source must not see it reappear on a later repair.
INSERT INTO public.sli_library_documents
    (title, category, description, source_url, created_at, updated_at)
SELECT seed.title, seed.category, seed.description, seed.source_url, LOCALTIMESTAMP, LOCALTIMESTAMP
FROM (VALUES
    ('Buscador oficial de legislación', 'Legislación',
     'Consulta legislación por nombre, decreto y otros criterios en el Departamento de Documentación Judicial.',
     'https://www.jurisprudencia.gob.sv/busqueda/busquedaLeg.php?id=2'),
    ('Constitución de la República', 'Constitucional',
     'Publicación de la Constitución en el portal oficial de la Asamblea Legislativa.',
     'https://www.asamblea.gob.sv/node/13639'),
    ('Leyes y decretos por año', 'Legislación',
     'Portal de la Asamblea Legislativa para localizar leyes, decretos y acuerdos publicados.',
     'https://www.asamblea.gob.sv/leyes-y-decretos/decretos-por-anios'),
    ('Colecciones de la Biblioteca Judicial', 'Bibliografía jurídica',
     'Catálogo de colecciones jurídicas de la Corte Suprema de Justicia; consulta las condiciones de acceso de cada material.',
     'https://www.csj.gob.sv/biblioteca-judicial-colecciones/')
) AS seed(title, category, description, source_url)
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations
    WHERE migration = '2026_10_20_000001_create_sli_library_documents_table'
) AND NOT EXISTS (
    SELECT 1 FROM public.sli_library_documents existing WHERE existing.source_url = seed.source_url
);

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE
    public.sli_library_documents, public.sli_library_annotations TO lexcita_sli_app;
GRANT USAGE, SELECT ON SEQUENCE
    public.sli_library_documents_id_seq, public.sli_library_annotations_id_seq TO lexcita_sli_app;

INSERT INTO public.migrations (migration, batch)
SELECT pending.migration, (SELECT COALESCE(MAX(batch), 0) + 1 FROM public.migrations)
FROM (VALUES
    ('2026_10_20_000001_create_sli_library_documents_table'),
    ('2026_10_22_000002_create_sli_library_annotations_table')
) AS pending(migration)
WHERE NOT EXISTS (SELECT 1 FROM public.migrations applied WHERE applied.migration = pending.migration);

COMMIT;
SELECT 'Biblioteca SLI: catálogo, anotaciones y permisos preparados.' AS resultado;
