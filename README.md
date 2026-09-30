# WhatsApp Button Plugin

Botón flotante de WhatsApp para WordPress con un formulario emergente que captura el lead (nombre, email, mensaje y, opcionalmente, teléfono) antes de abrir la conversación.

## Funcionalidades

- **Botón y formulario**: plantilla del mensaje configurable, con los marcadores `{name}`, `{email}`, `{message}` y `{phone}`. El mensaje puede ser texto libre o un select con opciones.
- **Aviso por correo**: cada lead se envía al correo configurado con los datos de contacto, el origen del tráfico (UTMs, gclid, fbclid, msclkid, página de entrada, referente), la fecha y el dispositivo. El correo lleva `Reply-To` con el email del lead para responderle directamente.
- **Registro en la base de datos**: los leads se guardan en *Leads WhatsApp* (solo administradores), aunque falle el envío del correo. Se pueden exportar a CSV.
- **Medición**: envía el evento `whatsapp_lead` al `dataLayer` (ver abajo).
- **Consentimiento**: casilla obligatoria de tratamiento de datos, con enlace a la política de privacidad.
- **Anti-spam**: nonce, campo honeypot y límite de 5 envíos por IP cada 10 minutos.

## Configuración

*Ajustes → WhatsApp Button*

| Opción | Descripción |
|---|---|
| Número de WhatsApp | Con código de país, solo dígitos (por ejemplo `573001234567`). |
| Plantilla del mensaje | Texto que se abre en WhatsApp. |
| Campo teléfono | Muestra un campo de teléfono opcional. |
| Tipo de campo / Opciones del select | Texto libre o lista de opciones separadas por comas. |
| Consentimiento de datos | Activa la casilla, define su texto y la URL de la política. |
| Correo para recibir leads | Si está vacío se usa el correo del administrador. |
| Código de seguimiento | Opcional, solo para sitios **sin** GTM. |

> El envío de correos depende de que WordPress pueda enviarlos (se recomienda un plugin SMTP).

## Medición con Google Tag Manager

Cada envío válido del formulario hace:

```js
dataLayer.push({
  event: 'whatsapp_lead',
  lead_source: 'whatsapp_button',
  page_location: 'https://…',
  lead_topic: 'Opción elegida' // solo si el mensaje es un select
});
```

En GTM:

1. **Activador** → Evento personalizado → nombre del evento `whatsapp_lead`.
2. **Variables** (opcional) → Variable de capa de datos `lead_topic`.
3. Asocia el activador a tus etiquetas (evento GA4 `generate_lead`, conversión de Google Ads, `Lead` de Meta, etc.).

No se envían nombre ni email al `dataLayer`, porque GA4 no permite datos personales. También se emite el evento DOM `wab:lead` para otras integraciones.

### Sin GTM

En el campo *Código de seguimiento* puedes pegar JavaScript que se ejecutará con cada lead (las etiquetas `<script>` se quitan solas). Los datos del evento están en la variable `data`. La librería correspondiente (por ejemplo `gtag.js`) debe estar ya cargada en el sitio.

```js
gtag('event', 'conversion', { send_to: 'AW-XXXXXXX/XXXXXXX' });
```

## Caché de páginas

El formulario usa un nonce de WordPress, que caduca a las 12–24 horas. Si el sitio usa caché de páginas, configura una vida de caché menor de 10 horas (el valor por defecto en WP Rocket y similares) para que el nonce no expire.

## Desinstalación

Al borrar el plugin se eliminan sus ajustes. Los leads guardados **no** se eliminan; si quieres borrarlos, hazlo desde *Leads WhatsApp* antes de desinstalar.

## Autor
Desarrollado por [BAV Publicidad](https://bavpublicidad.com/bavit) – Agencia de software especializada en soluciones web y automatización digital.

© 2025 BAV IT. Distribuido bajo licencia [GPLv2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).
