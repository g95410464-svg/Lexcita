<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{Usuario, Cita, HorarioDisponible};
use App\Services\HorarioService;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── ADMIN ────────────────────────────────────────────
        Usuario::firstOrCreate(['email' => 'admin@lexcita.sv'], [
            'nombre'   => 'Administrador',
            'password' => Hash::make('admin1234'),
            'rol'      => 'admin',
            'activo'   => true,
        ]);

        // ─── ABOGADOS ─────────────────────────────────────────
        $horarioService = new HorarioService();

        $abogados = [
            ['nombre' => 'Dra. María González',  'email' => 'mgonzalez@lexcita.sv', 'especialidad' => 'Derecho Familiar'],
            ['nombre' => 'Lic. Carlos Ramos',    'email' => 'cramos@lexcita.sv',    'especialidad' => 'Derecho Penal'],
            ['nombre' => 'Dra. Ana Martínez',    'email' => 'amartinez@lexcita.sv', 'especialidad' => 'Derecho Laboral'],
        ];

        foreach ($abogados as $datos) {
            $abogado = Usuario::firstOrCreate(['email' => $datos['email']], [
                'nombre'       => $datos['nombre'],
                'password'     => Hash::make('abogado1234'),
                'rol'          => 'abogado',
                'especialidad' => $datos['especialidad'],
                'activo'       => true,
            ]);
            $horarioService->crearHorariosDefault($abogado);
        }

        // ─── CLIENTE DE PRUEBA ────────────────────────────────
        $cliente = Usuario::firstOrCreate(['email' => 'cliente@lexcita.sv'], [
            'nombre'            => 'Juan Pérez',
            'password'          => Hash::make('cliente1234'),
            'rol'               => 'cliente',
            'telefono_whatsapp' => '+50312345678',
            'activo'            => true,
        ]);

        $this->command->info('✅ Datos de prueba creados correctamente.');
        $this->command->table(
            ['Rol', 'Email', 'Contraseña'],
            [
                ['Admin',   'admin@lexcita.sv',   'admin1234'],
                ['Abogado', 'mgonzalez@lexcita.sv','abogado1234'],
                ['Cliente', 'cliente@lexcita.sv', 'cliente1234'],
            ]
        );
    }
}
