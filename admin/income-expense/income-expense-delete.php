<?php
/**
 * Income & Expense Delete Component (`income-expense-delete.php`)
 * Location: admin/income-expense/income-expense-delete.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_finance = $wpdb->prefix . 'dev_nahian_income_expense';
$entry_id      = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
$page_slug     = 'awesome_car_rental';
$list_url      = admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense' );

if ( $entry_id > 0 && current_user_can( 'manage_options' ) ) {
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

    if ( wp_verify_nonce( $nonce, 'acrb_del_ledger_' . $entry_id ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $table_finance, array( 'id' => $entry_id ), array( '%d' ) );
        delete_transient( 'acrb_dashboard_revenue' );
    }
}

// Use a safe JavaScript redirect to avoid header-sent warnings since HTML output has already started
$target_url = add_query_arg( array( 'page' => $page_slug, 'tab' => 'income_expense', 'deleted' => '1' ), admin_url( 'admin.php' ) );
echo '<script>window.location.href = ' . wp_json_encode( $target_url ) . ';</script>';
exit;