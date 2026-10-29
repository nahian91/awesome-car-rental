<?php
/**
 * Delete Promo Code Component (`promo-codes-delete.php`)
 * Location: admin/promo-codes/promo-codes-delete.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_promos = $wpdb->prefix . 'dev_nahian_promo_codes';
$promo_id     = isset( $_GET['promo_id'] ) ? absint( $_GET['promo_id'] ) : 0; // Added missing ID extraction
$nonce        = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

if ( $promo_id > 0 && current_user_can( 'manage_options' ) && wp_verify_nonce( $nonce, 'acrb_del_promo_' . $promo_id ) ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->delete( $table_promos, array( 'id' => $promo_id ), array( '%d' ) );
}

$target_url = add_query_arg( array( 'page' => 'awesome_car_rental', 'tab' => 'promo_codes', 'deleted' => '1' ), admin_url( 'admin.php' ) );
echo '<script>window.location.href = ' . wp_json_encode( $target_url ) . ';</script>';
exit;