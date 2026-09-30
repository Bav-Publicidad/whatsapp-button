<?php

/**
 * Plugin Name: WhatsApp Button Plugin
 * Description: Muestra un botón de WhatsApp con formulario emergente y permite editar el mensaje predeterminado desde el administrador.
 * Version: 1.2.1
 * Author: BAV IT | BAV Publicidad
 * Author URI: https://bavpublicidad.com/bavit
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WAB_VERSION', '1.2.1');
define('WAB_PATH', plugin_dir_path(__FILE__));
define('WAB_URL', plugin_dir_url(__FILE__));
define('WAB_DEFAULT_TEMPLATE', 'Hola, soy {name} y mi email es {email}. Estoy interesado en: {message}');

require_once WAB_PATH . 'includes/frontend.php';

if (is_admin()) {
    require_once WAB_PATH . 'includes/admin.php';
}
