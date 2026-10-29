<?php
/**
 * Car Delete Component (`cars-delete.php`)
 * Location: admin/cars/cars-delete.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

$car_id    = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
$nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
$page_slug = 'awesome_car_rental';
$list_url  = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars&sub=list' );

if ( $car_id > 0 && wp_verify_nonce( $nonce, 'acrb_del_car_' . $car_id ) ) {
    $target_post = get_post( $car_id );

    if ( $target_post && 'acrb_cars' === $target_post->post_type ) {
        // Force delete bypassing trash
        $deleted = wp_delete_post( $car_id, true );

        if ( $deleted ) {
            delete_transient( 'acrb_dashboard_revenue' );
            $list_url = add_query_arg( 'deleted', '1', $list_url );
        } else {
            $list_url = add_query_arg( 'error', 'db', $list_url );
        }
    } else {
        $list_url = add_query_arg( 'error', 'not_found', $list_url );
    }
} else {
    $list_url = add_query_arg( 'error', 'nonce', $list_url );
}

// Safe redirection handling with headers-sent fallbacks
if ( headers_sent() ) {
    echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $list_url ) ) . ');</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $list_url ) . '"></noscript>';
    exit;
}

wp_safe_redirect( $list_url );
exit;