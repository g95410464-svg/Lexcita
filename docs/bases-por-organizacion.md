# Una base PostgreSQL por organización

Primera organización: **Servicios Legales Integrados S.A. de C.V.** (`servicios-legales-integrados`). Su base nueva será `lexcita_sli`, sin importar usuarios, citas ni sesiones de la instalación actual. El nombre de la empresa fue proporcionado por el usuario; no se ha verificado su denominación registral.

## Arquitectura

Cada organización usa el mismo código de Lexcita en un despliegue distinto. El servidor web y sus workers reciben únicamente el usuario PostgreSQL de esa organización. La aplicación no selecciona una base desde parámetros, cookies ni cabeceras del navegador.

- `lexcita_sli_owner`: ejecuta migraciones desde un proceso administrativo separado. No se entrega al servicio web.
- `lexcita_sli_app`: lee y modifica filas, usa secuencias, sesiones, caché y colas. No es propietario, superusuario ni miembro de otros roles. No tiene `CREATEDB`, `CREATEROLE`, `BYPASSRLS`, `TRUNCATE` ni permiso para crear tablas.
- El administrador del servidor PostgreSQL crea los recursos. Sus credenciales nunca se entregan a la aplicación.

Dos bases en un mismo servidor siguen compartiendo recursos y administrador. Para evitar que las credenciales administrativas de la instalación antigua den acceso a esta organización, se recomienda una instancia PostgreSQL nueva para SLI. Esto añade consumo facturable de Railway. Si posteriormente se comparten instancias, todas las aplicaciones deben usar roles limitados y todas las bases de organizaciones deben revocar `CONNECT` de `PUBLIC`.

Este modelo separa organizaciones; **no implementa Row Level Security dentro de cada base**. Los permisos de cliente, abogado y administrador siguen dependiendo de la autorización de Laravel. Tampoco elimina inyecciones SQL: deben mantenerse consultas parametrizadas y validación, además de los permisos limitados.

## Preparación de la base vacía

`scripts/provision-organization.php` requiere PHP con `pdo_pgsql` y estas variables suministradas por un gestor de secretos o un servicio administrativo privado:

| Variable | Valor o finalidad |
|---|---|
| `PGHOST`, `PGPORT`, `PGUSER`, `PGPASSWORD` | Conexión administrativa a PostgreSQL |
| `PGSSLMODE` | `require` por defecto; `verify-full` cuando haya CA y hostname verificables |
| `ORGANIZATION_ID` | `servicios-legales-integrados` |
| `ORGANIZATION_NAME` | `Servicios Legales Integrados S.A. de C.V.` |
| `ORGANIZATION_DATABASE` | `lexcita_sli` |
| `ORGANIZATION_OWNER_PASSWORD` | Secreto aleatorio exclusivo de al menos 32 caracteres |
| `ORGANIZATION_APP_PASSWORD` | Otro secreto aleatorio exclusivo de al menos 32 caracteres |

Ejecutar una vez desde ese proceso, sin endpoint HTTP:

```sh
php scripts/provision-organization.php
```

El script usa `template0`, rechaza nombres inseguros y recursos existentes, revoca acceso público, crea una identidad de organización que el rol de aplicación solo puede leer y configura permisos para tablas futuras del migrador. Limita el rol de aplicación a 20 conexiones, consultas de 15 segundos, espera de bloqueos de 5 segundos y transacciones inactivas de 30 segundos. Estos límites son iniciales, deben ajustarse con mediciones. No crea cuentas de usuarios de Lexcita ni ejecuta seeders.

Un fallo puede dejar recursos parciales; no se eliminan automáticamente ni se habilitan las credenciales de aplicación antes de terminar. Inspeccionarlos con una cuenta administrativa antes de repetir. El script no rota contraseñas ni reutiliza bases existentes.

## Migraciones y despliegue

1. Usar un proceso privado de migración con `DB_HOST`, `DB_PORT`, `DB_SSLMODE`, `ORGANIZATION_DATABASE=lexcita_sli` y `ORGANIZATION_OWNER_PASSWORD`. Ejecutar `php scripts/migrate-organization.php`: desactiva el perfil de ejecución, elimina `DB_URL` de su proceso y ejecuta migraciones con el rol `_owner`. Rechaza configuración cacheada. No ejecutar `migrate:fresh` ni `db:seed` en esta organización.
2. Configurar el servicio web usando `deploy/organizations/servicios-legales-integrados.env.example`. La clave `APP_KEY` debe ser nueva y compartirse solo entre los procesos de esta organización. El servicio usa el rol `_app`; nunca las variables administrativas del paso anterior.
3. Generar una dirección HTTPS de Railway para el servicio y establecerla en `APP_URL`. No hace falta un dominio propio para esta etapa. Activar `ORGANIZATION_ISOLATED=true` solo cuando la base, identidad y migraciones estén listas.
4. Ejecutar `php artisan config:clear` y `php artisan organization:check` antes de permitir tráfico. El segundo comando termina con error si la identidad, permisos o conexión son incorrectos. No registra contraseñas ni filas de clientes. Repetir después de cambios de permisos; no añade consultas a cada petición web.
5. Iniciar el servidor web sin migraciones automáticas. El `CMD` del Dockerfile antiguo ejecuta migraciones; debe reemplazarse en el servicio aislado. Configurar el servidor HTTP de producción y `GET /up` como healthcheck.
6. Iniciar un worker propio con `php artisan queue:work --tries=3 --timeout=60`. Comparte variables y base de SLI. No consumir la cola desde workers de otras organizaciones.
7. Configurar una aplicación Reverb propia, con ID, key y secret nuevos. Reconstruir el frontend con sus `VITE_REVERB_*` públicos. El perfil restringe los orígenes Reverb al hostname de `APP_URL`. El servidor Reverb puede ser separado; no reutilizar las claves de la instalación anterior.

El perfil obliga a guardar sesiones, caché, límites de solicitudes y colas en la base de la organización. La cookie usa prefijo `__Host-`, HTTPS, `HttpOnly`, ruta `/` y ningún dominio compartido. Rechaza otros hosts con HTTP 421 antes de iniciar sesión; `GET /up` permite el host interno de Railway.

Cada despliegue debe tener almacenamiento privado propio y persistente si se guardan archivos. No montar el mismo volumen de archivos entre organizaciones. El perfil utiliza disco local; no compartir buckets, credenciales S3 ni Redis mediante simples prefijos. Los respaldos y accesos administrativos deben separarse también. Probar restauración antes de recibir expedientes reales.

PayPal, Google OAuth y WhatsApp requieren sus propias decisiones de cuenta, callbacks y credenciales antes de habilitar esos flujos en la nueva dirección. El registro clásico no depende de Google.

## Cloudflare

Mientras se use exclusivamente el dominio generado por Railway, Cloudflare no está conectado a esta organización. Mantener `CLOUDFLARE_ORIGIN_SECRET` sin definir. Cuando exista un dominio propio, configurar DNS/proxy y la cabecera de origen, cambiar `APP_URL`, callbacks y frontend Reverb, y después activar el bloqueo de origen. Un cambio de host invalida la continuidad de las cookies del navegador y exige volver a iniciar sesión.

## Verificación reproducible

```sh
php vendor/phpunit/phpunit/phpunit
# PostgreSQL local desechable: usuario lexcita_test_admin y contraseña
# en PGTEST_ADMIN_PASSWORD; escucha exclusivamente en 127.0.0.1.
php -d extension=pdo_pgsql vendor/phpunit/phpunit/phpunit tests/Integration
```

La integración crea dos bases con nombres aleatorios, aplica migraciones, prueba registro y dashboard, impide conexión cruzada y operaciones DDL/SET ROLE/TRUNCATE, comprueba sesiones y caché independientes y elimina únicamente sus recursos de prueba. Se omite cuando falta el servidor local o `pdo_pgsql`.

Referencias: [privilegios PostgreSQL](https://www.postgresql.org/docs/current/ddl-priv.html), [bases por organización en PostgreSQL](https://docs.aws.amazon.com/prescriptive-guidance/latest/saas-multitenant-managed-postgresql/bridge.html).
