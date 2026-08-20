---
name: route-ticket-fix
description: Fixed RouteNotFoundException for cliente.ticket route
metadata:
  type: project
---

## Problem
The route `cliente.ticket` was resolving as `cliente.cliente.ticket` (double prefix), causing `RouteNotFoundException` in `resources/views/cliente/mis-citas.blade.php`.

## Root Cause
- The route group at `routes/web.php:20` has `name('cliente.')` as a prefix
- The ticket route at line 25 had `->name('cliente.ticket')`
- This created a double prefix: `cliente.` (group) + `cliente.ticket` (route) = `cliente.cliente.ticket`

## Fix Applied
**File**: `routes/web.php` (line 25)

**Changed**:
```php
// Before:
Route::get('/ticket/{id}',     [ClienteController::class, 'ticket'])->name('cliente.ticket');

// After:
Route::get('/ticket/{id}',     [ClienteController::class, 'ticket'])->name('ticket');
```

**Result**: The full registered route name is now `cliente.ticket` (group prefix `cliente.` + route name `ticket`), which matches the blade view call `route('cliente.ticket', $cita->id)`.

## Next Step
Run `php artisan route:clear` in the terminal to flush Laravel's route cache:
```bash
cd C:\laragon\www\lexcita-app
php artisan route:clear
```

After cache clearing, the "Ticket" links in `mis-citas.blade.php` will correctly resolve to `/cliente/ticket/{id}`.