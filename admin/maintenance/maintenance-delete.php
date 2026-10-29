<?php
/**
 * Maintenance Delete Component (`maintenance-delete.php`)
 * Location: admin/maintenance/maintenance-delete.php
 *
 * @package Awesome_Car_Rental
 * @since   1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
$maint_id          = isset( $_GET['maint_id'] ) ? absint( $_GET['maint_id'] ) : 0;
$page_slug         = 'awesome_car_rental';
$list_url          = admin_url( 'admin.php?page=' . $page_slug . '&tab=maintenance' );

if ( $maint_id > 0 && current_user_can( 'manage_options' ) ) {
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

    if ( wp_verify_nonce( $nonce, 'acrb_del_maint_' . $maint_id ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $table_maintenance, array( 'id' => $maint_id ), array( '%d' ) );
        echo '<div class="notice notice-warning is-dismissible" style="margin:0 0 20px;"><p><strong>' . esc_html__( 'Notice:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Maintenance record deleted successfully.', 'awesome-car-rental' ) . '</p></div>';
    }
}

// Automatically redirect back to the maintenance list directory
wp_safe_redirect( $list_url );
exit;