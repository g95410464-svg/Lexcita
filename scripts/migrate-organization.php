<?php

// A private, short-lived deployment task. Never run this in the web container.
require __DIR__.'/../vendor/autoload.php';

$fail = static function (string $message): never {
    fwrite(STDERR, $message."\n");
    exit(1);
};
$stage = 'configuración';
try {
    $database = getenv('ORGANIZATION_DATABASE');
    $password = getenv('ORGANIZATION_OWNER_PASSWORD');
    if (!is_string($database) || !preg_match('/\Alexcita_[a-z][a-z0-9_]{0,39}\z/', $database)) {
        $fail('Falta ORGANIZATION_DATABASE o su formato no es válido. Configúralo en SLI-Setup.');
    }
    if (!is_string($password) || $password === '') {
        $fail('Falta ORGANIZATION_OWNER_PASSWORD. Ejecuta este comando en SLI-Setup con sus variables privadas, no en SLI-Lexcita.');
    }
    if (strlen($password) < 32) {
        $fail('ORGANIZATION_OWNER_PASSWORD debe contener la contraseña existente del rol propietario y tener al menos 32 caracteres. No generes otra contraseña.');
    }
    if (!getenv('DB_HOST')) {
        $fail('Falta DB_HOST en SLI-Setup. Usa el host privado de PostgreSQL de esta organización.');
    }
    if (!extension_loaded('pdo_pgsql')) {
        $fail('La imagen de SLI-Setup requiere la extensión PHP pdo_pgsql. Usa deploy/Dockerfile.organization.');
    }
    // Disable the runtime-only profile before Laravel loads configuration.
    // A private migration service must not contain a cached web configuration.
    if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
        $fail('Hay configuración cacheada. Ejecuta php artisan config:clear en SLI-Setup y repite la migración.');
    }
    foreach (['ORGANIZATION_ISOLATED' => 'false', 'DB_URL' => '', 'DB_CONNECTION' => 'pgsql',
        'DB_DATABASE' => $database, 'DB_USERNAME' => $database.'_owner', 'DB_PASSWORD' => $password] as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    $stage = 'inicio de Laravel';
    $app = require __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $stage = 'conexión PostgreSQL';
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    $stage = 'migraciones';
    $exit = $kernel->call('migrate', ['--force' => true]);
    // Keep underlying connection errors and SQL out of administrative logs.
    if ($exit !== 0) {
        throw new RuntimeException('Migration failed');
    }
    fwrite(STDOUT, "Migraciones de la organización completadas, sin seeders.\n");
} catch (Throwable $error) {
    $hint = match ((string) $error->getCode()) {
        '28P01' => 'La contraseña del propietario fue rechazada. Revisa la credencial existente en SLI-Setup.',
        '42501' => 'El rol propietario no tiene los permisos necesarios para migrar este esquema.',
        '3D000' => 'La base de datos configurada no existe.',
        '42P01' => 'Falta una tabla que necesita una de las migraciones.',
        '42P07', '42701' => 'Ya existe una tabla o columna de la migración. Revisa si hubo una ejecución parcial.',
        default => 'Revisa DB_HOST, DB_PORT, DB_SSLMODE, la credencial existente del propietario y el estado de las migraciones en SLI-Setup.',
    };
    // Never print exception messages: they can contain connection strings or SQL values.
    fwrite(STDERR, "Falló la fase de {$stage}. {$hint}\n");
    exit(1);
}
