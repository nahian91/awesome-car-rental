<?php
/**
 * Car Delete Component (`cars-delete.php`)
 * Location: admin/cars/cars-delete.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$car_id    = isset( $_GET['car_id'] ) ? absint( $_GET['car_id'] ) : 0;
$page_slug = 'awesome_car_rental';
$list_url  = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars' );

if ( $car_id > 0 && current_user_can( 'manage_options' ) ) {
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

    if ( wp_verify_nonce( $nonce, 'acrb_del_car_' . $car_id ) ) {
        // Clear buffer so any prior notice/warning won't block headers
        if ( ob_get_level() ) {
            @ob_end_clean();
        }

        wp_delete_post( $car_id, true );

        if ( ! headers_sent() ) {
            wp_safe_redirect( $list_url );
            exit;
        }
    }
}

if ( ! headers_sent() ) {
    wp_safe_redirect( $list_url );
    exit;
}