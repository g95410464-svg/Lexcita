<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDatabaseSecurity extends Command
{
    protected $signature = 'security:database-audit';
    protected $description = 'Read PostgreSQL role and RLS metadata without exposing credentials or application records';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('Esta inspección requiere PostgreSQL; SQLite no verifica RLS.');
            return self::FAILURE;
        }

        try {
            $report = [
                'role' => DB::selectOne('SELECT current_user AS name, rolsuper AS superuser, rolbypassrls AS bypass_rls FROM pg_roles WHERE rolname = current_user'),
                'tables' => DB::select("SELECT c.relname AS name, pg_get_userbyid(c.relowner) AS owner, c.relrowsecurity AS rls_enabled, c.relforcerowsecurity AS rls_forced FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = 'public' AND c.relkind IN ('r', 'p') ORDER BY c.relname"),
                'policies' => DB::select("SELECT tablename, policyname, roles, cmd FROM pg_policies WHERE schemaname = 'public' ORDER BY tablename, policyname"),
                'cache_store' => config('cache.default'),
                'rate_limit_store' => config('cache.limiter') ?: config('cache.default'),
                'railway_ingress_trusted' => (bool) config('traffic.railway_ingress'),
            ];
        } catch (\Throwable) {
            $this->error('No se pudo leer la configuración de seguridad. Revisa la conexión y sus permisos.');
            return self::FAILURE;
        }

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return self::SUCCESS;
    }
}
