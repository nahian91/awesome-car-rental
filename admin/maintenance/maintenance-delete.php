<?php
/**
 * Maintenance Delete Component (`maintenance-delete.php`)
 * Location: admin/maintenance/maintenance-delete.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.9
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$acrb_table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
$acrb_maint_id          = isset( $_GET['maint_id'] ) ? absint( wp_unslash( $_GET['maint_id'] ) ) : 0;
$acrb_nonce             = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
$acrb_list_url          = admin_url( 'admin.php?page=awesome_car_rental&tab=maintenance' );

if ( $acrb_maint_id > 0 && wp_verify_nonce( $acrb_nonce, 'acrb_del_maint_' . $acrb_maint_id ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_deleted = $wpdb->delete( $acrb_table_maintenance, array( 'id' => $acrb_maint_id ), array( '%d' ) );

	if ( false !== $acrb_deleted ) {
		$acrb_list_url = add_query_arg( 'deleted', '1', $acrb_list_url );
	} else {
		$acrb_list_url = add_query_arg( 'error', 'db', $acrb_list_url );
	}
} else {
	$acrb_list_url = add_query_arg( 'error', 'nonce', $acrb_list_url );
}

// Check if headers have already been sent to avoid PHP warnings.
if ( headers_sent() ) {
	echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $acrb_list_url ) ) . ');</script>';
	echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $acrb_list_url ) . '"></noscript>';
	exit;
}

wp_safe_redirect( $acrb_list_url );
exit;