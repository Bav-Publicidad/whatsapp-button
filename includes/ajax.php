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

    $name    = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email   = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
    $url     = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';

    if ($name === '' || $message === '' || ! is_email($email)) {
        wp_send_json_error(['error' => 'Datos incompletos o email no válido.'], 400);
    }

    // Si el mensaje es un select, solo se aceptan las opciones configuradas
    if (get_option('whatsapp_message_field_type', 'text') === 'select'
        && ! in_array($message, whatsapp_button_get_select_options(), true)) {
        wp_send_json_error(['error' => 'Opción no válida.'], 400);
    }

    $name    = mb_substr($name, 0, 100);
    $message = mb_substr($message, 0, 1000);

    // Obtener correo de destino desde las opciones del plugin
    $admin_email = get_option('whatsapp_lead_email');
    if (! $admin_email || ! is_email($admin_email)) {
        $admin_email = get_option('admin_email'); // fallback al admin general de WP
    }

    $subject = 'Nuevo formulario de WhatsApp completado';
    $body    = "Nombre: $name\nEmail: $email\nMensaje: $message\nUrl: $url";
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    if (! wp_mail($admin_email, $subject, $body, $headers)) {
        wp_send_json_error(['error' => 'No se pudo enviar el correo. Verifica la configuración SMTP.'], 500);
    }

    wp_send_json_success();
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
