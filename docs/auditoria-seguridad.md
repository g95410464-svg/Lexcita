# Revisión de consultas y aislamiento de datos

## Resultado y límites de la revisión

Se revisaron los controladores, servicios, modelos, rutas, vistas PHP y migraciones
versionados. Las consultas Eloquent usan parámetros para los valores de entrada.
Las expresiones SQL manuales encontradas son constantes: agregación del dashboard
con bindings y agrupación por mes en estadísticas. No se encontró SQL construido
concatenando entradas del usuario en las rutas examinadas.

Esto no certifica ausencia total de vulnerabilidades. Las pruebas automatizadas de
este cambio corren sobre SQLite; la configuración efectiva de roles y políticas del
PostgreSQL de producción todavía no se ha podido consultar. No se ejecutó un ataque
de carga ni un escáner agresivo contra producción.

## Correcciones comprobadas

- Agrupación del OR de búsqueda administrativa: una coincidencia por código ya no
  evade los filtros seleccionados de estado y abogado. Era un problema lógico,
  no una inyección SQL.
- Validación de filtros como escalares, estado permitido, identificador numérico y
  búsqueda de longitud limitada. El texto buscado permanece como parámetro SQL.
- Identificadores de rutas numéricos: entradas malformadas dan 404 antes de coerción PHP.
- Horarios y reservas aceptan únicamente abogados activos y fechas YYYY-MM-DD.
- Retornos de PayPal rechazan arrays y tokens demasiado largos antes de consultar.
- Pruebas de intento de bypass del login, búsqueda con texto de inyección, filtros,
  identificadores, callbacks y acceso/cancelación de citas de otro cliente.

## RLS todavía no está activado por este cambio

Actualmente el esquema contiene usuarios con roles y citas ligadas a cliente y
abogado; no contiene organizaciones ni membresías por bufete. La administración
actual es global. Los filtros de Laravel y sus pruebas de autorización no equivalen
a políticas RLS ejecutadas por PostgreSQL.

Primero debe elegirse uno de estos modelos:

1. Tablas compartidas, organización en cada registro y políticas RLS. Menor coste
   operativo; exige membresías verificadas y comprobaciones entre organizaciones.
2. Esquema por organización. Separación lógica; los recursos siguen compartidos y
   las migraciones deben recorrer los esquemas.
3. Base de datos por organización dentro de un servidor compartido. Permite una
   administración/restauración por base; los recursos del servidor siguen compartidos.
4. Instalación dedicada. Mayor separación de recursos y operaciones, con mayor coste.

La propuesta para evaluar es RLS por organización para bufetes e instalación
dedicada cuando lo requiera un contrato institucional. No es una afirmación de
cumplimiento de una licitación concreta, cuyo pliego no se ha proporcionado.

## Requisitos antes de activar RLS en producción

- Ejecutar en el contenedor web `php artisan security:database-audit`. El comando
  solo lee metadatos de roles/tablas/políticas y nombres de almacenes de caché. No
  imprime registros de clientes, credenciales ni URLs de conexión. En SQLite falla
  explícitamente porque ese motor no valida las políticas de PostgreSQL.
- Preparar un rol de ejecución separado del rol de migraciones: sin superusuario,
  BYPASSRLS, propiedad de tablas ni permisos para modificar políticas o estructura.
  ENABLE/FORCE RLS no protege frente a un superusuario o un rol BYPASSRLS.
- Definir organización y membresía de cada registro existente. No asignar registros
  automáticamente a bufetes inventados ni aceptar una organización enviada por el
  navegador sin verificar que el usuario pertenece a ella.
- Implementar políticas de lectura y escritura (`USING` y `WITH CHECK`) para citas,
  participantes, archivos, mensajes y notas. Los permisos dentro de cada organización
  deben distinguir cliente, abogado y administración.
- Establecer el contexto de usuario/organización desde Laravel dentro de transacciones,
  limitar su duración a la operación y verificar limpieza al reutilizar conexiones.
  Los procesos en cola necesitan contexto explícito. No exponer credenciales SQL al
  navegador ni tratar variables de sesión SQL como defensa suficiente frente a SQL
  arbitrario: un usuario SQL con permisos puede cambiar variables personalizadas.
- Mantener disponible la consulta de horarios sin revelar las citas de otros clientes:
  una política que oculte reservas ajenas puede crear dobles reservas si se usa esa
  misma consulta para calcular disponibilidad. Resolverlo con una operación controlada
  que retorne solo disponibilidad y una restricción de integridad de reservas.
- Probar PostgreSQL real con el rol restringido: acceso cruzado denegado, escrituras
  ajenas denegadas, conexiones reutilizadas, tareas y registro/login. Ensayar recuperación
  con respaldo y activar políticas/coordinación de rol y código durante un despliegue
  preparado; no habilitarlas a ciegas sobre producción.

## Referencias

- [Laravel 12: Query Builder y bindings](https://laravel.com/docs/12.x/queries)
- [PostgreSQL: Row Security Policies](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
- [AWS: comparación de modelos de aislamiento](https://docs.aws.amazon.com/prescriptive-guidance/latest/saas-multitenant-managed-postgresql/matrix.html)
