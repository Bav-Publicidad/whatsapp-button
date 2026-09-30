<?php

if (! defined('ABSPATH')) {
    exit;
}

define('WAB_RATE_LIMIT_MAX', 5);                  // Envíos permitidos por IP...
define('WAB_RATE_LIMIT_WINDOW', 10 * MINUTE_IN_SECONDS); // ...en esta ventana de tiempo

// Recibir el lead del formulario (usuarios con y sin sesión)
function whatsapp_button_handle_lead()
{
    if (! check_ajax_referer('wab_submit_lead', 'nonce', false)) {
        wp_send_json_error(['error' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.'], 403);
    }

    // Honeypot: los humanos no ven este campo; si viene relleno es un bot.
    // Se responde "éxito" para no darle pistas.
    if (! empty($_POST['website'])) {
        wp_send_json_success();
    }

    if (whatsapp_button_is_rate_limited()) {
        wp_send_json_error(['error' => 'Demasiados envíos. Inténtalo de nuevo en unos minutos.'], 429);
    }

    $lead = whatsapp_button_get_posted_lead();

    if ($lead['name'] === '' || $lead['message'] === '') {
        wp_send_json_error(['error' => 'Datos incompletos.'], 400);
    }

    // El email solo es obligatorio si el campo está activo en los ajustes
    if (whatsapp_button_email_enabled()) {
        if (! is_email($lead['email'])) {
            wp_send_json_error(['error' => 'Email no válido.'], 400);
        }
    } else {
        $lead['email'] = '';
    }

    // Si el mensaje es un select, solo se aceptan las opciones configuradas
    if (get_option('whatsapp_message_field_type', 'text') === 'select'
        && ! in_array($lead['message'], whatsapp_button_get_select_options(), true)) {
        wp_send_json_error(['error' => 'Opción no válida.'], 400);
    }

    if (whatsapp_button_consent_enabled() && $lead['consent'] !== 'Sí') {
        wp_send_json_error(['error' => 'Debes aceptar el tratamiento de datos.'], 400);
    }

    // Se guarda primero en la BD: si el correo falla, el lead no se pierde
    $lead_id   = whatsapp_button_save_lead($lead);
    $mail_sent = whatsapp_button_send_lead_email($lead);

    if ($lead_id) {
        update_post_meta($lead_id, '_wab_mail_sent', $mail_sent ? '1' : '0');
    }

    if (! $lead_id && ! $mail_sent) {
        wp_send_json_error(['error' => 'No se pudo registrar el lead.'], 500);
    }

    wp_send_json_success(['saved' => (bool) $lead_id, 'mail_sent' => $mail_sent]);
}

// Leer y sanitizar los datos enviados por el formulario
function whatsapp_button_get_posted_lead()
{
    $text = function ($key, $max = 200) {
        return isset($_POST[$key]) ? mb_substr(sanitize_text_field(wp_unslash($_POST[$key])), 0, $max) : '';
    };
    $url = function ($key) {
        return isset($_POST[$key]) ? esc_url_raw(wp_unslash($_POST[$key])) : '';
    };

    return [
        'name'         => $text('name', 100),
        'email'        => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
        'phone'        => $text('phone', 30),
        'message'      => isset($_POST['message']) ? mb_substr(sanitize_textarea_field(wp_unslash($_POST['message'])), 0, 1000) : '',
        'page_url'     => $url('page_url'),
        'page_title'   => $text('page_title'),
        'landing_page' => $url('landing_page'),
        'referrer'     => $url('referrer'),
        'utm_source'   => $text('utm_source'),
        'utm_medium'   => $text('utm_medium'),
        'utm_campaign' => $text('utm_campaign'),
        'utm_term'     => $text('utm_term'),
        'utm_content'  => $text('utm_content'),
        'gclid'        => $text('gclid'),
        'fbclid'       => $text('fbclid'),
        'msclkid'      => $text('msclkid'),
        'consent'      => isset($_POST['consent']) && $_POST['consent'] === '1' ? 'Sí' : '',
        'device'       => wp_is_mobile() ? 'Móvil' : 'Escritorio',
        'date'         => wp_date('Y-m-d H:i'),
    ];
}

// Enviar el lead por correo
function whatsapp_button_send_lead_email(array $lead)
{
    // Obtener correo de destino desde las opciones del plugin
    $admin_email = get_option('whatsapp_lead_email');
    if (! $admin_email || ! is_email($admin_email)) {
        $admin_email = get_option('admin_email'); // fallback al admin general de WP
    }

    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $subject   = sprintf('Nuevo lead de WhatsApp: %s – %s', $lead['name'], $site_name);

    $lines = [
        '=== DATOS DEL CONTACTO ===',
        'Nombre: ' . $lead['name'],
    ];
    if ($lead['email'] !== '') {
        $lines[] = 'Email: ' . $lead['email'];
    }
    if ($lead['phone'] !== '') {
        $lines[] = 'Teléfono: ' . $lead['phone'];
    }
    $lines[] = 'Mensaje: ' . $lead['message'];

    $lines[] = '';
    $lines[] = '=== ORIGEN ===';
    $lines[] = 'Página: ' . ($lead['page_title'] !== '' ? $lead['page_title'] : '-');
    $lines[] = 'URL: ' . $lead['page_url'];
    if ($lead['landing_page'] !== '' && $lead['landing_page'] !== $lead['page_url']) {
        $lines[] = 'Página de entrada: ' . $lead['landing_page'];
    }
    $lines[] = 'Referente: ' . ($lead['referrer'] !== '' ? $lead['referrer'] : 'Directo / desconocido');

    $campaign_labels = [
        'utm_source'   => 'UTM source',
        'utm_medium'   => 'UTM medium',
        'utm_campaign' => 'UTM campaign',
        'utm_term'     => 'UTM term',
        'utm_content'  => 'UTM content',
        'gclid'        => 'gclid (Google Ads)',
        'fbclid'       => 'fbclid (Meta)',
        'msclkid'      => 'msclkid (Microsoft Ads)',
    ];
    foreach ($campaign_labels as $key => $label) {
        if ($lead[$key] !== '') {
            $lines[] = $label . ': ' . $lead[$key];
        }
    }

    $lines[] = '';
    $lines[] = '=== OTROS ===';
    $lines[] = 'Fecha: ' . $lead['date'];
    $lines[] = 'Dispositivo: ' . $lead['device'];
    if ($lead['consent'] !== '') {
        $lines[] = 'Aceptó tratamiento de datos: ' . $lead['consent'];
    }
    $lines[] = 'Sitio: ' . home_url();

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    // Reply-To: responder al correo le escribe directamente al lead.
    // Se quitan caracteres que podrían alterar la cabecera.
    if ($lead['email'] !== '') {
        $reply_name = trim(str_replace(['"', '<', '>', ',', ';', "\r", "\n"], '', $lead['name']));
        $headers[]  = sprintf('Reply-To: "%s" <%s>', $reply_name, $lead['email']);
    }

    return wp_mail($admin_email, $subject, implode("\n", $lines), $headers);
}
add_action('wp_ajax_wab_submit_lead', 'whatsapp_button_handle_lead');
add_action('wp_ajax_nopriv_wab_submit_lead', 'whatsapp_button_handle_lead');

// Límite simple de envíos por IP usando transients
function whatsapp_button_is_rate_limited()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    if ($ip === '') {
        return false;
    }

    $key   = 'wab_rl_' . md5($ip);
    $count = (int) get_transient($key);

    if ($count >= WAB_RATE_LIMIT_MAX) {
        return true;
    }

    set_transient($key, $count + 1, WAB_RATE_LIMIT_WINDOW);
    return false;
}
