<?php

namespace App\Console\Commands;

use App\Support\OrganizationDeployment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOrganization extends Command
{
    protected $signature = 'organization:check';
    protected $description = 'Verify database identity and runtime isolation without changing records';

    public function handle(OrganizationDeployment $organization): int
    {
        if (!$organization->enabled()) {
            $this->error('El perfil ORGANIZATION_ISOLATED no está habilitado.');
            return self::FAILURE;
        }
        try {
            $organization->validate();
            $role = DB::selectOne('SELECT current_database() AS database, current_user AS name, rolsuper, rolbypassrls, rolcreatedb, rolcreaterole, rolreplication FROM pg_roles WHERE rolname = current_user');
            $identity = DB::table('lexcita_organization')->sole();
            $privileges = DB::selectOne("SELECT
                has_database_privilege(current_user, current_database(), 'CREATE') AS create_database_objects,
                has_schema_privilege(current_user, 'public', 'CREATE') AS create_tables,
                EXISTS (SELECT 1 FROM pg_roles WHERE rolname <> current_user AND pg_has_role(current_user, oid, 'MEMBER')) AS memberships,
                EXISTS (SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = 'public' AND pg_get_userbyid(c.relowner) = current_user) AS owns_objects,
                EXISTS (SELECT 1 FROM pg_database WHERE datname <> current_database() AND left(datname, 8) = 'lexcita_' AND has_database_privilege(current_user, oid, 'CONNECT')) AS other_organization_access,
                EXISTS (SELECT 1 FROM pg_database d, LATERAL aclexplode(coalesce(d.datacl, acldefault('d', d.datdba))) a WHERE d.datname = current_database() AND a.grantee = 0 AND a.privilege_type = 'CONNECT') AS public_access");
            $checks = [
                'database_matches' => $role->database === config('organization.database'),
                'organization_matches' => $identity->id === config('organization.id') && $identity->name === config('organization.name'),
                'runtime_role_matches' => $role->name === config('organization.database').'_app',
                'no_elevated_role' => !$role->rolsuper && !$role->rolbypassrls && !$role->rolcreatedb && !$role->rolcreaterole && !$role->rolreplication,
                'no_ddl_or_ownership' => !$privileges->create_database_objects && !$privileges->create_tables && !$privileges->owns_objects,
                'no_role_memberships' => !$privileges->memberships,
                'no_other_organization_access' => !$privileges->other_organization_access,
                'no_public_database_access' => !$privileges->public_access,
                'schema_ready' => DB::getSchemaBuilder()->hasTable('usuarios') && DB::getSchemaBuilder()->hasTable('citas') && DB::getSchemaBuilder()->hasTable('sessions'),
            ];
            $this->line(json_encode($checks, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable) {
            $this->error('No se pudo verificar el aislamiento. Revisa conexión, identidad y migraciones; no se mostraron credenciales.');
            return self::FAILURE;
        }
    }
}
