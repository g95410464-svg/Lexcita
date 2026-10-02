<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateOrganizationAdmin extends Command
{
    protected $signature = 'organization:create-admin {email} {name}';
    protected $description = 'Create a new organization administrator using a password supplied privately in the process environment';

    public function handle(): int
    {
        if ($this->call('organization:check') !== self::SUCCESS) {
            return self::FAILURE;
        }
        $data = ['email' => strtolower(trim($this->argument('email'))), 'nombre' => $this->argument('name'),
            'password' => getenv('ORGANIZATION_ADMIN_PASSWORD')];
        if (Validator::make($data, ['email' => 'required|email:rfc|max:255', 'nombre' => 'required|string|max:255',
            'password' => 'required|string|min:20|max:64'])->fails()) {
            $this->error('Datos inválidos o falta ORGANIZATION_ADMIN_PASSWORD (20 a 64 caracteres).');
            return self::FAILURE;
        }
        try {
            if (Usuario::where('email', $data['email'])->exists()) {
                $this->error('Ese correo ya existe. No se cambió su contraseña ni su rol.');
                return self::FAILURE;
            }
            // The model hashes passwords. No password is printed or passed as
            // a command-line argument, and existing accounts are never promoted.
            Usuario::create([...$data, 'rol' => 'admin', 'activo' => true]);
            $this->info('Cuenta administradora creada.');
            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('No se pudo crear la cuenta administradora. No se mostraron credenciales.');
            return self::FAILURE;
        }
    }
}
