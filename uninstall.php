<?php
if ( ! defined('WP_UNINSTALL_PLUGIN') ) exit;

// Borra todas tus opciones:
delete_option('whatsapp_message_template');
delete_option('whatsapp_phone_number');
delete_option('whatsapp_message_field_type');
delete_option('whatsapp_select_options');
delete_option('whatsapp_lead_email');
delete_option('wa_plugin_version');

// Si es multisite:
delete_site_option('whatsapp_message_template');
