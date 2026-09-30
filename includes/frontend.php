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

// Campo email: activo por defecto
function whatsapp_button_email_enabled()
{
    return get_option('whatsapp_show_email_field', '1') === '1';
}

// Casilla de consentimiento de datos: activa por defecto
function whatsapp_button_consent_enabled()
{
    return get_option('whatsapp_consent_enabled', '1') === '1';
}

// URL de la política de privacidad: la configurada en el plugin o la de WordPress
function whatsapp_button_privacy_url()
{
    $url = get_option('whatsapp_privacy_url', '');
    return $url !== '' ? $url : get_privacy_policy_url();
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
    $form_title = get_option('whatsapp_form_title', WAB_DEFAULT_FORM_TITLE);
    $form_description = get_option('whatsapp_form_description', WAB_DEFAULT_FORM_DESCRIPTION);
    $dialog_label = $form_title !== '' ? 'aria-labelledby="whatsapp-popup-title"' : 'aria-label="Formulario de WhatsApp"';
?>
    <div class="whatsapp-container">
        <button type="button" class="whatsapp-button" aria-label="Abrir chat de WhatsApp" aria-expanded="false" aria-controls="whatsapp-popup">
            <img src="<?php echo esc_url(WAB_URL . 'whatsapp-icon.png'); ?>" alt="" />
        </button>
        <div class="whatsapp-popup" id="whatsapp-popup" role="dialog" <?php echo $dialog_label; ?> hidden>
            <button type="button" class="whatsapp-close" aria-label="Cerrar">&times;</button>
            <form id="whatsapp-form">
                <?php if ($form_title !== '') : ?>
                    <h3 id="whatsapp-popup-title"><?php echo esc_html($form_title); ?></h3>
                <?php endif; ?>
                <?php if ($form_description !== '') : ?>
                    <p><?php echo nl2br(esc_html($form_description)); ?></p>
                <?php endif; ?>

                <label for="whatsapp-name">Nombre:</label>
                <input type="text" id="whatsapp-name" name="name" required maxlength="100" autocomplete="name" placeholder="Tu nombre" />

                <?php if (whatsapp_button_email_enabled()) : ?>
                    <label for="whatsapp-email">Email:</label>
                    <input type="email" id="whatsapp-email" name="email" required autocomplete="email" placeholder="Tu email" />
                <?php endif; ?>

                <?php if (get_option('whatsapp_show_phone_field')) : ?>
                    <label for="whatsapp-phone">Teléfono (opcional):</label>
                    <input type="tel" id="whatsapp-phone" name="phone" maxlength="30" autocomplete="tel" placeholder="Tu teléfono" />
                <?php endif; ?>

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

                <?php if (whatsapp_button_consent_enabled()) : ?>
                    <label class="wab-consent" for="whatsapp-consent">
                        <input type="checkbox" id="whatsapp-consent" name="consent" value="1" required />
                        <span>
                            <?php echo esc_html(get_option('whatsapp_consent_text', WAB_DEFAULT_CONSENT_TEXT)); ?>
                            <?php $privacy_url = whatsapp_button_privacy_url(); ?>
                            <?php if ($privacy_url) : ?>
                                <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer">Ver política</a>
                            <?php endif; ?>
                        </span>
                    </label>
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
