<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle Vehicle Deletion (Move to Trash)
 * Action: admin_post_acrb_delete_car
 */
add_action( 'admin_post_acrb_delete_car', 'acrb_handle_car_deletion' );

function acrb_handle_car_deletion() {
    // 1. Check Permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    // 2. Validate Data
    $car_id = isset( $_GET['car'] ) ? absint( $_GET['car'] ) : 0;
    $nonce  = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

    // 3. Security Check
    if ( $car_id <= 0 || ! wp_verify_nonce( $nonce, 'acrb_delete_car_' . $car_id ) ) {
        wp_die( esc_html__( 'Security verification failed. Please try again.', 'awesome-car-rental' ) );
    }

    // 4. Verify Post Type (Safety first)
    if ( 'acrb_cars' !== get_post_type( $car_id ) ) {
        wp_die( esc_html__( 'Invalid vehicle record.', 'awesome-car-rental' ) );
    }

    // 5. Execute Action (Trash the car)
    $trashed = wp_trash_post( $car_id );

    if ( false === $trashed ) {
        wp_die( esc_html__( 'Failed to move the vehicle to the trash.', 'awesome-car-rental' ) );
    }

    // Clear related transient caches
    delete_transient( 'acrb_dashboard_revenue' );

    // 6. Redirect back to the Fleet List
    wp_safe_redirect( admin_url( 'admin.php?page=awesome_car_rental&tab=cars&sub=all&msg=deleted' ) );
    exit;
}