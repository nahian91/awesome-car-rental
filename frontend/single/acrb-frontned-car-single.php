<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * [acrb_single_car]
 * Displays a professional single car details page with a detailed real-time price breakdown summary.
 *
 * @package Awesome_Car_Rental
 * @since   1.8.7
 */

// 1. DATA SUBMISSION HANDLER
function acrb_process_car_booking_to_db() {
    if ( isset( $_POST['action'] ) && 'acrb_submit_booking_action' === $_POST['action'] ) {
        
        if ( ! isset( $_POST['acrb_booking_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['acrb_booking_nonce'] ) ), 'acrb_submit_booking' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'awesome-car-rental' ) );
        }

        $car_id      = isset( $_POST['car_id'] ) ? intval( wp_unslash( $_POST['car_id'] ) ) : 0;
        $cus_name    = isset( $_POST['cus_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_name'] ) ) : '';
        $cus_email   = isset( $_POST['cus_email'] ) ? sanitize_email( wp_unslash( $_POST['cus_email'] ) ) : '';
        $cus_phone   = isset( $_POST['cus_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_phone'] ) ) : '';
        $pickup_loc  = isset( $_POST['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_location'] ) ) : '';
        $pickup_date = isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : '';
        $return_date = isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : '';
        $total       = isset( $_POST['order_total'] ) ? floatval( wp_unslash( $_POST['order_total'] ) ) : 0;
        $method      = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
        $user_id     = get_current_user_id();

        $booking_id = wp_insert_post( array(
            'post_type'   => 'acrb_bookings',
            'post_status' => 'publish',
            'meta_input'  => array(
                'user_id'         => absint( $user_id ),
                'car_id'          => absint( $car_id ),
                'customer_name'   => $cus_name,
                'customer_email'  => $cus_email,
                'customer_phone'  => $cus_phone,
                'pickup_location' => $pickup_loc,
                'pickup_date'     => $pickup_date,
                'return_date'     => $return_date,
                'total_price'     => $total,
                'payment_method'  => $method,
                'status'          => 'pending',
                'booking_time'    => current_time( 'mysql' ),
            ),
        ) );

        if ( $booking_id ) {
            $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $booking_id ), esc_html__( 'pending', 'awesome-car-rental' ) );
            wp_update_post( array( 'ID' => $booking_id, 'post_title' => $acrb_msg ) );

            // Automatically sync or capture customer into CRM table (`dev_nahian_customers`)
            global $wpdb;
            $table_customers = $wpdb->prefix . 'dev_nahian_customers';
            if ( ! empty( $cus_email ) ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $existing_cust = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table_customers} WHERE email = %s", $cus_email ), ARRAY_A );
                if ( $existing_cust ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->update( $table_customers, array( 'name' => $cus_name, 'phone' => $cus_phone ), array( 'id' => absint( $existing_cust['id'] ) ), array( '%s', '%s' ), array( '%d' ) );
                } else {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->insert( $table_customers, array( 'name' => $cus_name, 'email' => $cus_email, 'phone' => $cus_phone ), array( '%s', '%s', '%s' ) );
                }
            }
        }

        // Redirect to Thank You page
        $success_page_id = get_option( 'acrb_success_page' );
        $redirect_url    = $success_page_id ? get_permalink( $success_page_id ) : home_url( '/acrb-thanks/' );
        $redirect_url    = add_query_arg( 'booking_id', $booking_id, $redirect_url );
        
        wp_safe_redirect( esc_url_raw( $redirect_url ) );
        exit;
    }
}
add_action( 'admin_post_nopriv_acrb_submit_booking_action', 'acrb_process_car_booking_to_db' );
add_action( 'admin_post_acrb_submit_booking_action', 'acrb_process_car_booking_to_db' );

// 2. THE UI SHORTCODE
function acrb_car_details_page_shortcode() {
    
    if ( ! is_user_logged_in() ) {
        $current_car_url = get_permalink() . ( isset( $_GET['car_id'] ) ? '?car_id=' . intval( wp_unslash( $_GET['car_id'] ) ) : '' );
        $login_page_url  = home_url( '/acrb-login/' );
        $final_url       = add_query_arg( 'redirect_to', urlencode( $current_car_url ), $login_page_url );
        return '<script>window.location.href="' . esc_url( $final_url ) . '";</script><div style="padding:50px; text-align:center; font-family:-apple-system,sans-serif; color:#64748b;">Redirecting to login...</div>';
    }

    // Get Car ID from URL, with fallback to the latest car if omitted
    $car_id = isset( $_GET['car_id'] ) ? intval( wp_unslash( $_GET['car_id'] ) ) : 0;
    if ( ! $car_id ) {
        $latest_cars = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => 1, 'post_status' => 'publish' ) );
        if ( ! empty( $latest_cars ) ) {
            $car_id = $latest_cars[0]->ID;
        }
    }

    if ( ! $car_id || 'acrb_cars' !== get_post_type( $car_id ) ) {
        return '<div class="acrb-alert error" style="padding:40px; text-align:center; background:#fee2e2; color:#b91c1c; border-radius:16px; font-family:-apple-system,sans-serif; font-weight:600;">' . esc_html__( 'Vehicle not found. Please select a car from the fleet grid.', 'awesome-car-rental' ) . '</div>';
    }

    $current_user  = wp_get_current_user();
    $full_name     = trim( $current_user->first_name . ' ' . $current_user->last_name ) ?: $current_user->display_name;
    $user_phone    = get_user_meta( $current_user->ID, 'billing_phone', true ) ?: get_user_meta( $current_user->ID, 'phone', true );
    $display_phone = ! empty( $user_phone ) ? $user_phone : 'Missing in Profile';

    // Comprehensive Price & Currency Fallbacks
    $price = floatval( get_post_meta( $car_id, 'daily_price', true ) 
          ?: get_post_meta( $car_id, 'price_per_day', true ) 
          ?: get_post_meta( $car_id, 'rate_per_day', true ) ?: 0 );

    $security_deposit = floatval( get_post_meta( $car_id, 'acrb_security_deposit', true ) ?: 0 );
    $insurance_fee    = floatval( get_post_meta( $car_id, 'acrb_insurance_fee', true ) ?: 0 );
    
    $currency = get_option( 'acrb_currency', '£' ); 

    // Load Specs, Amenities & Media
    $vehicle_status  = get_post_meta( $car_id, 'acrb_vehicle_status', true ) ?: 'active';
    $seating_cap     = get_post_meta( $car_id, 'acrb_seating_capacity', true ) ?: '5';
    $baggage_cap     = get_post_meta( $car_id, 'acrb_baggage_capacity', true ) ?: '2';
    $transmission    = get_post_meta( $car_id, 'acrb_transmission', true ) ?: 'Automatic';
    $fuel_type       = get_post_meta( $car_id, 'acrb_fuel_type', true ) ?: 'Petrol';
    $min_rental_days = get_post_meta( $car_id, 'acrb_min_rental_days', true ) ?: '1';

    $features  = get_post_meta( $car_id, 'acrb_car_features', true ) ?: get_post_meta( $car_id, 'car_features', true ) ?: array();
    $amenities = get_post_meta( $car_id, 'acrb_car_amenities', true ) ?: get_post_meta( $car_id, 'car_amenities', true ) ?: array();
    $gallery   = get_post_meta( $car_id, 'acrb_car_gallery', true ) ?: get_post_meta( $car_id, 'gallery', true );
    $vin       = get_post_meta( $car_id, 'vin_number', true ) ?: get_post_meta( $car_id, 'vin', true );
    $video_url = get_post_meta( $car_id, 'acrb_car_video', true );
    
    $custom_locations = get_option( 'acrb_custom_pickup_locations', array() ) ?: get_option( 'acrb_custom_locations', array() );
    
    $enabled_methods = array();
    if ( get_option( 'acrb_enable_pay_later' ) === 'yes' ) {
        $enabled_methods['pay_later'] = 'Pay at Pickup';
    }
    if ( get_option( 'acrb_enable_bank' ) === 'yes' ) {
        $enabled_methods['bank'] = 'Bank Transfer';
    }
    if ( get_option( 'acrb_enable_cash' ) === 'yes' ) {
        $enabled_methods['cash'] = 'Cash';
    }

    ob_start(); ?>

    <div class="acrb-main-container" data-day-rate="<?php echo esc_attr( $price ); ?>" data-deposit="<?php echo esc_attr( $security_deposit ); ?>" data-insurance="<?php echo esc_attr( $insurance_fee ); ?>" data-currency="<?php echo esc_attr( $currency ); ?>">
        <div class="acrb-flex-grid" style="display: flex; flex-wrap: wrap; gap: 36px; align-items: flex-start;">
            
            <div class="acrb-content-left" style="flex: 1; min-width: 340px;">
                <div class="acrb-gallery-stage">
                    <?php 
                    $hero_img = get_the_post_thumbnail_url( $car_id, 'large' );
                    if ( ! $hero_img && ! empty( $gallery ) ) {
                        $first_gallery_id = is_array( $gallery ) ? reset( $gallery ) : explode( ',', $gallery )[0];
                        $hero_img = wp_get_attachment_image_url( $first_gallery_id, 'large' );
                    }
                    ?>
                    <img src="<?php echo esc_url( $hero_img ); ?>" id="acrb-hero-view" alt="<?php echo esc_attr( get_the_title( $car_id ) ); ?>" style="width:100%; border-radius:20px; border: 1px solid #e2e8f0; height: 460px; object-fit: cover; box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08);">
                </div>

                <div class="acrb-header-box" style="margin-top:28px;">
                    <div style="background: #eff6ff; display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; color: #2563eb; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.6px; border: 1px solid #bfdbfe;">
                        ID: <?php echo esc_html( $vin ?: 'REF-' . $car_id ); ?>
                    </div>
                    <h1 style="margin:0; font-size: 30px; font-weight: 900; color: #0f172a; letter-spacing: -0.8px;"><?php echo esc_html( get_the_title( $car_id ) ); ?></h1>
                </div>

                <div class="acrb-tabs-nav">
                    <button class="acrb-tab-btn active" data-tab="overview"><?php esc_html_e( 'Overview', 'awesome-car-rental' ); ?></button>
                    <button class="acrb-tab-btn" data-tab="specs"><?php esc_html_e( 'Specifications & Extras', 'awesome-car-rental' ); ?></button>
                    <button class="acrb-tab-btn" data-tab="gallery"><?php esc_html_e( 'Gallery', 'awesome-car-rental' ); ?></button>
                    <button class="acrb-tab-btn" data-tab="video"><?php esc_html_e( 'Video', 'awesome-car-rental' ); ?></button>
                </div>

                <div class="acrb-tabs-content" style="margin-top: 28px;">
                    
                    <!-- Overview Tab -->
                    <div id="tab-overview" class="acrb-tab-pane active">
                        <div class="acrb-description" style="color: #475569; font-size: 15px; line-height: 1.7;">
                            <?php 
                            $content = get_post_field( 'post_content', $car_id );
                            echo ! empty( $content ) ? wp_kses_post( wpautop( $content ) ) : '<p class="acrb-empty-msg">' . esc_html__( 'No description available for this vehicle.', 'awesome-car-rental' ) . '</p>'; 
                            ?>
                        </div>
                    </div>

                    <!-- Specifications Tab -->
                    <div id="tab-specs" class="acrb-tab-pane">
                        <h3 style="margin:0 0 16px 0; font-size:13px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.8px;"><?php esc_html_e( 'Vehicle Specifications & Status', 'awesome-car-rental' ); ?></h3>
                        <div class="acrb-spec-wrap" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:16px; margin-bottom: 36px;">
                            
                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-yes-alt" style="color: #16a34a; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#dcfce7; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 14px; text-transform: capitalize;"><?php echo esc_html( $vehicle_status ); ?></div>
                            </div>

                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-groups" style="color: #3b82f6; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Seating', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $seating_cap ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></div>
                            </div>

                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-portfolio" style="color: #3b82f6; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Baggage', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $baggage_cap ); ?> <?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></div>
                            </div>

                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-admin-generic" style="color: #3b82f6; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $transmission ); ?></div>
                            </div>

                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-dashboard" style="color: #3b82f6; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Fuel / Engine', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $fuel_type ); ?></div>
                            </div>

                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-calendar" style="color: #3b82f6; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Min Rental Days', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $min_rental_days ); ?></div>
                            </div>

                            <?php if ( $security_deposit > 0 ) : ?>
                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-shield" style="color: #3b82f6; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Security Deposit', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $currency . number_format( $security_deposit, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ( $insurance_fee > 0 ) : ?>
                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:14px; box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.03);">
                                <span class="dashicons dashicons-awards" style="color: #3b82f6; margin-bottom: 10px; font-size:20px; width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:10px; padding:8px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.6px; margin-bottom:4px;"><?php esc_html_e( 'Insurance Fee', 'awesome-car-rental' ); ?></div>
                                <div style="font-weight:800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $currency . number_format( $insurance_fee, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                        </div>

                        <?php if ( ! empty( $features ) ) : ?>
                        <div style="margin-top:36px; padding-top:28px; border-top:1px solid #e2e8f0;">
                            <h3 style="margin:0 0 16px 0; font-size:13px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.8px;"><?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></h3>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
                                <?php foreach ( $features as $f ) : 
                                    $f_name    = is_array( $f ) ? ( $f['name'] ?? '' ) : '';
                                    $f_val     = is_array( $f ) ? ( $f['value'] ?? '' ) : $f;
                                    $f_icon    = is_array( $f ) ? ( $f['icon'] ?? 'dashicons-admin-generic' ) : 'dashicons-admin-generic';
                                    $f_enabled = is_array( $f ) ? ! empty( $f['enabled'] ) : true;

                                    if ( $f_enabled && ! empty( $f_val ) ) : 
                                ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid #e2e8f0; padding: 14px 18px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.01);">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span class="dashicons <?php echo esc_attr( $f_icon ); ?>" style="color: #4f46e5;"></span>
                                            <span style="font-size: 13.5px; font-weight: 600; color: #334155;"><?php echo esc_html( $f_name ); ?></span>
                                        </div>
                                        <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $f_val ); ?></strong>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $amenities ) ) : ?>
                        <div style="margin-top:36px; padding-top:28px; border-top:1px solid #e2e8f0;">
                            <h3 style="margin:0 0 16px 0; font-size:13px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.8px;"><?php esc_html_e( 'Global Add-on Extras (Rentables)', 'awesome-car-rental' ); ?></h3>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                                <?php foreach ( $amenities as $a ) : 
                                    $a_name    = is_array( $a ) ? ( $a['name'] ?? '' ) : $a;
                                    $a_icon    = is_array( $a ) ? ( $a['icon'] ?? 'dashicons-star-empty' ) : 'dashicons-star-empty';
                                    $a_enabled = is_array( $a ) ? ! empty( $a['enabled'] ) : true;

                                    if ( $a_enabled && ! empty( $a_name ) ) : 
                                ?>
                                    <div style="display: flex; align-items: center; gap: 10px; color: #334155; background: #fff; border: 1px solid #e2e8f0; padding: 14px 18px; border-radius: 12px; font-size: 14px; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.01);">
                                        <span class="dashicons <?php echo esc_attr( $a_icon ); ?>" style="color: #16a34a;"></span>
                                        <span><?php echo esc_html( $a_name ); ?></span>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                    <!-- Gallery Tab -->
                    <div id="tab-gallery" class="acrb-tab-pane">
                        <?php 
                        $gallery_ids = array();
                        if ( ! empty( $gallery ) ) {
                            if ( is_array( $gallery ) ) {
                                $gallery_ids = $gallery;
                            } else {
                                $gallery_ids = explode( ',', $gallery );
                            }
                        }
                        ?>
                        <?php if ( ! empty( $gallery_ids ) || $hero_img ) : ?>
                            <div class="acrb-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
                                <?php if ( $hero_img ) : ?>
                                    <img src="<?php echo esc_url( $hero_img ); ?>" class="acrb-gallery-item" style="width:100%; height:130px; object-fit:cover; border-radius:12px; cursor:pointer; border: 1px solid #e2e8f0;" alt="">
                                <?php endif; ?>
                                <?php foreach ( $gallery_ids as $gid ) : 
                                    $gid = trim( $gid );
                                    if ( ! empty( $gid ) ) : 
                                        $img_url = wp_get_attachment_image_url( $gid, 'large' );
                                        if ( $img_url ) :
                                ?>
                                    <img src="<?php echo esc_url( $img_url ); ?>" class="acrb-gallery-item" style="width:100%; height:130px; object-fit:cover; border-radius:12px; cursor:pointer; border: 1px solid #e2e8f0;" alt="">
                                <?php endif; endif; endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="acrb-empty-box">
                                <span class="dashicons dashicons-format-image"></span>
                                <p><?php esc_html_e( 'No additional images available.', 'awesome-car-rental' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Video Tab -->
                    <div id="tab-video" class="acrb-tab-pane">
                        <?php if ( ! empty( $video_url ) ) : 
                            $embed = wp_oembed_get( $video_url );
                            if ( $embed ) : ?>
                                <div class="acrb-video-container"><?php echo wp_kses_post( $embed ); ?></div>
                            <?php else : ?>
                                <p class="acrb-empty-msg"><?php esc_html_e( 'Invalid video URL.', 'awesome-car-rental' ); ?></p>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="acrb-empty-box">
                                <span class="dashicons dashicons-video-alt3"></span>
                                <p><?php esc_html_e( 'No video walkthrough available.', 'awesome-car-rental' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar with Price Breakdown -->
            <div class="acrb-sidebar-right" style="width: 440px; min-width: 320px;">
                <div class="acrb-booking-card">
                    <div class="acrb-price-hero">
                        <span style="font-size:32px; font-weight:900; color: #0f172a; letter-spacing: -0.8px;"><?php echo esc_html( $currency . number_format( $price, 2 ) ); ?></span>
                        <span style="color: #64748b; font-size: 13px; font-weight: 600;">/ per day</span>
                    </div>

                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="acrb-booking-form">
                        <?php wp_nonce_field( 'acrb_submit_booking', 'acrb_booking_nonce' ); ?>
                        <input type="hidden" name="action" value="acrb_submit_booking_action">
                        <input type="hidden" name="car_id" value="<?php echo esc_attr( $car_id ); ?>">
                        <input type="hidden" id="acrb_hidden_total" name="order_total" value="0.00">

                        <div style="margin-bottom:18px;">
                            <label class="acrb-label"><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></label>
                            <input type="text" name="cus_name" value="<?php echo esc_attr( $full_name ); ?>" readonly class="acrb-input acrb-readonly">
                        </div>

                        <div style="margin-bottom:18px;">
                            <label class="acrb-label"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
                            <input type="email" name="cus_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" readonly class="acrb-input acrb-readonly">
                        </div>

                        <div style="margin-bottom:18px;">
                            <label class="acrb-label"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
                            <input type="text" name="cus_phone" value="<?php echo esc_attr( $display_phone ); ?>" readonly class="acrb-input acrb-readonly">
                        </div>

                        <div style="display:flex; gap:14px; margin-bottom:18px;">
                            <div style="flex:1;">
                                <label class="acrb-label"><?php esc_html_e( 'Pickup Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" id="p_date" name="pickup_date" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                            <div style="flex:1;">
                                <label class="acrb-label"><?php esc_html_e( 'Return Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" id="r_date" name="return_date" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                        </div>

                        <div style="margin-bottom:18px;">
                            <label class="acrb-label"><?php esc_html_e( 'Pickup Location', 'awesome-car-rental' ); ?></label>
                            <select name="pickup_location" required class="acrb-input acrb-select">
                                <option value=""><?php esc_html_e( 'Select Location', 'awesome-car-rental' ); ?></option>
                                <?php foreach ( $custom_locations as $loc ) : 
                                    $loc_name = is_array( $loc ) ? ( $loc['name'] ?? '' ) : $loc;
                                    if ( ! empty( $loc_name ) ) :
                                ?>
                                    <option value="<?php echo esc_attr( $loc_name ); ?>"><?php echo esc_html( $loc_name ); ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>

                        <?php if ( ! empty( $enabled_methods ) ) : ?>
                        <div style="margin-bottom:20px;">
                            <label class="acrb-label"><?php esc_html_e( 'Payment Method', 'awesome-car-rental' ); ?></label>
                            <div class="acrb-payment-wrapper">
                                <?php foreach ( $enabled_methods as $k => $v ) : ?>
                                <label class="acrb-pay-option">
                                    <input type="radio" name="payment_method" value="<?php echo esc_attr( $k ); ?>" required>
                                    <span><?php echo esc_html( $v ); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Nice Price Breakdown Table -->
                        <div class="acrb-breakdown-box">
                            <div style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                                <?php esc_html_e( 'Price Breakdown', 'awesome-car-rental' ); ?>
                            </div>
                            
                            <div class="acrb-breakdown-row">
                                <span><?php esc_html_e( 'Rental Duration:', 'awesome-car-rental' ); ?></span>
                                <strong id="acrb_calc_days">0 <?php esc_html_e( 'Days', 'awesome-car-rental' ); ?></strong>
                            </div>
                            
                            <div class="acrb-breakdown-row">
                                <span><?php esc_html_e( 'Vehicle Subtotal:', 'awesome-car-rental' ); ?></span>
                                <strong id="acrb_calc_subtotal"><?php echo esc_html( $currency ); ?>0.00</strong>
                            </div>

                            <?php if ( $security_deposit > 0 ) : ?>
                            <div class="acrb-breakdown-row">
                                <span><?php esc_html_e( 'Security Deposit:', 'awesome-car-rental' ); ?></span>
                                <strong><?php echo esc_html( $currency . number_format( $security_deposit, 2 ) ); ?></strong>
                            </div>
                            <?php endif; ?>

                            <?php if ( $insurance_fee > 0 ) : ?>
                            <div class="acrb-breakdown-row">
                                <span><?php esc_html_e( 'Insurance Fee:', 'awesome-car-rental' ); ?></span>
                                <strong><?php echo esc_html( $currency . number_format( $insurance_fee, 2 ) ); ?></strong>
                            </div>
                            <?php endif; ?>

                            <div class="acrb-breakdown-total">
                                <span><?php esc_html_e( 'Estimated Total', 'awesome-car-rental' ); ?></span>
                                <span style="font-size: 22px; font-weight: 900; color: #3b82f6;"><?php echo esc_html( $currency ); ?><span id="acrb_sum_total">0.00</span></span>
                            </div>
                        </div>

                        <button type="submit" class="acrb-submit-btn"><?php esc_html_e( 'Reserve This Vehicle', 'awesome-car-rental' ); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab System
        const tabBtns = document.querySelectorAll('.acrb-tab-btn');
        const tabPanes = document.querySelectorAll('.acrb-tab-pane');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });

        // Price Breakdown Logic
        const pDate = document.getElementById('p_date');
        const rDate = document.getElementById('r_date');
        const displayTotal = document.getElementById('acrb_sum_total');
        const displayDays = document.getElementById('acrb_calc_days');
        const displaySubtotal = document.getElementById('acrb_calc_subtotal');
        const hiddenTotal = document.getElementById('acrb_hidden_total');
        const container = document.querySelector('.acrb-main-container');

        const rate = container ? parseFloat(container.dataset.dayRate) : 0;
        const deposit = container ? parseFloat(container.dataset.deposit) : 0;
        const insurance = container ? parseFloat(container.dataset.insurance) : 0;
        const currency = container ? container.dataset.currency : '£';

        function calc() {
            if (pDate && rDate && pDate.value && rDate.value) {
                const start = new Date(pDate.value);
                const end = new Date(rDate.value);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                
                if (days > 0) {
                    const subtotal = days * rate;
                    const grandTotal = subtotal + deposit + insurance;

                    displayDays.innerText = days + (days === 1 ? ' Day' : ' Days');
                    displaySubtotal.innerText = currency + subtotal.toFixed(2);
                    displayTotal.innerText = grandTotal.toFixed(2);
                    hiddenTotal.value = grandTotal.toFixed(2);
                } else {
                    displayDays.innerText = '0 Days';
                    displaySubtotal.innerText = currency + '0.00';
                    displayTotal.innerText = '0.00';
                    hiddenTotal.value = '0.00';
                }
            }
        }
        if (pDate && rDate) {
            pDate.addEventListener('change', calc);
            rDate.addEventListener('change', calc);
        }

        // Gallery Hero Switcher
        document.querySelectorAll('.acrb-gallery-item').forEach(img => {
            img.addEventListener('click', function() {
                const hero = document.getElementById('acrb-hero-view');
                if (hero) hero.src = this.src;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    });
    </script>
    
    <style>
        .acrb-tabs-nav { display: flex; gap: 8px; margin-top: 32px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; overflow-x: auto; }
        .acrb-tab-btn { background: #fff; border: 1px solid #e2e8f0; padding: 11px 22px; font-weight: 700; font-size: 13.5px; color: #475569; cursor: pointer; border-radius: 10px; transition: all 0.25s ease; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        .acrb-tab-btn:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
        .acrb-tab-btn.active { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
        
        .acrb-tab-pane { display: none; padding: 28px 0; }
        .acrb-tab-pane.active { display: block; animation: acrbFade 0.3s ease; }
        @keyframes acrbFade { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform: translateY(0); } }

        .acrb-empty-box { padding: 60px 20px; text-align: center; background: #f8fafc; border-radius: 16px; color: #64748b; border: 1px dashed #cbd5e1; }
        .acrb-empty-box .dashicons { font-size: 48px; width: 48px; height: 48px; margin-bottom: 12px; color: #94a3b8; }
        .acrb-empty-msg { color: #64748b; font-style: italic; }

        .acrb-video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 16px; background: #0f172a; border: 1px solid #e2e8f0; }
        .acrb-video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }

        .acrb-booking-card { padding: 32px; border: 1px solid #e2e8f0; border-radius: 24px; background: #fff; position: sticky; top: 32px; box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06); }
        .acrb-price-hero { margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; display: flex; align-items: baseline; gap: 6px; }
        
        .acrb-label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 700; color: #334155; }
        .acrb-readonly { background: #f8fafc; color: #64748b; cursor: not-allowed; border-color: #cbd5e1; }
        .acrb-input { width: 100%; height: 46px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.25s ease; box-sizing: border-box; }
        .acrb-input:hover { border-color: #94a3b8; }
        .acrb-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12); outline: none; }
        .acrb-select { appearance: none; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 16px center; background-size: 10px; padding-right: 40px; cursor: pointer; }

        .acrb-pay-option { display: flex; align-items: center; cursor: pointer; padding: 14px 18px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 600; color: #334155; background: #fff; transition: all 0.2s ease; margin-bottom: 10px; }
        .acrb-pay-option:hover { border-color: #94a3b8; background: #f8fafc; }
        .acrb-pay-option input[type="radio"] { accent-color: #3b82f6; width: 16px; height: 16px; margin-right: 12px; cursor: pointer; }

        .acrb-breakdown-box { padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin: 24px 0; }
        .acrb-breakdown-row { display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; color: #64748b; margin-bottom: 10px; }
        .acrb-breakdown-row strong { color: #1e293b; }
        .acrb-breakdown-total { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 12px; font-weight: 800; color: #0f172a; font-size: 15px; }

        .acrb-submit-btn { width: 100%; height: 48px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: inline-flex; align-items: center; justify-content: center; }
        .acrb-submit-btn:hover { background: linear-gradient(135deg, #2563eb 100%, #1d4ed8 100%); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); }

        @media (max-width: 900px) {
            .acrb-sidebar-right, .acrb-content-left { width: 100% !important; min-width: 100% !important; }
            .acrb-booking-card { position: static; }
        }
    </style>
    <?php return ob_get_clean();
}
add_shortcode( 'acrb_single_car', 'acrb_car_details_page_shortcode' );