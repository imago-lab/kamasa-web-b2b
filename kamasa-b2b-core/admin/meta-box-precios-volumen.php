<?php
/**
 * Campos repetibles para gestionar precios por volumen en productos de WooCommerce.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Imprime el bloque de "Precios por Volumen" dentro de la pestaña General.
 *
 * @return void
 */
function kamasa_b2b_render_precios_volumen_fields() {
    global $post;

    if ( ! $post || 'product' !== $post->post_type ) {
        return;
    }

    $rangos = get_post_meta( $post->ID, 'precios_volumen', true );

    if ( ! is_array( $rangos ) ) {
        $rangos = array();
    }

    $rangos_para_mostrar = $rangos;

    if ( empty( $rangos_para_mostrar ) ) {
        $rangos_para_mostrar[] = array(
            'min'       => '',
            'max'       => '',
            'descuento' => '',
        );
    }

    wp_nonce_field( 'kamasa_precios_volumen_guardar', 'kamasa_precios_volumen_nonce' );
    ?>
    <div class="options_group kamasa-precios-volumen">
        <h4><?php esc_html_e( 'Precios por Volumen', 'kamasa-b2b-core' ); ?></h4>
        <p><?php esc_html_e( 'Define descuentos porcentuales según la cantidad adquirida.', 'kamasa-b2b-core' ); ?></p>
        <table class="widefat kamasa-precios-volumen__tabla" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Cantidad mínima', 'kamasa-b2b-core' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Cantidad máxima', 'kamasa-b2b-core' ); ?></th>
                    <th scope="col"><?php esc_html_e( '% Descuento', 'kamasa-b2b-core' ); ?></th>
                    <th scope="col" class="column-actions">&nbsp;</th>
                </tr>
            </thead>
            <tbody id="kamasa-precios-volumen-rows">
                <?php foreach ( $rangos_para_mostrar as $rango ) :
                    $min       = isset( $rango['min'] ) ? $rango['min'] : '';
                    $max       = array_key_exists( 'max', $rango ) ? $rango['max'] : '';
                    $descuento = isset( $rango['descuento'] ) ? $rango['descuento'] : '';
                    ?>
                    <tr class="kamasa-precios-volumen__row">
                        <td>
                            <input type="number" class="short" name="rango_min[]" min="0" value="<?php echo esc_attr( $min ); ?>" />
                        </td>
                        <td>
                            <input type="number" class="short" name="rango_max[]" min="0" placeholder="<?php esc_attr_e( 'Max (vacío = ilimitado)', 'kamasa-b2b-core' ); ?>" value="<?php echo ( '' === $max || null === $max ) ? '' : esc_attr( $max ); ?>" />
                        </td>
                        <td>
                            <input type="number" class="short" name="rango_descuento[]" step="0.01" min="0" value="<?php echo esc_attr( $descuento ); ?>" />
                        </td>
                        <td class="column-actions">
                            <button type="button" class="button button-link-delete kamasa-remove-range"><?php esc_html_e( 'Eliminar', 'kamasa-b2b-core' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <button type="button" class="button kamasa-add-range">+ <?php esc_html_e( 'Agregar Rango', 'kamasa-b2b-core' ); ?></button>
        </p>
    </div>

    <table style="display:none;">
        <tbody id="kamasa-precios-volumen-row-template">
            <tr class="kamasa-precios-volumen__row">
                <td>
                    <input type="number" class="short" name="rango_min[]" min="0" value="" />
                </td>
                <td>
                    <input type="number" class="short" name="rango_max[]" min="0" placeholder="<?php esc_attr_e( 'Max (vacío = ilimitado)', 'kamasa-b2b-core' ); ?>" value="" />
                </td>
                <td>
                    <input type="number" class="short" name="rango_descuento[]" step="0.01" min="0" value="" />
                </td>
                <td class="column-actions">
                    <button type="button" class="button button-link-delete kamasa-remove-range"><?php esc_html_e( 'Eliminar', 'kamasa-b2b-core' ); ?></button>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}
add_action( 'woocommerce_product_options_general_product_data', 'kamasa_b2b_render_precios_volumen_fields', 25 );

/**
 * Guarda los valores del meta field de precios por volumen.
 *
 * @param int $product_id ID del producto.
 *
 * @return void
 */
function kamasa_b2b_save_precios_volumen_fields( $product_id ) {
    if ( empty( $_POST['kamasa_precios_volumen_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['kamasa_precios_volumen_nonce'] ), 'kamasa_precios_volumen_guardar' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        return;
    }

    if ( ! current_user_can( 'edit_post', $product_id ) ) {
        return;
    }

    $min_values       = isset( $_POST['rango_min'] ) ? (array) wp_unslash( $_POST['rango_min'] ) : array();
    $max_values       = isset( $_POST['rango_max'] ) ? (array) wp_unslash( $_POST['rango_max'] ) : array();
    $discount_values  = isset( $_POST['rango_descuento'] ) ? (array) wp_unslash( $_POST['rango_descuento'] ) : array();
    $rangos_sanitized = array();

    $total = max( count( $min_values ), count( $max_values ), count( $discount_values ) );

    for ( $i = 0; $i < $total; $i++ ) {
        $min_raw       = isset( $min_values[ $i ] ) ? trim( $min_values[ $i ] ) : '';
        $max_raw       = isset( $max_values[ $i ] ) ? trim( $max_values[ $i ] ) : '';
        $discount_raw  = isset( $discount_values[ $i ] ) ? trim( $discount_values[ $i ] ) : '';

        if ( '' === $min_raw && '' === $max_raw && '' === $discount_raw ) {
            continue;
        }

        if ( '' === $min_raw || ! is_numeric( $min_raw ) ) {
            continue;
        }

        if ( '' === $discount_raw || ! is_numeric( $discount_raw ) ) {
            continue;
        }

        $min = max( 0, (int) $min_raw );

        $max = null;
        if ( '' !== $max_raw ) {
            if ( ! is_numeric( $max_raw ) ) {
                continue;
            }

            $max = max( 0, (int) $max_raw );

            if ( $max < $min ) {
                continue;
            }
        }

        if ( function_exists( 'wc_format_decimal' ) ) {
            $discount = (float) wc_format_decimal( $discount_raw );
        } else {
            $discount = (float) $discount_raw;
        }

        $rangos_sanitized[] = array(
            'min'       => $min,
            'max'       => $max,
            'descuento' => $discount,
        );
    }

    if ( ! empty( $rangos_sanitized ) ) {
        update_post_meta( $product_id, 'precios_volumen', $rangos_sanitized );
    } else {
        delete_post_meta( $product_id, 'precios_volumen' );
    }
}
add_action( 'woocommerce_process_product_meta', 'kamasa_b2b_save_precios_volumen_fields' );

/**
 * Encola los scripts necesarios en la pantalla de edición de productos.
 *
 * @param string $hook_slug Hook actual del admin.
 *
 * @return void
 */
function kamasa_b2b_enqueue_precios_volumen_admin_scripts( $hook_slug ) {
    $screen = get_current_screen();

    if ( ! $screen || 'product' !== $screen->id ) {
        return;
    }

    wp_enqueue_script(
        'kamasa-precios-volumen',
        KAMASA_B2B_PLUGIN_URL . 'admin/js/precios-volumen.js',
        array( 'jquery' ),
        KAMASA_B2B_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'kamasa_b2b_enqueue_precios_volumen_admin_scripts' );
