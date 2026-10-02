<?php

namespace App\Support;

use InvalidArgumentException;
use PDO;
use RuntimeException;

/** Used only from an administrative process, never from a web request. */
class OrganizationDatabaseProvisioner
{
    public function provision(PDO $admin, callable $connectDatabase, string $database, string $id, string $name, string $ownerPassword, string $appPassword): void
    {
        if (!preg_match('/\Alexcita_[a-z][a-z0-9_]{0,39}\z/', $database)
            || !preg_match('/\A[a-z][a-z0-9-]{2,47}\z/', $id) || trim($name) === ''
            || strlen($ownerPassword) < 32 || strlen($appPassword) < 32
            || hash_equals($ownerPassword, $appPassword)) {
            throw new InvalidArgumentException('Identidad inválida o contraseñas débiles/reutilizadas.');
        }
        $owner = $database.'_owner';
        $app = $database.'_app';

        // Identifiers cannot be bound as SQL values. The allowlist above is
        // intentionally narrow, and every identifier is also double-quoted.
        $qdb = '"'.$database.'"';
        $qowner = '"'.$owner.'"';
        $qapp = '"'.$app.'"';
        $check = $admin->prepare('SELECT datname FROM pg_database WHERE datname = ?');
        $check->execute([$database]);
        $roles = $admin->prepare('SELECT rolname FROM pg_roles WHERE rolname IN (?, ?)');
        $roles->execute([$owner, $app]);
        if ($check->fetchColumn() !== false || $roles->fetchColumn() !== false) {
            throw new RuntimeException('La base o sus roles ya existen. No se modificó ningún recurso existente.');
        }

        // A failed provisioning operation never enables these credentials.
        // Do not auto-delete partial resources: let an administrator inspect them.
        $admin->exec("CREATE ROLE $qowner NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOREPLICATION NOBYPASSRLS");
        $admin->exec("CREATE ROLE $qapp NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOREPLICATION NOBYPASSRLS CONNECTION LIMIT 20");
        $admin->exec("CREATE DATABASE $qdb OWNER $qowner TEMPLATE template0 ENCODING 'UTF8' ALLOW_CONNECTIONS false");
        $admin->exec("REVOKE ALL ON DATABASE $qdb FROM PUBLIC");
        $admin->exec("GRANT CONNECT ON DATABASE $qdb TO $qapp");
        $admin->exec("ALTER DATABASE $qdb ALLOW_CONNECTIONS true");

        $db = $connectDatabase($database);
        $db->beginTransaction();
        try {
            $db->exec("REVOKE ALL ON SCHEMA public FROM PUBLIC");
            $db->exec("ALTER SCHEMA public OWNER TO $qowner");
            $db->exec("GRANT USAGE ON SCHEMA public TO $qapp");
            $db->exec("ALTER DEFAULT PRIVILEGES FOR ROLE $qowner IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO $qapp");
            $db->exec("ALTER DEFAULT PRIVILEGES FOR ROLE $qowner IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO $qapp");
            $db->exec("ALTER DEFAULT PRIVILEGES FOR ROLE $qowner REVOKE EXECUTE ON FUNCTIONS FROM PUBLIC");
            $db->exec('CREATE TABLE public.lexcita_organization (singleton boolean PRIMARY KEY DEFAULT true CHECK (singleton), id text NOT NULL UNIQUE, name text NOT NULL)');
            $insert = $db->prepare('INSERT INTO public.lexcita_organization (id, name) VALUES (?, ?)');
            $insert->execute([$id, $name]);
            $db->exec("GRANT SELECT ON public.lexcita_organization TO $qapp, $qowner");
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $admin->exec("ALTER ROLE $qapp SET statement_timeout = '15s'");
        $admin->exec("ALTER ROLE $qapp SET lock_timeout = '5s'");
        $admin->exec("ALTER ROLE $qapp SET idle_in_transaction_session_timeout = '30s'");
        // PDO quotes password literals, including embedded apostrophes. These
        // statements and exceptions must never be written to application logs.
        $admin->exec("ALTER ROLE $qowner LOGIN PASSWORD ".$admin->quote($ownerPassword));
        $admin->exec("ALTER ROLE $qapp LOGIN PASSWORD ".$admin->quote($appPassword));
    }
}
