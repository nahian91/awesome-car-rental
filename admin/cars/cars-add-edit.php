<?php
/**
 * Add / Edit Car Component (`cars-add-edit.php`)
 * Location: admin/cars/cars-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_categories = $wpdb->prefix . 'dev_nahian_categories';
$edit_car_id      = isset( $_GET['car_id'] ) ? absint( $_GET['car_id'] ) : 0;
$car              = $edit_car_id ? get_post( $edit_car_id ) : null;

// 1. SYNCHRONIZED DATA LOADING FROM EXTRAS TAB OPTIONS
$global_features     = get_option( 'acrb_custom_features', array() );
$saved_global_extras = get_option( 'acrb_global_addon_extras', get_option( 'acrb_custom_amenities', array() ) );

// Load Pickup Locations from Extras Tab (`acrb_custom_pickup_locations`)
$raw_pickups     = get_option( 'acrb_custom_pickup_locations', get_option( 'acrb_custom_locations', array() ) );
$pickup_locations = array();
if ( is_array( $raw_pickups ) ) {
    foreach ( $raw_pickups as $loc ) {
        $loc_name = is_array( $loc ) ? ( $loc['name'] ?? ( $loc['title'] ?? '' ) ) : (string) $loc;
        if ( ! empty( $loc_name ) ) {
            $pickup_locations[] = $loc_name;
        }
    }
}

// Load Drop-off Locations from Extras Tab (`acrb_custom_dropoff_locations`)
$raw_dropoffs     = get_option( 'acrb_custom_dropoff_locations', get_option( 'acrb_custom_locations', array() ) );
$dropoff_locations = array();
if ( is_array( $raw_dropoffs ) ) {
    foreach ( $raw_dropoffs as $loc ) {
        $loc_name = is_array( $loc ) ? ( $loc['name'] ?? ( $loc['title'] ?? '' ) ) : (string) $loc;
        if ( ! empty( $loc_name ) ) {
            $dropoff_locations[] = $loc_name;
        }
    }
}

// Fallback defaults if no locations are configured in Extras tab yet
if ( empty( $pickup_locations ) ) {
    $pickup_locations = array( 'Main Downtown Branch', 'Airport Terminal Hub', 'City Center Station' );
}
if ( empty( $dropoff_locations ) ) {
    $dropoff_locations = array( 'Main Downtown Branch', 'Airport Terminal Hub', 'City Center Station' );
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$categories_list = $wpdb->get_results( "SELECT * FROM {$table_categories} ORDER BY name ASC", ARRAY_A );
$save_notice = '';

// 2. FORM PROCESSING & SAVING
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['acrb_car_name'] ) ) {
    if ( ! isset( $_POST['acrb_car_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acrb_car_nonce'] ) ), 'acrb_save_car_nonce' ) ) {
        $save_notice = '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security check failed. Please try again.', 'awesome-car-rental' ) . '</p></div>';
    } elseif ( current_user_can( 'manage_options' ) ) {
        $post_args = array(
            'post_type'    => 'acrb_cars',
            'post_title'   => sanitize_text_field( wp_unslash( $_POST['acrb_car_name'] ) ),
            'post_content' => isset( $_POST['acrb_car_desc'] ) ? wp_kses_post( wp_unslash( $_POST['acrb_car_desc'] ) ) : '',
            'post_status'  => 'publish',
        );

        $is_update = (bool) $car;
        if ( $is_update ) {
            $post_args['ID'] = $edit_car_id;
            wp_update_post( $post_args );
        } else {
            $edit_car_id = wp_insert_post( $post_args );
        }

        if ( $edit_car_id && ! is_wp_error( $edit_car_id ) ) {
            update_post_meta( $edit_car_id, '_thumbnail_id', absint( $_POST['acrb_car_thumbnail_id'] ?? 0 ) );
            update_post_meta( $edit_car_id, 'acrb_car_video', esc_url_raw( $_POST['acrb_car_video'] ?? '' ) );
            update_post_meta( $edit_car_id, 'acrb_car_gallery', sanitize_text_field( $_POST['acrb_car_gallery'] ?? '' ) );
            update_post_meta( $edit_car_id, 'price_per_day', floatval( $_POST['acrb_car_price'] ?? 0 ) );
            
            update_post_meta( $edit_car_id, 'acrb_vehicle_status', sanitize_text_field( $_POST['acrb_vehicle_status'] ?? 'active' ) );
            update_post_meta( $edit_car_id, 'acrb_seating_capacity', absint( $_POST['acrb_seating_capacity'] ?? 5 ) );
            update_post_meta( $edit_car_id, 'acrb_baggage_capacity', absint( $_POST['acrb_baggage_capacity'] ?? 2 ) );
            update_post_meta( $edit_car_id, 'acrb_transmission', sanitize_text_field( $_POST['acrb_transmission'] ?? 'Automatic' ) );
            update_post_meta( $edit_car_id, 'acrb_fuel_type', sanitize_text_field( $_POST['acrb_fuel_type'] ?? 'Petrol' ) );
            update_post_meta( $edit_car_id, 'acrb_min_rental_days', absint( $_POST['acrb_min_rental_days'] ?? 1 ) );
            update_post_meta( $edit_car_id, 'acrb_security_deposit', floatval( $_POST['acrb_security_deposit'] ?? 0 ) );
            update_post_meta( $edit_car_id, 'acrb_insurance_fee', floatval( $_POST['acrb_insurance_fee'] ?? 0 ) );

            update_post_meta( $edit_car_id, 'acrb_default_pickup', sanitize_text_field( $_POST['acrb_default_pickup'] ?? '' ) );
            update_post_meta( $edit_car_id, 'acrb_default_dropoff', sanitize_text_field( $_POST['acrb_default_dropoff'] ?? '' ) );
            update_post_meta( $edit_car_id, 'acrb_car_features', map_deep( wp_unslash( $_POST['car_features'] ?? array() ), 'sanitize_text_field' ) );
            update_post_meta( $edit_car_id, 'acrb_car_amenities', map_deep( wp_unslash( $_POST['car_amenities'] ?? array() ), 'sanitize_text_field' ) );
            update_post_meta( $edit_car_id, 'acrb_seasonal_rates', map_deep( wp_unslash( $_POST['seasonal_rates'] ?? array() ), 'sanitize_text_field' ) );

            if ( isset( $_POST['acrb_car_category'] ) ) {
                update_post_meta( $edit_car_id, 'category', sanitize_text_field( wp_unslash( $_POST['acrb_car_category'] ) ) );
            }

            delete_transient( 'acrb_dashboard_revenue' );
            $success_msg = $is_update ? __( 'Vehicle updated successfully.', 'awesome-car-rental' ) : __( 'New vehicle created successfully.', 'awesome-car-rental' );
            $save_notice = '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html( $success_msg ) . '</p></div>';
        }
    }
}

$saved_features    = get_post_meta( $edit_car_id, 'acrb_car_features', true ) ?: array();
$saved_amenities   = get_post_meta( $edit_car_id, 'acrb_car_amenities', true ) ?: array();
$seasonal_rates    = get_post_meta( $edit_car_id, 'acrb_seasonal_rates', true ) ?: array();
$gallery_ids       = get_post_meta( $edit_car_id, 'acrb_car_gallery', true ) ?: '';
$video_url         = get_post_meta( $edit_car_id, 'acrb_car_video', true ) ?: '';
$thumbnail_id      = $edit_car_id ? (int) get_post_meta( $edit_car_id, '_thumbnail_id', true ) : 0;

$vehicle_status    = get_post_meta( $edit_car_id, 'acrb_vehicle_status', true ) ?: 'active';
$seating_cap       = get_post_meta( $edit_car_id, 'acrb_seating_capacity', true ) ?: 5;
$baggage_cap       = get_post_meta( $edit_car_id, 'acrb_baggage_capacity', true ) ?: 2;
$transmission      = get_post_meta( $edit_car_id, 'acrb_transmission', true ) ?: 'Automatic';
$fuel_type         = get_post_meta( $edit_car_id, 'acrb_fuel_type', true ) ?: 'Petrol';
$min_rental_days   = get_post_meta( $edit_car_id, 'acrb_min_rental_days', true ) ?: 1;
$security_deposit  = get_post_meta( $edit_car_id, 'acrb_security_deposit', true ) ?: '';
$insurance_fee     = get_post_meta( $edit_car_id, 'acrb_insurance_fee', true ) ?: '';
$selected_category = get_post_meta( $edit_car_id, 'category', true ) ?: '';
$default_pickup    = get_post_meta( $edit_car_id, 'acrb_default_pickup', true ) ?: '';
$default_dropoff   = get_post_meta( $edit_car_id, 'acrb_default_dropoff', true ) ?: '';
?>

<?php echo $save_notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<div style="max-width: 1000px; margin: 0 auto;">
    <form method="post" id="acrb-car-form">
        <?php wp_nonce_field( 'acrb_save_car_nonce', 'acrb_car_nonce' ); ?>
        
        <!-- Hero Title Box -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);">
            <label class="acrb-label" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px;"><?php esc_html_e( 'Vehicle Model Name', 'awesome-car-rental' ); ?></label>
            <input type="text" name="acrb_car_name" class="acrb-field-hero" value="<?php echo esc_attr( $car ? $car->post_title : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. BMW 5 Series / Tesla Model 3', 'awesome-car-rental' ); ?>" required style="margin-bottom: 0;">
        </div>

        <!-- Section 1: Pricing & Category -->
        <div class="acrb-panel is-open">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-tag" style="color: #4f46e5;"></span> <?php esc_html_e( 'Pricing & Category', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <div class="acrb-grid-2">
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Daily Rental Rate (£)', 'awesome-car-rental' ); ?></label>
                        <input type="number" step="0.01" name="acrb_car_price" class="acrb-input" value="<?php echo esc_attr( get_post_meta( $edit_car_id, 'price_per_day', true ) ); ?>" placeholder="0.00">
                    </div>
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Car Type / Category', 'awesome-car-rental' ); ?></label>
                        <select name="acrb_car_category" class="acrb-select">
                            <option value=""><?php esc_html_e( 'Choose Category...', 'awesome-car-rental' ); ?></option>
                            <?php foreach ( $categories_list as $cat_row ) : ?>
                                <option value="<?php echo esc_attr( $cat_row['name'] ); ?>" <?php selected( $selected_category, $cat_row['name'] ); ?>>
                                    <?php echo esc_html( $cat_row['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Fleet Specifications & Status -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-performance" style="color: #4f46e5;"></span> <?php esc_html_e( 'Fleet Specifications & Status', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <div class="acrb-grid-3" style="margin-bottom: 18px;">
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Availability Status', 'awesome-car-rental' ); ?></label>
                        <select name="acrb_vehicle_status" class="acrb-select">
                            <option value="active" <?php selected( $vehicle_status, 'active' ); ?>><?php esc_html_e( 'Available', 'awesome-car-rental' ); ?></option>
                            <option value="maintenance" <?php selected( $vehicle_status, 'maintenance' ); ?>><?php esc_html_e( 'Under Maintenance', 'awesome-car-rental' ); ?></option>
                            <option value="rented" <?php selected( $vehicle_status, 'rented' ); ?>><?php esc_html_e( 'Rented Out', 'awesome-car-rental' ); ?></option>
                        </select>
                    </div>
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Seating Capacity', 'awesome-car-rental' ); ?></label>
                        <input type="number" name="acrb_seating_capacity" class="acrb-input" value="<?php echo esc_attr( (string) $seating_cap ); ?>" min="1" max="50">
                    </div>
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Baggage Capacity', 'awesome-car-rental' ); ?></label>
                        <input type="number" name="acrb_baggage_capacity" class="acrb-input" value="<?php echo esc_attr( (string) $baggage_cap ); ?>" min="0" max="20">
                    </div>
                </div>

                <div class="acrb-grid-3">
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Transmission Type', 'awesome-car-rental' ); ?></label>
                        <select name="acrb_transmission" class="acrb-select">
                            <option value="Automatic" <?php selected( $transmission, 'Automatic' ); ?>><?php esc_html_e( 'Automatic', 'awesome-car-rental' ); ?></option>
                            <option value="Manual" <?php selected( $transmission, 'Manual' ); ?>><?php esc_html_e( 'Manual', 'awesome-car-rental' ); ?></option>
                        </select>
                    </div>
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Fuel / Engine', 'awesome-car-rental' ); ?></label>
                        <select name="acrb_fuel_type" class="acrb-select">
                            <option value="Petrol" <?php selected( $fuel_type, 'Petrol' ); ?>><?php esc_html_e( 'Petrol', 'awesome-car-rental' ); ?></option>
                            <option value="Diesel" <?php selected( $fuel_type, 'Diesel' ); ?>><?php esc_html_e( 'Diesel', 'awesome-car-rental' ); ?></option>
                            <option value="Electric" <?php selected( $fuel_type, 'Electric' ); ?>><?php esc_html_e( 'Electric', 'awesome-car-rental' ); ?></option>
                            <option value="Hybrid" <?php selected( $fuel_type, 'Hybrid' ); ?>><?php esc_html_e( 'Hybrid', 'awesome-car-rental' ); ?></option>
                        </select>
                    </div>
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Minimum Rental Days', 'awesome-car-rental' ); ?></label>
                        <input type="number" name="acrb_min_rental_days" class="acrb-input" value="<?php echo esc_attr( (string) $min_rental_days ); ?>" min="1">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Pickup & Drop-off Locations (Loaded from Extras Tab) -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-location-alt" style="color: #4f46e5;"></span> <?php esc_html_e( 'Pickup & Drop-off Locations', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <div class="acrb-grid-2">
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Pickup Location', 'awesome-car-rental' ); ?></label>
                        <select name="acrb_default_pickup" class="acrb-select">
                            <option value=""><?php esc_html_e( 'Select Pickup Location', 'awesome-car-rental' ); ?></option>
                            <?php foreach ( $pickup_locations as $loc_name ) : ?>
                                <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $default_pickup, $loc_name ); ?>>
                                    <?php echo esc_html( $loc_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Drop-off Location', 'awesome-car-rental' ); ?></label>
                        <select name="acrb_default_dropoff" class="acrb-select">
                            <option value=""><?php esc_html_e( 'Select Drop-off Location', 'awesome-car-rental' ); ?></option>
                            <?php foreach ( $dropoff_locations as $loc_name ) : ?>
                                <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $default_dropoff, $loc_name ); ?>>
                                    <?php echo esc_html( $loc_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Seasonal Rates -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-calendar-alt" style="color: #4f46e5;"></span> <?php esc_html_e( 'Seasonal Rates', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <p style="font-size: 13px; color: #64748b; margin-top: 0; margin-bottom: 16px;">
                    <?php esc_html_e( 'Define custom date ranges and override daily rates for peak seasons or holidays.', 'awesome-car-rental' ); ?>
                </p>
                <div id="acrb-seasonal-container">
                    <?php if ( ! empty( $seasonal_rates ) && is_array( $seasonal_rates ) ) : ?>
                        <?php foreach ( $seasonal_rates as $index => $rate ) : ?>
                            <div style="display: flex; gap: 12px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 12px;">
                                <input type="text" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][name]" class="acrb-input" placeholder="Season Name" value="<?php echo esc_attr( $rate['name'] ?? '' ); ?>">
                                <input type="date" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][start]" class="acrb-input" value="<?php echo esc_attr( $rate['start'] ?? '' ); ?>">
                                <input type="date" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][end]" class="acrb-input" value="<?php echo esc_attr( $rate['end'] ?? '' ); ?>">
                                <input type="number" step="0.01" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][price]" class="acrb-input" placeholder="Rate (£)" value="<?php echo esc_attr( $rate['price'] ?? '' ); ?>">
                                <button type="button" class="acrb-btn-secondary" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;" onclick="jQuery(this).closest('div').fadeOut(200, function(){jQuery(this).remove();});">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="acrb-btn-secondary" id="acrb-add-seasonal-btn">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                    <?php esc_html_e( 'Add Seasonal Rate', 'awesome-car-rental' ); ?>
                </button>
            </div>
        </div>

        <!-- Section 5: Security Deposit & Insurance Fees -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-shield" style="color: #4f46e5;"></span> <?php esc_html_e( 'Security Deposit & Insurance Fees', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <div class="acrb-grid-2">
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Security Deposit Hold (£)', 'awesome-car-rental' ); ?></label>
                        <input type="number" step="0.01" name="acrb_security_deposit" class="acrb-input" value="<?php echo esc_attr( (string) $security_deposit ); ?>" placeholder="0.00">
                    </div>
                    <div class="acrb-form-group">
                        <label class="acrb-label"><?php esc_html_e( 'Mandatory Insurance Fee (£)', 'awesome-car-rental' ); ?></label>
                        <input type="number" step="0.01" name="acrb_insurance_fee" class="acrb-input" value="<?php echo esc_attr( (string) $insurance_fee ); ?>" placeholder="0.00">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Media & Gallery -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-format-image" style="color: #4f46e5;"></span> <?php esc_html_e( 'Media & Gallery', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <div class="acrb-grid-2" style="margin-bottom: 20px;">
                    <div>
                        <label class="acrb-label"><?php esc_html_e( 'Vehicle Cover Image', 'awesome-car-rental' ); ?></label>
                        <div id="acrb-thumb-preview" style="background:#f8fafc; margin-bottom:12px; border-radius:8px; overflow:hidden; min-height:120px; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center;">
                            <?php 
                            if ( $thumbnail_id ) { 
                                echo wp_get_attachment_image( $thumbnail_id, 'medium', false, array( 'style' => 'width:100%;height:auto;display:block;' ) ); 
                            } else { 
                                echo '<span class="dashicons dashicons-car" style="font-size:40px; width:40px; height:40px; color:#cbd5e1;"></span>'; 
                            } 
                            ?>
                        </div>
                        <input type="hidden" name="acrb_car_thumbnail_id" id="acrb_car_thumbnail_id" value="<?php echo esc_attr( (string) $thumbnail_id ); ?>">
                        <button type="button" class="acrb-btn-secondary" id="acrb_set_thumb"><?php esc_html_e( 'Select Featured Image', 'awesome-car-rental' ); ?></button>
                    </div>
                    <div>
                        <label class="acrb-label"><?php esc_html_e( 'Video URL (YouTube/Vimeo)', 'awesome-car-rental' ); ?></label>
                        <input type="url" name="acrb_car_video" class="acrb-input" style="margin-bottom:20px;" value="<?php echo esc_url( $video_url ); ?>" placeholder="https://...">
                    </div>
                </div>

                <label class="acrb-label"><?php esc_html_e( 'Image Gallery', 'awesome-car-rental' ); ?></label>
                <input type="hidden" name="acrb_car_gallery" id="acrb_gallery_ids" value="<?php echo esc_attr( (string) $gallery_ids ); ?>">
                <button type="button" id="acrb_manage_gallery" class="acrb-btn-secondary" style="margin-bottom:10px;"><?php esc_html_e( 'Add Images to Gallery', 'awesome-car-rental' ); ?></button>
                
                <div id="acrb_gallery_preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 15px;">
                    <?php 
                    if ( $gallery_ids ) { 
                        $ids = explode( ',', $gallery_ids ); 
                        foreach ( $ids as $id ) {
                            $img = wp_get_attachment_image_src( (int) $id, 'thumbnail' );
                            if ( $img ) {
                                echo '<div style="position:relative; aspect-ratio:1; border-radius:8px; overflow:hidden; border:1px solid #ddd;" data-id="' . esc_attr( (string) $id ) . '">
                                    <span style="position:absolute; top:2px; right:2px; background:rgba(220,38,38,0.9); color:#fff; border-radius:50%; width:18px; height:18px; font-size:12px; line-height:18px; text-align:center; cursor:pointer;" onclick="jQuery(this).closest(\'div\').remove(); var ids=[]; jQuery(\'#acrb_gallery_preview > div\').each(function(){ids.push(jQuery(this).data(\'id\'));}); jQuery(\'#acrb_gallery_ids\').val(ids.join(\',\'));">×</span>
                                    <img src="' . esc_url( $img[0] ) . '" style="width:100%; height:100%; object-fit:cover;">
                                </div>';
                            }
                        } 
                    } 
                    ?>
                </div>
            </div>
        </div>

        <!-- Section 7: Global Add-on Extras (Loaded from Extras Tab) -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-star-empty" style="color: #4f46e5;"></span> <?php esc_html_e( 'Global Add-on Extras (Rentables)', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <p style="font-size: 13px; color: #64748b; margin-top: 0; margin-bottom: 16px;">
                    <?php esc_html_e( 'Enable or configure which global add-on extras are available for this specific vehicle.', 'awesome-car-rental' ); ?>
                </p>
                <div class="acrb-grid-2">
                    <?php if ( ! empty( $saved_global_extras ) ) : ?>
                        <?php foreach ( $saved_global_extras as $idx => $g ) : 
                            $en = 1;
                            foreach ( $saved_amenities as $s ) { 
                                if ( ( $s['name'] ?? '' ) === $g['name'] ) { 
                                    $en = $s['enabled'] ?? 0; 
                                    break; 
                                } 
                            }
                        ?>
                            <div style="display:flex; align-items:center; background:#f8fafc; padding:12px 16px; border-radius:10px; border:1px solid #e2e8f0;">
                                <label class="acrb-switch">
                                    <input type="checkbox" name="car_amenities[<?php echo esc_attr( (string) $idx ); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                    <span class="acrb-slider"></span>
                                </label>
                                <span style="font-size:13px; font-weight:600; color:#334155;"><?php echo esc_html( $g['name'] ); ?></span>
                                <input type="hidden" name="car_amenities[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $g['name'] ); ?>">
                                <input type="hidden" name="car_amenities[<?php echo esc_attr( (string) $idx ); ?>][icon]" value="<?php echo esc_attr( $g['icon'] ?? 'dashicons-star-empty' ); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p style="color: #64748b; font-style: italic; margin: 0;"><?php esc_html_e( 'No global add-on extras configured yet in the Extras tab.', 'awesome-car-rental' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section 8: Technical Specifications -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-admin-generic" style="color: #4f46e5;"></span> <?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="acrb-panel-body">
                <div class="acrb-grid-2">
                    <?php if ( ! empty( $global_features ) ) : ?>
                        <?php foreach ( $global_features as $idx => $g ) : 
                            $val = ''; $en = 1;
                            foreach ( $saved_features as $s ) { 
                                if ( ( $s['name'] ?? '' ) === $g['name'] ) { 
                                    $val = $s['value'] ?? ''; 
                                    $en = $s['enabled'] ?? 0; 
                                    break; 
                                } 
                            }
                        ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; padding:12px 16px; border-radius:10px; border:1px solid #e2e8f0;">
                                <div style="display:flex; align-items:center; gap: 12px;">
                                    <label class="acrb-switch">
                                        <input type="checkbox" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                        <span class="acrb-slider"></span>
                                    </label>
                                    <span style="font-size:13px; font-weight:600; color:#334155;"><?php echo esc_html( $g['name'] ); ?></span>
                                    <input type="hidden" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $g['name'] ); ?>">
                                    <input type="hidden" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][icon]" value="<?php echo esc_attr( $g['icon'] ?? 'dashicons-admin-generic' ); ?>">
                                </div>
                                <input type="text" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][value]" class="acrb-input" style="width:85px !important; height:36px !important; text-align:center;" value="<?php echo esc_attr( $val ?: ( $g['value'] ?? '' ) ); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p style="color: #64748b; font-style: italic; margin: 0;"><?php esc_html_e( 'No technical specifications configured yet.', 'awesome-car-rental' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Submit Footer Bar -->
        <div style="margin-top: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px 24px; display: flex; justify-content: flex-end; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);">
            <button type="submit" class="acrb-btn-primary" style="height: 44px; padding: 0 32px; font-size: 14px;">
                <span class="dashicons dashicons-saved" style="font-size:17px; width:17px; height:17px;"></span>
                <span><?php esc_html_e( 'Save Vehicle Changes', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($){
    // Accordion Toggle Behavior
    $('.acrb-panel-head').click(function(){
        $(this).parent('.acrb-panel').toggleClass('is-open');
    });

    // Dynamic Seasonal Rate Rows
    $('#acrb-add-seasonal-btn').click(function(e){
        e.preventDefault();
        var index = $('#acrb-seasonal-container > div').length;
        var newRow = '<div style="display: flex; gap: 12px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 12px;">' +
            '<input type="text" name="seasonal_rates['+index+'][name]" class="acrb-input" placeholder="Season Name">' +
            '<input type="date" name="seasonal_rates['+index+'][start]" class="acrb-input">' +
            '<input type="date" name="seasonal_rates['+index+'][end]" class="acrb-input">' +
            '<input type="number" step="0.01" name="seasonal_rates['+index+'][price]" class="acrb-input" placeholder="Rate (£)">' +
            '<button type="button" class="acrb-btn-secondary" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;" onclick="jQuery(this).closest(\'div\').fadeOut(200, function(){jQuery(this).remove();});">&times;</button>' +
            '</div>';
        $('#acrb-seasonal-container').append(newRow);
    });

    // Thumbnail Selection
    $('#acrb_set_thumb').click(function(e){
        e.preventDefault();
        var frame = wp.media({ title: 'Select Cover Image', button: { text: 'Use Image' }, multiple: false });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#acrb_car_thumbnail_id').val(attachment.id);
            $('#acrb-thumb-preview').html('<img src="'+attachment.url+'" style="width:100%;height:auto;display:block;">');
        }).open();
    });

    // Gallery Management
    var galleryFrame;
    $('#acrb_manage_gallery').click(function(e){
        e.preventDefault();
        if(galleryFrame) { galleryFrame.open(); return; }

        galleryFrame = wp.media({ title: 'Add to Gallery', button: { text: 'Add to Gallery' }, multiple: true });
        galleryFrame.on('select', function(){
            var selection = galleryFrame.state().get('selection');
            var currentIds = $('#acrb_gallery_ids').val() ? $('#acrb_gallery_ids').val().split(',') : [];
            
            selection.map(function(attachment){
                attachment = attachment.toJSON();
                if($.inArray(attachment.id.toString(), currentIds) === -1){
                    currentIds.push(attachment.id);
                    $('#acrb_gallery_preview').append(
                        '<div style="position:relative; aspect-ratio:1; border-radius:8px; overflow:hidden; border:1px solid #ddd;" data-id="'+attachment.id+'">' +
                        '<span style="position:absolute; top:2px; right:2px; background:rgba(220,38,38,0.9); color:#fff; border-radius:50%; width:18px; height:18px; font-size:12px; line-height:18px; text-align:center; cursor:pointer;" onclick="jQuery(this).closest(\'div\').remove(); var ids=[]; jQuery(\'#acrb_gallery_preview > div\').each(function(){ids.push(jQuery(this).data(\'id\'));}); jQuery(\'#acrb_gallery_ids\').val(ids.join(\',\'));">×</span>' +
                        '<img src="'+attachment.sizes.thumbnail.url+'" style="width:100%; height:100%; object-fit:cover;">' +
                        '</div>'
                    );
                }
            });
            $('#acrb_gallery_ids').val(currentIds.join(','));
        });
        galleryFrame.open();
    });
});
</script>