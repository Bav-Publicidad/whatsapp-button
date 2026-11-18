<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Nota: los leads guardados (tipo de contenido "leadivo_lead") NO se borran
// para no perder información de contacto; se pueden eliminar desde el admin
// antes de desinstalar.
function leadivo_uninstall_options()
{
    $options = [
        'leadivo_phone_number',
        'leadivo_message_template',
        'leadivo_message_field_type',
        'leadivo_select_options',
        'leadivo_lead_email',
        'leadivo_tracking_code',
        'leadivo_show_phone_field',
        'leadivo_show_email_field',
        'leadivo_show_message_field',
        'leadivo_form_title',
        'leadivo_form_description',
        'leadivo_consent_enabled',
        'leadivo_consent_text',
        'leadivo_privacy_url',
        'leadivo_legacy_migrated',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }
}

if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $site_id) {
        switch_to_blog($site_id);
        leadivo_uninstall_options();
        restore_current_blog();
    }
} else {
    leadivo_uninstall_options();
}
