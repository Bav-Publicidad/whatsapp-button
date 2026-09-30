<?php

/**
 * Plugin Name: WhatsApp Button Plugin
 * Description: Botón flotante de WhatsApp con formulario de captura de leads: envío por correo, registro en la base de datos y evento para Google Tag Manager.
 * Version: 1.3.0
 * Author: BAV IT | BAV Publicidad
 * Author URI: https://bavpublicidad.com/bavit
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WAB_VERSION', '1.3.0');
define('WAB_PATH', plugin_dir_path(__FILE__));
define('WAB_URL', plugin_dir_url(__FILE__));
define('WAB_DEFAULT_TEMPLATE', 'Hola, soy {name} y mi email es {email}. Estoy interesado en: {message}');
define('WAB_DEFAULT_CONSENT_TEXT', 'Acepto el tratamiento de mis datos personales para ser contactado.');

require_once WAB_PATH . 'includes/frontend.php';
require_once WAB_PATH . 'includes/leads.php';
require_once WAB_PATH . 'includes/ajax.php';

if (is_admin()) {
    require_once WAB_PATH . 'includes/admin.php';
}
