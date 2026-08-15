---
name: review-clientecontroller
description: Code review of ClienteController and related models for LexCita project
metadata:
  type: project
  date: 2026-08-14
---

# Code Review: `app/Http/Controllers/ClienteController.php`

**Project**: LexCita  
**Date**: 2026-08-14  
**Reviewer**: Senior Full-Stack Developer & Code Auditor

---

## 🔴 Errores Críticos / Bugs

1. **Race condition en `generarCodigo()` (Cita.php:43)**: El método `self::max('id')` no es atómico. En concurrentes solicitudes, dos procesos pueden leer el mismo `max('id')` y generar el mismo código `LEX-YYYY-NNNN`. Esto causaría duplicados de códigos y conflictos de negocio.

2. **Validación de conflicto de horario incompleta (ClienteController.php:49-53)**: La consulta de conflicto solo verifica coincidencia exacta de `hora_inicio`, pero no detecta superposiciones de intervalos. Una cita programada de 9:00-10:00 no bloquearía la creación de una cita a las 9:30, permitiendo solapamientos. La validación debería verificar ranges:
   ```sql
   -- Example corrected query:
   Cita::where('abogado_id', $data['abogado_id'])
       ->where('fecha', $data['fecha'])
       ->where('estado', '!=', 'cancelada')
       ->where(function ($q) {
           $q->where('hora_inicio', '<=', $data['hora_inicio'])
               ->where('hora_fin', '>=', $data['hora_inicio']);
       })
       ->exists();
   ```

3. **Hardcoded monto en `crearCita()` (ClienteController.php:70)**: El monto `35.00` está codificado en duro en el controlador. Esto debería ser configurable o venir del modelo/abogado, no endurecido en el controlador. Si cambia el precio, requiere código y despliegue.

4. **Falta de validación del lado del cliente/servidor para hora_fin**: Se calcula `hora_fin` sumando una hora al `hora_inicio`, pero no se valida que el slot de una hora sea válido (por ejemplo, citas que duran distinto tiempo, horarios de oficina, etc.).

---

## 🟡 Advertencias y Seguridad

1. **Migración MySQL → PostgreSQL compatible (Commit 3b4dab4)**: El cambio de `YEAR(fecha)` / `MONTH(fecha)` a `EXTRACT(YEAR FROM fecha)` / `EXTRACT(MONTH FROM fecha)` es correcto para PostgreSQL. Sin embargo, verifica que todos los raw queries hayan sido actualizados. Queden expresiones MySQL podrían fallar silenciosamente en PostgreSQL.

2. **Validación `date_format` vs formato real (ClienteController.php:39)**: La regla `date_format:H:i` solo verifica que el string coincida con el patrón, no que sea un horario válido. Un usuario podría ingresar "25:99" y pasaría la validación. Se recomienda usar `date_format` solo para formateo o agregar validación adicional con `after:00:00` y `before:23:59`.

3. **Consistencia en filtrado por cliente (ticket y cancelarCita)**: Ambos métodos filtran por `cliente_id = Auth::id()`, lo cual es correcto para prevenir IDOR. Sin embargo, el método `ticket` usa `firstOrFail()` que lanzará un error 404 si no encuentra, mientras que `cancelarCita` valida `puedeCancelarse()` después. Esto es consistente, pero asegúrate que el error 404 no revele información sobre citas que no pertenecen al usuario (lo cual ya está mitigado por el where).

4. **Exposición potencial de variables en redirección (ClienteController.php:74)**: `redirect()->route('pago.crear-sesion', $cita->id)` pasa el ID de la cita en la URL. Si la ruta `pago.crear-sesion` no tiene autorización adicional del lado del servidor, un usuario podría manipular este ID para acceder a sesiones de pago de otras citas. Asegúrate de que la ruta del pago verifique que el usuario es el cliente de esa cita.

5. **Sin manejo de excepciones explícito**: Ningún método tiene bloques try/catch. Laravel maneja excepciones a nivel global, pero operaciones como `Cita::create()` o redirecciones podrían fallar sin mensajes de error claros para el usuario en casos edge.

---

## 🟢 Sugerencias de Optimización

1. **Consolidar consulta de count (ClienteController.php:23)**: En `dashboard()`, la.query `count()` ejecuta un segundo query de base de datos aparte de la de `proximasCitas`. Se podría optimizar usando una sola query con `selectRaw('count(*)')` o usando el count del mismo builder:

   ```php
   $totalCitas = $cliente->citasComoCliente()
       ->where('estado', 'confirmada')
       ->count();
   ```

   *Nota: El count actual no filtra por estado 'confirmada', así que el comportamiento depende de si quieres contar todas o solo las confirmadas.*

2. **Evitar doble `orderBy` (ClienteController.php:19)**: Tener `->orderBy('fecha')->orderBy('hora_inicio')` es funcional (orderra primero por fecha, luego por hora para empates), pero es más limpio y eficiente usar un solo orderBy con múltiples columnas:

   ```php
   ->orderBy(['fecha', 'hora_inicio'])
   ```

3. **Mover lógica de cálculo de `hora_fin` al modelo**: La lógica de `addHour()` podría ser un método en el modelo `Cita` para reutilizarla y mantener el controlador delgado:

   ```php
   // En Cita.php
   public function getHoraFinAttribute($value) { ... }
   // o un método helper
   public function horaFin(): string {
       return Carbon::createFromFormat('H:i', $this->hora_inicio)->addHour()->format('H:i');
   }
   ```

4. **Constante para el monto**: Definir el monto como constante en lugar de hardcoded:

   ```php
   const MONTO_CITA = 35.00;
   // Luego en crearCita:
   'monto' => self::MONTO_CITA,
   ```

5. **Optimización de relaciones `with('abogado')`**: En ambos métodos `dashboard` y `misCitas`, se usa `with('abogado')` para eager loading. Verifica que la relación `abogado` en el modelo `Cita` esté definida correctamente y que no haya consultas adicionales innecesarias. El uso de `with` es correcto y evita el problema N+1.

---

## 🛠️ Código Corregido

### Solución para el race condition en `generarCodigo()` (Cita.php)

**Problema**: `self::max('id')` no es atómico - múltiples requests concurrentes pueden generar el mismo código.

**Solución**: Usar bloqueo optimista o una secuencia/auto-incremento a nivel de base de datos.

```php
// Opción 1: Usar locking a nivel de query (SQLite/PostgreSQL)
public static function generarCodigo(): string
{
    $ultimo = self::lockIncrement()->max('id') ?? 0;
    return 'LEX-' . date('Y') . '-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
}

// Opción 2: Usar secuencia de base de datos (PostgreSQL)
public static function generarCodigo(): string
{
    $ultimo = DB::selectOne("SELECT nextval('citas_id_seq') as id")->id ?? 0;
    return 'LEX-' . date('Y') . '-' . str_pad($ultimo, 4, '0', STR_PAD_LEFT);
}
```

### Validación de conflicto de horario mejorada (ClienteController.php)

**Problema**: La validación solo compara `hora_inicio` exacto, permitiendo solapamientos.

**Solución**: Validar ranges de superposición:

```php
// Validar que el slot siga libre (doble check server-side) - versión mejorada
$conflicto = Cita::where('abogado_id', $data['abogado_id'])
    ->where('fecha', $data['fecha'])
    ->where('estado', '!=', 'cancelada')
    ->where(function ($q) use ($data) {
        $q->where('hora_inicio', '<=', $data['hora_inicio'])
            ->where('hora_fin', '>=', $data['hora_inicio']);
    })
    ->orWhere(function ($q) use ($data) {
        $q->where('hora_inicio', '>=', $data['hora_inicio'])
            ->where('hora_inicio', '<=', $data['hora_fin']);
    })
    ->exists();
```

### Consolidar count y mejorar orderBy (ClienteController.php - dashboard)

```php
public function dashboard()
{
    $cliente = Auth::user();
    
    $query = $cliente->citasComoCliente()
        ->where('estado', 'confirmada')
        ->where('fecha', '>=', today());

    $proximasCitas = $query
        ->orderBy(['fecha', 'hora_inicio'])
        ->with('abogado')
        ->take(3)
        ->get();

    $totalCitas = $query->count();

    return view('cliente.dashboard', compact('proximasCitas', 'totalCitas'));
}
```

### Mover lógica de hora_fin al modelo Cita.php

```php
// Agregar al modelo Cita
public function horaFinFormateada(): string
{
    return Carbon::createFromFormat('H:i', $this->hora_inicio)->addHour()->format('H:i');
}
```

Y en el controlador, en lugar de la lógica inline, podrías acceder a `$cita->horaFinFormateada()` cuando necesites formatear.

---
*Review generado el 2026-08-14 para el proyecto LexCita.*