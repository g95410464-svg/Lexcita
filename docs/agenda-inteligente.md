# Agenda inteligente de SLI

## Inspección inicial — 25 de octubre de 2026

Rama `feat/sli-landing`, árbol limpio antes de comenzar. Se revisó la especificación
aportada por el propietario, incluyendo escenarios, diagramas y pruebas T01–T28.
El módulo se integra en Laravel y FullCalendar existentes; no crea otra aplicación.

La agenda existente muestra citas y horario recurrente. La disponibilidad compara
horas de inicio; todavía no consulta actividades ni viajes. SLI ya dispone de
apartados de 15 minutos desde Detalles y un cerrojo en la fila del abogado para
reservas y captura de pagos. Se reutilizará ese mismo cerrojo para todas las
escrituras de agenda: serializa más que un bloqueo por día y evita combinar
estrategias de bloqueo incompatibles.

Línea base, antes de cambiar código del módulo:

- `php artisan route:list --except-vendor --json`: 62 rutas.
- `npm test`: 12 pruebas correctas.
- `php artisan test --compact`, con OPENSSL_CONF del runtime Windows: 175 correctas,
  10 fallidas, 913 aserciones. Seis fallos usan `email_verified_at` en una tabla
  cuya migración anterior quitó esa columna; otros corresponden a expectativas
  antiguas de JWT, idempotencia PayPal, enlace de registro y registro anterior al OTP.
  Estos resultados son previos al módulo; no deben presentarse como regresiones nuevas.

## Adaptaciones y orden de trabajo

1. Migración aditiva de ubicaciones, preferencias, actividades, trayectos manuales,
   conflictos, propuestas, preferencias del cliente, avisos internos y auditoría.
   Fechas de actividades y opciones en UTC; formulario y calendario en
   `America/El_Salvador`.
2. Unificar intervalos completos de ocupación y revalidación bajo el cerrojo del
   abogado, conservando el vencimiento del apartado y el JSON de `/api/slots`.
3. Extender FullCalendar y añadir formularios privados y avisos. El cambio se
   activa solo en SLI y cuando sus tablas estén migradas; mientras tanto la agenda
   anterior debe seguir accesible.
4. Trayectos explícitos manuales y adaptador OpenRouteService configurable. Sin
   estimación fiable, no ofrecer horas cuya viabilidad dependa del viaje. No
   estimar tráfico mediante velocidades inventadas ni exponer claves al cliente.
5. Conflictos y propuestas que requieren aceptación explícita del cliente.
   Conservar la misma cita, pago, importe, código y sala virtual.
6. Pruebas de permisos, fechas, intervalos, viajes, pagos y concurrencia, informe
   de resultados y pasos de migración/despliegue en Railway.

No se ejecuta Docker ni se crean contenedores en la PC. Las comprobaciones
controladas no deben enviar correos, cobrar pagos ni estresar producción.

## Confirmaciones recibidas durante el trabajo

El propietario confirmó que PDF, portadas, OTP, horarios de abogados nuevos y
migraciones funcionan. Solicitó comprobar la expiración con reloj simulado,
mejorar la presentación del email y lector y completar la actualización efectiva
de PHP. Esas confirmaciones se incorporarán al tablero sin atribuirlas a pruebas
de producción realizadas por el agente.

## Implementación disponible en la rama

Calendario mes/semana/día, actividades con edición, duplicado, retiro y repetición
semanal limitada a doce ocurrencias; ubicaciones privadas, jornada y estimaciones
manuales dirigidas. El proveedor OpenRouteService es opcional: HTTPS, clave solo
en servidor, caché por coordenadas/dirección/transporte, circuito ante fallos,
veinte peticiones por minuto y como máximo cuatro llamadas por solicitud. Sus
estimaciones no incluyen tráfico. Sin una estimación válida se descartan solo
las opciones cuya viabilidad depende del desplazamiento.

Las reservas vuelven a validar intervalos y trayectos al salir de Detalles. Una
actividad que afecta citas exige confirmación explícita del abogado y abre un
conflicto; no cambia citas. Las propuestas priorizan mismo día y siguiente. El
cliente puede aceptar, rechazar o enviar hasta cinco ventanas, autorizando por
separado fechas posteriores. Aceptar vuelve a comprobar disponibilidad bajo el
cerrojo, conserva pago/código/importe/sala y modifica exclusivamente el horario
y la ubicación propuestos. Las opciones enviadas no apartan plazas: pueden dejar
de estar disponibles antes de aceptarlas. Cambiar la base exige renovar propuestas.

Avisos internos transaccionales, auditoría y panel administrativo de conflictos.
`agenda:review` revisa expiraciones y escala citas cercanas; también se ejecuta
al abrir la agenda del abogado. Web Push/PWA queda para una fase posterior: no
hay avisos push de fondo, y el flujo funciona con los avisos internos.

## Activación en Railway

1. Publicar la rama, dejando producción con la versión anterior mientras el build
   termina. No cambiar `main` ni quitar la retención de quince minutos.
2. En SLI-Setup, con su rol propietario y sus variables privadas existentes:
   `php scripts/migrate-organization.php`. La migración nueva es
   `2026_10_25_120000_create_sli_smart_agenda.php`; no hay seeders automáticos.
3. Desplegar SLI-Lexcita con el mismo commit y rol web limitado. El módulo se
   muestra solo en SLI y con todo el esquema preparado. Antes de migrar sigue
   funcionando el calendario anterior. `AGENDA_SMART_ENABLED=false` desactiva
   temporalmente la extensión conservando datos, propuestas y pagos.
4. Para revisión automática sin esperar a que alguien abra el calendario, mantener
   un proceso privado `php artisan schedule:work` con las variables del rol web.
   No necesita permisos DDL. No poner ese comando como inicio del servidor web.
5. Empezar con `ROUTING_PROVIDER=manual`. Para rutas externas configurar
   `ROUTING_PROVIDER=openrouteservice` y `ROUTING_API_KEY` privada. Se requieren
   coordenadas y estimaciones fiables; no se contrató ningún servicio de pago.

## Verificación del desarrollo

- 59 pruebas seleccionadas correctas, 338 aserciones: agenda, pagos, retenciones,
  Jitsi, biblioteca y correo HTML con Brevo simulado; sin envíos ni cobros reales.
- 12 pruebas JavaScript correctas, build Vite y compilación de todas las vistas.
- 41 archivos PHP nuevos/modificados revisados sintácticamente.
- Inspección en navegador de agenda, formularios, propuestas, lector y correo
  con fixtures SQLite privados fuera del repositorio; escritorio y 390px,
  sin errores JavaScript ni desbordamiento horizontal.
- La suite completa anterior tenía diez fallos. Ahora conserva nueve fallos ya
  presentes (fixtures admin/email_verified_at, expectativa de idempotencia PayPal
  y dos expectativas antiguas del portal); la expectativa antigua de JWT se
  corrigió para corresponder al final real de la cita. No se afirma suite global verde.

La prueba remota de 100 procesos y PostgreSQL fue aprobada en el run
40196918176 (`1473e2f`): 100 lecturas correctas, un apartado, 99 rechazos y reloj
simulado correcto para impagos/pagados. Informe en `docs/prueba-controlada-100.md`.
Las pruebas SQLite no demuestran por sí mismas concurrencia PostgreSQL. No se afirma
que estos cambios estén desplegados o que PHP efectivo esté actualizado hasta
tener evidencia de Railway. Véase `docs/php-railway-verificacion.md`.
