<?php
// Salir si se accede directamente
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once('../../../wp-load.php'); // Carga el entorno de WordPress
}

// Asegura que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message = sanitize_text_field($_POST['message']);

    // Obtener correo de destino desde las opciones del plugin
    $admin_email = get_option('whatsapp_lead_email');
    if (!$admin_email || !is_email($admin_email)) {
        $admin_email = get_option('admin_email'); // fallback al admin general de WP
    }

    $subject = 'Nuevo formulario de WhatsApp completado';
    $body = "Nombre: $name\nEmail: $email\nMensaje: $message\n.";
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    // Enviar correo
    $success = wp_mail($admin_email, $subject, $body, $headers);

    if (!$success) {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo enviar el correo. Verifica la configuración SMTP.'
        ]);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}
