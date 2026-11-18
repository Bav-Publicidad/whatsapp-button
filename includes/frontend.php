<?php

if (! defined('ABSPATH')) {
    exit;
}

// Opciones del select como array, sin entradas vacías
function leadivo_get_select_options()
{
    $raw = get_option('leadivo_select_options', '');
    return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
}

// Campo email: activo por defecto
function leadivo_email_enabled()
{
    return get_option('leadivo_show_email_field', '1') === '1';
}

// Campo mensaje: activo por defecto
function leadivo_message_enabled()
{
    return get_option('leadivo_show_message_field', '1') === '1';
}

// Casilla de consentimiento de datos: activa por defecto
function leadivo_consent_enabled()
{
    return get_option('leadivo_consent_enabled', '1') === '1';
}

// URL de la política de privacidad: la configurada en el plugin o la de WordPress
function leadivo_privacy_url()
{
    $url = get_option('leadivo_privacy_url', '');
    return $url !== '' ? $url : get_privacy_policy_url();
}

// Quita las etiquetas <script> que suelen venir en los snippets de Google Ads, Meta, etc.
// Dentro de un script en línea, un "</script>" cerraría el bloque y rompería el JS.
function leadivo_strip_script_tags($code)
{
    return trim(preg_replace('#</?script\b[^>]*>#i', '', (string) $code));
}

// Encolar estilos y scripts solo si hay un número configurado
function leadivo_enqueue_assets()
{
    if (empty(get_option('leadivo_phone_number', ''))) {
        return;
    }

    wp_enqueue_style('leadivo-button', LEADIVO_URL . 'assets/css/leadivo-button.css', [], LEADIVO_VERSION);
    wp_enqueue_script('leadivo-button', LEADIVO_URL . 'assets/js/leadivo-button.js', [], LEADIVO_VERSION, true);

    // Configuración para el JS (wp_json_encode evita romper el script con comillas o saltos de línea)
    $config = [
        'phoneNumber'     => get_option('leadivo_phone_number', ''),
        'messageTemplate' => get_option('leadivo_message_template', LEADIVO_DEFAULT_TEMPLATE),
        'fieldType'       => leadivo_message_enabled() ? get_option('leadivo_message_field_type', 'text') : 'none',
        'ajaxUrl'         => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('leadivo_submit_lead'),
    ];
    wp_add_inline_script('leadivo-button', 'window.leadivoConfig = ' . wp_json_encode($config) . ';', 'before');

    // Código de seguimiento personalizado, aislado en una función con try/catch
    // para que un error en él no impida enviar el lead ni abrir WhatsApp
    // (recibe los datos del evento como `data`). Se quitan etiquetas <script>
    // por si se guardaron antes de existir la sanitización.
    $tracking_code = leadivo_strip_script_tags(get_option('leadivo_tracking_code', ''));
    if (! empty($tracking_code)) {
        wp_add_inline_script(
            'leadivo-button',
            "window.leadivoTrack = function(data) {\ntry {\n" . $tracking_code . "\n} catch (e) {\nconsole.error('Error en el código de seguimiento de WhatsApp:', e);\n}\n};",
            'before'
        );
    }
}
add_action('wp_enqueue_scripts', 'leadivo_enqueue_assets');

// Mostrar el botón de WhatsApp
function leadivo_display()
{
    $phone_number = get_option('leadivo_phone_number', '');
    if (empty($phone_number)) {
        return;
    }

    // Obtener tipo de campo y opciones
    $message_field_type = get_option('leadivo_message_field_type', 'text');
    $select_options = leadivo_get_select_options();
    $form_title = get_option('leadivo_form_title', LEADIVO_DEFAULT_FORM_TITLE);
    $form_description = get_option('leadivo_form_description', LEADIVO_DEFAULT_FORM_DESCRIPTION);
    // Cabecera tipo chat de WhatsApp: icono del sitio (o el de WhatsApp) y nombre del sitio
    $site_name = get_bloginfo('name');
    $avatar_url = get_site_icon_url(96);
    if (! $avatar_url) {
        $avatar_url = LEADIVO_URL . 'whatsapp-icon.png';
    }
?>
    <div class="leadivo-container">
        <button type="button" class="leadivo-button" aria-label="Abrir chat de WhatsApp" aria-expanded="false" aria-controls="leadivo-popup">
            <img src="<?php echo esc_url(LEADIVO_URL . 'whatsapp-icon.png'); ?>" alt="" />
        </button>
        <div class="leadivo-popup" id="leadivo-popup" role="dialog" aria-labelledby="leadivo-popup-name" hidden>
            <div class="leadivo-header">
                <img class="leadivo-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="" />
                <div class="leadivo-header-text">
                    <span class="leadivo-header-name" id="leadivo-popup-name"><?php echo esc_html($site_name); ?></span>
                    <span class="leadivo-header-status">WhatsApp</span>
                </div>
                <button type="button" class="leadivo-close" aria-label="Cerrar">&times;</button>
            </div>
            <form id="leadivo-form" class="leadivo-body">
                <?php if ($form_title !== '' || $form_description !== '') : ?>
                    <div class="leadivo-bubble">
                        <?php if ($form_title !== '') : ?>
                            <span class="leadivo-bubble-title"><?php echo esc_html($form_title); ?></span>
                        <?php endif; ?>
                        <?php if ($form_description !== '') : ?>
                            <span class="leadivo-bubble-text"><?php echo nl2br(esc_html($form_description)); ?></span>
                        <?php endif; ?>
                        <span class="leadivo-bubble-time" aria-hidden="true"></span>
                    </div>
                <?php endif; ?>

                <div class="leadivo-fields">
                    <label for="leadivo-name">Nombre</label>
                    <input type="text" id="leadivo-name" name="name" required maxlength="100" autocomplete="name" placeholder="Tu nombre" />

                    <?php if (leadivo_email_enabled()) : ?>
                        <label for="leadivo-email">Email</label>
                        <input type="email" id="leadivo-email" name="email" required autocomplete="email" placeholder="Tu email" />
                    <?php endif; ?>

                    <?php if (get_option('leadivo_show_phone_field')) : ?>
                        <label for="leadivo-phone">Teléfono <span class="leadivo-optional">(opcional)</span></label>
                        <input type="tel" id="leadivo-phone" name="phone" maxlength="30" autocomplete="tel" placeholder="Tu teléfono" />
                    <?php endif; ?>

                    <?php if (leadivo_message_enabled()) : ?>
                        <label for="leadivo-message">Mensaje</label>
                        <?php if ($message_field_type === 'select') : ?>
                            <select id="leadivo-message" name="message" required>
                                <option value="" disabled selected>Selecciona una opción</option>
                                <?php foreach ($select_options as $option) : ?>
                                    <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <textarea id="leadivo-message" name="message" required maxlength="1000" placeholder="Escribe tu mensaje"></textarea>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (leadivo_consent_enabled()) : ?>
                        <label class="leadivo-consent" for="leadivo-consent">
                            <input type="checkbox" id="leadivo-consent" name="consent" value="1" required />
                            <span>
                                <?php echo esc_html(get_option('leadivo_consent_text', LEADIVO_DEFAULT_CONSENT_TEXT)); ?>
                                <?php $privacy_url = leadivo_privacy_url(); ?>
                                <?php if ($privacy_url) : ?>
                                    <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer">Ver política</a>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endif; ?>
                </div>

                <!-- Honeypot anti-spam: oculto para humanos -->
                <div class="leadivo-hp" aria-hidden="true">
                    <label for="leadivo-website">Website</label>
                    <input type="text" id="leadivo-website" name="website" tabindex="-1" autocomplete="off" />
                </div>

                <button type="submit" class="leadivo-submit">
                    <span>Iniciar chat</span>
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/></svg>
                </button>
            </form>
        </div>
    </div>
<?php
}
add_action('wp_footer', 'leadivo_display');
