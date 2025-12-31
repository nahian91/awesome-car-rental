<?php
if(!defined('ABSPATH')) exit;

/**
 * Handle Vehicle Deletion (Move to Trash)
 * Action: admin_post_acrb_delete_car
 */
add_action('admin_post_acrb_delete_car', 'acrb_handle_car_deletion');

function acrb_handle_car_deletion(){
    // 1. Check Permissions
    if (!current_user_can('manage_options')) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    // 2. Validate Data
    $car_id = intval($_GET['car'] ?? 0);
    $nonce  = $_GET['_wpnonce'] ?? '';

    // 3. Security Check
    if (!$car_id || !wp_verify_nonce($nonce, 'acrb_delete_car_' . $car_id)) {
        wp_die( esc_html__( 'Security verification failed. Please try again.', 'awesome-car-rental' ) );
    }

    // 4. Verify Post Type (Safety first)
    if (get_post_type($car_id) !== 'acrb_cars') {
        wp_die( esc_html__( 'Invalid vehicle record.', 'awesome-car-rental' ) );
    }

    // 5. Execute Action (Trash the car)
    wp_trash_post($car_id);

    // 6. Redirect back to the Fleet List
    wp_safe_redirect( admin_url( 'admin.php?page=awesome_car_rental&tab=cars&sub=all&msg=deleted' ) );
exit;
}