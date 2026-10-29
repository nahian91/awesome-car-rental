<?php
/**
 * Staff Delete Component (`staff-delete.php`)
 * Location: admin/staff/staff-delete.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_staff = $wpdb->prefix . 'dev_nahian_staff';
$staff_id    = isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0;
$page_slug   = 'awesome_car_rental';

if ( $staff_id > 0 && current_user_can( 'manage_options' ) ) {
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

    if ( wp_verify_nonce( $nonce, 'acrb_del_staff_' . $staff_id ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $table_staff, array( 'id' => $staff_id ), array( '%d' ) );
    }
}

// Use a safe JavaScript redirect to avoid header-sent warnings
$target_url = add_query_arg( array( 'page' => $page_slug, 'tab' => 'staff', 'deleted' => '1' ), admin_url( 'admin.php' ) );
echo '<script>window.location.href = ' . wp_json_encode( $target_url ) . ';</script>';
exit;