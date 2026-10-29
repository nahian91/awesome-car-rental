<?php
/**
 * Delete Inspection Component (`inspections-delete.php`)
 * Location: admin/inspections/inspections-delete.php
 *
 * @package Awesome_Car_Rental
 * @since   2.6.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$insp_id           = isset( $_GET['insp_id'] ) ? absint( wp_unslash( $_GET['insp_id'] ) ) : 0;
$nonce             = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
$list_url          = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=all' );

if ( $insp_id > 0 && wp_verify_nonce( $nonce, 'acrb_del_insp_' . $insp_id ) ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->delete( $table_inspections, array( 'id' => $insp_id ), array( '%d' ) );

    if ( false !== $deleted ) {
        $list_url = add_query_arg( 'deleted', '1', $list_url );
    } else {
        $list_url = add_query_arg( 'error', 'db', $list_url );
    }
} else {
    $list_url = add_query_arg( 'error', 'nonce', $list_url );
}

// Bypass PHP headers-sent warnings if HTML has already started rendering
if ( headers_sent() ) {
    echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $list_url ) ) . ');</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $list_url ) . '"></noscript>';
    exit;
}

wp_safe_redirect( $list_url );
exit;