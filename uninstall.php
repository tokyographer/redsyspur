<?php 

/**
 * NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE
 *
 * El uso de este software está sujeto a las Condiciones de uso de software que
 * se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
 * obtener una copia en la siguiente url:
 * http://www.redsys.es/wps/portal/redsys/publica/areadeserviciosweb/descargaDeDocumentacionYEjecutables
 *
 * Redsys es titular de todos los derechos de propiedad intelectual e industrial
 * del software.
 *
 * Quedan expresamente prohibidas la reproducción, la distribución y la
 * comunicación pública, incluida su modalidad de puesta a disposición con fines
 * distintos a los descritos en las Condiciones de uso.
 *
 * Redsys se reserva la posibilidad de ejercer las acciones legales que le
 * correspondan para hacer valer sus derechos frente a cualquier infracción de
 * los derechos de propiedad intelectual y/o industrial.
 *
 * Redsys Servicios de Procesamiento, S.L., CIF B85955367
 */

/** Si el proceso no fue llamado por Wordpress, salimos. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

/** Para sentencias SQL */
global $wpdb;

/** Conjunto de opciones creadas por el módulo. */
$options = array(
    'redsys_bizum_settings',
    'redsys_insite_settings',
    'redsys_settings',
);

/** Conjunto de tablas creadas por el módulo */
$tables = array(
    'redsys_reference',
    'redsys_order',
);

/** Ejecución de la limpieza, primero opciones, luego tablas. */
foreach ($options as $option) {
    delete_option('woocommerce_' . $option);
}

foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}