# Portal de Servicios Legales Integrados

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
