<?php
/**
 * Professional SaaS Edit Booking Interface
 */
if (!defined('ABSPATH')) exit;

// Ensure $booking_id is available and valid
$acrb_current_bid = isset($booking_id) ? absint($booking_id) : 0;

if (!$acrb_current_bid || get_post_type($acrb_current_bid) !== 'acrb_bookings') {
    echo '<div class="notice notice-error"><p>' . esc_html__('Invalid Booking Resource.', 'awesome-car-rental') . '</p></div>';
    return;
}

// 1. UPDATE LOGIC
if (isset($_POST['acrb_update_booking'])) {
    if (check_admin_referer('acrb_update_booking_action', 'acrb_update_booking_nonce')) {
        
        // Sanitize and Update Meta
        if ( isset( $_POST['acrb_customer_name'] ) ) {
    update_post_meta( $acrb_current_bid, 'customer_name', sanitize_text_field( wp_unslash( $_POST['acrb_customer_name'] ) ) );
}

if ( isset( $_POST['acrb_customer_phone'] ) ) {
    update_post_meta( $acrb_current_bid, 'customer_phone', sanitize_text_field( wp_unslash( $_POST['acrb_customer_phone'] ) ) );
}

if ( isset( $_POST['acrb_pickup_location'] ) ) {
    update_post_meta( $acrb_current_bid, 'pickup_location', sanitize_text_field( wp_unslash( $_POST['acrb_pickup_location'] ) ) );
}

if ( isset( $_POST['acrb_notes'] ) ) {
    update_post_meta( $acrb_current_bid, 'notes', sanitize_textarea_field( wp_unslash( $_POST['acrb_notes'] ) ) );
}

if ( isset( $_POST['acrb_status'] ) ) {
    $acrb_new_status = sanitize_key( wp_unslash( $_POST['acrb_status'] ) );
    update_post_meta( $acrb_current_bid, 'status', $acrb_new_status );

    // translators: 1: Booking ID, 2: New status name.
    $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_current_bid ), esc_html( $acrb_new_status ) );
    echo wp_kses_post( $acrb_msg );
}
        
        echo '<div class="notice notice-success is-dismissible acrb-notice-brand">
                <p><strong>' . esc_html__('Success:', 'awesome-car-rental') . '</strong> ' . 
                esc_html__('Reservation details synchronized successfully.', 'awesome-car-rental') . '</p>
              </div>';
    }
}

// 2. DATA RETRIEVAL (Prefixed to meet WP standards)
$acrb_edit_name   = get_post_meta($acrb_current_bid, 'customer_name', true);
$acrb_edit_phone  = get_post_meta($acrb_current_bid, 'customer_phone', true);
$acrb_edit_pickup = get_post_meta($acrb_current_bid, 'pickup_location', true);
$acrb_edit_notes  = get_post_meta($acrb_current_bid, 'notes', true);
$acrb_edit_status = get_post_meta($acrb_current_bid, 'status', true) ?: 'pending';

?>

<div class="acrb-saas-edit">
    <div class="acrb-header-bar">
        <div>
            <h1 class="acrb-header-title"><?php esc_html_e('Edit Reservation', 'awesome-car-rental'); ?></h1>
            <p class="acrb-header-sub">
    <?php 
    /* translators: %d: The ID number of the booking */
    printf( esc_html__( 'Modify records for Booking #%d', 'awesome-car-rental' ), absint( $acrb_current_bid ) ); 
    ?>
</p>
        </div>
        <a href="<?php echo esc_url(admin_url('admin.php?page=awesome_car_rental&tab=bookings')); ?>" class="acrb-back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e('Back to Directory', 'awesome-car-rental'); ?>
        </a>
    </div>

    <form method="post" id="acrb-edit-form">
        <?php wp_nonce_field('acrb_update_booking_action', 'acrb_update_booking_nonce'); ?>

        <div class="acrb-grid-layout">
            
            <div class="acrb-main-col">
                <div class="acrb-panel">
                    <div class="acrb-panel-head">
                        <span class="dashicons dashicons-admin-users"></span>
                        <h2><?php esc_html_e('Client Details', 'awesome-car-rental'); ?></h2>
                    </div>
                    <div class="acrb-panel-body">
                        <div class="acrb-form-row">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e('Customer Name', 'awesome-car-rental'); ?></label>
                                <input type="text" name="acrb_customer_name" value="<?php echo esc_attr($acrb_edit_name); ?>" class="acrb-field">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e('Phone Number', 'awesome-car-rental'); ?></label>
                                <input type="text" name="acrb_customer_phone" value="<?php echo esc_attr($acrb_edit_phone); ?>" class="acrb-field">
                            </div>
                        </div>
                        <div class="acrb-form-group mt-20">
                            <label class="acrb-label"><?php esc_html_e('Pick-up Location', 'awesome-car-rental'); ?></label>
                            <input type="text" name="acrb_pickup_location" value="<?php echo esc_attr($acrb_edit_pickup); ?>" class="acrb-field">
                        </div>
                    </div>
                </div>
            </div>

            <div class="acrb-sidebar-col">
                <div class="acrb-sidebar-sticky">
                    <div class="acrb-panel">
                        <div class="acrb-panel-head"><h2><?php esc_html_e('Status Control', 'awesome-car-rental'); ?></h2></div>
                        <div class="acrb-panel-body">
                            <label class="acrb-label"><?php esc_html_e('Booking Status', 'awesome-car-rental'); ?></label>
                            <select name="acrb_status" class="acrb-field mb-20">
                                <option value="pending" <?php selected($acrb_edit_status, 'pending'); ?>><?php esc_html_e('⏳ Pending Request', 'awesome-car-rental'); ?></option>
                                <option value="confirmed" <?php selected($acrb_edit_status, 'confirmed'); ?>><?php esc_html_e('💳 Confirmed', 'awesome-car-rental'); ?></option>
                                <option value="picked_up" <?php selected($acrb_edit_status, 'picked_up'); ?>><?php esc_html_e('🚗 Vehicle Out', 'awesome-car-rental'); ?></option>
                                <option value="returned" <?php selected($acrb_edit_status, 'returned'); ?>><?php esc_html_e('🏁 Returned', 'awesome-car-rental'); ?></option>
                                <option value="cancelled" <?php selected($acrb_edit_status, 'cancelled'); ?>><?php esc_html_e('❌ Cancelled', 'awesome-car-rental'); ?></option>
                            </select>
                            <button type="submit" name="acrb_update_booking" class="acrb-btn-save">
                                <?php esc_html_e('Update Reservation', 'awesome-car-rental'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="acrb-panel">
                        <div class="acrb-panel-head"><h2><?php esc_html_e('Internal Notes', 'awesome-car-rental'); ?></h2></div>
                        <div class="acrb-panel-body">
                            <textarea name="acrb_notes" class="acrb-field" rows="6" placeholder="<?php esc_attr_e('Add internal notes...', 'awesome-car-rental'); ?>"><?php echo esc_textarea($acrb_edit_notes); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>