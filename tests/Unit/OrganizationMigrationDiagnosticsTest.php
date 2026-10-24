<?php

namespace Tests\Unit;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class OrganizationMigrationDiagnosticsTest extends TestCase
{
    private function runMigrator(array $environment): string
    {
        $process = new Process([PHP_BINARY, base_path('scripts/migrate-organization.php')], base_path(), [
            'ORGANIZATION_DATABASE' => 'lexcita_sli', 'ORGANIZATION_OWNER_PASSWORD' => false,
            'DB_HOST' => false, ...$environment,
        ]);
        $process->run();
        $this->assertSame(1, $process->getExitCode());
        return $process->getOutput().$process->getErrorOutput();
    }

    public function test_missing_owner_password_names_the_private_service(): void
    {
        $output = $this->runMigrator([]);
        $this->assertStringContainsString('Falta ORGANIZATION_OWNER_PASSWORD', $output);
        $this->assertStringContainsString('SLI-Setup', $output);
    }

    public function test_short_password_is_never_echoed(): void
    {
        $output = $this->runMigrator(['ORGANIZATION_OWNER_PASSWORD' => 'sensitive-fixture']);
        $this->assertStringContainsString('al menos 32 caracteres', $output);
        $this->assertStringNotContainsString('sensitive-fixture', $output);
    }

    public function test_invalid_database_value_is_not_echoed(): void
    {
        $output = $this->runMigrator(['ORGANIZATION_DATABASE' => 'invalid-sensitive-input']);
        $this->assertStringContainsString('ORGANIZATION_DATABASE', $output);
        $this->assertStringNotContainsString('invalid-sensitive-input', $output);
    }

    public function test_missing_host_is_reported_before_connecting(): void
    {
        $password = str_repeat('sensitive-fixture-', 3);
        $output = $this->runMigrator(['ORGANIZATION_OWNER_PASSWORD' => $password]);
        $this->assertStringContainsString('Falta DB_HOST', $output);
        $this->assertStringNotContainsString($password, $output);
    }
}
