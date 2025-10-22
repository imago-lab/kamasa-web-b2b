<?php
/**
 * Personalizaciones de la sección "Mi Cuenta" de WooCommerce.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra el endpoint personalizado para el panel financiero.
 *
 * @return void
 */
function kamasa_b2b_register_financial_panel_endpoint() {
    add_rewrite_endpoint( 'panel-financiero', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'kamasa_b2b_register_financial_panel_endpoint' );

/**
 * Añade el endpoint personalizado a la lista de query vars de WooCommerce.
 *
 * @param array $query_vars Query vars registradas por WooCommerce.
 *
 * @return array
 */
function kamasa_b2b_add_financial_panel_query_var( $query_vars ) {
    $query_vars['panel-financiero'] = 'panel-financiero';

    return $query_vars;
}
add_filter( 'woocommerce_get_query_vars', 'kamasa_b2b_add_financial_panel_query_var' );

/**
 * Inserta el ítem de menú "Panel Financiero" para usuarios B2B.
 *
 * @param array $items Ítems del menú "Mi Cuenta".
 *
 * @return array
 */
function kamasa_b2b_add_financial_panel_menu_item( $items ) {
    if ( ! is_user_logged_in() || ! function_exists( 'kamasa_es_usuario_b2b' ) ) {
        return $items;
    }

    $user_id = get_current_user_id();

    if ( ! kamasa_es_usuario_b2b( $user_id ) ) {
        if ( isset( $items['panel-financiero'] ) ) {
            unset( $items['panel-financiero'] );
        }

        return $items;
    }

    $new_items = array();

    foreach ( $items as $endpoint => $label ) {
        if ( 'orders' === $endpoint ) {
            $new_items['panel-financiero'] = __( 'Panel Financiero', 'kamasa-b2b-core' );
        }

        $new_items[ $endpoint ] = $label;
    }

    if ( ! isset( $new_items['panel-financiero'] ) ) {
        $new_items['panel-financiero'] = __( 'Panel Financiero', 'kamasa-b2b-core' );
    }

    return $new_items;
}
add_filter( 'woocommerce_account_menu_items', 'kamasa_b2b_add_financial_panel_menu_item', 25 );

/**
 * Renderiza el contenido del nuevo endpoint "panel-financiero".
 *
 * @return void
 */
function kamasa_b2b_render_financial_panel_content() {
    if ( ! is_user_logged_in() || ! function_exists( 'kamasa_es_usuario_b2b' ) ) {
        return;
    }

    $user_id = get_current_user_id();

    if ( ! kamasa_es_usuario_b2b( $user_id ) ) {
        wc_print_notice( __( 'No tienes acceso a este panel.', 'kamasa-b2b-core' ), 'error' );
        return;
    }

    wc_get_template( 'myaccount/kamasa-panel-financiero.php' );
}
add_action( 'woocommerce_account_panel-financiero_endpoint', 'kamasa_b2b_render_financial_panel_content' );

/**
 * Encola los scripts personalizados de "Mi Cuenta" únicamente cuando es necesario.
 *
 * @return void
 */
function kamasa_b2b_enqueue_my_account_assets() {
    if ( ! is_account_page() || ! is_user_logged_in() || ! function_exists( 'kamasa_es_usuario_b2b' ) ) {
        return;
    }

    $user_id = get_current_user_id();

    if ( ! kamasa_es_usuario_b2b( $user_id ) ) {
        return;
    }

    $handle = 'kamasa-my-account';

    wp_register_script(
        $handle,
        KAMASA_B2B_PLUGIN_URL . 'public/js/kamasa-my-account.js',
        array(),
        KAMASA_B2B_VERSION,
        true
    );

    $rest_root  = esc_url_raw( rest_url() );
    $rest_nonce = wp_create_nonce( 'wp_rest' );

    $inline_settings = 'window.wpApiSettings = window.wpApiSettings || {};'
        . 'window.wpApiSettings.root = ' . wp_json_encode( $rest_root ) . ';'
        . 'window.wpApiSettings.nonce = ' . wp_json_encode( $rest_nonce ) . ';';

    wp_add_inline_script( $handle, $inline_settings, 'before' );

    wp_localize_script(
        $handle,
        'kamasaMyAccountSettings',
        array(
            'namespace' => 'kamasa/v1',
        )
    );

    wp_enqueue_script( $handle );
}
add_action( 'wp_enqueue_scripts', 'kamasa_b2b_enqueue_my_account_assets' );
