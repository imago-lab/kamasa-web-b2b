<?php
/**
 * Lógica de precios personalizada para clientes B2B.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determina si un usuario pertenece a alguno de los roles B2B registrados.
 *
 * @param int|array|string|WP_User $user_id_or_roles Identificador del usuario o listado de roles.
 *
 * @return bool
 */
function kamasa_es_usuario_b2b( $user_id_or_roles ) {
    $b2b_roles = (array) apply_filters(
        'kamasa_b2b_roles',
        array(
            'cliente_b2b_minorista',
            'cliente_b2b_mayorista',
            'cliente_b2b_distribuidor',
        )
    );

    $roles_to_check = array();

    if ( $user_id_or_roles instanceof WP_User ) {
        $roles_to_check = (array) $user_id_or_roles->roles;
    } elseif ( is_numeric( $user_id_or_roles ) ) {
        $user = get_user_by( 'id', (int) $user_id_or_roles );
        if ( $user ) {
            $roles_to_check = (array) $user->roles;
        }
    } elseif ( is_array( $user_id_or_roles ) ) {
        $roles_to_check = $user_id_or_roles;
    } elseif ( is_string( $user_id_or_roles ) && '' !== $user_id_or_roles ) {
        $roles_to_check = array( $user_id_or_roles );
    }

    return (bool) array_intersect( $roles_to_check, $b2b_roles );
}

/**
 * Obtiene el porcentaje de descuento base según el tipo de cliente.
 *
 * @param string $tipo Tipo de cliente almacenado en el meta "tipo_cliente".
 *
 * @return float
 */
function kamasa_get_descuento_por_tipo( $tipo ) {
    $tipo_normalizado = strtolower( (string) $tipo );

    $descuentos_por_tipo = (array) apply_filters(
        'kamasa_b2b_descuentos_por_tipo',
        array(
            'minorista'    => 0,
            'mayorista'    => 15,
            'distribuidor' => 25,
        )
    );

    return isset( $descuentos_por_tipo[ $tipo_normalizado ] )
        ? (float) $descuentos_por_tipo[ $tipo_normalizado ]
        : 0.0;
}

/**
 * Calcula el precio mostrado para usuarios B2B.
 *
 * @param string|float $price   Precio actual de WooCommerce.
 * @param WC_Product    $product Instancia del producto.
 *
 * @return string|float
 */
function kamasa_calcular_precio_display( $price, $product ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $price;
    }

    if ( ! is_user_logged_in() ) {
        return '';
    }

    $user_id = get_current_user_id();

    if ( ! kamasa_es_usuario_b2b( $user_id ) ) {
        return '';
    }

    if ( ! $product instanceof WC_Product ) {
        return $price;
    }

    $product_id = $product->get_id();
    $parent_id  = $product->is_type( 'variation' ) ? $product->get_parent_id() : 0;

    $cache_key = sprintf( 'kamasa_price_%d_user_%d', $product_id, $user_id );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $precio_base = get_post_meta( $product_id, 'precio_base', true );

    if ( '' === $precio_base && $parent_id ) {
        $precio_base = get_post_meta( $parent_id, 'precio_base', true );
    }

    if ( '' === $precio_base ) {
        $precio_base = $product->get_regular_price();
    }

    $precio_base = (float) $precio_base;

    $tipo_cliente    = get_user_meta( $user_id, 'tipo_cliente', true );
    $descuento_tipo  = kamasa_get_descuento_por_tipo( $tipo_cliente );
    $precio_con_tipo = max( 0, $precio_base * ( 1 - ( (float) $descuento_tipo / 100 ) ) );

    set_transient( $cache_key, $precio_con_tipo, HOUR_IN_SECONDS );

    return $precio_con_tipo;
}
add_filter( 'woocommerce_product_get_price', 'kamasa_calcular_precio_display', 10, 2 );
add_filter( 'woocommerce_product_get_regular_price', 'kamasa_calcular_precio_display', 10, 2 );
add_filter( 'woocommerce_product_variation_get_price', 'kamasa_calcular_precio_display', 10, 2 );

/**
 * Calcula el descuento por volumen aplicable según la cantidad del carrito.
 *
 * @param int $product_id ID del producto (o variación) a evaluar.
 * @param int $quantity   Cantidad de artículos en el carrito.
 *
 * @return float
 */
function kamasa_get_descuento_volumen( $product_id, $quantity ) {
    $quantity = (int) $quantity;

    if ( $quantity <= 0 ) {
        return 0.0;
    }

    $rangos = get_post_meta( $product_id, 'precios_volumen', true );

    if ( empty( $rangos ) ) {
        $product = wc_get_product( $product_id );
        if ( $product && $product->is_type( 'variation' ) ) {
            $parent_id = $product->get_parent_id();
            if ( $parent_id ) {
                $rangos = get_post_meta( $parent_id, 'precios_volumen', true );
            }
        }
    }

    $rangos = maybe_unserialize( $rangos );

    if ( ! is_array( $rangos ) ) {
        return 0.0;
    }

    foreach ( $rangos as $rango ) {
        $min        = isset( $rango['min'] ) ? (int) $rango['min'] : 0;
        $max        = isset( $rango['max'] ) ? $rango['max'] : null;
        $descuento  = isset( $rango['descuento'] ) ? (float) $rango['descuento'] : 0.0;
        $maximo_val = is_null( $max ) || '' === $max ? null : (int) $max;

        if ( $quantity >= $min && ( is_null( $maximo_val ) || $quantity <= $maximo_val ) ) {
            return $descuento;
        }
    }

    return 0.0;
}

/**
 * Aplica el descuento por volumen en los artículos del carrito antes de calcular totales.
 *
 * @param WC_Cart $cart_object Instancia del carrito.
 *
 * @return void
 */
function kamasa_aplicar_descuento_volumen_carrito( $cart_object ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    if ( ! $cart_object instanceof WC_Cart ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();

    if ( ! kamasa_es_usuario_b2b( $user_id ) ) {
        return;
    }

    foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
        if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
            continue;
        }

        $product      = $cart_item['data'];
        $product_id   = $product->get_id();
        $quantity     = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;
        $descuento    = kamasa_get_descuento_volumen( $product_id, $quantity );
        $precio_base  = isset( $cart_item['_kamasa_precio_tipo'] )
            ? (float) $cart_item['_kamasa_precio_tipo']
            : (float) $product->get_price();

        if ( ! isset( $cart_item['_kamasa_precio_tipo'] ) ) {
            $cart_object->cart_contents[ $cart_item_key ]['_kamasa_precio_tipo'] = $precio_base;
        } else {
            $product->set_price( $precio_base );
        }

        if ( 0.0 === $descuento ) {
            continue;
        }

        $precio_final = max( 0, $precio_base * ( 1 - ( $descuento / 100 ) ) );

        $product->set_price( $precio_final );
    }
}
add_action( 'woocommerce_before_calculate_totals', 'kamasa_aplicar_descuento_volumen_carrito', 10, 1 );
