<?php
/**
 * Funcionalidad de comparación de productos.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KAMASA_COMPARE_SESSION_KEY' ) ) {
    define( 'KAMASA_COMPARE_SESSION_KEY', 'kamasa_compare_list' );
}

/**
 * Inicia la sesión PHP si aún no está activa.
 *
 * @return void
 */
function kamasa_compare_start_session() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return;
    }

    if ( PHP_SESSION_NONE === session_status() && ! headers_sent() ) {
        session_start();
    }

    if ( empty( $_SESSION[ KAMASA_COMPARE_SESSION_KEY ] ) || ! is_array( $_SESSION[ KAMASA_COMPARE_SESSION_KEY ] ) ) {
        $_SESSION[ KAMASA_COMPARE_SESSION_KEY ] = array();
    }
}
add_action( 'init', 'kamasa_compare_start_session', 1 );

/**
 * Obtiene la lista actual de productos en comparación.
 *
 * @return int[]
 */
function kamasa_get_compare_list() {
    if ( empty( $_SESSION[ KAMASA_COMPARE_SESSION_KEY ] ) || ! is_array( $_SESSION[ KAMASA_COMPARE_SESSION_KEY ] ) ) {
        return array();
    }

    $list = array_filter( array_map( 'absint', (array) $_SESSION[ KAMASA_COMPARE_SESSION_KEY ] ) );

    return array_values( array_unique( $list ) );
}

/**
 * Guarda la lista de productos a comparar en la sesión.
 *
 * @param int[] $list Lista de IDs de productos.
 *
 * @return void
 */
function kamasa_set_compare_list( $list ) {
    if ( ! is_array( $list ) ) {
        $list = array();
    }

    $_SESSION[ KAMASA_COMPARE_SESSION_KEY ] = array_values( array_unique( array_filter( array_map( 'absint', $list ) ) ) );
}

/**
 * Determina si un producto ya está en la lista de comparación.
 *
 * @param int $product_id ID del producto.
 *
 * @return bool
 */
function kamasa_is_product_in_compare( $product_id ) {
    $product_id = absint( $product_id );

    if ( ! $product_id ) {
        return false;
    }

    return in_array( $product_id, kamasa_get_compare_list(), true );
}

/**
 * Renderiza el botón de comparación para un producto.
 *
 * @param int $product_id ID del producto.
 *
 * @return void
 */
function kamasa_display_compare_button( $product_id ) {
    $product_id = absint( $product_id );

    if ( ! $product_id ) {
        return;
    }

    $is_selected = kamasa_is_product_in_compare( $product_id );

    $button_classes = array( 'kamasa-compare-button', 'button' );

    if ( $is_selected ) {
        $button_classes[] = 'is-selected';
    }

    $button_text_add    = esc_html__( 'Comparar', 'kamasa-b2b-core' );
    $button_text_remove = esc_html__( 'Quitar de la comparación', 'kamasa-b2b-core' );
    $button_label       = $is_selected ? $button_text_remove : $button_text_add;
    ?>
    <button
        type="button"
        class="<?php echo esc_attr( implode( ' ', array_unique( $button_classes ) ) ); ?>"
        data-product-id="<?php echo esc_attr( $product_id ); ?>"
        data-label-add="<?php echo esc_attr( $button_text_add ); ?>"
        data-label-remove="<?php echo esc_attr( $button_text_remove ); ?>"
        data-selected="<?php echo $is_selected ? 'true' : 'false'; ?>"
        aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>"
    >
        <span class="kamasa-compare-button__label"><?php echo esc_html( $button_label ); ?></span>
    </button>
    <?php
}

/**
 * Muestra el botón de comparación dentro del loop de productos.
 *
 * @return void
 */
function kamasa_render_compare_button_in_loop() {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    kamasa_display_compare_button( $product->get_id() );
}
add_action( 'woocommerce_after_shop_loop_item', 'kamasa_render_compare_button_in_loop', 25 );

/**
 * Muestra el botón de comparación en la ficha de producto individual.
 *
 * @return void
 */
function kamasa_render_compare_button_single() {
    global $product;

    if ( $product instanceof WC_Product ) {
        kamasa_display_compare_button( $product->get_id() );
    }
}
add_action( 'woocommerce_single_product_summary', 'kamasa_render_compare_button_single', 35 );

/**
 * Devuelve la URL de la página de comparación.
 *
 * De forma predeterminada apunta a /comparar/, donde se recomienda crear
 * una página que utilice el shortcode [kamasa_comparar_productos].
 *
 * @return string
 */
function kamasa_get_compare_page_url() {
    /**
     * Permite modificar la URL de la página de comparación.
     *
     * @since 1.0.0
     *
     * @param string $url URL actual de comparación.
     */
    return apply_filters( 'kamasa_compare_page_url', home_url( '/comparar/' ) );
}

/**
 * Renderiza el indicador flotante con el enlace a la comparación.
 *
 * @return void
 */
function kamasa_display_compare_link() {
    $compare_list = kamasa_get_compare_list();
    $count        = count( $compare_list );

    $classes = array( 'kamasa-compare-indicator' );

    if ( 0 === $count ) {
        $classes[] = 'is-hidden';
    }
    ?>
    <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-max-items="3">
        <a class="kamasa-compare-indicator__link" href="<?php echo esc_url( kamasa_get_compare_page_url() ); ?>">
            <?php esc_html_e( 'Comparar', 'kamasa-b2b-core' ); ?>
            (<span id="kamasa-compare-count"><?php echo (int) $count; ?></span>
            <?php esc_html_e( 'productos', 'kamasa-b2b-core' ); ?>)
        </a>
    </div>
    <?php
}
add_action( 'wp_footer', 'kamasa_display_compare_link' );

/**
 * Encola los assets necesarios para la comparación.
 *
 * @return void
 */
function kamasa_enqueue_compare_assets() {
    if ( is_admin() ) {
        return;
    }

    wp_enqueue_style(
        'kamasa-compare',
        KAMASA_B2B_PLUGIN_URL . 'public/css/kamasa-compare.css',
        array(),
        KAMASA_B2B_VERSION
    );

    wp_enqueue_script(
        'kamasa-compare',
        KAMASA_B2B_PLUGIN_URL . 'public/js/kamasa-compare.js',
        array( 'jquery' ),
        KAMASA_B2B_VERSION,
        true
    );

    wp_localize_script(
        'kamasa-compare',
        'kamasaCompareData',
        array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'kamasa_compare_nonce' ),
            'maxItems'     => 3,
            'currentCount' => count( kamasa_get_compare_list() ),
            'compareUrl'   => esc_url( kamasa_get_compare_page_url() ),
            'messages'     => array(
                'limitReached' => esc_html__( 'Solo puedes comparar hasta 3 productos a la vez.', 'kamasa-b2b-core' ),
                'genericError' => esc_html__( 'Ocurrió un error al actualizar la comparación.', 'kamasa-b2b-core' ),
            ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'kamasa_enqueue_compare_assets' );

/**
 * Maneja la petición AJAX para añadir o quitar productos de la comparación.
 *
 * @return void
 */
function kamasa_toggle_compare_ajax_handler() {
    check_ajax_referer( 'kamasa_compare_nonce', 'nonce' );

    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

    if ( ! $product_id ) {
        wp_send_json_error(
            array(
                'message' => esc_html__( 'Producto no válido.', 'kamasa-b2b-core' ),
            )
        );
    }

    $compare_list = kamasa_get_compare_list();
    $action       = 'none';

    if ( in_array( $product_id, $compare_list, true ) ) {
        $compare_list = array_values( array_diff( $compare_list, array( $product_id ) ) );
        $action       = 'removed';
    } elseif ( count( $compare_list ) >= 3 ) {
        wp_send_json_success(
            array(
                'action' => 'limit_reached',
                'count'  => count( $compare_list ),
            )
        );
    } else {
        $compare_list[] = $product_id;
        $action         = 'added';
    }

    kamasa_set_compare_list( $compare_list );

    wp_send_json_success(
        array(
            'action' => $action,
            'count'  => count( $compare_list ),
            'ids'    => $compare_list,
        )
    );
}
add_action( 'wp_ajax_kamasa_toggle_compare', 'kamasa_toggle_compare_ajax_handler' );
add_action( 'wp_ajax_nopriv_kamasa_toggle_compare', 'kamasa_toggle_compare_ajax_handler' );

/**
 * Shortcode que renderiza la tabla comparativa.
 *
 * Utiliza el shortcode [kamasa_comparar_productos] en la página definida por
 * {@see kamasa_get_compare_page_url()} para mostrar la tabla.
 *
 * @return string
 */
function kamasa_compare_table_shortcode() {
    $compare_list = kamasa_get_compare_list();

    if ( empty( $compare_list ) ) {
        return '<p class="kamasa-compare-empty">' . esc_html__( 'No hay productos para comparar en este momento.', 'kamasa-b2b-core' ) . '</p>';
    }

    if ( ! function_exists( 'wc_get_product' ) ) {
        return '';
    }

    $products = array();

    foreach ( $compare_list as $product_id ) {
        $product = wc_get_product( $product_id );

        if ( $product ) {
            $products[ $product_id ] = $product;
        }
    }

    if ( empty( $products ) ) {
        return '<p class="kamasa-compare-empty">' . esc_html__( 'No hay productos disponibles para comparar.', 'kamasa-b2b-core' ) . '</p>';
    }

    $comparison_rows = array(
        'price'      => array( 'label' => esc_html__( 'Precio', 'kamasa-b2b-core' ) ),
        'sku'        => array( 'label' => esc_html__( 'SKU', 'kamasa-b2b-core' ) ),
        'stock'      => array( 'label' => esc_html__( 'Disponibilidad', 'kamasa-b2b-core' ) ),
        'dimensions' => array( 'label' => esc_html__( 'Dimensiones', 'kamasa-b2b-core' ) ),
        'weight'     => array( 'label' => esc_html__( 'Peso', 'kamasa-b2b-core' ) ),
    );

    $attribute_rows = array();

    foreach ( $products as $product_id => $product ) {
        $comparison_rows['price']['values'][ $product_id ]      = $product->get_price_html() ? wp_kses_post( $product->get_price_html() ) : '&mdash;';
        $comparison_rows['sku']['values'][ $product_id ]        = $product->get_sku() ? esc_html( $product->get_sku() ) : '&mdash;';
        $comparison_rows['stock']['values'][ $product_id ]      = wp_kses_post( wc_get_stock_html( $product ) );
        $comparison_rows['dimensions']['values'][ $product_id ] = $product->has_dimensions() ? esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ) : '&mdash;';
        $comparison_rows['weight']['values'][ $product_id ]     = $product->has_weight() ? esc_html( wc_format_weight( $product->get_weight() ) ) : '&mdash;';

        $attributes = $product->get_attributes();

        foreach ( $attributes as $attribute ) {
            if ( $attribute->is_taxonomy() ) {
                $label   = wc_attribute_label( $attribute->get_name() );
                $options = wc_get_product_terms( $product_id, $attribute->get_name(), array( 'fields' => 'names' ) );
            } else {
                $label   = $attribute->get_name();
                $options = $attribute->get_options();
            }

            $label = $label ? $label : $attribute->get_name();
            $key   = 'attr_' . sanitize_title( $label );

            if ( ! isset( $attribute_rows[ $key ] ) ) {
                $attribute_rows[ $key ] = array(
                    'label'  => $label,
                    'values' => array(),
                );
            }

            $attribute_rows[ $key ]['values'][ $product_id ] = ! empty( $options ) ? esc_html( implode( ', ', $options ) ) : '&mdash;';
        }
    }

    $rows = array_merge( $comparison_rows, $attribute_rows );

    ob_start();
    ?>
    <div class="kamasa-compare-table-wrapper">
        <table class="kamasa-compare-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Características', 'kamasa-b2b-core' ); ?></th>
                    <?php foreach ( $products as $product ) : ?>
                        <th>
                            <div class="kamasa-compare-table__product">
                                <a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="kamasa-compare-table__thumb">
                                    <?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </a>
                                <a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="kamasa-compare-table__name">
                                    <?php echo esc_html( $product->get_name() ); ?>
                                </a>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rows as $row ) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
                        <?php foreach ( $products as $product_id => $product ) : ?>
                            <?php
                            $value = isset( $row['values'][ $product_id ] ) ? $row['values'][ $product_id ] : '&mdash;';
                            ?>
                            <td><?php echo wp_kses_post( $value ); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'kamasa_comparar_productos', 'kamasa_compare_table_shortcode' );
