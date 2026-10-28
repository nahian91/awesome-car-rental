<?php
/**
 * Delete Customer Component (`customers-delete.php`)
 * Location: admin/customers/customers-delete.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_customers = $wpdb->prefix . 'dev_nahian_customers';
$cust_id = isset( $_GET['cust_id'] ) ? absint( $_GET['cust_id'] ) : 0;
$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

if ( $cust_id > 0 && current_user_can( 'manage_options' ) && wp_verify_nonce( $nonce, 'acrb_del_cust_' . $cust_id ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete( $table_customers, array( 'id' => $cust_id ), array( '%d' ) );
}

wp_safe_redirect( admin_url( 'admin.php?page=awesome_car_rental&tab=customers&sub=list' ) );
exit;