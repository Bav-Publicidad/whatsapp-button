<?php

/**
 * Plugin Name:       Leadivo – Lead Chat Button
 * Description:       Botón flotante de chat con formulario de captura de leads: envío por correo, registro en la base de datos y evento para Google Tag Manager.
 * Version:           1.4.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            BAV IT | BAV Publicidad
 * Author URI:        https://bavpublicidad.com/bavit
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       leadivo
 */

if (! defined('ABSPATH')) {
    exit;
}

define('LEADIVO_VERSION', '1.4.0');
define('LEADIVO_PATH', plugin_dir_path(__FILE__));
define('LEADIVO_URL', plugin_dir_url(__FILE__));
define('LEADIVO_DEFAULT_TEMPLATE', 'Hola, soy {name} y mi email es {email}. Estoy interesado en: {message}');
define('LEADIVO_DEFAULT_FORM_TITLE', '¡Hola! ¿Cómo podemos ayudarte?');
define('LEADIVO_DEFAULT_FORM_DESCRIPTION', 'Por favor, completa la información para iniciar la conversación:');
define('LEADIVO_DEFAULT_CONSENT_TEXT', 'Acepto el tratamiento de mis datos personales para ser contactado.');

require_once LEADIVO_PATH . 'includes/frontend.php';
require_once LEADIVO_PATH . 'includes/leads.php';
require_once LEADIVO_PATH . 'includes/ajax.php';
require_once LEADIVO_PATH . 'includes/migration.php';

if (is_admin()) {
    require_once LEADIVO_PATH . 'includes/admin.php';
}
