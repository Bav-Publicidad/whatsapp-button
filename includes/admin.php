<?php

if (! defined('ABSPATH')) {
    exit;
}

// Agregar opciones de configuración en el administrador
function leadivo_settings_menu()
{
    add_options_page(
        'Configuración de Leadivo',
        'Leadivo',
        'manage_options',
        'leadivo-settings',
        'leadivo_settings_page'
    );
}
add_action('admin_menu', 'leadivo_settings_menu');

// JS de la página de ajustes: muestra u oculta las opciones del select
function leadivo_admin_enqueue_assets($hook_suffix)
{
    if ($hook_suffix !== 'settings_page_leadivo-settings') {
        return;
    }
    wp_enqueue_script('leadivo-admin', LEADIVO_URL . 'assets/js/leadivo-admin.js', [], LEADIVO_VERSION, true);
}
add_action('admin_enqueue_scripts', 'leadivo_admin_enqueue_assets');

function leadivo_settings_page()
{
?>
    <div class="wrap">
        <h1>Configuración de Leadivo</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('leadivo_settings');
            do_settings_sections('leadivo_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Número de WhatsApp</th>
                    <td>
                        <input type="text" name="leadivo_phone_number" value="<?php echo esc_attr(get_option('leadivo_phone_number')); ?>" placeholder="573001234567" />
                        <p>Con código de país. Se eliminan automáticamente espacios, guiones y el signo +.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Título del formulario</th>
                    <td>
                        <input type="text" name="leadivo_form_title" value="<?php echo esc_attr(get_option('leadivo_form_title', LEADIVO_DEFAULT_FORM_TITLE)); ?>" style="width: 100%;" />
                        <p>Se muestra en negrita en la burbuja de bienvenida del chat. Déjalo vacío para no mostrar título.</p>
                        <p>La cabecera del chat usa el nombre del sitio y el icono del sitio (<em>Apariencia → Personalizar → Identidad del sitio</em>); si no hay icono, se usa el de WhatsApp.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Descripción del formulario</th>
                    <td>
                        <textarea name="leadivo_form_description" rows="2" style="width: 100%;"><?php echo esc_textarea(get_option('leadivo_form_description', LEADIVO_DEFAULT_FORM_DESCRIPTION)); ?></textarea>
                        <p>Texto de la burbuja de bienvenida, bajo el título. Déjalo vacío para no mostrar descripción.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Plantilla del Mensaje</th>
                    <td>
                        <?php $template = get_option('leadivo_message_template', LEADIVO_DEFAULT_TEMPLATE); ?>
                        <textarea name="leadivo_message_template" rows="4" style="width: 100%;"><?php echo esc_textarea($template); ?></textarea>
                        <p>Usa los marcadores: <code>{name}</code>, <code>{message}</code> (si está activo el campo mensaje), <code>{email}</code> (si está activo el campo email) y <code>{phone}</code> (si está activo el campo teléfono).</p>
                        <?php if (! leadivo_email_enabled() && strpos($template, '{email}') !== false) : ?>
                            <p style="color: #d63638;">⚠ El campo email está desactivado pero la plantilla usa <code>{email}</code>: quedará vacío en el mensaje de WhatsApp.</p>
                        <?php endif; ?>
                        <?php if (! leadivo_message_enabled() && strpos($template, '{message}') !== false) : ?>
                            <p style="color: #d63638;">⚠ El campo mensaje está desactivado pero la plantilla usa <code>{message}</code>: quedará vacío en el mensaje de WhatsApp.</p>
                        <?php endif; ?>
                        <?php if (! get_option('leadivo_show_phone_field') && strpos($template, '{phone}') !== false) : ?>
                            <p style="color: #d63638;">⚠ El campo teléfono está desactivado pero la plantilla usa <code>{phone}</code>: quedará vacío en el mensaje de WhatsApp.</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Campos del formulario</th>
                    <td>
                        <p><em>El nombre siempre se pide.</em></p>
                        <label style="display: block; margin: 8px 0;">
                            <input type="checkbox" name="leadivo_show_message_field" value="1" <?php checked(leadivo_message_enabled()); ?> />
                            Pedir mensaje (obligatorio; texto o select según "Tipo de campo para el mensaje")
                        </label>
                        <label style="display: block; margin: 8px 0;">
                            <input type="checkbox" name="leadivo_show_email_field" value="1" <?php checked(leadivo_email_enabled()); ?> />
                            Pedir email (obligatorio)
                        </label>
                        <label style="display: block; margin: 8px 0;">
                            <input type="checkbox" name="leadivo_show_phone_field" value="1" <?php checked(get_option('leadivo_show_phone_field'), '1'); ?> />
                            Pedir teléfono (opcional)
                        </label>
                        <p>Si no pides el email, el correo de aviso no tendrá "Responder a" y tendrás que contactar al lead por WhatsApp o por teléfono.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Medición con Google Tag Manager</th>
                    <td>
                        <p>Cada lead válido envía al <code>dataLayer</code> el evento <code>whatsapp_lead</code> con:</p>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <li><code>lead_source</code>: siempre <code>whatsapp_button</code></li>
                            <li><code>lead_topic</code>: la opción elegida (solo si el mensaje es un select)</li>
                            <li><code>page_location</code>: URL de la página</li>
                        </ul>
                        <p>En GTM crea un activador de tipo <strong>Evento personalizado</strong> con el nombre <code>whatsapp_lead</code> y úsalo en tus etiquetas de GA4, Google Ads, Meta, etc.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Código de Seguimiento (opcional)</th>
                    <td>
                        <textarea name="leadivo_tracking_code" rows="6" style="width: 100%; font-family: monospace;" placeholder="gtag('event', 'conversion', { send_to: 'AW-XXXXXXX/XXXXXXX' });"><?php echo esc_textarea(get_option('leadivo_tracking_code', '')); ?></textarea>
                        <p><strong>Si usas Google Tag Manager, déjalo vacío</strong> y usa el evento <code>whatsapp_lead</code>.</p>
                        <p>Para sitios sin GTM: JavaScript que se ejecuta con cada lead válido (las etiquetas <code>&lt;script&gt;</code> se quitan automáticamente). Los datos del evento están disponibles en la variable <code>data</code>.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Tipo de campo para el mensaje</th>
                    <td>
                        <select name="leadivo_message_field_type">
                            <option value="text" <?php selected(get_option('leadivo_message_field_type'), 'text'); ?>>Texto</option>
                            <option value="select" <?php selected(get_option('leadivo_message_field_type'), 'select'); ?>>Select</option>
                        </select>
                        <p>Selecciona si el campo de mensaje será un campo de texto o un menú desplegable (select).</p>
                    </td>
                </tr>

                <tr valign="top" id="leadivo-select-options-row" style="<?php echo (get_option('leadivo_message_field_type') !== 'select') ? 'display:none;' : ''; ?>">
                    <th scope="row">Opciones del Select</th>
                    <td>
                        <textarea name="leadivo_select_options" rows="4" style="width: 100%;"><?php echo esc_textarea(get_option('leadivo_select_options', 'Opción 1, Opción 2, Opción 3')); ?></textarea>
                        <p>Escribe las opciones separadas por comas si el campo es un select.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Consentimiento de datos</th>
                    <td>
                        <label>
                            <input type="checkbox" name="leadivo_consent_enabled" value="1" <?php checked(leadivo_consent_enabled()); ?> />
                            Pedir aceptación del tratamiento de datos antes de enviar (recomendado)
                        </label>
                        <p style="margin-top: 10px;">Texto de la casilla:</p>
                        <input type="text" name="leadivo_consent_text" value="<?php echo esc_attr(get_option('leadivo_consent_text', LEADIVO_DEFAULT_CONSENT_TEXT)); ?>" style="width: 100%;" />
                        <p style="margin-top: 10px;">URL de la política de privacidad:</p>
                        <input type="url" name="leadivo_privacy_url" value="<?php echo esc_attr(get_option('leadivo_privacy_url', '')); ?>" placeholder="<?php echo esc_attr(get_privacy_policy_url()); ?>" style="width: 100%;" />
                        <p>Si se deja vacío se usa la página de privacidad configurada en <em>Ajustes → Privacidad</em><?php echo get_privacy_policy_url() ? '' : ' (actualmente no hay ninguna, así que no se mostrará enlace)'; ?>.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Correo para recibir leads</th>
                    <td>
                        <input type="email" name="leadivo_lead_email" value="<?php echo esc_attr(get_option('leadivo_lead_email', get_option('admin_email'))); ?>" style="width: 100%;" />
                        <p>Este será el correo donde llegarán los leads incluso si el usuario no inicia el chat.</p>
                        <p>Todos los leads se guardan además en <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . LEADIVO_LEAD_POST_TYPE)); ?>">Leadivo Leads</a>, aunque falle el envío del correo.</p>
                    </td>
                </tr>


            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}


// Registrar las opciones
function leadivo_register_settings()
{
    $group = 'leadivo_settings';

    register_setting($group, 'leadivo_phone_number', ['sanitize_callback' => 'leadivo_sanitize_phone']);
    register_setting($group, 'leadivo_lead_email', ['sanitize_callback' => 'leadivo_sanitize_lead_email']);
    register_setting($group, 'leadivo_message_template', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting($group, 'leadivo_tracking_code', ['sanitize_callback' => 'leadivo_sanitize_tracking_code']);
    register_setting($group, 'leadivo_message_field_type', ['sanitize_callback' => 'leadivo_sanitize_field_type']);
    register_setting($group, 'leadivo_select_options', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting($group, 'leadivo_show_phone_field', ['sanitize_callback' => 'leadivo_sanitize_checkbox']);
    register_setting($group, 'leadivo_show_message_field', ['sanitize_callback' => 'leadivo_sanitize_checkbox']);
    register_setting($group, 'leadivo_show_email_field', ['sanitize_callback' => 'leadivo_sanitize_checkbox']);
    register_setting($group, 'leadivo_form_title', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting($group, 'leadivo_form_description', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting($group, 'leadivo_consent_enabled', ['sanitize_callback' => 'leadivo_sanitize_checkbox']);
    register_setting($group, 'leadivo_consent_text', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting($group, 'leadivo_privacy_url', ['sanitize_callback' => 'esc_url_raw']);
}
add_action('admin_init', 'leadivo_register_settings');

// wa.me solo acepta dígitos (código de país + número, sin "+", espacios ni guiones)
function leadivo_sanitize_phone($value)
{
    return preg_replace('/\D/', '', (string) $value);
}

function leadivo_sanitize_lead_email($value)
{
    $email = sanitize_email($value);
    if (trim((string) $value) !== '' && ! is_email($email)) {
        add_settings_error('leadivo_lead_email', 'invalid_email', 'El correo para recibir leads no es válido.');
        return get_option('leadivo_lead_email');
    }
    return $email;
}

// El código se ejecuta tal cual en el sitio: solo quien puede publicar HTML/JS sin filtrar
// (administradores; en multisite, solo el superadmin) puede modificarlo.
function leadivo_sanitize_tracking_code($value)
{
    if (! current_user_can('unfiltered_html')) {
        return get_option('leadivo_tracking_code', '');
    }
    return leadivo_strip_script_tags($value);
}

function leadivo_sanitize_checkbox($value)
{
    return $value ? '1' : '';
}

function leadivo_sanitize_field_type($value)
{
    return in_array($value, ['text', 'select'], true) ? $value : 'text';
}
