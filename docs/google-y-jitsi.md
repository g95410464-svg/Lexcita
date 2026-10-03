# Google y videollamadas

## Reutilizar el cliente de Google

No es necesario crear un proyecto OAuth para cada despliegue. En Railway, el servicio SLI-Lexcita puede referenciar las variables del servicio Lexcita:

```dotenv
GOOGLE_CLIENT_ID=${{Lexcita.GOOGLE_CLIENT_ID}}
GOOGLE_CLIENT_SECRET=${{Lexcita.GOOGLE_CLIENT_SECRET}}
GOOGLE_REDIRECT_URI=https://sli-lexcita-production.up.railway.app/auth/google/callback
```

En Google Cloud, abrir el cliente OAuth existente y añadir esa dirección exacta a las URI de redirección autorizadas. Conservar la dirección de la aplicación anterior. No copiar usuarios ni credenciales de la base anterior: cada callback consulta exclusivamente la base del despliegue que lo recibe.

La cuenta existente `servicioslegales209@gmail.com` conserva su rol administrador al entrar con ese mismo correo verificado por Google. Las cuentas nuevas se crean únicamente como clientes. Las cuentas inactivas no pueden entrar.

El flujo usa `state` y `nonce` aleatorios, vinculados a la sesión y consumidos una sola vez. El intento vence a los diez minutos. El SDK valida el ID token; la aplicación verifica además audiencia, vencimiento, nonce y correo verificado. Gmail y Google Workspace pueden entrar automáticamente. Para cuentas Google con correo externo, usar correo y contraseña: Google no garantiza la propiedad actual de ese buzón. No se guardan access tokens ni refresh tokens.

Validar con una sesión real de Google después de autorizar el callback. Una redirección HTTP hacia Google solo demuestra que la aplicación construye la solicitud, no que Google haya aceptado la configuración.

## Jitsi público

`JITSI_PROVIDER=public` mantiene `meet.jit.si` y los nombres de salas existentes. La persona que crea la conferencia debe autenticarse en Jitsi. La vista explica al abogado cómo iniciar la consulta y al cliente cómo esperar. Esa autenticación es independiente del login de Lexcita.

El acceso a la página de Lexcita exige ser participante activo de una cita virtual confirmada dentro del horario permitido. El nombre aleatorio de la sala no equivale a autorización en el servidor público: el abogado debe activar la sala de espera y admitir solo a su cliente. No prometer aislamiento de participantes mediante el login de Lexcita mientras se use el servicio público.

## JaaS administrado

La integración está preparada, pero requiere una cuenta JaaS, un AppID y una clave pública registrada allí. Guardar la clave privada exclusivamente como secreto `JAAS_PRIVATE_KEY` en el servicio web, nunca en Git, variables VITE, capturas o mensajes. También se aceptan saltos de línea representados como `\n`.

```dotenv
JITSI_PROVIDER=jaas
JAAS_APP_ID=vpaas-magic-cookie-IDENTIFICADOR
JAAS_KEY_ID=vpaas-magic-cookie-IDENTIFICADOR/CLAVE
JAAS_PRIVATE_KEY="CLAVE PRIVADA PEM"
```

Lexcita firma con RS256 un JWT distinto por participante, limitado al nombre exacto de su sala, sin comodines ni expresiones regulares. Solo el abogado asignado recibe permiso de moderador. El JWT vence como máximo en treinta minutos o al finalizar la cita, lo que ocurra antes. El vencimiento limita nuevos ingresos; no garantiza expulsar una llamada que ya está conectada. Recargar la página dentro del horario genera otra credencial.

Se deshabilitan en el JWT grabación, transcripción, streaming, llamadas telefónicas y carga de archivos. No se incluye correo, teléfono ni descripción del caso. La respuesta se marca privada, sin caché y sin referente. Si faltan claves o la firma falla, devuelve 503: nunca cambia automáticamente a una sala pública.

No activar JaaS hasta revisar la cuenta y sus condiciones comerciales y verificar una llamada de dos participantes con cámara y micrófono. Las pruebas automatizadas comprueban autorización, firma y configuración; no sustituyen esa prueba de medios ni verifican la disponibilidad del proveedor.

Referencias oficiales:

- [OAuth web de Google](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Propiedad del correo y verificación de identidad](https://developers.google.com/identity/gsi/web/reference/html-reference)
- [Seguridad de Jitsi público](https://jitsi.org/security/)
- [Integración JaaS](https://developer.8x8.com/jaas/docs/iframe-api-integration/)
- [JWT de JaaS](https://developer.8x8.com/jaas/docs/api-keys-jwt/)
