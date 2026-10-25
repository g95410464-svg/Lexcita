# Prueba controlada de 100 usuarios

`scripts/agenda-concurrency.php` libera 100 procesos PHP desde una barrera común,
primero para consultar disponibilidad y después para competir por el mismo horario.
Comprueba 100 lecturas correctas, exactamente un apartado y 99 rechazos normales.
Después avanza el reloj simulado 15 minutos y un segundo: otro cliente debe poder
apartar la hora impaga. Una cita pagada continúa ocupando su hora.

No mide capacidad HTTP de producción, ancho de banda, OAuth, correo ni PayPal.
No envía códigos ni captura pagos. Los percentiles corresponden a llamadas del
servicio contra PostgreSQL de pruebas, con el tiempo de espera por el cerrojo incluido.

## Ejecución remota

El workflow `.github/workflows/sli-agenda.yml` se ejecuta al subir cambios pertinentes
a `feat/sli-landing`. Usa PostgreSQL desechable **en el runner de GitHub**, nunca
Docker en la PC del propietario. Publica `sli-concurrency-report` como artefacto.
Su resultado debe comprobarse antes de marcar la prueba terminada.

También puede ejecutarse en una rama y servicio de pruebas de Railway Linux con
PHP/pcntl/posix y una base exclusiva `lexcita_sli_loadtest`, vacía de usuarios.
Se necesitan al menos 110 conexiones disponibles (el workflow configura 160) y
memoria suficiente para 100 procesos. Tiempo máximo de cada SQL: 20 segundos;
espera de cerrojo: 15 segundos; barrera: 45 segundos. El job completo tiene límite
de 12 minutos. No apuntar al PostgreSQL de producción de SLI.

Variables del servicio **de pruebas**:

```dotenv
APP_ENV=testing
APP_DEBUG=false
ORGANIZATION_ISOLATED=false
ORGANIZATION_ID=servicios-legales-integrados
DB_CONNECTION=pgsql
DB_DATABASE=lexcita_sli_loadtest
AGENDA_LOAD_CONFIRM=isolated-test-database
CACHE_STORE=array
SESSION_DRIVER=array
MAIL_MAILER=array
BROADCAST_CONNECTION=null
QUEUE_CONNECTION=sync
```

Agregar APP_KEY y credenciales de esa base de pruebas de manera privada. El script
verifica tanto el nombre configurado como `current_database()` antes de migrar o
crear datos, y rechaza bases que ya contengan usuarios. No usa `migrate:fresh`.

```sh
php scripts/agenda-concurrency.php
```

Los usuarios temporales se retiran al terminar; las tablas quedan para inspección
en la base de pruebas. Un proceso abortado por el host puede dejar fixtures:
el siguiente intento se negará a continuar, para no borrar datos sin revisión.
