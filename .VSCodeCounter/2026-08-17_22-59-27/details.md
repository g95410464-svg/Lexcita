# Details

Date : 2026-08-17 22:59:27

Directory c:\\laragon\\www\\lexcita-app

Total : 80 files,  13238 codes, 619 comments, 798 blanks, all 14655 lines

[Summary](results.md) / Details / [Diff Summary](diff.md) / [Diff Details](diff-details.md)

## Files
| filename | language | code | comment | blank | total |
| :--- | :--- | ---: | ---: | ---: | ---: |
| [.claude/settings.local.json](/.claude/settings.local.json) | JSON | 35 | 0 | 1 | 36 |
| [Dockerfile](/Dockerfile) | Docker | 14 | 0 | 8 | 22 |
| [README.md](/README.md) | Markdown | 22 | 0 | 9 | 31 |
| [app/Http/Controllers/AbogadoController.php](/app/Http/Controllers/AbogadoController.php) | PHP | 42 | 1 | 10 | 53 |
| [app/Http/Controllers/AdminController.php](/app/Http/Controllers/AdminController.php) | PHP | 93 | 2 | 21 | 116 |
| [app/Http/Controllers/ApiController.php](/app/Http/Controllers/ApiController.php) | PHP | 19 | 0 | 6 | 25 |
| [app/Http/Controllers/AuthController.php](/app/Http/Controllers/AuthController.php) | PHP | 102 | 1 | 24 | 127 |
| [app/Http/Controllers/ClienteController.php](/app/Http/Controllers/ClienteController.php) | PHP | 88 | 3 | 20 | 111 |
| [app/Http/Controllers/Controller.php](/app/Http/Controllers/Controller.php) | PHP | 5 | 1 | 3 | 9 |
| [app/Http/Controllers/PagoController.php](/app/Http/Controllers/PagoController.php) | PHP | 64 | 0 | 17 | 81 |
| [app/Http/Middleware/RolMiddleware.php](/app/Http/Middleware/RolMiddleware.php) | PHP | 28 | 7 | 7 | 42 |
| [app/Models/Cita.php](/app/Models/Cita.php) | PHP | 36 | 4 | 11 | 51 |
| [app/Models/HorarioDisponible.php](/app/Models/HorarioDisponible.php) | PHP | 22 | 1 | 8 | 31 |
| [app/Models/User.php](/app/Models/User.php) | PHP | 27 | 16 | 7 | 50 |
| [app/Models/Usuario.php](/app/Models/Usuario.php) | PHP | 42 | 3 | 13 | 58 |
| [app/Providers/AppServiceProvider.php](/app/Providers/AppServiceProvider.php) | PHP | 18 | 1 | 5 | 24 |
| [app/Services/CitaService.php](/app/Services/CitaService.php) | PHP | 53 | 7 | 12 | 72 |
| [app/Services/HorarioService.php](/app/Services/HorarioService.php) | PHP | 16 | 3 | 5 | 24 |
| [app/Services/WhatsAppService.php](/app/Services/WhatsAppService.php) | PHP | 58 | 0 | 14 | 72 |
| [bootstrap/app.php](/bootstrap/app.php) | PHP | 20 | 2 | 3 | 25 |
| [bootstrap/providers.php](/bootstrap/providers.php) | PHP | 4 | 0 | 2 | 6 |
| [compose.yaml](/compose.yaml) | YAML | 56 | 0 | 1 | 57 |
| [composer.json](/composer.json) | JSON | 62 | 0 | 1 | 63 |
| [composer.lock](/composer.lock) | JSON | 8,468 | 0 | 1 | 8,469 |
| [config.php](/config.php) | PHP | 14 | 0 | 4 | 18 |
| [config/app.php](/config/app.php) | PHP | 22 | 82 | 23 | 127 |
| [config/auth.php](/config/auth.php) | PHP | 28 | 0 | 8 | 36 |
| [config/cache.php](/config/cache.php) | PHP | 64 | 35 | 19 | 118 |
| [config/database.php](/config/database.php) | PHP | 119 | 43 | 23 | 185 |
| [config/filesystems.php](/config/filesystems.php) | PHP | 36 | 32 | 13 | 81 |
| [config/google.php](/config/google.php) | PHP | 6 | 0 | 3 | 9 |
| [config/logging.php](/config/logging.php) | PHP | 79 | 33 | 21 | 133 |
| [config/mail.php](/config/mail.php) | PHP | 57 | 43 | 19 | 119 |
| [config/queue.php](/config/queue.php) | PHP | 65 | 45 | 20 | 130 |
| [config/services.php](/config/services.php) | PHP | 12 | 2 | 5 | 19 |
| [config/session.php](/config/session.php) | PHP | 23 | 160 | 35 | 218 |
| [database/factories/UserFactory.php](/database/factories/UserFactory.php) | PHP | 26 | 14 | 6 | 46 |
| [database/migrations/0001\_01\_01\_000000\_create\_users\_table.php](/database/migrations/0001_01_01_000000_create_users_table.php) | PHP | 38 | 6 | 6 | 50 |
| [database/migrations/0001\_01\_01\_000001\_create\_cache\_table.php](/database/migrations/0001_01_01_000001_create_cache_table.php) | PHP | 25 | 6 | 5 | 36 |
| [database/migrations/0001\_01\_01\_000002\_create\_jobs\_table.php](/database/migrations/0001_01_01_000002_create_jobs_table.php) | PHP | 46 | 6 | 6 | 58 |
| [database/migrations/2024\_01\_01\_000001\_create\_usuarios\_table.php](/database/migrations/2024_01_01_000001_create_usuarios_table.php) | PHP | 26 | 0 | 4 | 30 |
| [database/migrations/2024\_01\_01\_000002\_create\_horarios\_disponibles\_table.php](/database/migrations/2024_01_01_000002_create_horarios_disponibles_table.php) | PHP | 23 | 0 | 4 | 27 |
| [database/migrations/2024\_01\_01\_000003\_create\_citas\_table.php](/database/migrations/2024_01_01_000003_create_citas_table.php) | PHP | 31 | 0 | 4 | 35 |
| [database/migrations/2024\_01\_01\_000004\_add\_email\_verified\_at\_to\_usuarios.php](/database/migrations/2024_01_01_000004_add_email_verified_at_to_usuarios.php) | PHP | 19 | 0 | 3 | 22 |
| [database/migrations/2026\_08\_04\_000000\_remove\_email\_verified\_at\_from\_usuarios\_table.php](/database/migrations/2026_08_04_000000_remove_email_verified_at_from_usuarios_table.php) | PHP | 21 | 6 | 4 | 31 |
| [database/seeders/DatabaseSeeder.php](/database/seeders/DatabaseSeeder.php) | PHP | 50 | 3 | 9 | 62 |
| [memory/review-clientecontroller.md](/memory/review-clientecontroller.md) | Markdown | 129 | 0 | 48 | 177 |
| [package.json](/package.json) | JSON | 17 | 0 | 1 | 18 |
| [phpunit.xml](/phpunit.xml) | XML | 35 | 0 | 1 | 36 |
| [public/css/lexcita.css](/public/css/lexcita.css) | PostCSS | 353 | 21 | 26 | 400 |
| [public/index.php](/public/index.php) | PHP | 10 | 4 | 7 | 21 |
| [resources/css/app.css](/resources/css/app.css) | PostCSS | 9 | 0 | 3 | 12 |
| [resources/js/app.js](/resources/js/app.js) | JavaScript | 1 | 0 | 1 | 2 |
| [resources/js/bootstrap.js](/resources/js/bootstrap.js) | JavaScript | 3 | 0 | 2 | 5 |
| [resources/views/abogado/agenda.blade.php](/resources/views/abogado/agenda.blade.php) | PHP | 56 | 1 | 6 | 63 |
| [resources/views/abogado/dashboard.blade.php](/resources/views/abogado/dashboard.blade.php) | PHP | 81 | 0 | 8 | 89 |
| [resources/views/auth/login.blade.php](/resources/views/auth/login.blade.php) | PHP | 165 | 0 | 17 | 182 |
| [resources/views/auth/registro.blade.php](/resources/views/auth/registro.blade.php) | PHP | 146 | 0 | 17 | 163 |
| [resources/views/auth/verificacion-aviso.blade.php](/resources/views/auth/verificacion-aviso.blade.php) | PHP | 54 | 0 | 10 | 64 |
| [resources/views/cliente/dashboard.blade.php](/resources/views/cliente/dashboard.blade.php) | PHP | 189 | 0 | 16 | 205 |
| [resources/views/cliente/mis-citas.blade.php](/resources/views/cliente/mis-citas.blade.php) | PHP | 117 | 0 | 15 | 132 |
| [resources/views/cliente/nueva-cita.blade.php](/resources/views/cliente/nueva-cita.blade.php) | PHP | 340 | 6 | 37 | 383 |
| [resources/views/cliente/ticket.blade.php](/resources/views/cliente/ticket.blade.php) | PHP | 304 | 9 | 45 | 358 |
| [resources/views/components/nav-link.blade.php](/resources/views/components/nav-link.blade.php) | PHP | 9 | 0 | 2 | 11 |
| [resources/views/interno/abogados.blade.php](/resources/views/interno/abogados.blade.php) | PHP | 122 | 0 | 5 | 127 |
| [resources/views/interno/citas.blade.php](/resources/views/interno/citas.blade.php) | PHP | 96 | 0 | 5 | 101 |
| [resources/views/interno/clientes.blade.php](/resources/views/interno/clientes.blade.php) | PHP | 50 | 0 | 3 | 53 |
| [resources/views/interno/dashboard.blade.php](/resources/views/interno/dashboard.blade.php) | PHP | 78 | 0 | 5 | 83 |
| [resources/views/interno/estadisticas.blade.php](/resources/views/interno/estadisticas.blade.php) | PHP | 31 | 0 | 4 | 35 |
| [resources/views/layouts/app.blade.php](/resources/views/layouts/app.blade.php) | PHP | 149 | 2 | 12 | 163 |
| [resources/views/pago/cancelado.blade.php](/resources/views/pago/cancelado.blade.php) | PHP | 15 | 0 | 2 | 17 |
| [resources/views/pago/exito.blade.php](/resources/views/pago/exito.blade.php) | PHP | 24 | 0 | 4 | 28 |
| [resources/views/pago/instrucciones.blade.php](/resources/views/pago/instrucciones.blade.php) | PHP | 63 | 0 | 9 | 72 |
| [resources/views/welcome.blade.php](/resources/views/welcome.blade.php) | PHP | 270 | 0 | 8 | 278 |
| [routes/console.php](/routes/console.php) | PHP | 6 | 0 | 3 | 9 |
| [routes/web.php](/routes/web.php) | PHP | 48 | 0 | 8 | 56 |
| [tests/Feature/ExampleTest.php](/tests/Feature/ExampleTest.php) | PHP | 11 | 4 | 5 | 20 |
| [tests/TestCase.php](/tests/TestCase.php) | PHP | 6 | 1 | 4 | 11 |
| [tests/Unit/ExampleTest.php](/tests/Unit/ExampleTest.php) | PHP | 10 | 3 | 4 | 17 |
| [vite.config.js](/vite.config.js) | JavaScript | 17 | 0 | 2 | 19 |

[Summary](results.md) / Details / [Diff Summary](diff.md) / [Diff Details](diff-details.md)