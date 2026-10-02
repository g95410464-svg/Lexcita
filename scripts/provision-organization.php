<?php

// Run separately from the public Laravel deployment. No .env is loaded here.
require __DIR__.'/../vendor/autoload.php';

use App\Support\OrganizationDatabaseProvisioner;

try {
    $required = ['PGHOST', 'PGPORT', 'PGUSER', 'PGPASSWORD', 'ORGANIZATION_DATABASE', 'ORGANIZATION_ID', 'ORGANIZATION_NAME', 'ORGANIZATION_OWNER_PASSWORD', 'ORGANIZATION_APP_PASSWORD'];
    foreach ($required as $key) {
        if (getenv($key) === false || getenv($key) === '') {
            throw new RuntimeException('Falta una variable requerida.');
        }
    }
    $host = getenv('PGHOST');
    $port = getenv('PGPORT');
    $sslmode = getenv('PGSSLMODE') ?: 'require';
    if (!preg_match('/\A[a-zA-Z0-9.:-]+\z/', $host) || !ctype_digit($port)
        || !in_array($sslmode, ['require', 'verify-full', 'disable'], true)) {
        throw new RuntimeException('Configuración de conexión inválida.');
    }
    $connect = static fn (string $database): PDO => new PDO(
        "pgsql:host=$host;port=$port;dbname=$database;sslmode=$sslmode;connect_timeout=10",
        getenv('PGUSER'), getenv('PGPASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    (new OrganizationDatabaseProvisioner)->provision(
        $connect('postgres'), $connect, getenv('ORGANIZATION_DATABASE'),
        getenv('ORGANIZATION_ID'), getenv('ORGANIZATION_NAME'),
        getenv('ORGANIZATION_OWNER_PASSWORD'), getenv('ORGANIZATION_APP_PASSWORD')
    );
    fwrite(STDOUT, "Base vacía creada; roles separados y acceso PUBLIC revocado. Ejecuta las migraciones con el rol _owner.\n");
} catch (Throwable) {
    // PDO errors can contain SQL and credentials. Keep them out of deploy logs.
    fwrite(STDERR, "No se pudo aprovisionar. Revisa variables, permisos administrativos y posibles recursos existentes. Los recursos existentes no se reemplazan; un fallo puede dejar recursos parciales para inspección.\n");
    exit(1);
}
