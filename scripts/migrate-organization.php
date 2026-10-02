<?php

// A private, short-lived deployment task. Never run this in the web container.
require __DIR__.'/../vendor/autoload.php';

try {
    $database = getenv('ORGANIZATION_DATABASE');
    $password = getenv('ORGANIZATION_OWNER_PASSWORD');
    if (!is_string($database) || !preg_match('/\Alexcita_[a-z][a-z0-9_]{0,39}\z/', $database)
        || !is_string($password) || strlen($password) < 32) {
        throw new RuntimeException('Missing migration configuration');
    }
    // Disable the runtime-only profile before Laravel loads configuration.
    // A private migration service must not contain a cached web configuration.
    if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
        throw new RuntimeException('Cached configuration is not allowed here');
    }
    foreach (['ORGANIZATION_ISOLATED' => 'false', 'DB_URL' => '', 'DB_CONNECTION' => 'pgsql',
        'DB_DATABASE' => $database, 'DB_USERNAME' => $database.'_owner', 'DB_PASSWORD' => $password] as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    $app = require __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $exit = $kernel->call('migrate', ['--force' => true]);
    // Keep underlying connection errors and SQL out of administrative logs.
    if ($exit !== 0) {
        throw new RuntimeException('Migration failed');
    }
    fwrite(STDOUT, "Migraciones de la organización completadas, sin seeders.\n");
} catch (Throwable) {
    fwrite(STDERR, "No se pudieron completar las migraciones de la organización. Revisa variables, permisos y versión del esquema.\n");
    exit(1);
}
