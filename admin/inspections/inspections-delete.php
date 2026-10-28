<?php
/**
 * Delete Inspection Component (`inspections-delete.php`)
 * Location: admin/inspections/inspections-delete.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

if ( $insp_id > 0 && current_user_can( 'manage_options' ) && wp_verify_nonce( $nonce, 'acrb_del_insp_' . $insp_id ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete( $table_inspections, array( 'id' => $insp_id ), array( '%d' ) );
}

wp_safe_redirect( admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=all' ) );
exit;