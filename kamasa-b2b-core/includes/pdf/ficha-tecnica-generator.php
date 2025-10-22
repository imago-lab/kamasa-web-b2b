<?php
/**
 * Generador dinámico de fichas técnicas en PDF.
 *
 * @package Kamasa_B2B_Core
 */

defined( 'ABSPATH' ) || exit;

// Asegura que la librería TCPDF esté disponible.
if ( ! class_exists( 'TCPDF' ) ) {
    $tcpdf_autoload = KAMASA_B2B_PLUGIN_DIR . 'vendor/autoload.php';

    if ( file_exists( $tcpdf_autoload ) ) {
        require_once $tcpdf_autoload;
    }
}

if ( ! class_exists( 'TCPDF' ) ) {
    $tcpdf_fallback = KAMASA_B2B_PLUGIN_DIR . 'includes/lib/tcpdf_min/tcpdf.php';

    if ( file_exists( $tcpdf_fallback ) ) {
        require_once $tcpdf_fallback;
    }
}

/**
 * Escucha el parámetro de generación y produce el PDF cuando corresponde.
 *
 * @return void
 */
function kamasa_generar_ficha_tecnica_pdf() {
    if ( empty( $_GET['generar_ficha_tecnica'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    $product_id = absint( wp_unslash( $_GET['generar_ficha_tecnica'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( ! $product_id ) {
        wp_die( esc_html__( 'Producto no válido.', 'kamasa-b2b-core' ) );
    }

    if ( ! is_user_logged_in() ) {
        wp_die( esc_html__( 'Acceso no autorizado.', 'kamasa-b2b-core' ) );
    }

    if ( ! function_exists( 'kamasa_es_usuario_b2b' ) || ! kamasa_es_usuario_b2b( get_current_user_id() ) ) {
        wp_die( esc_html__( 'Acceso restringido para usuarios B2B.', 'kamasa-b2b-core' ) );
    }

    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        wp_die( esc_html__( 'Producto no encontrado.', 'kamasa-b2b-core' ) );
    }

    if ( ! class_exists( 'TCPDF' ) ) {
        wp_die( esc_html__( 'La librería TCPDF no está disponible.', 'kamasa-b2b-core' ) );
    }

    nocache_headers();

    kamasa_crear_pdf_ficha_tecnica( $product );

    exit;
}
add_action( 'template_redirect', 'kamasa_generar_ficha_tecnica_pdf' );

/**
 * Construye y envía al navegador la ficha técnica en PDF de un producto.
 *
 * @param WC_Product $product Producto de WooCommerce.
 *
 * @return void
 */
function kamasa_crear_pdf_ficha_tecnica( $product ) {
    if ( ! $product instanceof WC_Product ) {
        wp_die( esc_html__( 'Producto inválido.', 'kamasa-b2b-core' ) );
    }

    if ( ! class_exists( 'TCPDF' ) ) {
        wp_die( esc_html__( 'La librería TCPDF no está disponible.', 'kamasa-b2b-core' ) );
    }

    $product_name  = $product->get_name();
    $product_sku   = $product->get_sku();
    $short_desc    = $product->get_short_description();
    $image_id      = $product->get_image_id();
    $image_path    = $image_id ? get_attached_file( $image_id ) : '';
    $image_url     = $image_id ? wp_get_attachment_image_url( $image_id, 'medium_large' ) : '';
    $attributes    = $product->get_attributes();
    $attribute_rows = '';

    if ( ! empty( $attributes ) ) {
        foreach ( $attributes as $attribute ) {
            if ( ! $attribute instanceof WC_Product_Attribute ) {
                continue;
            }

            $attribute_name  = $attribute->get_name();
            $attribute_label = wc_attribute_label( $attribute_name, $product );
            $attribute_value = $product->get_attribute( $attribute_name );

            if ( '' === $attribute_value ) {
                continue;
            }

            $attribute_rows .= sprintf(
                '<tr><td style="border:1px solid #ddd; width:40%%;"><strong>%1$s</strong></td><td style="border:1px solid #ddd;">%2$s</td></tr>',
                esc_html( $attribute_label ),
                esc_html( wp_strip_all_tags( $attribute_value ) )
            );
        }
    }

    $pdf = new TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );
    $pdf->SetCreator( get_bloginfo( 'name', 'display' ) );
    $pdf->SetAuthor( __( 'Grupo Kamasa', 'kamasa-b2b-core' ) );
    $pdf->SetTitle( sprintf( __( 'Ficha Técnica - %s', 'kamasa-b2b-core' ), wp_strip_all_tags( $product_name ) ) );
    $pdf->SetSubject( __( 'Ficha técnica de producto', 'kamasa-b2b-core' ) );
    $pdf->SetMargins( 15, 20, 15 );
    $pdf->SetAutoPageBreak( true, 20 );
    $pdf->AddPage();

    $pdf->SetFont( 'helvetica', 'B', 18 );
    $pdf->Cell( 0, 12, wp_strip_all_tags( $product_name ), 0, 1, 'C' );

    if ( $product_sku ) {
        $pdf->SetFont( 'helvetica', '', 10 );
        $pdf->Cell( 0, 6, sprintf( __( 'SKU: %s', 'kamasa-b2b-core' ), $product_sku ), 0, 1, 'C' );
    }

    $pdf->Ln( 5 );

    if ( $image_path && file_exists( $image_path ) ) {
        $pdf->Image( $image_path, '', '', 80, 80, '', '', '', true, 300, 'C', false, false, 0, false, false, false );
        $pdf->Ln( 5 );
    } elseif ( $image_url ) {
        $pdf->Image( $image_url, '', '', 80, 80, '', '', '', true, 300, 'C', false, false, 0, false, false, false );
        $pdf->Ln( 5 );
    }

    if ( $short_desc ) {
        $pdf->SetFont( 'helvetica', '', 11 );
        $pdf->writeHTML( wpautop( wp_kses_post( $short_desc ) ), true, false, true, false, '' );
        $pdf->Ln( 3 );
    }

    $pdf->SetFont( 'helvetica', 'B', 14 );
    $pdf->Cell( 0, 8, __( 'Características Técnicas', 'kamasa-b2b-core' ), 0, 1, 'L' );

    if ( $attribute_rows ) {
        $pdf->SetFont( 'helvetica', '', 10 );
        $table_html  = '<table style="width:100%; border-collapse:collapse;" cellpadding="6">';
        $table_html .= '<thead><tr style="background-color:#f5f5f5;"><th style="border:1px solid #ddd; text-align:left;">' . esc_html__( 'Atributo', 'kamasa-b2b-core' ) . '</th><th style="border:1px solid #ddd; text-align:left;">' . esc_html__( 'Valor', 'kamasa-b2b-core' ) . '</th></tr></thead>';
        $table_html .= '<tbody>' . $attribute_rows . '</tbody>';
        $table_html .= '</table>';

        $pdf->writeHTML( $table_html, true, false, true, false, '' );
    } else {
        $pdf->SetFont( 'helvetica', '', 10 );
        $pdf->MultiCell( 0, 6, __( 'Este producto no tiene atributos técnicos registrados.', 'kamasa-b2b-core' ), 0, 'L' );
    }

    $pdf->Ln( 6 );

    $pdf->SetFont( 'helvetica', 'I', 8 );
    $pdf->MultiCell( 0, 6, __( 'Documento generado automáticamente por el portal B2B de Kamasa.', 'kamasa-b2b-core' ), 0, 'C' );

    $filename = sprintf( 'ficha-tecnica-%s.pdf', sanitize_title( $product_name ) );

    $pdf->Output( $filename, 'I' );
}
