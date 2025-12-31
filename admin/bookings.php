<?php
if (!defined('ABSPATH')) exit;

/**
 * Main Bookings Tab Logic
 */
function acrb_bookings_tab() {

    $action     = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
    $booking_id = isset( $_GET['booking_id'] ) ? intval( $_GET['booking_id'] ) : 0;

    // 1. Verify Nonce ONLY if an action is being performed
    if ( ! empty( $action ) ) {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'acrb_car_action' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'awesome-car-rental' ) );
        }
    }

    // 2. Handle specific actions (e.g., Delete)
    if ( 'delete' === $action && $booking_id ) {
        // Perform deletion logic here...
        
        // translators: 1: Booking ID, 2: New status name.
        $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $booking_id ), esc_html__( 'Deleted', 'awesome-car-rental' ) );
        echo wp_kses_post( $acrb_msg );
    }

    // 3. Switch between different booking management views
    switch ( $action ) {
        case 'edit':
            if ( $booking_id ) {
                require_once ACRB_PATH . 'admin/bookings/acrb-bookings-edit.php';
            }
            break;

        case 'view':
            if ( $booking_id ) {
                require_once ACRB_PATH . 'admin/bookings/acrb-bookings-view.php';
            }
            break;

        case 'print':
            if ( $booking_id ) {
                require_once ACRB_PATH . 'admin/bookings/acrb-bookings-print.php';
            }
            break;

        default:
            require_once ACRB_PATH . 'admin/bookings/acrb-bookings-list.php';
            break;
    }
}