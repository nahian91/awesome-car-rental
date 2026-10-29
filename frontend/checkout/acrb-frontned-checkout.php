<?php
/**
 * [acrb_checkout]
 * Ultra-Luxury Elite SaaS Checkout & Reservation Review Page with Secure AJAX Handler
 * Location: templates/shortcodes/checkout.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.9.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

add_shortcode( 'acrb_checkout', 'acrb_render_checkout_shortcode' );

/**
 * AJAX Handler for Secure Booking Submission
 */
function acrb_handle_checkout_ajax_submission(): void {
    check_ajax_referer( 'acrb_checkout_nonce', 'security' );

    $car_id      = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
    $cus_name    = isset( $_POST['cus_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_name'] ) ) : '';
    $cus_email   = isset( $_POST['cus_email'] ) ? sanitize_email( wp_unslash( $_POST['cus_email'] ) ) : '';
    $cus_phone   = isset( $_POST['cus_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_phone'] ) ) : '';
    $pickup_loc  = isset( $_POST['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_location'] ) ) : '';
    $return_loc  = isset( $_POST['return_location'] ) ? sanitize_text_field( wp_unslash( $_POST['return_location'] ) ) : '';
    $pickup_date = isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : '';
    $return_date = isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : '';
    $total       = isset( $_POST['order_total'] ) ? floatval( wp_unslash( $_POST['order_total'] ) ) : 0;
    $method      = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
    $user_id     = get_current_user_id();

    if ( ! $car_id || empty( $pickup_date ) || empty( $return_date ) ) {
        wp_send_json_error( array( 'message' => __( 'Missing required reservation parameters.', 'awesome-car-rental' ) ) );
    }

    // Insert Booking Post
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
            'return_location' => $return_loc,
            'pickup_date'     => $pickup_date,
            'return_date'     => $return_date,
            'total_price'     => $total,
            'payment_method'  => $method,
            'status'          => 'pending',
            'booking_time'    => current_time( 'mysql' ),
        ),
    ) );

    if ( is_wp_error( $booking_id ) || ! $booking_id ) {
        wp_send_json_error( array( 'message' => __( 'Failed to record reservation. Please try again.', 'awesome-car-rental' ) ) );
    }

    $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $booking_id ), esc_html__( 'pending', 'awesome-car-rental' ) );
    wp_update_post( array( 'ID' => $booking_id, 'post_title' => $acrb_msg ) );

    // Sync into CRM table (`dev_nahian_customers`) if it exists
    global $wpdb;
    $table_customers = $wpdb->prefix . 'dev_nahian_customers';
    $table_exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_customers ) );

    if ( $table_exists === $table_customers && ! empty( $cus_email ) ) {
        $existing_cust = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table_customers} WHERE email = %s", $cus_email ), ARRAY_A );
        if ( $existing_cust ) {
            $wpdb->update( $table_customers, array( 'name' => $cus_name, 'phone' => $cus_phone ), array( 'id' => absint( $existing_cust['id'] ) ), array( '%s', '%s' ), array( '%d' ) );
        } else {
            $wpdb->insert( $table_customers, array( 'name' => $cus_name, 'email' => $cus_email, 'phone' => $cus_phone ), array( '%s', '%s', '%s' ) );
        }
    }

    // Determine Thank You Page URL
    $success_page_id = get_option( 'acrb_success_page' );
    $redirect_url    = $success_page_id ? get_permalink( (int) $success_page_id ) : home_url( '/acrb-thanks/' );
    $redirect_url    = add_query_arg( 'booking_id', $booking_id, $redirect_url );

    wp_send_json_success( array( 'redirect_url' => esc_url_raw( $redirect_url ) ) );
}
add_action( 'wp_ajax_acrb_process_checkout', 'acrb_handle_checkout_ajax_submission' );
add_action( 'wp_ajax_nopriv_acrb_process_checkout', 'acrb_handle_checkout_ajax_submission' );

function acrb_render_checkout_shortcode(): string {
    if ( ! is_user_logged_in() ) {
        $login_page_url = home_url( '/acrb-login/' );
        $final_url      = add_query_arg( 'redirect_to', rawurlencode( home_url( $_SERVER['REQUEST_URI'] ?? '' ) ), $login_page_url );
        return '<script>window.location.href=' . wp_json_encode( esc_url_raw( $final_url ) ) . ';</script><div style="padding:60px; text-align:center; font-family:-apple-system,sans-serif; color:#64748b;">Redirecting to secure login portal...</div>';
    }

    // Retrieve GET parameters submitted from the single car booking form
    $car_id      = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
    $pickup_date = isset( $_GET['pdate'] ) ? sanitize_text_field( wp_unslash( $_GET['pdate'] ) ) : ( isset( $_GET['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_GET['pickup_date'] ) ) : '' );
    $return_date = isset( $_GET['rdate'] ) ? sanitize_text_field( wp_unslash( $_GET['rdate'] ) ) : ( isset( $_GET['return_date'] ) ? sanitize_text_field( wp_unslash( $_GET['return_date'] ) ) : '' );
    $pickup_loc  = isset( $_GET['ploc'] ) ? sanitize_text_field( wp_unslash( $_GET['ploc'] ) ) : ( isset( $_GET['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_GET['pickup_location'] ) ) : '' );
    $return_loc  = isset( $_GET['dloc'] ) ? sanitize_text_field( wp_unslash( $_GET['dloc'] ) ) : ( isset( $_GET['return_location'] ) ? sanitize_text_field( wp_unslash( $_GET['return_location'] ) ) : $pickup_loc );

    if ( ! $car_id || 'acrb_cars' !== get_post_type( $car_id ) || empty( $pickup_date ) || empty( $return_date ) ) {
        $fleet_url = home_url( '/acrb-cars/' );
        return sprintf(
            '<div class="acrb-checkout-error" style="max-width:580px; margin:80px auto; padding:50px 40px; background:#fff; border:1px solid #e2e8f0; border-radius:20px; text-align:center; font-family:-apple-system,sans-serif; box-shadow:0 10px 25px -5px rgba(15,23,42,0.05);">
                <h3 style="color:#0f172a; margin-top:0; font-size:22px; font-weight:900; letter-spacing:-0.02em;">%s</h3>
                <p style="color:#64748b; font-size:14px; margin:8px 0 28px 0; line-height:1.6;">%s</p>
                <a href="%s" style="background:#4f46e5; color:#fff; padding:12px 26px; border-radius:10px; text-decoration:none; font-weight:700; font-size:14px;">%s</a>
            </div>',
            esc_html__( 'Session Expired or Invalid', 'awesome-car-rental' ),
            esc_html__( 'Your checkout parameters are incomplete. Please select your desired vehicle and schedule from the fleet inventory.', 'awesome-car-rental' ),
            esc_url( $fleet_url ),
            esc_html__( 'Browse Available Fleet', 'awesome-car-rental' )
        );
    }

    // Fetch Dynamic Styling & Typography Tokens from Style Customizer
    $primary_color   = sanitize_hex_color( (string) get_option( 'acrb_style_primary_color', '#4f46e5' ) );
    $secondary_color = sanitize_hex_color( (string) get_option( 'acrb_style_secondary_color', '#64748b' ) );
    $text_color      = sanitize_hex_color( (string) get_option( 'acrb_style_text_color', '#0f172a' ) );
    $accent_color    = sanitize_hex_color( (string) get_option( 'acrb_style_accent_color', '#059669' ) );
    $font_slug       = sanitize_text_field( (string) get_option( 'acrb_style_font_family', 'jakarta' ) );

    $font_stacks = array(
        'jakarta'    => "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif",
        'inter'      => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
        'poppins'    => "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif",
        'montserrat' => "'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif",
        'roboto'     => "'Roboto', -apple-system, BlinkMacSystemFont, sans-serif",
    );
    $selected_font = $font_stacks[ $font_slug ] ?? $font_stacks['jakarta'];

    $current_user  = wp_get_current_user();
    $full_name     = trim( (string) $current_user->first_name . ' ' . (string) $current_user->last_name ) ?: $current_user->display_name;
    $user_phone    = get_user_meta( $current_user->ID, 'billing_phone', true ) ?: get_user_meta( $current_user->ID, 'phone', true );
    $display_phone = ! empty( $user_phone ) ? (string) $user_phone : __( 'Missing in Profile', 'awesome-car-rental' );

    // Pricing Calculations
    $price = floatval( get_post_meta( $car_id, 'daily_price', true ) 
          ?: get_post_meta( $car_id, 'price_per_day', true ) 
          ?: get_post_meta( $car_id, 'rate_per_day', true ) ?: 0 );

    $security_deposit = floatval( get_post_meta( $car_id, 'acrb_security_deposit', true ) ?: 0 );
    $insurance_fee    = floatval( get_post_meta( $car_id, 'acrb_insurance_fee', true ) ?: 0 );
    
    $currency    = get_option( 'acrb_currency', '$' );
    $rental_unit = get_option( 'acrb_rental_unit', 'day' );

    $days        = ceil( abs( strtotime( $return_date ) - strtotime( $pickup_date ) ) / 86400 );
    $days        = ( $days <= 0 ) ? 1 : (int) $days;
    $subtotal    = $days * $price;
    $grand_total = $subtotal + $security_deposit + $insurance_fee;

    $hero_img = get_the_post_thumbnail_url( $car_id, 'large' );
    $vin      = (string) ( get_post_meta( $car_id, 'vin_number', true ) ?: get_post_meta( $car_id, 'vin', true ) );

    // Global Add-on Extras
    $global_extras = get_option( 'acrb_global_addon_extras', get_option( 'acrb_custom_amenities', array() ) );
    $car_amenities = get_post_meta( $car_id, 'acrb_car_amenities', true ) ?: array();

    $available_extras = array();
    if ( ! empty( $global_extras ) ) {
        foreach ( $global_extras as $ge ) {
            $g_name  = is_array( $ge ) ? ( $ge['name'] ?? '' ) : $ge;
            $g_price = is_array( $ge ) ? floatval( $ge['price'] ?? 15.00 ) : 15.00;
            $is_en   = true;
            if ( ! empty( $car_amenities ) ) {
                foreach ( $car_amenities as $ca ) {
                    if ( ( $ca['name'] ?? '' ) === $g_name && empty( $ca['enabled'] ) ) {
                        $is_en = false;
                        break;
                    }
                }
            }
            if ( $is_en && ! empty( $g_name ) ) {
                $available_extras[] = array(
                    'name'  => $g_name,
                    'price' => $g_price,
                );
            }
        }
    }

    $enabled_methods = array();
    if ( get_option( 'acrb_enable_pay_later' ) === 'yes' ) {
        $enabled_methods['pay_later'] = __( 'Pay at Counter / Terminal Pickup', 'awesome-car-rental' );
    }
    if ( get_option( 'acrb_enable_bank' ) === 'yes' ) {
        $enabled_methods['bank'] = __( 'Direct Bank Wire Transfer (BACS)', 'awesome-car-rental' );
    }
    if ( get_option( 'acrb_enable_cash' ) === 'yes' ) {
        $enabled_methods['cash'] = __( 'Secure Cash on Hand', 'awesome-car-rental' );
    }
    if ( empty( $enabled_methods ) ) {
        $enabled_methods['pay_later'] = __( 'Pay at Counter / Terminal Pickup', 'awesome-car-rental' );
    }

    $ajax_nonce = wp_create_nonce( 'acrb_checkout_nonce' );

    ob_start();
    ?>
    
    <style>
        :root {
            --acrb-checkout-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-checkout-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-checkout-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-checkout-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-checkout-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-checkout-wrapper {
            max-width: 1200px;
            margin: 50px auto;
            font-family: var(--acrb-checkout-font);
            color: var(--acrb-checkout-text);
            box-sizing: border-box;
        }
        .acrb-checkout-wrapper *,
        .acrb-checkout-wrapper *::before,
        .acrb-checkout-wrapper *::after {
            box-sizing: border-box;
        }

        /* SVG Icon Standards */
        .acrb-checkout-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Solid Flat Progress Steps Header */
        .acrb-checkout-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 36px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px 36px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .acrb-step-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 700;
            color: var(--acrb-checkout-secondary);
        }
        .acrb-step-item.active {
            color: var(--acrb-checkout-text);
        }
        .acrb-step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f1f5f9;
            color: var(--acrb-checkout-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
        }
        .acrb-step-item.active .acrb-step-num {
            background: var(--acrb-checkout-primary);
            color: #ffffff;
        }

        .acrb-checkout-grid {
            display: grid;
            grid-template-columns: 1fr 480px;
            gap: 36px;
            align-items: flex-start;
        }

        /* Solid Card Scaffolding */
        .acrb-checkout-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 28px;
        }
        .acrb-checkout-card:last-child {
            margin-bottom: 0;
        }
        .acrb-checkout-title {
            font-size: 17.5px;
            font-weight: 900;
            color: var(--acrb-checkout-text);
            margin: 0 0 20px 0;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 14px;
        }
        .acrb-checkout-title .acrb-checkout-icon {
            color: var(--acrb-checkout-primary);
            width: 22px;
            height: 22px;
        }

        /* Solid Review Box */
        .acrb-review-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
        .acrb-review-vehicle {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .acrb-review-thumb {
            width: 140px;
            height: 95px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            flex-shrink: 0;
        }
        .acrb-review-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .acrb-review-info h4 {
            margin: 0 0 6px 0;
            font-size: 19px;
            font-weight: 900;
            color: var(--acrb-checkout-text);
            letter-spacing: -0.02em;
        }
        .acrb-itinerary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
        }
        .acrb-itinerary-pill {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 12px;
            color: var(--acrb-checkout-secondary);
            font-weight: 600;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .acrb-itinerary-pill strong {
            color: var(--acrb-checkout-text);
            font-size: 14px;
            font-weight: 800;
        }

        /* Extras Grid */
        .acrb-extras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 14px;
        }
        .acrb-extra-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }
        .acrb-extra-option:hover {
            border-color: var(--acrb-checkout-primary);
            background: #ffffff;
        }
        .acrb-extra-option input[type="checkbox"] {
            accent-color: var(--acrb-checkout-primary);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Form Controls */
        .acrb-checkout-field {
            margin-bottom: 18px;
        }
        .acrb-checkout-field label {
            display: block;
            font-size: 13px;
            font-weight: 800;
            color: var(--acrb-checkout-text);
            margin-bottom: 8px;
        }
        .acrb-checkout-input {
            width: 100% !important;
            height: 46px !important;
            padding: 0 16px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            color: var(--acrb-checkout-text) !important;
            background: #f8fafc !important;
            font-family: inherit !important;
            line-height: 44px !important;
        }
        .acrb-checkout-input:read-only {
            background: #f1f5f9 !important;
            color: var(--acrb-checkout-secondary) !important;
            cursor: not-allowed;
        }

        /* Coupon Section */
        .acrb-coupon-wrap {
            display: flex;
            gap: 10px;
        }
        .acrb-coupon-btn {
            background: var(--acrb-checkout-primary);
            color: #ffffff;
            border: none;
            padding: 0 22px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 13.5px;
            cursor: pointer;
            transition: opacity 0.15s ease;
            white-space: nowrap;
        }
        .acrb-coupon-btn:hover {
            opacity: 0.9;
        }

        .acrb-pay-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 15px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            background: #ffffff;
            transition: border-color 0.15s ease, background-color 0.15s ease;
            margin-bottom: 12px;
        }
        .acrb-pay-option:hover {
            border-color: var(--acrb-checkout-primary);
            background: #f8fafc;
        }
        .acrb-pay-option input[type="radio"] {
            accent-color: var(--acrb-checkout-primary);
            width: 18px;
            height: 18px;
            margin-right: 14px;
            cursor: pointer;
        }

        /* Solid Card-Based Price Breakdown Section */
        .acrb-breakdown-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .acrb-bd-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 14px;
            color: #334155;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .acrb-bd-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .acrb-bd-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-weight: 600;
        }
        .acrb-bd-label .acrb-checkout-icon {
            width: 16px;
            height: 16px;
            color: var(--acrb-checkout-primary);
            margin-top: 2px;
        }
        .acrb-bd-sub {
            display: block;
            font-size: 11.5px;
            color: var(--acrb-checkout-secondary);
            margin-top: 3px;
            font-weight: 500;
        }
        .acrb-bd-val {
            font-weight: 800;
            color: var(--acrb-checkout-text);
        }

        .acrb-summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px dashed #cbd5e1;
            padding-top: 18px;
            margin-top: 4px;
            font-weight: 900;
            color: var(--acrb-checkout-text);
            font-size: 20px;
        }

        .acrb-btn-confirm {
            width: 100%;
            height: 50px;
            background: var(--acrb-checkout-primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            transition: opacity 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: inherit;
        }
        .acrb-btn-confirm:hover {
            opacity: 0.92;
        }
        .acrb-btn-confirm:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .acrb-trust-badges {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 20px;
            font-size: 12.5px;
            color: var(--acrb-checkout-secondary);
            font-weight: 700;
        }
        .acrb-trust-badge-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .acrb-trust-badge-item .acrb-checkout-icon {
            width: 15px;
            height: 15px;
            color: var(--acrb-checkout-accent);
        }

        @media (max-width: 900px) {
            .acrb-checkout-grid {
                grid-template-columns: 1fr;
            }
            .acrb-checkout-steps {
                display: none;
            }
        }
    </style>

    <div class="acrb-checkout-wrapper">
        <!-- Progress Steps -->
        <div class="acrb-checkout-steps">
            <div class="acrb-step-item">
                <div class="acrb-step-num">✓</div>
                <span><?php esc_html_e( '1. Select Vehicle', 'awesome-car-rental' ); ?></span>
            </div>
            <div class="acrb-step-item active">
                <div class="acrb-step-num">2</div>
                <span><?php esc_html_e( '2. Review & Secure Checkout', 'awesome-car-rental' ); ?></span>
            </div>
            <div class="acrb-step-item">
                <div class="acrb-step-num">3</div>
                <span><?php esc_html_e( '3. Instant Handover Confirmation', 'awesome-car-rental' ); ?></span>
            </div>
        </div>

        <form id="acrb-checkout-form">
            <input type="hidden" id="acrb_car_id" value="<?php echo absint( $car_id ); ?>">
            <input type="hidden" id="acrb_pickup_date" value="<?php echo esc_attr( $pickup_date ); ?>">
            <input type="hidden" id="acrb_return_date" value="<?php echo esc_attr( $return_date ); ?>">
            <input type="hidden" id="acrb_pickup_loc" value="<?php echo esc_attr( $pickup_loc ); ?>">
            <input type="hidden" id="acrb_return_loc" value="<?php echo esc_attr( $return_loc ); ?>">
            <input type="hidden" id="acrb_final_total_input" value="<?php echo esc_attr( (string) $grand_total ); ?>">

            <div class="acrb-checkout-grid">
                
                <!-- Left Column: Review, Extras & Customer Details -->
                <div>
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M5 17h14v-5l-2-6H7l-2 6v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
                            <span><?php esc_html_e( 'Review Vehicle & Itinerary', 'awesome-car-rental' ); ?></span>
                        </h2>

                        <div class="acrb-review-box">
                            <div class="acrb-review-vehicle">
                                <div class="acrb-review-thumb">
                                    <?php if ( $hero_img ) : ?>
                                        <img src="<?php echo esc_url( $hero_img ); ?>" alt="">
                                    <?php endif; ?>
                                </div>
                                <div class="acrb-review-info">
                                    <h4><?php echo esc_html( get_the_title( $car_id ) ); ?></h4>
                                    <div class="acrb-review-meta">
                                        <?php esc_html_e( 'Vehicle Ref:', 'awesome-car-rental' ); ?> <strong>#<?php echo esc_html( $vin ?: $car_id ); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="acrb-itinerary-grid">
                                <div class="acrb-itinerary-pill">
                                    <span style="display:inline-flex; align-items:center; gap:5px; color:var(--acrb-checkout-primary);">
                                        <svg class="acrb-checkout-icon" style="width:14px;height:14px;" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <?php esc_html_e( 'Pickup Date', 'awesome-car-rental' ); ?>
                                    </span>
                                    <strong><?php echo esc_html( $pickup_date ); ?></strong>
                                </div>
                                <div class="acrb-itinerary-pill">
                                    <span style="display:inline-flex; align-items:center; gap:5px; color:var(--acrb-checkout-primary);">
                                        <svg class="acrb-checkout-icon" style="width:14px;height:14px;" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <?php esc_html_e( 'Return Date', 'awesome-car-rental' ); ?>
                                    </span>
                                    <strong><?php echo esc_html( $return_date ); ?></strong>
                                </div>
                                <div class="acrb-itinerary-pill">
                                    <span style="display:inline-flex; align-items:center; gap:5px; color:var(--acrb-checkout-primary);">
                                        <svg class="acrb-checkout-icon" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                        <?php esc_html_e( 'Pickup Location', 'awesome-car-rental' ); ?>
                                    </span>
                                    <strong><?php echo esc_html( $pickup_loc ?: __( 'Main Terminal', 'awesome-car-rental' ) ); ?></strong>
                                </div>
                                <div class="acrb-itinerary-pill">
                                    <span style="display:inline-flex; align-items:center; gap:5px; color:var(--acrb-checkout-primary);">
                                        <svg class="acrb-checkout-icon" style="width:14px;height:14px;" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                        <?php esc_html_e( 'Return Location', 'awesome-car-rental' ); ?>
                                    </span>
                                    <strong><?php echo esc_html( $return_loc ?: __( 'Main Terminal', 'awesome-car-rental' ) ); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ( ! empty( $available_extras ) ) : ?>
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            <span><?php esc_html_e( 'Optional Add-on Extras & Protection', 'awesome-car-rental' ); ?></span>
                        </h2>
                        <div class="acrb-extras-grid">
                            <?php foreach ( $available_extras as $idx => $ext ) : ?>
                                <label class="acrb-extra-option">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <input type="checkbox" name="acrb_selected_extras[]" value="<?php echo esc_attr( $ext['name'] ); ?>" data-price="<?php echo esc_attr( (string) $ext['price'] ); ?>" class="acrb-extra-checkbox">
                                        <span style="font-size: 13.5px; font-weight: 800; color: #334155;"><?php echo esc_html( $ext['name'] ); ?></span>
                                    </div>
                                    <span style="font-size: 13px; font-weight: 900; color: var(--acrb-checkout-primary);">+<?php echo esc_html( $currency . number_format( $ext['price'], 2 ) ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span><?php esc_html_e( 'Verified Customer Account', 'awesome-car-rental' ); ?></span>
                        </h2>

                        <div class="acrb-checkout-field">
                            <label><?php esc_html_e( 'Full Legal Name', 'awesome-car-rental' ); ?></label>
                            <input type="text" id="acrb_cus_name" value="<?php echo esc_attr( $full_name ); ?>" readonly class="acrb-checkout-input">
                        </div>

                        <div class="acrb-checkout-field">
                            <label><?php esc_html_e( 'Verified Email Address', 'awesome-car-rental' ); ?></label>
                            <input type="email" id="acrb_cus_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" readonly class="acrb-checkout-input">
                        </div>

                        <div class="acrb-checkout-field" style="margin-bottom: 0;">
                            <label><?php esc_html_e( 'Registered Phone Number', 'awesome-car-rental' ); ?></label>
                            <input type="text" id="acrb_cus_phone" value="<?php echo esc_attr( $display_phone ); ?>" readonly class="acrb-checkout-input">
                        </div>
                    </div>
                </div>

                <!-- Right Column: Payment, Promo & Breakdown -->
                <div>
                    <!-- Payment Card -->
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            <span><?php esc_html_e( 'Select Payment Method', 'awesome-car-rental' ); ?></span>
                        </h2>

                        <div style="margin-bottom: 0;">
                            <?php foreach ( $enabled_methods as $k => $v ) : ?>
                                <label class="acrb-pay-option">
                                    <input type="radio" name="payment_method" value="<?php echo esc_attr( $k ); ?>" required>
                                    <span><?php echo esc_html( $v ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Promo Code Card -->
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                            <span><?php esc_html_e( 'Have a Promo Code?', 'awesome-car-rental' ); ?></span>
                        </h2>
                        <div class="acrb-coupon-wrap">
                            <input type="text" id="acrb_coupon_code" placeholder="<?php esc_attr_e( 'Enter code (e.g. VIP10)', 'awesome-car-rental' ); ?>" class="acrb-checkout-input" style="height: 42px !important;">
                            <button type="button" id="acrb_apply_coupon" class="acrb-coupon-btn"><?php esc_html_e( 'Apply', 'awesome-car-rental' ); ?></button>
                        </div>
                        <div id="acrb_coupon_msg" style="font-size: 12.5px; font-weight: 700; margin-top: 8px; display: none;"></div>
                    </div>

                    <!-- Price Breakdown Summary Card -->
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                            <span><?php esc_html_e( 'Price Breakdown Summary', 'awesome-car-rental' ); ?></span>
                        </h2>

                        <div class="acrb-breakdown-card">
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M5 17h14v-5l-2-6H7l-2 6v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
                                    <div>
                                        <span><?php echo esc_html( get_the_title( $car_id ) ); ?></span>
                                        <span class="acrb-bd-sub"><?php echo absint( $days ); ?> <?php echo esc_html( ucfirst( $rental_unit ) ); ?>(s) × <?php echo esc_html( $currency . number_format( $price, 2 ) ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_sum_subtotal"><?php echo esc_html( $currency . number_format( $subtotal, 2 ) ); ?></div>
                            </div>

                            <div class="acrb-bd-item" id="acrb_extras_div" style="display: none;">
                                <div class="acrb-bd-label">
                                    <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                    <div>
                                        <span><?php esc_html_e( 'Selected Add-on Extras', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Equipment & Protection', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_sum_extras"><?php echo esc_html( $currency ); ?>0.00</div>
                            </div>

                            <div class="acrb-bd-item" id="acrb_discount_div" style="display: none; color: var(--acrb-checkout-accent);">
                                <div class="acrb-bd-label" style="color: var(--acrb-checkout-accent);">
                                    <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                    <div>
                                        <span><?php esc_html_e( 'Promo Code Discount', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Special Promotion', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_sum_discount">-<?php echo esc_html( $currency ); ?>0.00</div>
                            </div>

                            <?php if ( $security_deposit > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    <div>
                                        <span><?php esc_html_e( 'Security Deposit', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Fully Refundable Hold', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $currency . number_format( $security_deposit, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ( $insurance_fee > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                                    <div>
                                        <span><?php esc_html_e( 'Mandatory Insurance', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Coverage Protection', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $currency . number_format( $insurance_fee, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="acrb-summary-total">
                            <span><?php esc_html_e( 'Total Amount Due', 'awesome-car-rental' ); ?></span>
                            <span style="color: var(--acrb-checkout-primary);"><?php echo esc_html( $currency ); ?><span id="acrb_sum_grand_total"><?php echo number_format( $grand_total, 2 ); ?></span></span>
                        </div>

                        <div style="margin: 20px 0 14px 0;">
                            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; color: var(--acrb-checkout-secondary); font-weight: 700;">
                                <input type="checkbox" id="acrb_terms_check" required style="margin-top: 3px; width: 16px; height: 16px; accent-color: var(--acrb-checkout-primary);">
                                <span><?php esc_html_e( 'I agree to the rental terms, driver requirements, and cancellation policy.', 'awesome-car-rental' ); ?></span>
                            </label>
                        </div>

                        <button type="button" id="acrb_submit_reservation_btn" class="acrb-btn-confirm">
                            <svg class="acrb-checkout-icon" style="width:18px;height:18px;" viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"></path></svg>
                            <span id="acrb_btn_text"><?php esc_html_e( 'Confirm & Place Secure Reservation', 'awesome-car-rental' ); ?></span>
                        </button>

                        <div class="acrb-trust-badges">
                            <div class="acrb-trust-badge-item">
                                <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span><?php esc_html_e( 'Free Cancellation', 'awesome-car-rental' ); ?></span>
                            </div>
                            <div class="acrb-trust-badge-item">
                                <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0110 0v4"></path></svg>
                                <span><?php esc_html_e( '256-Bit SSL Encryption', 'awesome-car-rental' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const baseSubtotal = <?php echo floatval( $subtotal ); ?>;
        const deposit = <?php echo floatval( $security_deposit ); ?>;
        const insurance = <?php echo floatval( $insurance_fee ); ?>;
        const currencySymbol = '<?php echo esc_js( $currency ); ?>';

        const extraCheckboxes = document.querySelectorAll('.acrb-extra-checkbox');
        const sumGrandTotal = document.getElementById('acrb_sum_grand_total');
        const sumExtras = document.getElementById('acrb_sum_extras');
        const extrasDiv = document.getElementById('acrb_extras_div');
        const finalTotalInput = document.getElementById('acrb_final_total_input');
        
        const couponBtn = document.getElementById('acrb_apply_coupon');
        const couponInput = document.getElementById('acrb_coupon_code');
        const couponMsg = document.getElementById('acrb_coupon_msg');
        const discountDiv = document.getElementById('acrb_discount_div');
        const sumDiscount = document.getElementById('acrb_sum_discount');

        let discountRate = 0;

        function recalcCheckout() {
            let extrasTotal = 0;
            extraCheckboxes.forEach(cb => {
                if (cb.checked) {
                    extrasTotal += parseFloat(cb.dataset.price || 0);
                }
            });

            if (extrasTotal > 0) {
                sumExtras.textContent = currencySymbol + extrasTotal.toFixed(2);
                extrasDiv.style.display = 'flex';
            } else {
                extrasDiv.style.display = 'none';
            }

            let computedSub = baseSubtotal + extrasTotal;
            let discountAmt = computedSub * discountRate;

            if (discountAmt > 0) {
                sumDiscount.textContent = '-' + currencySymbol + discountAmt.toFixed(2);
                discountDiv.style.display = 'flex';
            } else {
                discountDiv.style.display = 'none';
            }

            let grand = computedSub - discountAmt + deposit + insurance;
            sumGrandTotal.textContent = grand.toFixed(2);
            finalTotalInput.value = grand.toFixed(2);
        }

        extraCheckboxes.forEach(cb => {
            cb.addEventListener('change', recalcCheckout);
        });

        if (couponBtn && couponInput) {
            couponBtn.addEventListener('click', function() {
                const code = couponInput.value.trim().toUpperCase();
                if (code === 'VIP10' || code === 'AWESOME10') {
                    discountRate = 0.10;
                    couponMsg.style.color = '#059669';
                    couponMsg.textContent = 'Success! 10% discount applied.';
                    couponMsg.style.display = 'block';
                    recalcCheckout();
                } else if (code === 'VIP20') {
                    discountRate = 0.20;
                    couponMsg.style.color = '#059669';
                    couponMsg.textContent = 'Success! 20% discount applied.';
                    couponMsg.style.display = 'block';
                    recalcCheckout();
                } else {
                    discountRate = 0;
                    couponMsg.style.color = '#dc2626';
                    couponMsg.textContent = 'Invalid promo code.';
                    couponMsg.style.display = 'block';
                    recalcCheckout();
                }
            });
        }

        // AJAX Checkout Submission Handler
        const submitBtn = document.getElementById('acrb_submit_reservation_btn');
        const btnText = document.getElementById('acrb_btn_text');
        const termsCheck = document.getElementById('acrb_terms_check');

        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (!termsCheck.checked) {
                    alert('Please agree to the rental terms and cancellation policy before proceeding.');
                    termsCheck.focus();
                    return;
                }

                const paymentMethodInput = document.querySelector('input[name="payment_method"]:checked');
                if (!paymentMethodInput) {
                    alert('Please select a payment method.');
                    return;
                }

                submitBtn.disabled = true;
                btnText.textContent = 'Processing Reservation...';

                const formData = new URLSearchParams();
                formData.append('action', 'acrb_process_checkout');
                formData.append('security', '<?php echo esc_js( $ajax_nonce ); ?>');
                formData.append('car_id', document.getElementById('acrb_car_id').value);
                formData.append('pickup_date', document.getElementById('acrb_pickup_date').value);
                formData.append('return_date', document.getElementById('acrb_return_date').value);
                formData.append('pickup_location', document.getElementById('acrb_pickup_loc').value);
                formData.append('return_location', document.getElementById('acrb_return_loc').value);
                formData.append('cus_name', document.getElementById('acrb_cus_name').value);
                formData.append('cus_email', document.getElementById('acrb_cus_email').value);
                formData.append('cus_phone', document.getElementById('acrb_cus_phone').value);
                formData.append('payment_method', paymentMethodInput.value);
                formData.append('order_total', finalTotalInput.value);

                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.redirect_url) {
                        window.location.href = data.data.redirect_url;
                    } else {
                        alert(data.data && data.data.message ? data.data.message : 'An error occurred. Please try again.');
                        submitBtn.disabled = false;
                        btnText.textContent = 'Confirm & Place Secure Reservation';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network connection error. Please try again.');
                    submitBtn.disabled = false;
                    btnText.textContent = 'Confirm & Place Secure Reservation';
                });
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}