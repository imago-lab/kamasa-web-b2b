<?php
/**
 * Lógica de visualización frontend para usuarios B2B.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determina si el usuario actual tiene acceso a precios y compras B2B.
 *
 * @return bool
 */
function kamasa_b2b_usuario_autorizado() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $user_id = get_current_user_id();

    if ( ! $user_id ) {
        return false;
    }

    if ( ! function_exists( 'kamasa_es_usuario_b2b' ) ) {
        return false;
    }

    $is_authorized = kamasa_es_usuario_b2b( $user_id );

    /**
     * Permite filtrar si un usuario está autorizado como B2B.
     *
     * @param bool $is_authorized Resultado de la comprobación.
     * @param int  $user_id       ID del usuario actual.
     */
    return (bool) apply_filters( 'kamasa_b2b_usuario_autorizado', $is_authorized, $user_id );
}

/**
 * Registra los assets del frontend sin encolarlos todavía.
 *
 * @return void
 */
function kamasa_b2b_register_frontend_assets() {
    wp_register_script(
        'kamasa-quote-form',
        KAMASA_B2B_PLUGIN_URL . 'public/js/kamasa-quote-form.js',
        array(),
        KAMASA_B2B_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'kamasa_b2b_register_frontend_assets' );

/**
 * Filtra el botón de "Añadir al carrito" en el loop de productos.
 *
 * @param string     $button_html Marcado original del botón.
 * @param WC_Product $product     Producto del loop.
 *
 * @return string
 */
function kamasa_boton_cotizar_loop( $button_html, $product ) {
    if ( kamasa_b2b_usuario_autorizado() || ! $product instanceof WC_Product ) {
        return $button_html;
    }

    $quote_url = apply_filters( 'kamasa_b2b_quote_page_url', home_url( '/solicitar-cotizacion/' ) );

    $custom_button = sprintf(
        '<a class="button kamasa-quote-button" href="%1$s">%2$s</a>',
        esc_url( $quote_url ),
        esc_html__( 'Solicitar Cotización', 'kamasa-b2b-core' )
    );

    return apply_filters( 'kamasa_b2b_loop_quote_button', $custom_button, $product );
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'kamasa_boton_cotizar_loop', 10, 2 );

/**
 * Reemplaza el botón de añadir al carrito en la ficha de producto por el de cotización si aplica.
 *
 * @return void
 */
function kamasa_b2b_maybe_replace_single_add_to_cart() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }

    if ( kamasa_b2b_usuario_autorizado() ) {
        return;
    }

    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
    add_action( 'woocommerce_single_product_summary', 'kamasa_b2b_render_quote_button_single', 30 );
}
add_action( 'template_redirect', 'kamasa_b2b_maybe_replace_single_add_to_cart' );

/**
 * Renderiza el botón de solicitud de cotización en la vista de producto individual.
 *
 * @return void
 */
function kamasa_b2b_render_quote_button_single() {
    $quote_url = apply_filters( 'kamasa_b2b_quote_page_url', home_url( '/solicitar-cotizacion/' ) );
    ?>
    <div class="kamasa-quote-button-wrapper">
        <a class="button kamasa-quote-button" href="<?php echo esc_url( $quote_url ); ?>"><?php esc_html_e( 'Solicitar Cotización', 'kamasa-b2b-core' ); ?></a>
    </div>
    <?php
}

/**
 * Shortcode del formulario de cotización.
 *
 * @return string
 */
function kamasa_formulario_cotizacion_shortcode() {
    if ( ! wp_script_is( 'kamasa-quote-form', 'registered' ) ) {
        kamasa_b2b_register_frontend_assets();
    }

    wp_enqueue_script( 'kamasa-quote-form' );
    wp_localize_script(
        'kamasa-quote-form',
        'kamasaQuoteForm',
        array(
            'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
            'successMessage'     => __( 'Hemos recibido tu solicitud de cotización. Te contactaremos pronto.', 'kamasa-b2b-core' ),
            'errorMessage'       => __( 'No pudimos enviar la solicitud. Inténtalo nuevamente más tarde.', 'kamasa-b2b-core' ),
            'missingAjaxMessage' => __( 'No pudimos procesar tu solicitud en este momento. Recarga la página e inténtalo de nuevo.', 'kamasa-b2b-core' ),
        )
    );

    ob_start();
    ?>
    <form id="form-cotizacion" class="kamasa-quote-form" method="post">
        <div class="kamasa-quote-form__field">
            <label for="kamasa_nombre"><?php esc_html_e( 'Nombre', 'kamasa-b2b-core' ); ?></label>
            <input type="text" id="kamasa_nombre" name="nombre" required />
        </div>

        <div class="kamasa-quote-form__field">
            <label for="kamasa_email"><?php esc_html_e( 'Email', 'kamasa-b2b-core' ); ?></label>
            <input type="email" id="kamasa_email" name="email" required />
        </div>

        <div class="kamasa-quote-form__field">
            <label for="kamasa_telefono"><?php esc_html_e( 'Teléfono', 'kamasa-b2b-core' ); ?></label>
            <input type="tel" id="kamasa_telefono" name="telefono" />
        </div>

        <div class="kamasa-quote-form__field">
            <label for="kamasa_empresa"><?php esc_html_e( 'Empresa', 'kamasa-b2b-core' ); ?></label>
            <input type="text" id="kamasa_empresa" name="empresa" />
        </div>

        <div class="kamasa-quote-form__field">
            <label for="kamasa_mensaje"><?php esc_html_e( 'Productos / Mensaje', 'kamasa-b2b-core' ); ?></label>
            <textarea id="kamasa_mensaje" name="mensaje" rows="5" required></textarea>
        </div>

        <div class="kamasa-quote-form__field kamasa-quote-form__field--checkbox">
            <label for="kamasa_crear_cuenta">
                <input type="checkbox" id="kamasa_crear_cuenta" name="crear_cuenta" value="1" />
                <?php esc_html_e( 'Crear cuenta B2B', 'kamasa-b2b-core' ); ?>
            </label>
        </div>

        <?php wp_nonce_field( 'kamasa_enviar_cotizacion', 'kamasa_cotizacion_nonce' ); ?>

        <button type="submit" class="button kamasa-quote-form__submit"><?php esc_html_e( 'Enviar solicitud', 'kamasa-b2b-core' ); ?></button>
        <div class="kamasa-quote-form__messages" role="status" aria-live="polite"></div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'kamasa_cotizacion', 'kamasa_formulario_cotizacion_shortcode' );

/**
 * Manejador AJAX para el formulario de cotización.
 *
 * @return void
 */
function kamasa_enviar_cotizacion_ajax_handler() {
    check_ajax_referer( 'kamasa_enviar_cotizacion', 'kamasa_cotizacion_nonce' );

    $nombre   = isset( $_POST['nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['nombre'] ) ) : '';
    $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $telefono = isset( $_POST['telefono'] ) ? sanitize_text_field( wp_unslash( $_POST['telefono'] ) ) : '';
    $empresa  = isset( $_POST['empresa'] ) ? sanitize_text_field( wp_unslash( $_POST['empresa'] ) ) : '';
    $mensaje  = isset( $_POST['mensaje'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ) ) : '';
    $crear    = isset( $_POST['crear_cuenta'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['crear_cuenta'] ) );

    if ( empty( $nombre ) || empty( $email ) || empty( $mensaje ) ) {
        wp_send_json_error( array( 'message' => __( 'Por favor, completa los campos obligatorios.', 'kamasa-b2b-core' ) ) );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => __( 'Introduce un correo electrónico válido.', 'kamasa-b2b-core' ) ) );
    }

    // Aquí podrías guardar el lead en un CPT "lead" con wp_insert_post().

    $webhook_url = get_option( 'kamasa_b2b_n8n_webhook_url', '' );

    if ( empty( $webhook_url ) ) {
        wp_send_json_error( array( 'message' => __( 'No se encontró la configuración del webhook de cotización.', 'kamasa-b2b-core' ) ) );
    }

    $payload = array(
        'nombre'          => $nombre,
        'email'           => $email,
        'telefono'        => $telefono,
        'empresa'         => $empresa,
        'mensaje'         => $mensaje,
        'crear_cuenta_b2b'=> $crear ? 1 : 0,
        'origen'          => 'formulario_cotizacion_kamasa',
        'site_url'        => home_url(),
        'referer'         => wp_get_referer(),
    );

    $response = wp_remote_post(
        $webhook_url,
        array(
            'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 20,
        )
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $status_code = (int) wp_remote_retrieve_response_code( $response );

    if ( $status_code < 200 || $status_code >= 300 ) {
        wp_send_json_error( array( 'message' => __( 'El servicio de cotización respondió con un error.', 'kamasa-b2b-core' ) ) );
    }

    wp_send_json_success( array( 'message' => __( 'Solicitud enviada correctamente.', 'kamasa-b2b-core' ) ) );
}
add_action( 'wp_ajax_nopriv_kamasa_enviar_cotizacion', 'kamasa_enviar_cotizacion_ajax_handler' );
add_action( 'wp_ajax_kamasa_enviar_cotizacion', 'kamasa_enviar_cotizacion_ajax_handler' );
