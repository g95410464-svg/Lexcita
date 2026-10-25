# PHP efectivo de SLI

La imagen de organización declara `dunglas/frankenphp:1-php8.3.36-bookworm`.
Ahora el build comprueba la versión mínima y el arranque imprime `php -v` antes
del chequeo de organización. Esto permite verificar el runtime desde los logs;
un cambio de Dockerfile no prueba por sí mismo que producción se haya actualizado.

En SLI-Lexcita y SLI-Setup hay que usar `deploy/Dockerfile.organization`, la rama
`feat/sli-landing` y reconstruir el último commit. Reutilizar un despliegue antiguo
o reiniciar su contenedor mantiene su imagen anterior. La web conserva su comando
de servidor y el rol limitado; Setup conserva su comando de migración privada.
No copiar la contraseña del propietario al servicio web.

Comprobación en la consola del despliegue nuevo:

```sh
php -v
php -r 'echo "upload_progress=".ini_get("session.upload_progress.enabled").PHP_EOL;'
```

Resultado esperado en esta línea: PHP 8.3.36 o parche posterior y upload_progress=0.
No se instalaron paquetes ni se crearon contenedores en la PC. El conector Railway
pidió reautenticación durante este trabajo; la actualización efectiva no se puede
marcar completada sin logs del nuevo build/arranque o esa salida de consola.
Tras la reconexión confirmada por el propietario, las acciones del plugin
devolvieron `Unknown tool`. El fallback de navegador tampoco pudo usarse:
Computer Use se detuvo porque la comprobación de URL no está soportada en el
navegador Windows disponible. No se realizaron cambios de producción por esas vías.

Referencias oficiales consultadas el 25/10/2026:

- [PHP 8.3.36](https://www.php.net/releases/8_3_36.php).
- [Avisos PHP del 21 y 23 de octubre](https://www.php.net/archive/2026.php#2026-10-21-5).

Se añade `deploy/php-security.ini` con session.upload_progress.enabled=0, aplicada
antes de analizar solicitudes, siguiendo el aviso del 23 de octubre. Se conserva
la versión corregida; esta opción no sustituye la actualización.
