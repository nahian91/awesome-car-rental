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

$acrb_car_id    = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
$acrb_nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
$acrb_page_slug = 'awesome_car_rental';
$acrb_list_url  = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=cars&sub=list' );

if ( $acrb_car_id > 0 && wp_verify_nonce( $acrb_nonce, 'acrb_del_car_' . $acrb_car_id ) ) {
	$acrb_target_post = get_post( $acrb_car_id );

	if ( $acrb_target_post && 'acrb_cars' === $acrb_target_post->post_type ) {
		// Force delete bypassing trash.
		$acrb_deleted = wp_delete_post( $acrb_car_id, true );

		if ( $acrb_deleted ) {
			delete_transient( 'acrb_dashboard_revenue' );
			$acrb_list_url = add_query_arg( 'deleted', '1', $acrb_list_url );
		} else {
			$acrb_list_url = add_query_arg( 'error', 'db', $acrb_list_url );
		}
	} else {
		$acrb_list_url = add_query_arg( 'error', 'not_found', $acrb_list_url );
	}
} else {
	$acrb_list_url = add_query_arg( 'error', 'nonce', $acrb_list_url );
}

// Safe redirection handling with headers-sent fallbacks.
if ( headers_sent() ) {
	echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $acrb_list_url ) ) . ');</script>';
	echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $acrb_list_url ) . '"></noscript>';
	exit;
}

wp_safe_redirect( $acrb_list_url );
exit;