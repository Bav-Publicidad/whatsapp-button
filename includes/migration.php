<?php

if (! defined('ABSPATH')) {
    exit;
}

// Las versiones anteriores (Chat Lead Button / WhatsApp Button) guardaban los datos
// con los prefijos "whatsapp_" (opciones) y "wab_" (leads). Esta migración los pasa
// una sola vez a los nombres con el prefijo del plugin para no perder ajustes ni leads.
function leadivo_migrate_legacy_data()
{
    if (get_option('leadivo_legacy_migrated') === '1') {
        return;
    }

    global $wpdb;

    $options = [
        'phone_number', 'lead_email', 'message_template', 'tracking_code',
        'message_field_type', 'select_options', 'show_phone_field', 'show_email_field',
        'show_message_field', 'form_title', 'form_description', 'consent_enabled',
        'consent_text', 'privacy_url',
    ];
    $missing = new stdClass();

    foreach ($options as $option) {
        $legacy = get_option('whatsapp_' . $option, $missing);
        if ($legacy === $missing) {
            continue;
        }
        if (get_option('leadivo_' . $option, $missing) === $missing) {
            add_option('leadivo_' . $option, $legacy);
        }
        delete_option('whatsapp_' . $option);
    }

    // Leads guardados: tipo de contenido y metadatos
    // phpcs:disable WordPress.DB.DirectDatabaseQuery
    $wpdb->update($wpdb->posts, ['post_type' => LEADIVO_LEAD_POST_TYPE], ['post_type' => 'wab_lead']);
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_key = CONCAT(%s, SUBSTRING(meta_key, 6)) WHERE meta_key LIKE %s",
            '_leadivo_',
            $wpdb->esc_like('_wab_') . '%'
        )
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery

    update_option('leadivo_legacy_migrated', '1');
}
add_action('init', 'leadivo_migrate_legacy_data', 5);
