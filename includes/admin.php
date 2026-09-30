<?php

if (! defined('ABSPATH')) {
    exit;
}

// Agregar opciones de configuración en el administrador
function whatsapp_button_settings_menu()
{
    add_options_page(
        'Configuración del Botón de WhatsApp',
        'WhatsApp Button',
        'manage_options',
        'whatsapp-button-settings',
        'whatsapp_button_settings_page'
    );
}
add_action('admin_menu', 'whatsapp_button_settings_menu');

function whatsapp_button_settings_page()
{
?>
    <div class="wrap">
        <h1>Configuración del Botón de WhatsApp</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('whatsapp-button-settings-group');
            do_settings_sections('whatsapp-button-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Número de WhatsApp</th>
                    <td>
                        <input type="text" name="whatsapp_phone_number" value="<?php echo esc_attr(get_option('whatsapp_phone_number')); ?>" placeholder="573001234567" />
                        <p>Con código de país. Se eliminan automáticamente espacios, guiones y el signo +.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Plantilla del Mensaje</th>
                    <td>
                        <textarea name="whatsapp_message_template" rows="4" style="width: 100%;"><?php echo esc_textarea(get_option('whatsapp_message_template', WAB_DEFAULT_TEMPLATE)); ?></textarea>
                        <p>Usa los marcadores: <code>{name}</code>, <code>{email}</code>, <code>{message}</code>.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Código de Seguimiento</th>
                    <td>
                        <textarea name="whatsapp_tracking_code" rows="6" style="width: 100%;"><?php echo esc_textarea(get_option('whatsapp_tracking_code', '')); ?></textarea>
                        <p>Pega aquí tu código de seguimiento de eventos (Google Ads, Analytics, etc.).</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Tipo de campo para el mensaje</th>
                    <td>
                        <select name="whatsapp_message_field_type">
                            <option value="text" <?php selected(get_option('whatsapp_message_field_type'), 'text'); ?>>Texto</option>
                            <option value="select" <?php selected(get_option('whatsapp_message_field_type'), 'select'); ?>>Select</option>
                        </select>
                        <p>Selecciona si el campo de mensaje será un campo de texto o un menú desplegable (select).</p>
                    </td>
                </tr>

                <tr valign="top" id="select-options-row" style="<?php echo (get_option('whatsapp_message_field_type') !== 'select') ? 'display:none;' : ''; ?>">
                    <th scope="row">Opciones del Select</th>
                    <td>
                        <textarea name="whatsapp_select_options" rows="4" style="width: 100%;"><?php echo esc_textarea(get_option('whatsapp_select_options', 'Opción 1, Opción 2, Opción 3')); ?></textarea>
                        <p>Escribe las opciones separadas por comas si el campo es un select.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Correo para recibir leads</th>
                    <td>
                        <input type="email" name="whatsapp_lead_email" value="<?php echo esc_attr(get_option('whatsapp_lead_email', get_option('admin_email'))); ?>" style="width: 100%;" />
                        <p>Este será el correo donde llegarán los leads incluso si el usuario no inicia el chat.</p>
                    </td>
                </tr>


            </table>
            <?php submit_button(); ?>
        </form>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fieldTypeSelect = document.querySelector('select[name="whatsapp_message_field_type"]');
                const selectOptionsRow = document.getElementById('select-options-row');

                if (fieldTypeSelect && selectOptionsRow) {
                    fieldTypeSelect.addEventListener('change', function() {
                        if (this.value === 'select') {
                            selectOptionsRow.style.display = 'table-row';
                        } else {
                            selectOptionsRow.style.display = 'none';
                        }
                    });
                }
            });
        </script>
    </div>
<?php
}


// Registrar las opciones
function whatsapp_button_register_settings()
{
    $group = 'whatsapp-button-settings-group';

    register_setting($group, 'whatsapp_phone_number', ['sanitize_callback' => 'whatsapp_button_sanitize_phone']);
    register_setting($group, 'whatsapp_lead_email', ['sanitize_callback' => 'whatsapp_button_sanitize_lead_email']);
    register_setting($group, 'whatsapp_message_template', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting($group, 'whatsapp_tracking_code');
    register_setting($group, 'whatsapp_message_field_type', ['sanitize_callback' => 'whatsapp_button_sanitize_field_type']);
    register_setting($group, 'whatsapp_select_options', ['sanitize_callback' => 'sanitize_textarea_field']);
}
add_action('admin_init', 'whatsapp_button_register_settings');

// wa.me solo acepta dígitos (código de país + número, sin "+", espacios ni guiones)
function whatsapp_button_sanitize_phone($value)
{
    return preg_replace('/\D/', '', (string) $value);
}

function whatsapp_button_sanitize_lead_email($value)
{
    $email = sanitize_email($value);
    if (trim((string) $value) !== '' && ! is_email($email)) {
        add_settings_error('whatsapp_lead_email', 'invalid_email', 'El correo para recibir leads no es válido.');
        return get_option('whatsapp_lead_email');
    }
    return $email;
}

function whatsapp_button_sanitize_field_type($value)
{
    return in_array($value, ['text', 'select'], true) ? $value : 'text';
}
