<?php

if (! defined('ABSPATH')) {
    exit;
}

define('WAB_LEAD_POST_TYPE', 'wab_lead');

// Campos que se guardan de cada lead (clave => etiqueta)
function whatsapp_button_lead_fields()
{
    return [
        'name'         => 'Nombre',
        'email'        => 'Email',
        'phone'        => 'Teléfono',
        'message'      => 'Mensaje',
        'page_title'   => 'Página',
        'page_url'     => 'URL',
        'landing_page' => 'Página de entrada',
        'referrer'     => 'Referente',
        'utm_source'   => 'UTM source',
        'utm_medium'   => 'UTM medium',
        'utm_campaign' => 'UTM campaign',
        'utm_term'     => 'UTM term',
        'utm_content'  => 'UTM content',
        'gclid'        => 'gclid',
        'fbclid'       => 'fbclid',
        'msclkid'      => 'msclkid',
        'device'       => 'Dispositivo',
        'consent'      => 'Aceptó tratamiento de datos',
        'mail_sent'    => 'Correo enviado',
    ];
}

// Tipo de contenido privado: solo visible en el admin para administradores
function whatsapp_button_register_lead_post_type()
{
    register_post_type(WAB_LEAD_POST_TYPE, [
        'labels' => [
            'name'               => 'Leads WhatsApp',
            'singular_name'      => 'Lead WhatsApp',
            'menu_name'          => 'Leads WhatsApp',
            'all_items'          => 'Todos los leads',
            'edit_item'          => 'Detalle del lead',
            'search_items'       => 'Buscar leads',
            'not_found'          => 'No hay leads todavía.',
            'not_found_in_trash' => 'No hay leads en la papelera.',
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => false,
        'menu_icon'           => 'dashicons-whatsapp',
        'supports'            => false,
        'map_meta_cap'        => false,
        'capabilities'        => [
            'create_posts'           => 'do_not_allow',
            'edit_post'              => 'manage_options',
            'read_post'              => 'manage_options',
            'delete_post'            => 'manage_options',
            'edit_posts'             => 'manage_options',
            'edit_others_posts'      => 'manage_options',
            'edit_published_posts'   => 'manage_options',
            'publish_posts'          => 'manage_options',
            'read_private_posts'     => 'manage_options',
            'delete_posts'           => 'manage_options',
            'delete_others_posts'    => 'manage_options',
            'delete_published_posts' => 'manage_options',
        ],
    ]);
}
add_action('init', 'whatsapp_button_register_lead_post_type');

// Guardar un lead. Devuelve el ID del post o 0 si falla.
function whatsapp_button_save_lead(array $lead)
{
    $contact = $lead['email'] !== '' ? $lead['email'] : $lead['phone'];

    $post_id = wp_insert_post([
        'post_type'   => WAB_LEAD_POST_TYPE,
        'post_status' => 'publish',
        'post_title'  => $contact !== '' ? sprintf('%s <%s>', $lead['name'], $contact) : $lead['name'],
    ], true);

    if (is_wp_error($post_id)) {
        return 0;
    }

    foreach (array_keys(whatsapp_button_lead_fields()) as $key) {
        if (isset($lead[$key]) && $lead[$key] !== '') {
            update_post_meta($post_id, '_wab_' . $key, $lead[$key]);
        }
    }

    return $post_id;
}

function whatsapp_button_get_lead_meta($post_id, $key)
{
    return (string) get_post_meta($post_id, '_wab_' . $key, true);
}

// Resumen del origen: campaña UTM o dominio del referente
function whatsapp_button_lead_source_summary($post_id)
{
    $source = whatsapp_button_get_lead_meta($post_id, 'utm_source');
    if ($source !== '') {
        $medium = whatsapp_button_get_lead_meta($post_id, 'utm_medium');
        return $medium !== '' ? "$source / $medium" : $source;
    }
    if (whatsapp_button_get_lead_meta($post_id, 'gclid') !== '') {
        return 'Google Ads';
    }
    if (whatsapp_button_get_lead_meta($post_id, 'fbclid') !== '') {
        return 'Meta';
    }
    $referrer = whatsapp_button_get_lead_meta($post_id, 'referrer');
    if ($referrer !== '') {
        return (string) wp_parse_url($referrer, PHP_URL_HOST);
    }
    return 'Directo';
}

/* ---------- Listado en el admin ---------- */

function whatsapp_button_lead_columns()
{
    return [
        'cb'        => '<input type="checkbox" />',
        'title'     => 'Lead',
        'wab_phone' => 'Teléfono',
        'wab_msg'   => 'Mensaje',
        'wab_src'   => 'Origen',
        'wab_mail'  => 'Correo',
        'date'      => 'Fecha',
    ];
}
add_filter('manage_' . WAB_LEAD_POST_TYPE . '_posts_columns', 'whatsapp_button_lead_columns');

function whatsapp_button_lead_column_content($column, $post_id)
{
    switch ($column) {
        case 'wab_phone':
            echo esc_html(whatsapp_button_get_lead_meta($post_id, 'phone'));
            break;
        case 'wab_msg':
            echo esc_html(wp_trim_words(whatsapp_button_get_lead_meta($post_id, 'message'), 12));
            break;
        case 'wab_src':
            echo esc_html(whatsapp_button_lead_source_summary($post_id));
            break;
        case 'wab_mail':
            echo whatsapp_button_get_lead_meta($post_id, 'mail_sent') === '1'
                ? '<span style="color:#008a20;">Enviado</span>'
                : '<span style="color:#d63638;">Falló</span>';
            break;
    }
}
add_action('manage_' . WAB_LEAD_POST_TYPE . '_posts_custom_column', 'whatsapp_button_lead_column_content', 10, 2);

// Sin edición rápida: los leads son de solo lectura
function whatsapp_button_lead_row_actions($actions, $post)
{
    if ($post->post_type === WAB_LEAD_POST_TYPE) {
        unset($actions['inline hide-if-no-js']);
        if (isset($actions['edit'])) {
            $actions['edit'] = sprintf('<a href="%s">Ver detalle</a>', esc_url(get_edit_post_link($post->ID)));
        }
    }
    return $actions;
}
add_filter('post_row_actions', 'whatsapp_button_lead_row_actions', 10, 2);

// La búsqueda del listado también busca en los metadatos
function whatsapp_button_lead_search($search, $query)
{
    global $wpdb;

    if (! is_admin() || ! $query->is_main_query() || $query->get('post_type') !== WAB_LEAD_POST_TYPE || $query->get('s') === '') {
        return $search;
    }

    $like     = '%' . $wpdb->esc_like($query->get('s')) . '%';
    $meta_key = $wpdb->esc_like('_wab_') . '%';
    return $wpdb->prepare(
        " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value LIKE %s))",
        $like,
        $meta_key,
        $like
    );
}
add_filter('posts_search', 'whatsapp_button_lead_search', 10, 2);

/* ---------- Detalle del lead ---------- */

function whatsapp_button_lead_meta_boxes()
{
    add_meta_box('wab_lead_details', 'Datos del lead', 'whatsapp_button_lead_details_box', WAB_LEAD_POST_TYPE, 'normal', 'high');
}
add_action('add_meta_boxes', 'whatsapp_button_lead_meta_boxes');

function whatsapp_button_lead_details_box($post)
{
    $url_fields = ['page_url', 'landing_page', 'referrer'];

    echo '<table class="widefat striped"><tbody>';
    echo '<tr><th style="width:180px;">Fecha</th><td>' . esc_html(get_the_date('Y-m-d H:i', $post)) . '</td></tr>';

    foreach (whatsapp_button_lead_fields() as $key => $label) {
        $value = whatsapp_button_get_lead_meta($post->ID, $key);
        if ($key === 'mail_sent') {
            $value = $value === '1' ? 'Sí' : 'No';
        }
        if ($value === '') {
            continue;
        }

        echo '<tr><th>' . esc_html($label) . '</th><td>';
        if ($key === 'email') {
            echo '<a href="' . esc_url('mailto:' . $value) . '">' . esc_html($value) . '</a>';
        } elseif (in_array($key, $url_fields, true)) {
            echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($value) . '</a>';
        } else {
            echo nl2br(esc_html($value));
        }
        echo '</td></tr>';
    }

    echo '</tbody></table>';
}

/* ---------- Exportar CSV ---------- */

function whatsapp_button_lead_export_button($which)
{
    global $typenow;

    if ($typenow !== WAB_LEAD_POST_TYPE || $which !== 'top') {
        return;
    }

    $url = wp_nonce_url(admin_url('admin-post.php?action=wab_export_leads'), 'wab_export_leads');
    echo '<div class="alignleft actions"><a href="' . esc_url($url) . '" class="button">Exportar CSV</a></div>';
}
add_action('manage_posts_extra_tablenav', 'whatsapp_button_lead_export_button');

function whatsapp_button_export_leads()
{
    if (! current_user_can('manage_options')) {
        wp_die('No tienes permisos para exportar leads.', 403);
    }
    check_admin_referer('wab_export_leads');

    $fields = whatsapp_button_lead_fields();
    $ids    = get_posts([
        'post_type'      => WAB_LEAD_POST_TYPE,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="leads-whatsapp-' . wp_date('Y-m-d') . '.csv"');

    echo "\xEF\xBB\xBF"; // BOM para que Excel reconozca UTF-8

    // php://output escribe directo en la respuesta (no es un archivo en disco);
    // PHP cierra el flujo al terminar con exit.
    $out = fopen('php://output', 'w');
    fputcsv($out, array_merge(['Fecha'], array_values($fields)));

    foreach ($ids as $id) {
        $row = [get_the_date('Y-m-d H:i', $id)];
        foreach (array_keys($fields) as $key) {
            $value = whatsapp_button_get_lead_meta($id, $key);
            // Evita inyección de fórmulas al abrir el CSV en Excel
            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                $value = "'" . $value;
            }
            $row[] = $value;
        }
        fputcsv($out, $row);
    }

    exit;
}
add_action('admin_post_wab_export_leads', 'whatsapp_button_export_leads');
