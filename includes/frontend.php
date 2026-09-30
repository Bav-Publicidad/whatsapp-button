<?php

if (! defined('ABSPATH')) {
    exit;
}

// Opciones del select como array, sin entradas vacías
function whatsapp_button_get_select_options()
{
    $raw = get_option('whatsapp_select_options', '');
    return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
}

// Quita las etiquetas <script> que suelen venir en los snippets de Google Ads, Meta, etc.
// Dentro de un script en línea, un "</script>" cerraría el bloque y rompería el JS.
function whatsapp_button_strip_script_tags($code)
{
    return trim(preg_replace('#</?script\b[^>]*>#i', '', (string) $code));
}

// Encolar estilos y scripts solo si hay un número configurado
function whatsapp_button_enqueue_assets()
{
    if (empty(get_option('whatsapp_phone_number', ''))) {
        return;
    }

    wp_enqueue_style('whatsapp-button', WAB_URL . 'assets/css/whatsapp-button.css', [], WAB_VERSION);
    wp_enqueue_script('whatsapp-button', WAB_URL . 'assets/js/whatsapp-button.js', [], WAB_VERSION, true);

    // Configuración para el JS (wp_json_encode evita romper el script con comillas o saltos de línea)
    $config = [
        'phoneNumber'     => get_option('whatsapp_phone_number', ''),
        'messageTemplate' => get_option('whatsapp_message_template', WAB_DEFAULT_TEMPLATE),
        'fieldType'       => get_option('whatsapp_message_field_type', 'text'),
        'ajaxUrl'         => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('wab_submit_lead'),
    ];
    wp_add_inline_script('whatsapp-button', 'window.wabConfig = ' . wp_json_encode($config) . ';', 'before');

    // Código de seguimiento personalizado, aislado en una función con try/catch
    // para que un error en él no impida enviar el lead ni abrir WhatsApp
    // (recibe los datos del evento como `data`). Se quitan etiquetas <script>
    // por si se guardaron antes de existir la sanitización.
    $tracking_code = whatsapp_button_strip_script_tags(get_option('whatsapp_tracking_code', ''));
    if (! empty($tracking_code)) {
        wp_add_inline_script(
            'whatsapp-button',
            "window.wabTrack = function(data) {\ntry {\n" . $tracking_code . "\n} catch (e) {\nconsole.error('Error en el código de seguimiento de WhatsApp:', e);\n}\n};",
            'before'
        );
    }
}
add_action('wp_enqueue_scripts', 'whatsapp_button_enqueue_assets');

// Mostrar el botón de WhatsApp
function whatsapp_button_display()
{
    $phone_number = get_option('whatsapp_phone_number', '');
    if (empty($phone_number)) {
        return;
    }

    // Obtener tipo de campo y opciones
    $message_field_type = get_option('whatsapp_message_field_type', 'text');
    $select_options = whatsapp_button_get_select_options();
?>
    <div class="whatsapp-container">
        <button type="button" class="whatsapp-button" aria-label="Abrir chat de WhatsApp" aria-expanded="false" aria-controls="whatsapp-popup">
            <img src="<?php echo esc_url(WAB_URL . 'whatsapp-icon.png'); ?>" alt="" />
        </button>
        <div class="whatsapp-popup" id="whatsapp-popup" role="dialog" aria-labelledby="whatsapp-popup-title" hidden>
            <button type="button" class="whatsapp-close" aria-label="Cerrar">&times;</button>
            <form id="whatsapp-form">
                <h3 id="whatsapp-popup-title">¡Hola! ¿Cómo podemos ayudarte?</h3>
                <p>Por favor, completa la información para iniciar la conversación:</p>

                <label for="whatsapp-name">Nombre:</label>
                <input type="text" id="whatsapp-name" name="name" required maxlength="100" autocomplete="name" placeholder="Tu nombre" />

                <label for="whatsapp-email">Email:</label>
                <input type="email" id="whatsapp-email" name="email" required autocomplete="email" placeholder="Tu email" />

                <label for="whatsapp-message">Mensaje:</label>
                <?php if ($message_field_type === 'select') : ?>
                    <select id="whatsapp-message" name="message" required>
                        <option value="" disabled selected>Selecciona una opción</option>
                        <?php foreach ($select_options as $option) : ?>
                            <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <textarea id="whatsapp-message" name="message" required maxlength="1000" placeholder="Escribe tu mensaje"></textarea>
                <?php endif; ?>

                <!-- Honeypot anti-spam: oculto para humanos -->
                <div class="wab-hp" aria-hidden="true">
                    <label for="whatsapp-website">Website</label>
                    <input type="text" id="whatsapp-website" name="website" tabindex="-1" autocomplete="off" />
                </div>

                <button type="submit">Iniciar Chat</button>
            </form>
        </div>
    </div>
<?php
}
add_action('wp_footer', 'whatsapp_button_display');
