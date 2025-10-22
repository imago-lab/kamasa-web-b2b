<?php
/**
 * Template del botón en el loop, mantiene el filtro para personalizar el HTML.
 *
 * Copiar este archivo en kamasa-b2b-theme/woocommerce/loop/add-to-cart.php.
 *
 * @package Kamasa_B2B_Theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) ) {
    return;
}

$defaults = array(
    'quantity'   => 1,
    'class'      => 'button',
    'attributes' => array(),
);

$args = isset( $args ) ? wp_parse_args( $args, $defaults ) : $defaults;

echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    'woocommerce_loop_add_to_cart_link',
    sprintf(
        '<a href="%1$s" data-quantity="%2$s" class="%3$s" %4$s>%5$s</a>',
        esc_url( $product->add_to_cart_url() ),
        esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
        esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
        isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
        esc_html( $product->add_to_cart_text() )
    ),
    $product,
    $args
);
