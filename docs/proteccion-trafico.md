# Protección de tráfico de Lexcita

## Estado y alcance

Laravel limita rutas sensibles y marca las respuestas dinámicas como `private, no-store`.
Cloudflare requiere un dominio propio y acceso a su zona DNS; el dominio generado
`lexcita-production.up.railway.app` pertenece a Railway. Esta guía no significa que
Cloudflare ya esté conectado. Anubis no está activado.

Los límites protegen trabajo en controladores, pero las peticiones todavía llegan a
PHP y al almacenamiento de sesión/caché. No sustituyen protección en el borde ni un
servidor de producción. El comando actual de Railway usa `php -S`; su sustitución por
un servidor PHP de producción y las pruebas de capacidad son una tarea independiente.

## Límites aplicados

| Operación | Presupuesto |
| --- | --- |
| POST login | 5/minuto por correo normalizado + IP, y 60/minuto por IP |
| POST registro | 10/minuto y 30/hora por IP |
| Google, inicio y retorno | 20/minuto por IP, compartidos entre las dos rutas |
| Páginas públicas y portales | 120/minuto por usuario o IP para visitantes |
| Consultar horarios | 60/minuto por usuario |
| Crear cita | 5/minuto por usuario |
| Operaciones de pago | 10/minuto por usuario, compartidos entre las rutas de pago |
| Escrituras administrativas/cancelación | 30/minuto por usuario |
| Sala y señalización de videollamada | 240/minuto por usuario |
| Autorización de WebSocket | 60/minuto por usuario o IP para visitantes |

Las rutas de clientes y administración conservan también el presupuesto del portal.
Salir de la sesión no se limita. `/up` queda disponible para la comprobación de salud.
Los límites de login cuentan intentos válidos e inválidos. Los límites por IP de
registro son compartidos por personas detrás de un mismo router; ajustar tras medir
eventos de altas masivas. Los valores están en `config/traffic.php`.

El exceso produce HTTP 429 con `Retry-After`, respuesta JSON para AJAX y una página
en español para formularios. El selector de horarios respeta el tiempo indicado.
Los contadores usan `RATE_LIMIT_STORE` o, si no se define, `CACHE_STORE`. Para varias
réplicas se necesita un almacén compartido, como database o Redis; `array` no sirve
en producción y `file` separa los contadores por contenedor. Redis evita añadir
consultas de límites a PostgreSQL, pero debe aprovisionarse antes de seleccionarlo.

## Identificación del cliente en Railway

`TRUST_RAILWAY_INGRESS` se activa por defecto cuando existe `RAILWAY_ENVIRONMENT_ID`.
Solo debe activarse si las solicitudes públicas pasan por el ingress HTTP de Railway.
El limitador usa su `X-Real-IP`, validado como una sola dirección IPv4/IPv6. No usa
`CF-Connecting-IP` ni cadenas `X-Forwarded-For` enviadas por el cliente. Fuera de
Railway utiliza la IP de la conexión reconocida por Laravel.

Railway documenta `X-Real-IP` y su equipo indica que lo reemplaza en el borde y
reconoce Cloudflare. Este comportamiento debe verificarse después de cambios de
dominio o proxy: agotar un presupuesto con datos ficticios y comprobar que cambiar
cabeceras del cliente no evita el 429. No habilitar este modo en un servidor expuesto
directamente a Internet ni mediante un proxy TCP. El acceso interno al contenedor
queda dentro de la frontera de confianza del proyecto Railway.

## Conectar Cloudflare cuando exista el dominio

1. Añadir el dominio al servicio web Lexcita en Railway. Copiar los registros CNAME
   y TXT exactos que genere Railway; no adivinar el destino ni cambiar registros de correo.
2. Añadirlos en la zona Cloudflare del dominio. Activar la zona mediante los
   nameservers correspondientes cuando sea necesario. Verificar el dominio y el
   certificado en Railway antes de activar el proxy del CNAME (nube naranja).
3. Configurar SSL/TLS Full (strict) cuando el certificado del origen cubra el dominio.
   Si falla la validación, resolver primero la emisión del certificado; no bajar a
   Flexible. Activar HTTPS y mantener WebSockets habilitados.
4. Usar las protecciones DDoS y reglas administradas disponibles en el plan. Revisar
   eventos de seguridad para comprobar que login, registro, PayPal y WebSockets
   funcionan. Aplicar desafíos solo donde un navegador pueda resolverlos; evitar
   desafíos en callbacks, AJAX, autorización de canales o mensajes WebSocket.
5. CDN: cachear únicamente archivos públicos de `/build/`, `/css/`, `/js/` e imágenes
   públicas conocidas. Mantener la política de caché estándar y añadir una regla
   de bypass para todo lo demás. No activar Cache Everything sobre el portal.
   Ejemplo de expresión de bypass, limitado al hostname real de la aplicación:

   ```text
   (http.host eq "HOSTNAME_REAL") and not (
     starts_with(http.request.uri.path, "/build/") or
     starts_with(http.request.uri.path, "/css/") or
     starts_with(http.request.uri.path, "/js/")
   )
   ```

   Sustituir `HOSTNAME_REAL`; no copiar el marcador literalmente. No cachear
   `/login`, `/registro`, `/cliente`, `/interno`, `/abogado`, `/api`, `/auth`,
   `/pago`, `/videollamada`, `/broadcasting` ni documentos privados. Las reglas
   de caché existentes se deben revisar para que ninguna anule este bypass.
6. Actualizar APP_URL, GOOGLE_REDIRECT_URI y la URL autorizada en Google. Actualizar
   URLs de retorno configuradas externamente en PayPal. Añadir el nuevo origen en
   REVERB_ALLOWED_ORIGINS del servicio WebSocket y verificar conexión de ambos roles.
   No cambiar SESSION_DOMAIN sin necesidad: una cookie del host es adecuada para
   el mismo dominio. El cambio de hostname requiere iniciar sesión de nuevo.
7. Cerrar el acceso alternativo al origen antes de considerar completa la protección.
   Una regla Transform de cabeceras de solicitud de Cloudflare debe **establecer**
   `X-Lexcita-Origin` con un secreto aleatorio de al menos 32 bytes, sobrescribiendo
   cualquier valor que mande el cliente. Nunca devolver ese secreto al navegador.
   Guardar el mismo valor como CLOUDFLARE_ORIGIN_SECRET en el servicio web Railway.
   Configurar primero la cabecera, comprobar el dominio, y después activar el secreto
   en Railway. Sin ese valor la comprobación está desactivada.
8. Verificar que el dominio Cloudflare funciona, el dominio Railway directo rechaza
   páginas dinámicas con 403 y `/up` sigue respondiendo. Los estáticos públicos pueden
   seguir siendo servidos directamente por el servidor; no contienen información privada.
   Esta comprobación en Laravel evita eludir la aplicación, pero consume PHP: para
   bloquear tráfico antes del proceso, complementar con una regla de borde o una
   arquitectura de origen privado. El servicio Reverb requiere protección propia.

Si se necesita revertir la restricción de origen, retirar CLOUDFLARE_ORIGIN_SECRET
del servicio web y desplegar. Ese paso vuelve a abrir el acceso directo al origen.
No activar la restricción hasta verificar todos los callbacks y consumidores.

## Decisión sobre Anubis

Se aplaza: no hay evidencia de rastreo abusivo y Lexcita depende de WebSockets,
autenticación, pagos y AJAX. Anubis añade un proxy con desafíos de prueba de trabajo;
su documentación señala dudas sobre aplicaciones que mantienen WebSockets abiertos.
Evaluarlo en un entorno de prueba solo si los eventos muestran bots que las reglas
actuales no controlan. Probar usuarios móviles y redes lentas; no poner desafíos a
callbacks de Google/PayPal, health checks ni a señalización/autorización WebSocket.
No crear excepciones que permitan saltarse la autenticación o los límites de Laravel.

## Verificación

- PHPUnit cubre agotamiento/recuperación, cuentas distintas en una oficina, cambio de
  correo, IPv6, cabeceras no confiables, límites de reserva y WebSockets, no-store y origen.
- Node comprueba que el selector deja de consultar durante Retry-After.
- En producción hacer solo una prueba acotada: cinco logins fallidos a un correo
  ficticio, sexto 429, y una comprobación con cabeceras falsas. No crear cuentas ni
  citas para probar límites y no usar clientes reales. Para capacidad, usar un entorno
  de pruebas y escenarios autenticados representativos; GET /login no mide consultas
  a expedientes ni el transporte de video.

## Referencias

- [Laravel 12: rate limiting](https://laravel.com/docs/12.x/routing#rate-limiting)
- [Railway: dominios](https://docs.railway.com/networking/domains/working-with-domains)
- [Railway: cabeceras del ingress](https://docs.railway.com/networking/public-networking/specs-and-limits)
- [Railway: explicación del equipo sobre IP del cliente](https://station.railway.com/questions/security-critical-questions-on-edge-prox-8fddd775)
- [Cloudflare: caché predeterminada](https://developers.cloudflare.com/cache/concepts/default-cache-behavior/)
- [Cloudflare: modificar cabeceras al origen](https://developers.cloudflare.com/rules/transform/request-header-modification/)
- [Anubis: instalación y límites conocidos](https://github.com/TecharoHQ/anubis/blob/main/docs/docs/admin/installation.mdx)
