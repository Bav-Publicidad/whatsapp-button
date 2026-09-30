<?php

/**
 * Plugin Name:       Chat Lead Button – Lead capture for WhatsApp
 * Description:       Botón flotante de chat con formulario de captura de leads: envío por correo, registro en la base de datos y evento para Google Tag Manager.
 * Version:           1.3.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            BAV IT | BAV Publicidad
 * Author URI:        https://bavpublicidad.com/bavit
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       chat-lead-button
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WAB_VERSION', '1.3.0');
define('WAB_PATH', plugin_dir_path(__FILE__));
define('WAB_URL', plugin_dir_url(__FILE__));
define('WAB_DEFAULT_TEMPLATE', 'Hola, soy {name} y mi email es {email}. Estoy interesado en: {message}');
define('WAB_DEFAULT_FORM_TITLE', '¡Hola! ¿Cómo podemos ayudarte?');
define('WAB_DEFAULT_FORM_DESCRIPTION', 'Por favor, completa la información para iniciar la conversación:');
define('WAB_DEFAULT_CONSENT_TEXT', 'Acepto el tratamiento de mis datos personales para ser contactado.');

require_once WAB_PATH . 'includes/frontend.php';
require_once WAB_PATH . 'includes/leads.php';
require_once WAB_PATH . 'includes/ajax.php';

if (is_admin()) {
    require_once WAB_PATH . 'includes/admin.php';
}
