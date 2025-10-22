<?php
/**
 * Registro del Custom Post Type para almacenar conversaciones con la IA.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra el Custom Post Type "Conversación IA".
 *
 * Estructura recomendada de metadatos:
 * - respuesta_ia (texto largo con la respuesta del asistente)
 * - productos_recomendados (array serializado con IDs o URLs)
 * - user_id_wp (ID del usuario autenticado en WordPress)
 * - session_id_visitante (identificador para visitantes no autenticados)
 *
 * @return void
 */
function kamasa_register_conversacion_ia_cpt() {
    $labels = array(
        'name'                  => __( 'Conversaciones IA', 'kamasa-b2b-core' ),
        'singular_name'         => __( 'Conversación IA', 'kamasa-b2b-core' ),
        'menu_name'             => __( 'Conversaciones IA', 'kamasa-b2b-core' ),
        'name_admin_bar'        => __( 'Conversación IA', 'kamasa-b2b-core' ),
        'add_new'               => __( 'Añadir nueva', 'kamasa-b2b-core' ),
        'add_new_item'          => __( 'Añadir nueva conversación IA', 'kamasa-b2b-core' ),
        'edit_item'             => __( 'Editar conversación IA', 'kamasa-b2b-core' ),
        'new_item'              => __( 'Nueva conversación IA', 'kamasa-b2b-core' ),
        'view_item'             => __( 'Ver conversación IA', 'kamasa-b2b-core' ),
        'search_items'          => __( 'Buscar conversaciones IA', 'kamasa-b2b-core' ),
        'not_found'             => __( 'No se encontraron conversaciones IA.', 'kamasa-b2b-core' ),
        'not_found_in_trash'    => __( 'No se encontraron conversaciones IA en la papelera.', 'kamasa-b2b-core' ),
        'all_items'             => __( 'Todas las conversaciones IA', 'kamasa-b2b-core' ),
        'archives'              => __( 'Archivos de conversaciones IA', 'kamasa-b2b-core' ),
        'attributes'            => __( 'Atributos de la conversación IA', 'kamasa-b2b-core' ),
        'insert_into_item'      => __( 'Insertar en conversación IA', 'kamasa-b2b-core' ),
        'uploaded_to_this_item' => __( 'Subido a esta conversación IA', 'kamasa-b2b-core' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'supports'           => array( 'title', 'editor', 'custom-fields', 'author' ),
        'menu_icon'          => 'dashicons-format-chat',
        'has_archive'        => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    );

    register_post_type( 'kamasa_conversacion_ia', $args );
}
