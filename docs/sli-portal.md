# Portal de Servicios Legales Integrados

## Despliegue de la retención de horarios

La actualización de horarios requiere la migración
`2026_10_22_120000_add_temporary_booking_holds.php`. Desplegar primero `SLI-Setup`
con el mismo commit y su comando privado `php scripts/migrate-organization.php`;
cuando termine correctamente, desplegar `SLI-Lexcita`. El servicio web usa el rol
`_app` sin permisos DDL: no ejecutar migraciones desde su consola ni darle
credenciales del propietario.

`SLI-Setup` es una tarea puntual: cuando finaliza no quedan instancias activas y
su consola muestra «Disconnected». No necesita mantenerse encendido. En Railway,
configurar su Start Command como `php scripts/migrate-organization.php`, desplegar
el commit actual de `feat/sli-landing` y revisar los logs del despliegue. El mensaje
de éxito es «Migraciones de la organización completadas, sin seeders». Un redeploy
de una imagen antigua no incluye las migraciones añadidas en commits posteriores.

Si el proceso privado sigue sin aplicar la migración, el administrador de
PostgreSQL puede ejecutar `scripts/sql/repair-sli-booking-holds.sql` mediante psql
conectado explícitamente a `lexcita_sli`. El script se limita a esa base, usa el rol
propietario existente, crea el mismo esquema de la migración y concede únicamente
los permisos de filas y secuencia del rol de aplicación. Registra la migración en
la misma transacción; puede repetirse sin duplicar ese registro ni extender
apartados existentes. No requiere reconstruir el servicio web. Este script no
aplica las migraciones de biblioteca.

Durante un despliegue con el esquema pendiente, los horarios se pueden consultar
y las citas existentes conservan su apartado. Las nuevas reservas reciben un
aviso temporal hasta que existan la tabla `booking_holds` y la columna
`citas.hold_expires_at`; no se permite reservar saltándose la retención. Al aplicar
la migración, la función se activa sin cambios de configuración. Las horas admiten
los formatos `H:i` y `H:i:s`. Solo las reservas impagas se liberan a los 15 minutos.

## Biblioteca comunitaria

Administración publica PDF o enlaces en `/interno/biblioteca` y es el único rol
que puede retirar recursos. Todos los abogados consultan el mismo catálogo en
`/abogado/biblioteca`; no existen cargas de libros desde ese portal.

Los PDF cargados se abren en un lector PDF.js interno, sin botones de descarga.
El catálogo muestra solo los PDF como libros: toda la tarjeta abre el lector.
PDF.js dibuja la primera página como portada cuando se acerca al área visible,
con un máximo de dos cargas simultáneas y lienzos limitados a 600 píxeles. Si una
portada no se puede generar, permanece una cubierta con el título y el acceso al
lector. No requiere columnas ni migraciones nuevas. Los portales externos se
consultan en un apartado plegable «Fuentes oficiales» y no consumen páginas de
libros. Administración dispone de un formulario principal para publicar PDF y
otro independiente para añadir fuentes externas. Un enlace a una página web no
se convierte automáticamente en un PDF.

Incluye páginas, zoom, resaltado, subrayado, notas y marcadores. Las anotaciones
se guardan por documento y usuario: no alteran el PDF ni aparecen a otro abogado.
Los enlaces externos siguen abriendo la fuente original; para usar anotaciones
es necesario que administración cargue el PDF. Los escaneos sin capa de texto
admiten notas de página y marcadores, pero no selección textual ni OCR.

El despliegue debe ejecutar las migraciones desde `SLI-Setup` con el rol propietario
para crear `sli_library_documents` y `sli_library_annotations`. La consola del
servicio web puede comprobar su existencia sin Tinker, cargando `bootstrap/app.php`
y consultando `Schema::hasTable`. Si falta el catálogo, el controlador devuelve 503;
si faltan las anotaciones, el lector devuelve 503.

Si la tarea privada sigue sin aplicar esas migraciones, ejecutar
`scripts/sql/repair-sli-library.sql` con psql como administrador de PostgreSQL,
conectado a `lexcita_sli`. El script comprueba base y organización, crea ambas
tablas, otorga permisos de filas y secuencias al rol de aplicación y registra ambas
migraciones en una transacción. Conserva documentos y anotaciones existentes y
no repone fuentes que administración haya retirado tras aplicar la migración.
Puede repetirse y no requiere reconstruir la web. La consola de PostgreSQL no
necesita curl ni wget: se puede pegar el contenido mediante un heredoc de psql.

Vite empaqueta el lector y copia las fuentes, CMaps
y WASM de PDF.js a `public/build/pdfjs`; se sirve con el mismo despliegue y
el almacenamiento privado `sli-assets` existente, sin proveedor adicional.

El frontend institucional se activa únicamente cuando la configuración del servidor
contiene `ORGANIZATION_ID=servicios-legales-integrados`. `AppServiceProvider` prioriza
las vistas de `resources/views/sli`; las demás organizaciones mantienen sus vistas.
No se seleccionan organizaciones mediante parámetros, cookies ni entradas del cliente.

La identidad visual toma como referencia https://servicioslegalesint.com/, indicada
por la organización: azul marino, superficies claras, acentos cálidos, tipografía
Bricolage Grotesque y el logotipo G.C. Tu Conexión Legal. El logotipo se sirve desde
`public/images/sli-firma.png`; procede del archivo público
`https://servicioslegalesint.com/wp-content/uploads/2025/11/GC-Largo-1536x585.png`.
Los enlaces de ayuda llevan a la página real del despacho.

La raíz `/` presenta una landing breve exclusivamente para SLI, con acceso al
login, creación de cuenta, explicación del portal y contacto con el despacho.
`/login` y `/registro` conservan los formularios existentes y ofrecen un enlace
de regreso al inicio. La raíz de las demás organizaciones sigue redirigiendo al
login. La selección depende de la configuración del servidor.

La landing combina un fondo marfil, una sección independiente «Cómo funciona»
en verde grisáceo y un retrato ilustrativo generado por IA. La imagen representa
un personaje ficticio, no un integrante identificado del despacho. Se sirve desde
`public/images/sli-abogado-retrato.webp`, optimizada en WebP; no depende de una URL externa.

Incluye acceso, registro, paneles de los tres roles, reserva y consulta de citas,
agenda, gestión de abogados y clientes, estadísticas, pago, comprobante imprimible,
videollamada y aviso de límite de solicitudes. Mantiene los controladores, rutas,
permisos, sesiones, OAuth y aislamiento de datos existentes.

La barra lateral adapta la plantilla CodingNepal entregada por el usuario en
`create-sidebar-menu-html-css-javascript.zip`: panel flotante con esquinas
redondeadas, contracción a iconos, etiquetas al enfocar y menú desplegable en móvil.
La preferencia de escritorio se conserva en almacenamiento local del navegador;
no genera solicitudes al servidor. Los enlaces se generan según el rol y cerrar
sesión sigue siendo una petición POST con CSRF. El menú móvil se cierra con Escape,
al pulsar fuera o al mover el foco fuera de él.

Los estilos y Alpine se compilan con Vite. Las páginas SLI no cargan el compilador
Tailwind desde un CDN ni abren conexiones de Echo sin necesitarlas. La carga de
horarios conserva su caché, cancelación de solicitudes y tratamiento de HTTP 429.
El calendario FullCalendar, las fuentes y Jitsi conservan sus servicios externos.

## Verificación

Ejecutar `npm ci`, `npm test`, `npm run build`, `php artisan test --compact`
y `php artisan view:cache`. `SliPortalTest` comprueba la selección por organización,
el registro desde los campos reales del formulario, persistencia de sesión,
validación, navegación por rol, propiedad de citas y acceso a videollamadas.
En Windows, las pruebas JaaS requieren `OPENSSL_CONF` apuntando al `openssl.cnf`
del runtime PHP.

Las comprobaciones de navegador se realizan con datos ficticios en una base local:
acceso y cierre de sesión, navegación móvil, selección de abogado/fecha/hora,
preconfirmación y modal de abogados. El diseño no habilita PayPal: si no está
configurado, muestra indicaciones para contactar al despacho y mantiene la cita
pendiente. No crea cuentas, citas ni pagos de prueba en producción.
