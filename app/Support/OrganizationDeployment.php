<?php

namespace App\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ConfigurationUrlParser;
use RuntimeException;

class OrganizationDeployment
{
    public function __construct(private Repository $config) {}

    public function enabled(): bool
    {
        return (bool) $this->config->get('organization.isolated');
    }

    public function host(): string
    {
        return strtolower((string) parse_url($this->config->get('app.url'), PHP_URL_HOST));
    }

    public function validate(): void
    {
        $id = $this->config->get('organization.id');
        $database = $this->config->get('organization.database');
        if (!is_string($id) || !preg_match('/\A[a-z][a-z0-9-]{2,47}\z/', $id)
            || !is_string($database) || !preg_match('/\Alexcita_[a-z][a-z0-9_]{0,39}\z/', $database)
            || !is_string($this->config->get('organization.name')) || trim($this->config->get('organization.name')) === '') {
            throw new RuntimeException('Define ORGANIZATION_ID, ORGANIZATION_NAME y ORGANIZATION_DATABASE válidos.');
        }

        $url = parse_url((string) $this->config->get('app.url'));
        if (!$url || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
            || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
            || (isset($url['path']) && !in_array($url['path'], ['', '/'], true))) {
            throw new RuntimeException('APP_URL debe ser la dirección HTTPS de esta organización, sin rutas ni parámetros.');
        }

        $default = $this->config->get('database.default');
        $connection = (new ConfigurationUrlParser)->parseConfiguration(
            $this->config->get("database.connections.$default", [])
        );
        if (($connection['driver'] ?? '') !== 'pgsql'
            || ($connection['database'] ?? '') !== $database
            || ($connection['username'] ?? '') !== $database.'_app'
            || isset($connection['read']) || isset($connection['write'])) {
            throw new RuntimeException('La conexión debe usar la base de la organización y su rol limitado terminado en _app. Revisa también DB_URL.');
        }
    }

    public function configure(): void
    {
        if (!$this->enabled()) {
            return;
        }
        $this->validate();
        $id = $this->config->get('organization.id');
        $connection = $this->config->get('database.default');

        // These stores belong to the same database as the business records.
        // Shared Redis/S3 backends require their own access controls and are
        // deliberately not used by the initial isolated deployment profile.
        $this->config->set([
            'session.driver' => 'database',
            'session.connection' => $connection,
            'session.cookie' => '__Host-lexcita-'.$id.'-session',
            'session.domain' => null,
            'session.path' => '/',
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'cache.default' => 'database',
            'cache.limiter' => 'database',
            'cache.prefix' => 'lexcita-'.$id.'-',
            'cache.stores.database.connection' => $connection,
            'cache.stores.database.lock_connection' => $connection,
            'queue.default' => 'database',
            'queue.connections.database.connection' => $connection,
            'queue.connections.database.queue' => 'default',
            'queue.batching.database' => $connection,
            'queue.failed.database' => $connection,
            'filesystems.default' => 'local',
            'reverb.apps.apps.0.allowed_origins' => [$this->host()],
        ]);
    }
}
