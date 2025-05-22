<?php

/**
 * Plugin Name: WhatsApp Button Plugin
 * Description: Muestra un botón de WhatsApp con formulario emergente y permite editar el mensaje predeterminado desde el administrador.
 * Version: 1.0.0
 * Author: BAV IT | BAV Publicidad
 * Author URI: https://bavpublicidad.com/bavit
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
    exit;
}

// Mostrar el botón de WhatsApp
// Mostrar el botón de WhatsApp
function whatsapp_button_display()
{
    $phone_number = get_option('whatsapp_phone_number', '');
    if (! empty($phone_number)) {
        // Obtener tipo de campo y opciones
        $message_field_type = get_option('whatsapp_message_field_type', 'text');
        $select_options_raw = get_option('whatsapp_select_options', '');
        $select_options = array_map('trim', explode(',', $select_options_raw));

        echo '<div class="whatsapp-container">
        <a href="javascript:void(0)" class="whatsapp-button">
            <img src="' . plugin_dir_url(__FILE__) . 'whatsapp-icon.png" alt="WhatsApp" />
        </a>
        <div class="whatsapp-popup" style="display: none;">
        <form id="whatsapp-form">
        <h3>¡Hola! ¿Cómo podemos ayudarte?</h3>
        <p>Por favor, completa la información para iniciar la conversación:</p>

        <label for="whatsapp-name">Nombre:</label>
        <input type="text" id="whatsapp-name" name="name" required placeholder="Tu nombre" />

        <label for="whatsapp-email">Email:</label>
        <input type="email" id="whatsapp-email" name="email" required placeholder="Tu email" />';

        // Campo de mensaje dinámico
        echo '<label for="whatsapp-message">Mensaje:</label>';
        if ($message_field_type === 'select') {
            echo '<select id="whatsapp-message" name="message" required>';
            foreach ($select_options as $option) {
                echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<textarea id="whatsapp-message" name="message" required placeholder="Escribe tu mensaje"></textarea>';
        }

        // Continuar con el formulario
        echo '<button type="submit">Iniciar Chat</button>
        </form>
        </div>
        </div>';
    }
}
add_action('wp_footer', 'whatsapp_button_display');


// Estilos Mejorados
function whatsapp_button_styles()
{
    echo '<style>
    .whatsapp-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }
    .whatsapp-button img {
        width: 80px;
        height: 80px;
        cursor: pointer;
    }
    .whatsapp-popup {
        position: fixed;
        bottom: 80px;
        right: 20px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        padding: 20px;
        width: 300px;
        z-index: 1001;
    }
    .whatsapp-popup h3 {
        margin: 0 0 10px;
        font-size: 18px;
        color: #333;
        text-align: center;
    }
    .whatsapp-popup p {
        margin: 0 0 15px;
        font-size: 14px;
        color: #555;
        text-align: center;
    }
    .whatsapp-popup label {
        display: block;
        margin: 10px 0 5px;
        font-size: 14px;
        color: #555;
    }
    .whatsapp-popup input,
    .whatsapp-popup textarea {
        width: calc(100% - 20px); /* Asegura que los bordes no se salgan */
        padding: 10px;
        margin: 0 auto 10px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        display: block;
        box-sizing: border-box; /* Evita que el padding afecte el ancho */
    }
    .whatsapp-popup textarea {
        resize: none;
        height: 80px;
    }
    .whatsapp-popup button {
        width: calc(100% - 20px); /* Ajuste del ancho */
        padding: 10px;
        background-color: #25D366;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        display: block;
        margin: 0 auto;
    }
    .whatsapp-popup button:hover {
        background-color: #1EBE58;
    }

    .whatsapp-popup select {
    width: calc(100% - 20px);
    padding: 10px;
    margin: 0 auto 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    display: block;
    box-sizing: border-box;
    }
    </style>';
}
add_action('wp_head', 'whatsapp_button_styles');

// Script con funcionalidad mejorada
function whatsapp_button_script()
{

    $tracking_code = get_option('whatsapp_tracking_code', '');

?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const whatsappButton = document.querySelector(".whatsapp-button");
            const whatsappPopup = document.querySelector(".whatsapp-popup");
            const whatsappForm = document.getElementById("whatsapp-form");
            const phoneNumber = "<?php echo get_option('whatsapp_phone_number', ''); ?>";
            const customMessageTemplate = "<?php echo get_option('whatsapp_message_template', 'Hola, soy {name} y mi email es {email}.  {message}'); ?>";

            if (!phoneNumber) {
                console.error("Número de WhatsApp no configurado.");
                return;
            }

            whatsappButton.addEventListener("click", function() {
                if (whatsappPopup.style.display === "none") {
                    whatsappPopup.style.display = "block";
                } else {
                    whatsappPopup.style.display = "none";
                }
            });

            whatsappForm.addEventListener("submit", function(e) {
                e.preventDefault();

                const name = document.getElementById("whatsapp-name").value.trim();
                const email = document.getElementById("whatsapp-email").value.trim();
                const message = document.getElementById("whatsapp-message").value.trim();

                if (!name || !email || !message) {
                    alert("Por favor, completa todos los campos.");
                    return;
                }

                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    alert("Por favor, ingresa un correo electrónico válido.");
                    return;
                }

                const fullMessage = customMessageTemplate
                    .replace("{name}", name)
                    .replace("{email}", email)
                    .replace("{message}", message);

                const whatsappURL = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(fullMessage)}`;


                // Código de seguimiento
                <?php if (! empty($tracking_code)) : ?>
                        (function() {
                            <?php echo $tracking_code; ?>
                        })();
                <?php endif; ?>


                window.open(whatsappURL, "_blank");
            });
        });
    </script>
<?php
}
add_action('wp_footer', 'whatsapp_button_script');

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
                        <input type="text" name="whatsapp_phone_number" value="<?php echo esc_attr(get_option('whatsapp_phone_number')); ?>" placeholder="1234567890" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Plantilla del Mensaje</th>
                    <td>
                        <textarea name="whatsapp_message_template" rows="4" style="width: 100%;"><?php echo esc_textarea(get_option('whatsapp_message_template', 'Hola, soy {name} y mi email es {email}. {message}')); ?></textarea>
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
    register_setting('whatsapp-button-settings-group', 'whatsapp_phone_number');
    register_setting('whatsapp-button-settings-group', 'whatsapp_message_template');
    register_setting('whatsapp-button-settings-group', 'whatsapp_tracking_code');
    register_setting('whatsapp-button-settings-group', 'whatsapp_message_field_type');
    register_setting('whatsapp-button-settings-group', 'whatsapp_select_options');
}
add_action('admin_init', 'whatsapp_button_register_settings');
