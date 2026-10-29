<?php
/**
 * Add / Edit Car Component (`cars-add-edit.php`)
 * Location: admin/cars/cars-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$table_categories = $wpdb->prefix . 'dev_nahian_categories';
$edit_car_id      = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$car              = $edit_car_id ? get_post( $edit_car_id ) : null;
$currency_symbol  = get_option( 'acrb_currency', '$' );
$list_url         = admin_url( 'admin.php?page=awesome_car_rental&tab=cars' );
$error_notice     = '';

// 1. Synchronized Data Loading from Extras Options
$global_features     = get_option( 'acrb_custom_features', array() );
$saved_global_extras = get_option( 'acrb_global_addon_extras', get_option( 'acrb_custom_amenities', array() ) );

// Load Pickup Locations
$raw_pickups      = get_option( 'acrb_custom_pickup_locations', get_option( 'acrb_custom_locations', array() ) );
$pickup_locations = array();
if ( is_array( $raw_pickups ) ) {
    foreach ( $raw_pickups as $loc ) {
        $loc_name = is_array( $loc ) ? ( $loc['name'] ?? ( $loc['title'] ?? '' ) ) : (string) $loc;
        if ( ! empty( $loc_name ) ) {
            $pickup_locations[] = $loc_name;
        }
    }
}

// Load Drop-off Locations
$raw_dropoffs      = get_option( 'acrb_custom_dropoff_locations', get_option( 'acrb_custom_locations', array() ) );
$dropoff_locations = array();
if ( is_array( $raw_dropoffs ) ) {
    foreach ( $raw_dropoffs as $loc ) {
        $loc_name = is_array( $loc ) ? ( $loc['name'] ?? ( $loc['title'] ?? '' ) ) : (string) $loc;
        if ( ! empty( $loc_name ) ) {
            $dropoff_locations[] = $loc_name;
        }
    }
}

// Fallback Location Defaults
if ( empty( $pickup_locations ) ) {
    $pickup_locations = array( 'Main Downtown Branch', 'Airport Terminal Hub', 'City Center Station' );
}
if ( empty( $dropoff_locations ) ) {
    $dropoff_locations = array( 'Main Downtown Branch', 'Airport Terminal Hub', 'City Center Station' );
}

// Fetch Taxonomy / Database Categories
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$categories_list = $wpdb->get_results( "SELECT * FROM {$table_categories} ORDER BY name ASC", ARRAY_A );

// 2. Form Processing & Safe Redirect
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['acrb_car_name'] ) ) {
    if ( ! isset( $_POST['acrb_car_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acrb_car_nonce'] ) ), 'acrb_save_car_nonce' ) ) {
        $error_notice = __( 'Security check failed. Please try again.', 'awesome-car-rental' );
    } else {
        $car_title = sanitize_text_field( wp_unslash( $_POST['acrb_car_name'] ) );

        if ( empty( $car_title ) ) {
            $error_notice = __( 'Please specify a vehicle model name.', 'awesome-car-rental' );
        } else {
            $post_args = array(
                'post_type'    => 'acrb_cars',
                'post_title'   => $car_title,
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
                update_post_meta( $edit_car_id, 'acrb_car_video', esc_url_raw( wp_unslash( $_POST['acrb_car_video'] ?? '' ) ) );
                update_post_meta( $edit_car_id, 'acrb_car_gallery', sanitize_text_field( wp_unslash( $_POST['acrb_car_gallery'] ?? '' ) ) );
                update_post_meta( $edit_car_id, 'price_per_day', floatval( wp_unslash( $_POST['acrb_car_price'] ?? 0 ) ) );
                
                update_post_meta( $edit_car_id, 'acrb_vehicle_status', sanitize_key( wp_unslash( $_POST['acrb_vehicle_status'] ?? 'active' ) ) );
                update_post_meta( $edit_car_id, 'acrb_seating_capacity', max( 1, absint( $_POST['acrb_seating_capacity'] ?? 5 ) ) );
                update_post_meta( $edit_car_id, 'acrb_baggage_capacity', absint( $_POST['acrb_baggage_capacity'] ?? 2 ) );
                update_post_meta( $edit_car_id, 'acrb_transmission', sanitize_text_field( wp_unslash( $_POST['acrb_transmission'] ?? 'Automatic' ) ) );
                update_post_meta( $edit_car_id, 'acrb_fuel_type', sanitize_text_field( wp_unslash( $_POST['acrb_fuel_type'] ?? 'Petrol' ) ) );
                update_post_meta( $edit_car_id, 'acrb_min_rental_days', max( 1, absint( $_POST['acrb_min_rental_days'] ?? 1 ) ) );
                update_post_meta( $edit_car_id, 'acrb_security_deposit', floatval( wp_unslash( $_POST['acrb_security_deposit'] ?? 0 ) ) );
                update_post_meta( $edit_car_id, 'acrb_insurance_fee', floatval( wp_unslash( $_POST['acrb_insurance_fee'] ?? 0 ) ) );

                update_post_meta( $edit_car_id, 'acrb_default_pickup', sanitize_text_field( wp_unslash( $_POST['acrb_default_pickup'] ?? '' ) ) );
                update_post_meta( $edit_car_id, 'acrb_default_dropoff', sanitize_text_field( wp_unslash( $_POST['acrb_default_dropoff'] ?? '' ) ) );
                update_post_meta( $edit_car_id, 'acrb_car_features', map_deep( wp_unslash( $_POST['car_features'] ?? array() ), 'sanitize_text_field' ) );
                update_post_meta( $edit_car_id, 'acrb_car_amenities', map_deep( wp_unslash( $_POST['car_amenities'] ?? array() ), 'sanitize_text_field' ) );
                update_post_meta( $edit_car_id, 'acrb_seasonal_rates', map_deep( wp_unslash( $_POST['seasonal_rates'] ?? array() ), 'sanitize_text_field' ) );

                if ( isset( $_POST['acrb_car_category'] ) ) {
                    update_post_meta( $edit_car_id, 'category', sanitize_text_field( wp_unslash( $_POST['acrb_car_category'] ) ) );
                }

                delete_transient( 'acrb_dashboard_revenue' );

                $redirect_url = add_query_arg(
                    array(
                        $is_update ? 'updated' : 'created' => '1',
                        'car_id'                           => $edit_car_id,
                    ),
                    $list_url
                );

                if ( headers_sent() ) {
                    echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
                    echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
                    exit;
                }

                wp_safe_redirect( $redirect_url );
                exit;
            }
        }
    }
}

// 3. Stored Meta Values
$saved_features    = get_post_meta( $edit_car_id, 'acrb_car_features', true ) ?: array();
$saved_amenities   = get_post_meta( $edit_car_id, 'acrb_car_amenities', true ) ?: array();
$seasonal_rates    = get_post_meta( $edit_car_id, 'acrb_seasonal_rates', true ) ?: array();
$gallery_ids       = (string) ( get_post_meta( $edit_car_id, 'acrb_car_gallery', true ) ?: '' );
$video_url         = (string) ( get_post_meta( $edit_car_id, 'acrb_car_video', true ) ?: '' );
$thumbnail_id      = $edit_car_id ? (int) get_post_meta( $edit_car_id, '_thumbnail_id', true ) : 0;

$vehicle_status    = (string) ( get_post_meta( $edit_car_id, 'acrb_vehicle_status', true ) ?: 'active' );
$seating_cap       = get_post_meta( $edit_car_id, 'acrb_seating_capacity', true ) ?: 5;
$baggage_cap       = get_post_meta( $edit_car_id, 'acrb_baggage_capacity', true ) ?: 2;
$transmission      = (string) ( get_post_meta( $edit_car_id, 'acrb_transmission', true ) ?: 'Automatic' );
$fuel_type         = (string) ( get_post_meta( $edit_car_id, 'acrb_fuel_type', true ) ?: 'Petrol' );
$min_rental_days   = get_post_meta( $edit_car_id, 'acrb_min_rental_days', true ) ?: 1;
$security_deposit  = get_post_meta( $edit_car_id, 'acrb_security_deposit', true ) ?: '';
$insurance_fee     = get_post_meta( $edit_car_id, 'acrb_insurance_fee', true ) ?: '';
$selected_category = (string) ( get_post_meta( $edit_car_id, 'category', true ) ?: '' );
$default_pickup    = (string) ( get_post_meta( $edit_car_id, 'acrb_default_pickup', true ) ?: '' );
$default_dropoff   = (string) ( get_post_meta( $edit_car_id, 'acrb_default_dropoff', true ) ?: '' );
?>

<style>
    .acrb-car-form-wrapper {
        max-width: 960px;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #1e293b;
    }
    .acrb-car-form-wrapper *,
    .acrb-car-form-wrapper *::before,
    .acrb-car-form-wrapper *::after {
        box-sizing: border-box;
    }

    /* Error Alert */
    .acrb-car-alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 12px 18px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Hero Model Input Card */
    .acrb-car-hero-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px 24px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .acrb-car-hero-label {
        display: block;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.06em;
        font-weight: 800;
        color: #64748b;
        margin-bottom: 8px;
    }
    .acrb-car-field-hero {
        width: 100% !important;
        font-size: 18px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        height: 48px !important;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        padding: 0 16px !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
    }
    .acrb-car-field-hero:focus {
        background: #ffffff !important;
        border-color: #4f46e5 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    }

    /* Accordion Panels */
    .acrb-car-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        margin-bottom: 16px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        overflow: hidden;
        transition: border-color 0.15s ease;
    }
    .acrb-car-panel:hover {
        border-color: #cbd5e1;
    }
    .acrb-car-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 22px;
        background: #ffffff;
        cursor: pointer;
        user-select: none;
        border-bottom: 1px solid transparent;
        transition: background-color 0.15s ease;
    }
    .acrb-car-panel-head:hover {
        background: #f8fafc;
    }
    .acrb-car-panel-head-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .acrb-car-panel-head-title .dashicons {
        color: #4f46e5;
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    .acrb-car-panel-chevron {
        color: #64748b;
        font-size: 18px;
        width: 18px;
        height: 18px;
        transition: transform 0.2s ease;
    }
    .acrb-car-panel.is-open .acrb-car-panel-chevron {
        transform: rotate(180deg);
    }
    .acrb-car-panel.is-open .acrb-car-panel-head {
        border-bottom-color: #f1f5f9;
        background: #fafbff;
    }
    .acrb-car-panel-body {
        padding: 22px;
        display: none;
    }
    .acrb-car-panel.is-open .acrb-car-panel-body {
        display: block;
    }

    /* Grids & Form Controls */
    .acrb-car-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }
    .acrb-car-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .acrb-car-form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 16px;
    }
    .acrb-car-form-group:last-child {
        margin-bottom: 0;
    }
    .acrb-car-label {
        display: block;
        font-weight: 700;
        color: #1e293b;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .acrb-car-input,
    .acrb-car-select {
        display: block;
        width: 100% !important;
        max-width: 100% !important;
        height: 42px !important;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0 14px !important;
        font-size: 13.5px !important;
        font-family: inherit !important;
        color: #1e293b !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
        line-height: 40px !important;
    }
    .acrb-car-input:hover,
    .acrb-car-select:hover {
        border-color: #94a3b8 !important;
        background: #ffffff !important;
    }
    .acrb-car-input:focus,
    .acrb-car-select:focus {
        background: #ffffff !important;
        border-color: #4f46e5 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    }

    /* Select Chevron Wrap */
    .acrb-car-select-wrap {
        position: relative;
        width: 100%;
    }
    .acrb-car-select-wrap select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 36px !important;
        cursor: pointer;
    }
    .acrb-car-select-arrow {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #64748b;
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    /* Seasonal Rate Rows */
    .acrb-seasonal-row {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr 40px;
        gap: 10px;
        align-items: center;
        background: #f8fafc;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        margin-bottom: 12px;
    }
    .acrb-btn-remove-row {
        height: 42px;
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
        transition: background 0.15s ease;
    }
    .acrb-btn-remove-row:hover {
        background: #fca5a5;
    }

    /* Cover & Gallery Previews */
    .acrb-thumb-box {
        background: #f8fafc;
        margin-bottom: 12px;
        border-radius: 10px;
        overflow: hidden;
        min-height: 150px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .acrb-thumb-box img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: cover;
    }
    .acrb-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .acrb-gallery-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
    }
    .acrb-gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .acrb-gallery-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(220, 38, 38, 0.9);
        color: #ffffff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 13px;
        line-height: 20px;
        text-align: center;
        cursor: pointer;
        font-weight: 800;
        transition: transform 0.1s ease;
    }
    .acrb-gallery-remove:hover {
        transform: scale(1.1);
    }

    /* Switch Component */
    .acrb-toggle-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        transition: border-color 0.15s ease;
    }
    .acrb-toggle-card:hover {
        border-color: #cbd5e1;
    }
    .acrb-toggle-card-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .acrb-switch-ui {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .acrb-switch-ui input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .acrb-switch-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #cbd5e1;
        transition: .25s ease;
        border-radius: 24px;
    }
    .acrb-switch-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .25s ease;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    input:checked + .acrb-switch-slider {
        background-color: #4f46e5;
    }
    input:checked + .acrb-switch-slider:before {
        transform: translateX(20px);
    }

    /* Buttons */
    .acrb-btn-secondary {
        background: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .acrb-btn-secondary:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #94a3b8;
    }
    .acrb-car-footer-actions {
        margin-top: 24px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 24px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    }
    .acrb-car-btn-submit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 12px 28px;
        font-size: 13.5px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        transition: background 0.15s ease, transform 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .acrb-car-btn-submit:hover {
        background: #4338ca;
        transform: translateY(-1px);
    }
    .acrb-car-btn-cancel {
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 14px;
        border-radius: 8px;
        transition: color 0.15s ease;
    }
    .acrb-car-btn-cancel:hover {
        color: #0f172a;
    }

    @media (max-width: 820px) {
        .acrb-car-grid-2,
        .acrb-car-grid-3 {
            grid-template-columns: 1fr;
        }
        .acrb-seasonal-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="acrb-car-form-wrapper">

    <?php if ( ! empty( $error_notice ) ) : ?>
        <div class="acrb-car-alert-error">
            <span class="dashicons dashicons-warning"></span>
            <span><?php echo esc_html( $error_notice ); ?></span>
        </div>
    <?php endif; ?>

    <form method="post" id="acrb-car-form">
        <?php wp_nonce_field( 'acrb_save_car_nonce', 'acrb_car_nonce' ); ?>

        <!-- Hero Title Box -->
        <div class="acrb-car-hero-card">
            <label class="acrb-car-hero-label" for="acrb_car_name"><?php esc_html_e( 'Vehicle Model Name', 'awesome-car-rental' ); ?></label>
            <input type="text" id="acrb_car_name" name="acrb_car_name" class="acrb-car-field-hero" value="<?php echo esc_attr( $car ? $car->post_title : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. BMW 5 Series / Tesla Model 3', 'awesome-car-rental' ); ?>" required>
        </div>

        <!-- Section 1: Pricing & Category -->
        <div class="acrb-car-panel is-open">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-tag"></span>
                    <span><?php esc_html_e( 'Pricing & Category', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <div class="acrb-car-grid-2">
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_car_price">
                            <?php printf( esc_html__( 'Base Daily Rental Rate (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?>
                        </label>
                        <input type="number" step="0.01" min="0" id="acrb_car_price" name="acrb_car_price" class="acrb-car-input" value="<?php echo esc_attr( (string) get_post_meta( $edit_car_id, 'price_per_day', true ) ); ?>" placeholder="0.00" required>
                    </div>
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_car_category"><?php esc_html_e( 'Vehicle Class / Category', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-car-select-wrap">
                            <select id="acrb_car_category" name="acrb_car_category" class="acrb-car-select">
                                <option value=""><?php esc_html_e( 'Choose Category...', 'awesome-car-rental' ); ?></option>
                                <?php foreach ( $categories_list as $cat_row ) : ?>
                                    <option value="<?php echo esc_attr( $cat_row['name'] ); ?>" <?php selected( $selected_category, $cat_row['name'] ); ?>>
                                        <?php echo esc_html( $cat_row['name'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Fleet Specifications & Status -->
        <div class="acrb-car-panel">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-performance"></span>
                    <span><?php esc_html_e( 'Fleet Specifications & Status', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <div class="acrb-car-grid-3" style="margin-bottom: 16px;">
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_vehicle_status"><?php esc_html_e( 'Availability Status', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-car-select-wrap">
                            <select id="acrb_vehicle_status" name="acrb_vehicle_status" class="acrb-car-select">
                                <option value="active" <?php selected( $vehicle_status, 'active' ); ?>><?php esc_html_e( 'Available for Booking', 'awesome-car-rental' ); ?></option>
                                <option value="maintenance" <?php selected( $vehicle_status, 'maintenance' ); ?>><?php esc_html_e( 'Under Maintenance', 'awesome-car-rental' ); ?></option>
                                <option value="rented" <?php selected( $vehicle_status, 'rented' ); ?>><?php esc_html_e( 'Rented Out / In Service', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
                        </div>
                    </div>
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_seating_capacity"><?php esc_html_e( 'Seating Capacity', 'awesome-car-rental' ); ?></label>
                        <input type="number" id="acrb_seating_capacity" name="acrb_seating_capacity" class="acrb-car-input" value="<?php echo esc_attr( (string) $seating_cap ); ?>" min="1" max="50">
                    </div>
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_baggage_capacity"><?php esc_html_e( 'Baggage Capacity', 'awesome-car-rental' ); ?></label>
                        <input type="number" id="acrb_baggage_capacity" name="acrb_baggage_capacity" class="acrb-car-input" value="<?php echo esc_attr( (string) $baggage_cap ); ?>" min="0" max="20">
                    </div>
                </div>

                <div class="acrb-car-grid-3">
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_transmission"><?php esc_html_e( 'Transmission Type', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-car-select-wrap">
                            <select id="acrb_transmission" name="acrb_transmission" class="acrb-car-select">
                                <option value="Automatic" <?php selected( $transmission, 'Automatic' ); ?>><?php esc_html_e( 'Automatic', 'awesome-car-rental' ); ?></option>
                                <option value="Manual" <?php selected( $transmission, 'Manual' ); ?>><?php esc_html_e( 'Manual', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
                        </div>
                    </div>
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_fuel_type"><?php esc_html_e( 'Engine / Fuel Type', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-car-select-wrap">
                            <select id="acrb_fuel_type" name="acrb_fuel_type" class="acrb-car-select">
                                <option value="Petrol" <?php selected( $fuel_type, 'Petrol' ); ?>><?php esc_html_e( 'Petrol', 'awesome-car-rental' ); ?></option>
                                <option value="Diesel" <?php selected( $fuel_type, 'Diesel' ); ?>><?php esc_html_e( 'Diesel', 'awesome-car-rental' ); ?></option>
                                <option value="Electric" <?php selected( $fuel_type, 'Electric' ); ?>><?php esc_html_e( 'Electric', 'awesome-car-rental' ); ?></option>
                                <option value="Hybrid" <?php selected( $fuel_type, 'Hybrid' ); ?>><?php esc_html_e( 'Hybrid', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
                        </div>
                    </div>
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_min_rental_days"><?php esc_html_e( 'Minimum Rental Cycle (Days)', 'awesome-car-rental' ); ?></label>
                        <input type="number" id="acrb_min_rental_days" name="acrb_min_rental_days" class="acrb-car-input" value="<?php echo esc_attr( (string) $min_rental_days ); ?>" min="1">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Pickup & Drop-off Locations -->
        <div class="acrb-car-panel">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-location-alt"></span>
                    <span><?php esc_html_e( 'Assigned Hubs & Locations', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <div class="acrb-car-grid-2">
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_default_pickup"><?php esc_html_e( 'Primary Pickup Location', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-car-select-wrap">
                            <select id="acrb_default_pickup" name="acrb_default_pickup" class="acrb-car-select">
                                <option value=""><?php esc_html_e( 'Select Pickup Location', 'awesome-car-rental' ); ?></option>
                                <?php foreach ( $pickup_locations as $loc_name ) : ?>
                                    <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $default_pickup, $loc_name ); ?>>
                                        <?php echo esc_html( $loc_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
                        </div>
                    </div>
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_default_dropoff"><?php esc_html_e( 'Primary Drop-off Location', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-car-select-wrap">
                            <select id="acrb_default_dropoff" name="acrb_default_dropoff" class="acrb-car-select">
                                <option value=""><?php esc_html_e( 'Select Drop-off Location', 'awesome-car-rental' ); ?></option>
                                <?php foreach ( $dropoff_locations as $loc_name ) : ?>
                                    <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $default_dropoff, $loc_name ); ?>>
                                        <?php echo esc_html( $loc_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Seasonal Rates -->
        <div class="acrb-car-panel">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php esc_html_e( 'Seasonal Rate Overrides', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">
                    <?php esc_html_e( 'Define custom date intervals and override standard daily rates for peak seasons or holiday surcharges.', 'awesome-car-rental' ); ?>
                </p>
                <div id="acrb-seasonal-container">
                    <?php if ( ! empty( $seasonal_rates ) && is_array( $seasonal_rates ) ) : ?>
                        <?php foreach ( $seasonal_rates as $index => $rate ) : ?>
                            <div class="acrb-seasonal-row">
                                <input type="text" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][name]" class="acrb-car-input" placeholder="<?php esc_attr_e( 'Season Name', 'awesome-car-rental' ); ?>" value="<?php echo esc_attr( $rate['name'] ?? '' ); ?>">
                                <input type="date" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][start]" class="acrb-car-input" value="<?php echo esc_attr( $rate['start'] ?? '' ); ?>">
                                <input type="date" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][end]" class="acrb-car-input" value="<?php echo esc_attr( $rate['end'] ?? '' ); ?>">
                                <input type="number" step="0.01" name="seasonal_rates[<?php echo esc_attr( (string) $index ); ?>][price]" class="acrb-car-input" placeholder="<?php echo esc_attr( sprintf( __( 'Rate (%s)', 'awesome-car-rental' ), $currency_symbol ) ); ?>" value="<?php echo esc_attr( $rate['price'] ?? '' ); ?>">
                                <button type="button" class="acrb-btn-remove-row" onclick="jQuery(this).closest('.acrb-seasonal-row').fadeOut(200, function(){jQuery(this).remove();});">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="acrb-btn-secondary" id="acrb-add-seasonal-btn">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php esc_html_e( 'Add Seasonal Interval', 'awesome-car-rental' ); ?></span>
                </button>
            </div>
        </div>

        <!-- Section 5: Security Deposit & Insurance -->
        <div class="acrb-car-panel">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-shield"></span>
                    <span><?php esc_html_e( 'Security Deposit & Insurance Holds', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <div class="acrb-car-grid-2">
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_security_deposit">
                            <?php printf( esc_html__( 'Security Deposit Hold (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?>
                        </label>
                        <input type="number" step="0.01" min="0" id="acrb_security_deposit" name="acrb_security_deposit" class="acrb-car-input" value="<?php echo esc_attr( (string) $security_deposit ); ?>" placeholder="0.00">
                    </div>
                    <div class="acrb-car-form-group">
                        <label class="acrb-car-label" for="acrb_insurance_fee">
                            <?php printf( esc_html__( 'Mandatory Insurance Surcharge (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?>
                        </label>
                        <input type="number" step="0.01" min="0" id="acrb_insurance_fee" name="acrb_insurance_fee" class="acrb-car-input" value="<?php echo esc_attr( (string) $insurance_fee ); ?>" placeholder="0.00">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Media & Gallery -->
        <div class="acrb-car-panel">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-format-image"></span>
                    <span><?php esc_html_e( 'Media & Gallery Showcase', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <div class="acrb-car-grid-2" style="margin-bottom: 22px;">
                    <div>
                        <label class="acrb-car-label"><?php esc_html_e( 'Vehicle Featured Cover Image', 'awesome-car-rental' ); ?></label>
                        <div id="acrb-thumb-preview" class="acrb-thumb-box">
                            <?php 
                            if ( $thumbnail_id ) { 
                                echo wp_get_attachment_image( $thumbnail_id, 'medium', false, array( 'class' => 'acrb-thumb-img' ) ); 
                            } else { 
                                echo '<span class="dashicons dashicons-car" style="font-size:48px; width:48px; height:48px; color:#cbd5e1;"></span>'; 
                            } 
                            ?>
                        </div>
                        <input type="hidden" name="acrb_car_thumbnail_id" id="acrb_car_thumbnail_id" value="<?php echo esc_attr( (string) $thumbnail_id ); ?>">
                        <button type="button" class="acrb-btn-secondary" id="acrb_set_thumb">
                            <span class="dashicons dashicons-admin-media"></span>
                            <span><?php esc_html_e( 'Select Cover Image', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>

                    <div>
                        <div class="acrb-car-form-group">
                            <label class="acrb-car-label" for="acrb_car_video"><?php esc_html_e( 'Video Walkaround URL (YouTube / Vimeo)', 'awesome-car-rental' ); ?></label>
                            <input type="url" id="acrb_car_video" name="acrb_car_video" class="acrb-car-input" value="<?php echo esc_url( $video_url ); ?>" placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="acrb-car-label"><?php esc_html_e( 'Additional Gallery Photos', 'awesome-car-rental' ); ?></label>
                    <input type="hidden" name="acrb_car_gallery" id="acrb_gallery_ids" value="<?php echo esc_attr( (string) $gallery_ids ); ?>">
                    <button type="button" id="acrb_manage_gallery" class="acrb-btn-secondary">
                        <span class="dashicons dashicons-images-alt2"></span>
                        <span><?php esc_html_e( 'Upload / Manage Gallery Photos', 'awesome-car-rental' ); ?></span>
                    </button>
                    
                    <div id="acrb_gallery_preview" class="acrb-gallery-grid">
                        <?php 
                        if ( $gallery_ids ) { 
                            $ids = explode( ',', $gallery_ids ); 
                            foreach ( $ids as $id ) {
                                $img = wp_get_attachment_image_src( (int) $id, 'thumbnail' );
                                if ( $img ) {
                                    echo '<div class="acrb-gallery-item" data-id="' . esc_attr( (string) $id ) . '">
                                            <span class="acrb-gallery-remove">&times;</span>
                                            <img src="' . esc_url( $img[0] ) . '" alt="">
                                          </div>';
                                }
                            } 
                        } 
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 7: Global Add-on Extras -->
        <div class="acrb-car-panel">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-star-empty"></span>
                    <span><?php esc_html_e( 'Global Add-on Extras & Equipment', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">
                    <?php esc_html_e( 'Enable which global add-on extras are offered as optional equipment for this specific vehicle.', 'awesome-car-rental' ); ?>
                </p>
                <div class="acrb-car-grid-2">
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
                            <div class="acrb-toggle-card">
                                <div class="acrb-toggle-card-info">
                                    <label class="acrb-switch-ui">
                                        <input type="checkbox" name="car_amenities[<?php echo esc_attr( (string) $idx ); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                        <span class="acrb-switch-slider"></span>
                                    </label>
                                    <span style="font-size:13px; font-weight:700; color:#334155;"><?php echo esc_html( $g['name'] ); ?></span>
                                </div>
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
        <div class="acrb-car-panel">
            <div class="acrb-car-panel-head">
                <h3 class="acrb-car-panel-head-title">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <span><?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></span>
                </h3>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
            </div>
            <div class="acrb-car-panel-body">
                <div class="acrb-car-grid-2">
                    <?php if ( ! empty( $global_features ) ) : ?>
                        <?php foreach ( $global_features as $idx => $g ) : 
                            $val = ''; 
                            $en  = 1;
                            foreach ( $saved_features as $s ) { 
                                if ( ( $s['name'] ?? '' ) === $g['name'] ) { 
                                    $val = $s['value'] ?? ''; 
                                    $en  = $s['enabled'] ?? 0; 
                                    break; 
                                } 
                            }
                        ?>
                            <div class="acrb-toggle-card">
                                <div class="acrb-toggle-card-info">
                                    <label class="acrb-switch-ui">
                                        <input type="checkbox" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                        <span class="acrb-switch-slider"></span>
                                    </label>
                                    <span style="font-size:13px; font-weight:700; color:#334155;"><?php echo esc_html( $g['name'] ); ?></span>
                                    <input type="hidden" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $g['name'] ); ?>">
                                    <input type="hidden" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][icon]" value="<?php echo esc_attr( $g['icon'] ?? 'dashicons-admin-generic' ); ?>">
                                </div>
                                <input type="text" name="car_features[<?php echo esc_attr( (string) $idx ); ?>][value]" class="acrb-car-input" style="width: 110px !important; text-align: center;" value="<?php echo esc_attr( $val ?: ( $g['value'] ?? '' ) ); ?>" placeholder="Value">
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p style="color: #64748b; font-style: italic; margin: 0;"><?php esc_html_e( 'No technical specifications configured yet.', 'awesome-car-rental' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Submit Footer Bar -->
        <div class="acrb-car-footer-actions">
            <a href="<?php echo esc_url( $list_url ); ?>" class="acrb-car-btn-cancel">
                <?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
            </a>
            <button type="submit" class="acrb-car-btn-submit">
                <span class="dashicons dashicons-saved"></span>
                <span><?php echo $car ? esc_html__( 'Update Fleet Vehicle', 'awesome-car-rental' ) : esc_html__( 'Save Vehicle to Fleet', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($){
    // Accordion Toggle Behavior
    $('.acrb-car-panel-head').on('click', function(){
        $(this).parent('.acrb-car-panel').toggleClass('is-open');
    });

    // Dynamic Seasonal Rate Rows
    $('#acrb-add-seasonal-btn').on('click', function(e){
        e.preventDefault();
        var index = $('#acrb-seasonal-container > div').length;
        var newRow = '<div class="acrb-seasonal-row">' +
            '<input type="text" name="seasonal_rates['+index+'][name]" class="acrb-car-input" placeholder="<?php echo esc_js( __( 'Season Name', 'awesome-car-rental' ) ); ?>">' +
            '<input type="date" name="seasonal_rates['+index+'][start]" class="acrb-car-input">' +
            '<input type="date" name="seasonal_rates['+index+'][end]" class="acrb-car-input">' +
            '<input type="number" step="0.01" name="seasonal_rates['+index+'][price]" class="acrb-car-input" placeholder="<?php echo esc_js( sprintf( __( 'Rate (%s)', 'awesome-car-rental' ), $currency_symbol ) ); ?>">' +
            '<button type="button" class="acrb-btn-remove-row" onclick="jQuery(this).closest(\'.acrb-seasonal-row\').fadeOut(200, function(){jQuery(this).remove();});">&times;</button>' +
            '</div>';
        $('#acrb-seasonal-container').append(newRow);
    });

    // Thumbnail Selection Frame
    $('#acrb_set_thumb').on('click', function(e){
        e.preventDefault();
        var frame = wp.media({ 
            title: <?php echo wp_json_encode( __( 'Select Vehicle Cover Image', 'awesome-car-rental' ) ); ?>, 
            button: { text: <?php echo wp_json_encode( __( 'Set Cover Image', 'awesome-car-rental' ) ); ?> }, 
            multiple: false 
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#acrb_car_thumbnail_id').val(attachment.id);
            $('#acrb-thumb-preview').html('<img src="' + attachment.url + '" style="width:100%;height:auto;display:block;">');
        }).open();
    });

    // Gallery Management Frame
    var galleryFrame;
    $('#acrb_manage_gallery').on('click', function(e){
        e.preventDefault();
        if (galleryFrame) { 
            galleryFrame.open(); 
            return; 
        }

        galleryFrame = wp.media({ 
            title: <?php echo wp_json_encode( __( 'Add Images to Vehicle Gallery', 'awesome-car-rental' ) ); ?>, 
            button: { text: <?php echo wp_json_encode( __( 'Add to Gallery', 'awesome-car-rental' ) ); ?> }, 
            multiple: true 
        });

        galleryFrame.on('select', function(){
            var selection = galleryFrame.state().get('selection');
            var currentIds = $('#acrb_gallery_ids').val() ? $('#acrb_gallery_ids').val().split(',') : [];
            
            selection.map(function(attachment){
                attachment = attachment.toJSON();
                var idStr = attachment.id.toString();
                if ($.inArray(idStr, currentIds) === -1) {
                    currentIds.push(idStr);
                    var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    $('#acrb_gallery_preview').append(
                        '<div class="acrb-gallery-item" data-id="' + idStr + '">' +
                        '<span class="acrb-gallery-remove">&times;</span>' +
                        '<img src="' + thumbUrl + '" alt="">' +
                        '</div>'
                    );
                }
            });
            $('#acrb_gallery_ids').val(currentIds.join(','));
        });
        galleryFrame.open();
    });

    // Remove Gallery Photo
    $('#acrb_gallery_preview').on('click', '.acrb-gallery-remove', function(){
        $(this).closest('.acrb-gallery-item').remove();
        var ids = [];
        $('#acrb_gallery_preview > .acrb-gallery-item').each(function(){
            ids.push($(this).data('id'));
        });
        $('#acrb_gallery_ids').val(ids.join(','));
    });
});
</script>