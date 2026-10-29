<?php
/**
 * Staff Delete Component (`staff-delete.php`)
 * Location: admin/staff/staff-delete.php
 *
 * @package Awesome_Car_Rental
 * @since   2.9.7
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$table_staff = $wpdb->prefix . 'dev_nahian_staff';
$staff_id    = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0;
$nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
$list_url    = admin_url( 'admin.php?page=awesome_car_rental&tab=staff&sub=list' );

if ( $staff_id > 0 && wp_verify_nonce( $nonce, 'acrb_del_staff_' . $staff_id ) ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->delete( $table_staff, array( 'id' => $staff_id ), array( '%d' ) );

    if ( false !== $deleted ) {
        $list_url = add_query_arg( 'deleted', '1', $list_url );
    } else {
        $list_url = add_query_arg( 'error', 'db', $list_url );
    }
} else {
    $list_url = add_query_arg( 'error', 'nonce', $list_url );
}

// Bypass PHP headers-sent warnings if output has already started
if ( headers_sent() ) {
    echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $list_url ) ) . ');</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $list_url ) . '"></noscript>';
    exit;
}

wp_safe_redirect( $list_url );
exit;