<?php
/**
 * Template para mostrar el precio de producto con restricción B2B.
 *
 * Copiar este archivo en kamasa-b2b-theme/woocommerce/single-product/price.php.
 *
 * @package Kamasa_B2B_Theme
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
    return;
}

$usuario_autorizado = is_user_logged_in() && kamasa_es_usuario_b2b( get_current_user_id() );

if ( ! $usuario_autorizado ) :
    $login_url = wp_login_url( get_permalink( $product->get_id() ) );
    ?>
    <div class="kamasa-price-restricted">
        <p class="kamasa-price-restricted__message"><?php esc_html_e( 'Inicia sesión para ver precios personalizados.', 'kamasa-b2b-core' ); ?></p>
        <a class="kamasa-price-restricted__link" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Ir a iniciar sesión', 'kamasa-b2b-core' ); ?></a>
    </div>
<?php else : ?>
    <p class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
<?php endif; ?>
