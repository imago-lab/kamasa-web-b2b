<?php
/**
 * Plantilla para el panel financiero personalizado de clientes B2B.
 *
 * @package Kamasa_B2B_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="kamasa-panel-financiero">
    <h2><?php esc_html_e( 'Resumen Financiero', 'kamasa-b2b-core' ); ?></h2>

    <div id="kamasa-financiero-error" class="kamasa-panel-financiero__alert" style="display:none;"></div>

    <div class="kamasa-panel-financiero__resumen">
        <div class="kamasa-panel-financiero__card">
            <h3><?php esc_html_e( 'Línea de crédito disponible', 'kamasa-b2b-core' ); ?></h3>
            <div id="kamasa-credito" class="kamasa-panel-financiero__value"><?php esc_html_e( 'Cargando…', 'kamasa-b2b-core' ); ?></div>
        </div>

        <div class="kamasa-panel-financiero__card">
            <h3><?php esc_html_e( 'Saldo vencido', 'kamasa-b2b-core' ); ?></h3>
            <div id="kamasa-saldo-vencido" class="kamasa-panel-financiero__value"><?php esc_html_e( 'Cargando…', 'kamasa-b2b-core' ); ?></div>
        </div>
    </div>

    <div class="kamasa-panel-financiero__facturas">
        <h3><?php esc_html_e( 'Facturas pendientes', 'kamasa-b2b-core' ); ?></h3>
        <div id="kamasa-facturas" class="kamasa-panel-financiero__facturas-list"><?php esc_html_e( 'Cargando…', 'kamasa-b2b-core' ); ?></div>
    </div>

    <hr />

    <section class="kamasa-panel-financiero__asesor">
        <h3><?php esc_html_e( 'Tu asesor asignado', 'kamasa-b2b-core' ); ?></h3>
        <div id="kamasa-asesor-error" class="kamasa-panel-financiero__alert" style="display:none;"></div>
        <ul class="kamasa-panel-financiero__asesor-datos">
            <li><strong><?php esc_html_e( 'Nombre:', 'kamasa-b2b-core' ); ?></strong> <span id="kamasa-asesor-nombre"><?php esc_html_e( 'Cargando…', 'kamasa-b2b-core' ); ?></span></li>
            <li><strong><?php esc_html_e( 'Correo:', 'kamasa-b2b-core' ); ?></strong> <a id="kamasa-asesor-email" href="#"><?php esc_html_e( 'Cargando…', 'kamasa-b2b-core' ); ?></a></li>
            <li><strong><?php esc_html_e( 'Teléfono:', 'kamasa-b2b-core' ); ?></strong> <span id="kamasa-asesor-telefono"><?php esc_html_e( 'Cargando…', 'kamasa-b2b-core' ); ?></span></li>
        </ul>
        <div class="kamasa-panel-financiero__acciones">
            <a id="kamasa-asesor-mailto" class="button" href="#" rel="noopener" style="display:none;">
                <?php esc_html_e( 'Enviar correo a tu asesor', 'kamasa-b2b-core' ); ?>
            </a>
            <a id="kamasa-asesor-whatsapp" class="button" href="#" target="_blank" rel="noopener" style="display:none;">
                <?php esc_html_e( 'Contactar por WhatsApp', 'kamasa-b2b-core' ); ?>
            </a>
        </div>
    </section>
</div>
