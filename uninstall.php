<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Nota: los leads guardados (tipo de contenido "wab_lead") NO se borran
// para no perder información de contacto; se pueden eliminar desde el admin
// antes de desinstalar.
function whatsapp_button_uninstall_options()
{
    $options = [
        'whatsapp_phone_number',
        'whatsapp_message_template',
        'whatsapp_message_field_type',
        'whatsapp_select_options',
        'whatsapp_lead_email',
        'whatsapp_tracking_code',
        'whatsapp_show_phone_field',
        'whatsapp_show_email_field',
        'whatsapp_form_title',
        'whatsapp_form_description',
        'whatsapp_consent_enabled',
        'whatsapp_consent_text',
        'whatsapp_privacy_url',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }
}

if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $site_id) {
        switch_to_blog($site_id);
        whatsapp_button_uninstall_options();
        restore_current_blog();
    }
} else {
    whatsapp_button_uninstall_options();
}
