# LexCita — Laravel 11 + MySQL
Sistema de agendación de citas legales con pago anticipado (Stripe) y notificaciones WhatsApp (Twilio).

---

## Instalación completa paso a paso

### Requisitos previos
- PHP >= 8.2 con extensiones: `mbstring`, `xml`, `curl`, `zip`, `pdo_mysql`
- Composer
- MySQL 8
- Node.js (solo si usas Vite; en este proyecto no es necesario)

---

### Paso 1 — Crear el proyecto Laravel base

```bash
composer create-project laravel/laravel lexcita-app
cd lexcita-app
```

### Paso 2 — Copiar los archivos de este ZIP

Descomprime el ZIP y copia **todo el contenido** encima del proyecto base:

```bash
# Desde la carpeta donde descomprimiste el ZIP:
cp -r lexcita/. lexcita-app/
```

Esto sobreescribe:
- `app/` (Controllers, Middleware, Models, Providers, Services)
- `bootstrap/app.php` (registro del middleware de rol)
- `config/auth.php` y `config/services.php`
- `database/migrations/` y `database/seeders/`
- `resources/views/` (todas las vistas Blade)
- `routes/web.php`
- `composer.json` (agrega stripe/stripe-php)

### Paso 3 — Instalar dependencias

```bash
composer install
```

### Paso 4 — Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` con tus datos:

```env
APP_NAME=LexCita
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lexcita
DB_USERNAME=root
DB_PASSWORD=tu_password

# Stripe — https://dashboard.stripe.com/test/apikeys
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...

# Twilio WhatsApp — https://console.twilio.com
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

### Paso 5 — Crear la base de datos

En MySQL:
```sql
CREATE DATABASE lexcita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Paso 6 — Ejecutar migraciones y datos de prueba

```bash
php artisan migrate --seed
```

Usuarios creados automáticamente:

| Rol     | Email                   | Contraseña   |
|---------|-------------------------|--------------|
| Admin   | admin@lexcita.sv        | admin1234    |
| Abogado | mgonzalez@lexcita.sv    | abogado1234  |
| Abogado | cramos@lexcita.sv       | abogado1234  |
| Abogado | amartinez@lexcita.sv    | abogado1234  |
| Cliente | cliente@lexcita.sv      | cliente1234  |

### Paso 7 — Arrancar el servidor

```bash
php artisan serve
```

Abre: **http://localhost:8000/login**

---

## Tarjeta de prueba Stripe

```
Número:  4242 4242 4242 4242
Venc:    12/34
CVC:     123
```

---

## Flujo completo de la aplicación

```
/login  ──────────────────────────────────────────────────────
        │
        ├─ rol: cliente  → /cliente/dashboard
        │                      └─ Nueva Cita → selecciona abogado
        │                                    → selecciona fecha (calendario JS)
        │                                    → selecciona slot (/api/slots AJAX)
        │                                    → detalle de consulta
        │                                    → POST /cliente/nueva-cita
        │                                    → redirect /pago/crear-sesion/{id}
        │                                    → Stripe Checkout (pago real)
        │                                    → /pago/exito → confirmar cita + WhatsApp
        │
        ├─ rol: abogado  → /abogado/dashboard
        │                      └─ /abogado/agenda (FullCalendar)
        │
        └─ rol: admin    → /interno/dashboard
                               ├─ /interno/abogados  (CRUD + toggle activo)
                               ├─ /interno/clientes  (listado)
                               ├─ /interno/citas     (filtros por estado/abogado)
                               └─ /interno/estadisticas (gráfico ingresos)
```

---

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php       login, registro, logout
│   │   ├── ClienteController.php    dashboard, nueva-cita, mis-citas, cancelar
│   │   ├── AbogadoController.php    dashboard, agenda
│   │   ├── AdminController.php      panel interno completo
│   │   ├── PagoController.php       Stripe Checkout
│   │   └── ApiController.php        GET /api/slots (AJAX)
│   └── Middleware/
│       └── RolMiddleware.php        protección por rol
├── Models/
│   ├── Usuario.php
│   ├── Cita.php
│   └── HorarioDisponible.php
├── Providers/
│   └── AppServiceProvider.php       bind de servicios
└── Services/
    ├── CitaService.php              lógica de slots disponibles
    ├── HorarioService.php           horarios default al crear abogado
    └── WhatsAppService.php          Twilio API

bootstrap/
└── app.php                          registra middleware 'rol'

database/
├── migrations/                      3 tablas
└── seeders/DatabaseSeeder.php       usuarios de prueba

resources/views/
├── layouts/app.blade.php            layout con sidebar Tailwind
├── components/nav-link.blade.php    ítem de nav reutilizable
├── auth/{login,registro}.blade.php
├── cliente/{dashboard,nueva-cita,mis-citas}.blade.php
├── abogado/{dashboard,agenda}.blade.php
├── interno/{dashboard,abogados,clientes,citas,estadisticas}.blade.php
└── pago/{exito,cancelado}.blade.php
```

---

## Para producción

```bash
APP_ENV=production
APP_DEBUG=false

# Cambiar a claves Stripe live:
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...

# Optimizar:
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
