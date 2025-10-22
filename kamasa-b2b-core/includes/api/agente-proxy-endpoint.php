<?php
/**
 * Endpoint REST para servir como proxy entre el frontend y el asistente IA en n8n.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registra el endpoint REST del agente IA.
 *
 * @return void
 */
function kamasa_register_agente_proxy_endpoint() {
    register_rest_route(
        'kamasa/v1',
        '/agente/preguntar',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'kamasa_proxy_agente_ia_handler',
            'permission_callback' => 'kamasa_agente_proxy_permission_callback',
            'args'                => array(
                'pregunta'            => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'description'       => __( 'Pregunta que realiza el usuario al asistente.', 'kamasa-b2b-core' ),
                ),
                'conversacion_previa' => array(
                    'required'    => false,
                    'type'        => 'array',
                    'description' => __( 'Mensajes previos que mantiene el contexto de la conversación.', 'kamasa-b2b-core' ),
                ),
                'session_id'          => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => __( 'Identificador de sesión para visitantes no autenticados.', 'kamasa-b2b-core' ),
                ),
                'nonce'               => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => __( 'Nonce de seguridad enviado por el widget de chat.', 'kamasa-b2b-core' ),
                ),
            ),
        )
    );
}

/**
 * Valida que la petición cuente con un nonce correcto.
 *
 * @param WP_REST_Request $request Petición REST.
 *
 * @return bool|WP_Error
 */
function kamasa_agente_proxy_permission_callback( WP_REST_Request $request ) {
    $nonce = $request->get_param( 'nonce' );

    if ( empty( $nonce ) ) {
        return new WP_Error(
            'kamasa_agente_nonce_missing',
            __( 'Nonce ausente. Actualiza la página e inténtalo nuevamente.', 'kamasa-b2b-core' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    if ( ! wp_verify_nonce( $nonce, 'kamasa_agente_proxy' ) ) {
        return new WP_Error(
            'kamasa_agente_nonce_invalid',
            __( 'Nonce inválido. No se pudo validar la solicitud.', 'kamasa-b2b-core' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    return true;
}

/**
 * Maneja la petición enviada al agente IA de n8n.
 *
 * @param WP_REST_Request $request Petición REST.
 *
 * @return WP_REST_Response|WP_Error
 */
function kamasa_proxy_agente_ia_handler( WP_REST_Request $request ) {
    $pregunta = sanitize_textarea_field( wp_unslash( $request->get_param( 'pregunta' ) ) );
    $session_id = $request->get_param( 'session_id' );
    $session_id = is_string( $session_id ) ? sanitize_text_field( wp_unslash( $session_id ) ) : '';

    $conversacion_previa = $request->get_param( 'conversacion_previa' );
    if ( ! is_array( $conversacion_previa ) ) {
        $conversacion_previa = array();
    }

    $user_id = get_current_user_id();

    $webhook_url = get_option( 'kamasa_n8n_rag_webhook_url', '' );
    if ( empty( $webhook_url ) ) {
        return new WP_Error(
            'kamasa_agente_webhook_missing',
            __( 'No se ha configurado la URL del webhook del asistente.', 'kamasa-b2b-core' ),
            array( 'status' => 500 )
        );
    }

    $payload = array(
        'pregunta'            => $pregunta,
        'user_id'             => $user_id ? $user_id : null,
        'conversacion_previa' => $conversacion_previa,
        'session_id'          => $session_id,
    );

    $response = wp_remote_post(
        esc_url_raw( $webhook_url ),
        array(
            'timeout' => 20,
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
            ),
            'body'    => wp_json_encode( $payload ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error(
            'kamasa_agente_request_failed',
            __( 'Error al conectar con el asistente.', 'kamasa-b2b-core' ),
            array( 'status' => 502 )
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code < 200 || $status_code >= 300 ) {
        return new WP_Error(
            'kamasa_agente_http_error',
            __( 'El asistente devolvió una respuesta no válida.', 'kamasa-b2b-core' ),
            array(
                'status' => $status_code ? $status_code : 500,
                'body'   => wp_remote_retrieve_body( $response ),
            )
        );
    }

    $body          = wp_remote_retrieve_body( $response );
    $decoded_body  = json_decode( $body, true );
    $decoded_body  = is_array( $decoded_body ) ? $decoded_body : array();

    // Aquí podría ejecutarse wp_insert_post() para almacenar la conversación en el CPT
    // "kamasa_conversacion_ia" inmediatamente después de recibir la respuesta. No se
    // realiza en este punto para evitar retrasos; se recomienda que n8n lo gestione de
    // manera asíncrona una vez que genere la respuesta definitiva.

    return rest_ensure_response( $decoded_body );
}
