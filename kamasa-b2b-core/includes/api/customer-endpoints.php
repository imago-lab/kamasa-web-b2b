<?php
/**
 * Endpoints API REST para datos del cliente B2B.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra los endpoints del cliente en la API REST.
 *
 * @return void
 */
function kamasa_register_customer_api_routes() {
    register_rest_route(
        'kamasa/v1',
        '/cliente/financiero',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'kamasa_get_datos_financieros_api',
            'permission_callback' => 'kamasa_b2b_rest_check_permissions',
        )
    );

    register_rest_route(
        'kamasa/v1',
        '/cliente/historial-compras',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'kamasa_get_historial_compras_api',
            'permission_callback' => 'kamasa_b2b_rest_check_permissions',
        )
    );

    register_rest_route(
        'kamasa/v1',
        '/cliente/asesor',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'kamasa_get_datos_asesor_api',
            'permission_callback' => 'kamasa_b2b_rest_check_permissions',
        )
    );
}
add_action( 'rest_api_init', 'kamasa_register_customer_api_routes' );

/**
 * Valida los permisos del usuario autenticado para acceder a los endpoints.
 *
 * @return true|WP_Error
 */
function kamasa_b2b_rest_check_permissions() {
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'Debes iniciar sesión para acceder a estos datos.', 'kamasa-b2b-core' ),
            array( 'status' => 403 )
        );
    }

    $user_id = get_current_user_id();

    if ( ! function_exists( 'kamasa_es_usuario_b2b' ) || ! kamasa_es_usuario_b2b( $user_id ) ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'No tienes permisos para acceder a estos datos.', 'kamasa-b2b-core' ),
            array( 'status' => 403 )
        );
    }

    return true;
}

/**
 * Devuelve los datos financieros del cliente autenticado.
 *
 * @param WP_REST_Request $request Petición actual.
 *
 * @return WP_REST_Response|array
 */
function kamasa_get_datos_financieros_api( WP_REST_Request $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $user_id = get_current_user_id();

    // TODO: Reemplazar por datos reales desde metadatos de usuario o servicios externos.
    $data = array(
        'linea_credito_disponible' => 15000.50,
        'facturas_pendientes'      => array(
            array(
                'numero'           => 'F001-123',
                'fecha_emision'    => '2025-10-15',
                'fecha_vencimiento'=> '2025-11-14',
                'monto'            => 1250.75,
                'estado'           => 'pendiente',
            ),
            array(
                'numero'           => 'F001-120',
                'fecha_emision'    => '2025-10-01',
                'fecha_vencimiento'=> '2025-10-31',
                'monto'            => 800.00,
                'estado'           => 'pendiente',
            ),
        ),
        'saldo_vencido'            => 0.00,
    );

    /**
     * Filtro para modificar la respuesta del endpoint financiero.
     *
     * @param array $data    Datos simulados del cliente.
     * @param int   $user_id ID del usuario actual.
     */
    $data = apply_filters( 'kamasa_b2b_rest_financial_data', $data, $user_id );

    return rest_ensure_response( $data );
}

/**
 * Devuelve el historial de compras del cliente autenticado.
 *
 * @param WP_REST_Request $request Petición actual.
 *
 * @return WP_REST_Response|array
 */
function kamasa_get_historial_compras_api( WP_REST_Request $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $user_id = get_current_user_id();
    $orders  = array();

    if ( function_exists( 'wc_get_orders' ) ) {
        $query_args = array(
            'customer_id' => $user_id,
            'limit'       => 20,
            'orderby'     => 'date',
            'order'       => 'DESC',
        );

        $orders = wc_get_orders( $query_args );
    }

    $response = array();

    foreach ( $orders as $order ) {
        if ( ! is_a( $order, 'WC_Order' ) ) {
            continue;
        }

        $order_date = $order->get_date_created();

        $response[] = array(
            'id'     => $order->get_id(),
            'fecha'  => $order_date ? $order_date->date( 'c' ) : '',
            'total'  => (float) $order->get_total(),
            'estado' => $order->get_status(),
        );
    }

    /**
     * Filtro para modificar el historial de compras retornado.
     *
     * @param array $response Datos formateados del historial.
     * @param int   $user_id  ID del usuario actual.
     */
    $response = apply_filters( 'kamasa_b2b_rest_purchase_history', $response, $user_id );

    return rest_ensure_response( $response );
}

/**
 * Devuelve los datos del asesor asignado al cliente autenticado.
 *
 * @param WP_REST_Request $request Petición actual.
 *
 * @return WP_REST_Response|array
 */
function kamasa_get_datos_asesor_api( WP_REST_Request $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $user_id    = get_current_user_id();
    $advisor    = array(
        'email'    => '',
        'nombre'   => '',
        'telefono' => '',
    );

    $advisor_email = get_user_meta( $user_id, 'asesor_asignado_email', true );

    if ( ! empty( $advisor_email ) ) {
        $advisor['email'] = sanitize_email( $advisor_email );

        $advisor_user = get_user_by( 'email', $advisor['email'] );

        if ( $advisor_user ) {
            $advisor['nombre']   = $advisor_user->display_name;
            $advisor['telefono'] = sanitize_text_field( get_user_meta( $advisor_user->ID, 'telefono_contacto', true ) );
        }
    }

    /**
     * Filtro para modificar la respuesta del asesor asignado.
     *
     * @param array $advisor Datos del asesor.
     * @param int   $user_id ID del usuario actual.
     */
    $advisor = apply_filters( 'kamasa_b2b_rest_advisor_data', $advisor, $user_id );

    return rest_ensure_response( $advisor );
}
