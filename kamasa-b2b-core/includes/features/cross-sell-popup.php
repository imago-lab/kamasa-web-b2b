<?php
/**
 * Cross-sell popup for B2B users after adding products to the cart.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue assets required for the cross-sell modal on the frontend.
 *
 * @return void
 */
function kamasa_b2b_enqueue_cross_sell_popup_assets() {
    if ( is_admin() ) {
        return;
    }

    if ( ! function_exists( 'kamasa_es_usuario_b2b' ) ) {
        return;
    }

    $user_id = get_current_user_id();

    if ( ! $user_id || ! kamasa_es_usuario_b2b( $user_id ) ) {
        return;
    }

    wp_enqueue_style(
        'kamasa-cross-sell-popup',
        KAMASA_B2B_PLUGIN_URL . 'public/css/kamasa-cross-sell-popup.css',
        array(),
        KAMASA_B2B_VERSION
    );

    wp_enqueue_script(
        'kamasa-cross-sell-popup',
        KAMASA_B2B_PLUGIN_URL . 'public/js/kamasa-cross-sell-popup.js',
        array( 'jquery', 'wc-add-to-cart' ),
        KAMASA_B2B_VERSION,
        true
    );

    wp_localize_script(
        'kamasa-cross-sell-popup',
        'kamasaPopupData',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cross_sell_nonce' ),
            'i18n'     => array(
                'title' => __( 'Quizás también necesites:', 'kamasa-b2b-core' ),
                'close' => __( 'Cerrar', 'kamasa-b2b-core' ),
            ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'kamasa_b2b_enqueue_cross_sell_popup_assets' );

/**
 * AJAX handler to fetch cross-sell products for the popup.
 *
 * @return void
 */
function kamasa_get_cross_sells_ajax_handler() {
    check_ajax_referer( 'cross_sell_nonce' );

    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

    if ( ! $product_id ) {
        wp_send_json_error( __( 'Producto inválido.', 'kamasa-b2b-core' ) );
    }

    if ( ! function_exists( 'kamasa_es_usuario_b2b' ) || ! kamasa_es_usuario_b2b( get_current_user_id() ) ) {
        wp_send_json_error( __( 'Not authorized', 'kamasa-b2b-core' ) );
    }

    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        wp_send_json_error( __( 'Producto no encontrado.', 'kamasa-b2b-core' ) );
    }

    $cross_sell_ids = $product->get_cross_sell_ids();

    if ( empty( $cross_sell_ids ) ) {
        wp_send_json_success(
            array(
                'html' => '',
            )
        );
    }

    $items_html = array();

    foreach ( $cross_sell_ids as $cross_sell_id ) {
        $cross_sell_product = wc_get_product( $cross_sell_id );

        if ( ! $cross_sell_product || ! $cross_sell_product->is_purchasable() ) {
            continue;
        }

        $item_markup = kamasa_b2b_get_cross_sell_item_html( $cross_sell_product );

        if ( $item_markup ) {
            $items_html[] = $item_markup;
        }
    }

    if ( empty( $items_html ) ) {
        wp_send_json_success(
            array(
                'html' => '',
            )
        );
    }

    $html = '<div class="kamasa-cross-sell-products">' . implode( '', $items_html ) . '</div>';

    wp_send_json_success(
        array(
            'html' => $html,
        )
    );
}
add_action( 'wp_ajax_kamasa_get_cross_sells', 'kamasa_get_cross_sells_ajax_handler' );
add_action( 'wp_ajax_nopriv_kamasa_get_cross_sells', 'kamasa_get_cross_sells_ajax_handler' );

/**
 * Generate the HTML for a single cross-sell product within the popup.
 *
 * @param WC_Product $cross_sell_product Cross-sell product object.
 *
 * @return string
 */
function kamasa_b2b_get_cross_sell_item_html( $cross_sell_product ) {
    if ( ! $cross_sell_product instanceof WC_Product ) {
        return '';
    }

    $permalink = $cross_sell_product->get_permalink();
    $image     = $cross_sell_product->get_image( 'woocommerce_thumbnail' );
    $name      = $cross_sell_product->get_name();
    $price     = $cross_sell_product->get_price_html();

    $button_html = '';

    if ( function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
        global $product;

        $original_product = isset( $product ) ? $product : null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        $product          = $cross_sell_product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

        ob_start();
        woocommerce_template_loop_add_to_cart();
        $button_html = ob_get_clean();

        if ( null !== $original_product ) {
            $product = $original_product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        } else {
            unset( $product ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        }
    }

    if ( empty( $button_html ) ) {
        $button_html = sprintf(
            '<a href="%1$s" class="button add_to_cart_button">%2$s</a>',
            esc_url( $cross_sell_product->add_to_cart_url() ),
            esc_html( $cross_sell_product->add_to_cart_text() )
        );
    }

    $item_html  = '<div class="kamasa-cross-sell-item">';
    $item_html .= sprintf(
        '<a class="kamasa-cross-sell-thumb" href="%1$s">%2$s</a>',
        esc_url( $permalink ),
        wp_kses_post( $image )
    );
    $item_html .= '<div class="kamasa-cross-sell-info">';
    $item_html .= sprintf(
        '<a class="kamasa-cross-sell-name" href="%1$s">%2$s</a>',
        esc_url( $permalink ),
        esc_html( $name )
    );
    $item_html .= sprintf(
        '<div class="kamasa-cross-sell-price">%s</div>',
        wp_kses_post( $price )
    );
    $item_html .= sprintf(
        '<div class="kamasa-cross-sell-actions">%s</div>',
        wp_kses_post( $button_html )
    );
    $item_html .= '</div>';
    $item_html .= '</div>';

    return $item_html;
}
