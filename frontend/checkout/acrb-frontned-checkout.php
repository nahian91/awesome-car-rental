<?php
/**
 * [acrb_checkout]
 * Ultra-Luxury Elite SaaS Checkout & Reservation Review Page with template_redirect Handler
 * Location: templates/shortcodes/checkout.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * Handle checkout form submission via template_redirect for flawless redirection.
 */
function acrb_handle_native_checkout_submission() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['acrb_checkout_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acrb_checkout_nonce'] ) ), 'acrb_submit_reservation' ) ) {
        wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'awesome-car-rental' ) );
    }

    global $wpdb;
    $table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
    $table_cars     = $wpdb->prefix . 'dev_nahian_cars';

    $acrb_car_id      = isset( $_POST['car_id'] ) ? absint( wp_unslash( $_POST['car_id'] ) ) : 0;
    $acrb_cus_name    = isset( $_POST['cus_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_name'] ) ) : '';
    $acrb_cus_email   = isset( $_POST['cus_email'] ) ? sanitize_email( wp_unslash( $_POST['cus_email'] ) ) : '';
    $acrb_cus_phone   = isset( $_POST['cus_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_phone'] ) ) : '';
    $acrb_pickup_loc  = isset( $_POST['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_location'] ) ) : 'Main Terminal';
    $acrb_return_loc  = isset( $_POST['return_location'] ) ? sanitize_text_field( wp_unslash( $_POST['return_location'] ) ) : 'Main Terminal';
    $acrb_pickup_date = isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : gmdate( 'Y-m-d H:i' );
    $acrb_return_date = isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : gmdate( 'Y-m-d H:i', strtotime( '+3 days' ) );
    $acrb_total       = isset( $_POST['order_total'] ) ? floatval( wp_unslash( $_POST['order_total'] ) ) : 0;
    $acrb_method      = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'pay_later';
    
    // Capture Insurance, Border Permit, and Promo Code from POST submission
    $acrb_insurance   = isset( $_POST['insurance_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['insurance_plan'] ) ) : '';
    $acrb_border      = isset( $_POST['border_permit'] ) ? sanitize_text_field( wp_unslash( $_POST['border_permit'] ) ) : '';
    $acrb_promo       = isset( $_POST['promo_code'] ) ? sanitize_text_field( wp_unslash( $_POST['promo_code'] ) ) : '';

    if ( $acrb_car_id > 0 && ! empty( $acrb_pickup_date ) && ! empty( $acrb_return_date ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $car_record = $wpdb->get_row( $wpdb->prepare( "SELECT name, brand FROM {$table_cars} WHERE id = %d", $acrb_car_id ), ARRAY_A );
        $car_title  = $car_record ? trim( ( $car_record['brand'] ?? '' ) . ' ' . $car_record['name'] ) : __( 'Fleet Vehicle', 'awesome-car-rental' );

        $booking_code = 'BK-' . gmdate('Y') . '-' . wp_rand( 1000, 9999 );

        // Insert complete data directly into custom MySQL table.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $inserted = $wpdb->insert(
            $table_bookings,
            array(
                'booking_code'    => $booking_code,
                'car_id'          => absint( $acrb_car_id ),
                'car_name'        => $car_title,
                'customer_name'   => $acrb_cus_name,
                'customer_email'  => $acrb_cus_email,
                'customer_phone'  => $acrb_cus_phone,
                'pickup_location' => $acrb_pickup_loc,
                'return_location' => $acrb_return_loc,
                'pickup_date'     => $acrb_pickup_date,
                'return_date'     => $acrb_return_date,
                'insurance_plan'  => $acrb_insurance,
                'border_permit'   => $acrb_border,
                'promo_code'      => $acrb_promo,
                'total_amount'    => $acrb_total,
                'payment_method'  => $acrb_method,
                'booking_status'  => 'pending',
                'created_at'      => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            $acrb_booking_id = $wpdb->insert_id;
            delete_transient( 'acrb_dashboard_revenue' );

            $acrb_redirect_url = home_url( '/acrb-thanks/?booking_id=' . $acrb_booking_id );
            wp_safe_redirect( $acrb_redirect_url );
            exit;
        } else {
            wp_die( 'Database Insertion Error: ' . esc_html( $wpdb->last_error ) );
        }
    }
}
add_action( 'template_redirect', 'acrb_handle_native_checkout_submission' );

add_shortcode( 'acrb_checkout', 'acrb_render_checkout_shortcode' );

function acrb_render_checkout_shortcode(): string {
    if ( ! is_user_logged_in() ) {
        $acrb_login_page_url = home_url( '/acrb-login/' );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $acrb_request_uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $acrb_final_url      = add_query_arg( 'redirect_to', rawurlencode( home_url( $acrb_request_uri ) ), $acrb_login_page_url );
        return '<script>window.location.href=' . wp_json_encode( esc_url_raw( $acrb_final_url ) ) . ';</script><div style="padding:60px; text-align:center; font-family:-apple-system,sans-serif; color:#64748b;">Redirecting to secure login portal...</div>';
    }

    global $wpdb;
    $table_cars   = $wpdb->prefix . 'dev_nahian_cars';
    $table_promos = $wpdb->prefix . 'dev_nahian_promo_codes';

    $acrb_enabled_methods = array();
    if ( get_option( 'acrb_enable_pay_later' ) === 'yes' ) {
        $acrb_enabled_methods['pay_later'] = __( 'Book Now, Pay Later', 'awesome-car-rental' );
    }
    if ( get_option( 'acrb_enable_bank' ) === 'yes' ) {
        $acrb_enabled_methods['bank'] = __( 'Direct Bank Transfer (BACS)', 'awesome-car-rental' );
    }
    if ( get_option( 'acrb_enable_cash' ) === 'yes' ) {
        $acrb_enabled_methods['cash'] = __( 'Cash on Pickup', 'awesome-car-rental' );
    }
    if ( empty( $acrb_enabled_methods ) ) {
        $acrb_enabled_methods['pay_later'] = __( 'Book Now, Pay Later', 'awesome-car-rental' );
    }

    // Retrieve GET parameters from single car page
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_car_id      = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_pickup_date = isset( $_GET['pdate'] ) ? sanitize_text_field( wp_unslash( $_GET['pdate'] ) ) : gmdate( 'Y-m-d' );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_pickup_time = isset( $_GET['ptime'] ) ? sanitize_text_field( wp_unslash( $_GET['ptime'] ) ) : '10:00';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_return_date = isset( $_GET['rdate'] ) ? sanitize_text_field( wp_unslash( $_GET['rdate'] ) ) : gmdate( 'Y-m-d', strtotime( '+3 days' ) );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_return_time = isset( $_GET['rtime'] ) ? sanitize_text_field( wp_unslash( $_GET['rtime'] ) ) : '10:00';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_pickup_loc  = isset( $_GET['ploc'] ) ? sanitize_text_field( wp_unslash( $_GET['ploc'] ) ) : __( 'Main Terminal', 'awesome-car-rental' );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_return_loc  = isset( $_GET['dloc'] ) ? sanitize_text_field( wp_unslash( $_GET['dloc'] ) ) : $acrb_pickup_loc;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_sel_ins     = isset( $_GET['acrb_selected_insurance'] ) ? sanitize_text_field( wp_unslash( $_GET['acrb_selected_insurance'] ) ) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_sel_border  = isset( $_GET['acrb_selected_border'] ) ? sanitize_text_field( wp_unslash( $_GET['acrb_selected_border'] ) ) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_pass_method = isset( $_GET['pmethod'] ) ? sanitize_text_field( wp_unslash( $_GET['pmethod'] ) ) : key( $acrb_enabled_methods );

    if ( ! array_key_exists( $acrb_pass_method, $acrb_enabled_methods ) ) {
        $acrb_pass_method = key( $acrb_enabled_methods );
    }

    if ( ! $acrb_car_id ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $latest_car = $wpdb->get_row( "SELECT id FROM {$table_cars} WHERE status != 'retired' ORDER BY id DESC LIMIT 1", ARRAY_A );
        if ( $latest_car ) {
            $acrb_car_id = absint( $latest_car['id'] );
        }
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $car = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cars} WHERE id = %d", $acrb_car_id ), ARRAY_A );

    if ( ! $car ) {
        $acrb_fleet_url = home_url( '/acrb-cars/' );
        return sprintf(
            '<div class="acrb-checkout-error" style="max-width:580px; margin:80px auto; padding:50px 40px; background:#fff; border:1px solid #e2e8f0; border-radius:20px; text-align:center; font-family:-apple-system,sans-serif; box-shadow:0 10px 25px -5px rgba(15,23,42,0.05);">
                <h3 style="color:#0f172a; margin-top:0; font-size:22px; font-weight:900; letter-spacing:-0.02em;">%s</h3>
                <p style="color:#64748b; font-size:14px; margin:8px 0 28px 0; line-height:1.6;">%s</p>
                <a href="%s" style="background:#4f46e5; color:#fff; padding:12px 26px; border-radius:10px; text-decoration:none; font-weight:700; font-size:14px;">%s</a>
            </div>',
            esc_html__( 'Fleet Inventory Empty', 'awesome-car-rental' ),
            esc_html__( 'No active vehicles found in your database. Please add a car in your admin dashboard first.', 'awesome-car-rental' ),
            esc_url( $acrb_fleet_url ),
            esc_html__( 'Browse Fleet Inventory', 'awesome-car-rental' )
        );
    }

    $acrb_primary_color   = sanitize_hex_color( (string) get_option( 'acrb_style_primary_color', '#4f46e5' ) );
    $acrb_secondary_color = sanitize_hex_color( (string) get_option( 'acrb_style_secondary_color', '#64748b' ) );
    $acrb_text_color      = sanitize_hex_color( (string) get_option( 'acrb_style_text_color', '#0f172a' ) );
    $acrb_accent_color    = sanitize_hex_color( (string) get_option( 'acrb_style_accent_color', '#059669' ) );
    $acrb_font_slug       = sanitize_text_field( (string) get_option( 'acrb_style_font_family', 'jakarta' ) );

    $acrb_font_stacks = array(
        'jakarta'    => "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif",
        'inter'      => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
        'poppins'    => "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif",
        'montserrat' => "'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif",
        'roboto'     => "'Roboto', -apple-system, BlinkMacSystemFont, sans-serif",
    );
    $acrb_selected_font = $acrb_font_stacks[ $acrb_font_slug ] ?? $acrb_font_stacks['jakarta'];

    $acrb_current_user   = wp_get_current_user();
    $acrb_full_name      = trim( (string) $acrb_current_user->first_name . ' ' . (string) $acrb_current_user->last_name ) ?: $acrb_current_user->display_name;
    $acrb_user_phone     = get_user_meta( $acrb_current_user->ID, 'billing_phone', true ) ?: get_user_meta( $acrb_current_user->ID, 'phone', true );
    $acrb_display_phone  = ! empty( $acrb_user_phone ) ? (string) $acrb_user_phone : __( 'Missing in Profile', 'awesome-car-rental' );

    $acrb_price             = floatval( $car['price'] ?? 0.00 );
    $acrb_security_deposit = floatval( $car['security_deposit'] ?? 0.00 );
    $acrb_insurance_fee    = floatval( $car['insurance_fee'] ?? 0.00 );
    
    $acrb_currency     = get_option( 'acrb_currency', '£' );
    $acrb_rental_unit  = get_option( 'acrb_rental_unit', 'day' );

    $start_dt = new DateTime( $acrb_pickup_date . ' ' . $acrb_pickup_time );
    $end_dt   = new DateTime( $acrb_return_date . ' ' . $acrb_return_time );
    $diff_hrs = ( $end_dt->getTimestamp() - $start_dt->getTimestamp() ) / 3600;
    $acrb_days = max( 1, (int) ceil( $diff_hrs / 24 ) );
    
    $acrb_subtotal = $acrb_days * $acrb_price;

    $ins_cost = 0;
    $car_insurance_json = ! empty( $car['insurance_plans'] ) ? json_decode( (string) $car['insurance_plans'], true ) : array();
    if ( ! empty( $acrb_sel_ins ) && is_array( $car_insurance_json ) ) {
        foreach ( $car_insurance_json as $ins ) {
            if ( ( $ins['name'] ?? '' ) === $acrb_sel_ins ) {
                $p = floatval( $ins['price'] ?? 0 );
                $t = $ins['type'] ?? 'day';
                $ins_cost = ( 'day' === $t ) ? $p * $acrb_days : $p;
                break;
            }
        }
    }

    $border_cost = 0;
    $car_border_json = ! empty( $car['border_crossing'] ) ? json_decode( (string) $car['border_crossing'], true ) : array();
    if ( ! empty( $acrb_sel_border ) && is_array( $car_border_json ) ) {
        foreach ( $car_border_json as $b ) {
            if ( ( $b['country'] ?? '' ) === $acrb_sel_border ) {
                $f = floatval( $b['fee'] ?? 0 );
                $t = $b['type'] ?? 'flat';
                $border_cost = ( 'day' === $t ) ? $f * $acrb_days : $f;
                break;
            }
        }
    }

    $acrb_grand_total = $acrb_subtotal + $ins_cost + $border_cost + $acrb_security_deposit + $acrb_insurance_fee;

    $raw_img       = (string) ( $car['image'] ?? '' );
    $acrb_hero_img = '';
    if ( is_numeric( $raw_img ) && absint( $raw_img ) > 0 ) {
        $acrb_hero_img = wp_get_attachment_image_url( absint( $raw_img ), 'large' ) ?: '';
    } elseif ( ! empty( $raw_img ) && filter_var( $raw_img, FILTER_VALIDATE_URL ) ) {
        $acrb_hero_img = $raw_img;
    }

    $acrb_car_name   = esc_html( $car['name'] );
    $acrb_car_brand  = esc_html( $car['brand'] ?? '' );
    $acrb_full_title = trim( ( $acrb_car_brand ? $acrb_car_brand . ' ' : '' ) . $acrb_car_name );
    $acrb_vin        = (string) ( $car['vin'] ?? '' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $db_promos = $wpdb->get_results( "SELECT * FROM {$table_promos} WHERE status = 'active'", ARRAY_A );
    $promo_map = array();
    if ( ! empty( $db_promos ) ) {
        foreach ( $db_promos as $p ) {
            $promo_map[ strtoupper( trim( $p['code'] ) ) ] = array(
                'type'      => $p['discount_type'],
                'value'     => floatval( $p['discount_value'] ),
                'min_spend' => floatval( $p['min_spend'] ),
                'expiry'    => $p['expiry_date'],
            );
        }
    }

    ob_start();
    ?>
    
    <style>
        :root {
            --acrb-checkout-primary: <?php echo esc_html( $acrb_primary_color ); ?>;
            --acrb-checkout-secondary: <?php echo esc_html( $acrb_secondary_color ); ?>;
            --acrb-checkout-text: <?php echo esc_html( $acrb_text_color ); ?>;
            --acrb-checkout-accent: <?php echo esc_html( $acrb_accent_color ); ?>;
            --acrb-checkout-font: <?php echo esc_html( $acrb_selected_font ); ?>;
        }
        .acrb-checkout-wrapper { max-width: 1200px; margin: 50px auto; font-family: var(--acrb-checkout-font); color: var(--acrb-checkout-text); box-sizing: border-box; }
        .acrb-checkout-wrapper *, .acrb-checkout-wrapper *::before, .acrb-checkout-wrapper *::after { box-sizing: border-box; }
        .acrb-checkout-icon { display: inline-block; width: 18px; height: 18px; stroke-width: 2; stroke: currentColor; fill: none; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
        .acrb-checkout-steps { display: flex; justify-content: space-between; align-items: center; margin-bottom: 36px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px 36px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .acrb-step-item { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 700; color: var(--acrb-checkout-secondary); }
        .acrb-step-item.active { color: var(--acrb-checkout-text); }
        .acrb-step-num { width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: var(--acrb-checkout-secondary); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; }
        .acrb-step-item.active .acrb-step-num { background: var(--acrb-checkout-primary); color: #ffffff; }
        .acrb-checkout-grid { display: grid; grid-template-columns: 1fr 480px; gap: 36px; align-items: flex-start; }
        .acrb-checkout-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); margin-bottom: 28px; }
        .acrb-checkout-title { font-size: 17.5px; font-weight: 900; color: var(--acrb-checkout-text); margin: 0 0 20px 0; letter-spacing: -0.02em; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; }
        .acrb-review-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
        .acrb-review-vehicle { display: flex; gap: 20px; align-items: center; }
        .acrb-review-thumb { width: 140px; height: 95px; border-radius: 10px; overflow: hidden; border: 1px solid #cbd5e1; background: #ffffff; flex-shrink: 0; }
        .acrb-review-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .acrb-review-info h4 { margin: 0 0 6px 0; font-size: 19px; font-weight: 900; color: var(--acrb-checkout-text); }
        .acrb-itinerary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 18px; padding-top: 18px; border-top: 1px solid #e2e8f0; }
        .acrb-itinerary-pill { background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 10px; font-size: 12px; color: var(--acrb-checkout-secondary); font-weight: 600; display: flex; flex-direction: column; gap: 4px; }
        .acrb-itinerary-pill strong { color: var(--acrb-checkout-text); font-size: 13.5px; font-weight: 800; }
        .acrb-checkout-field { margin-bottom: 18px; }
        .acrb-checkout-field label { display: block; font-size: 13px; font-weight: 800; color: var(--acrb-checkout-text); margin-bottom: 8px; }
        .acrb-checkout-input { width: 100% !important; height: 46px !important; padding: 0 16px !important; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; font-size: 14px !important; color: var(--acrb-checkout-text) !important; background: #f8fafc !important; font-family: inherit !important; line-height: 44px !important; }
        .acrb-checkout-input:read-only { background: #f1f5f9 !important; color: var(--acrb-checkout-secondary) !important; cursor: not-allowed; }
        .acrb-coupon-wrap { display: flex; gap: 10px; }
        .acrb-coupon-btn { background: var(--acrb-checkout-primary); color: #ffffff; border: none; padding: 0 22px; border-radius: 8px; font-weight: 800; font-size: 13.5px; cursor: pointer; white-space: nowrap; }
        .acrb-pay-option { display: flex; align-items: center; cursor: pointer; padding: 15px 20px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 700; color: #334155; background: #ffffff; transition: all 0.15s ease; margin-bottom: 12px; }
        .acrb-pay-option.selected { border-color: var(--acrb-checkout-primary); background: #eef2ff; }
        .acrb-pay-option input[type="radio"] { accent-color: var(--acrb-checkout-primary); width: 18px; height: 18px; margin-right: 14px; cursor: pointer; }
        .acrb-breakdown-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 14px; }
        .acrb-bd-item { display: flex; justify-content: space-between; align-items: flex-start; font-size: 14px; color: #334155; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0; }
        .acrb-bd-item:last-child { border-bottom: none; padding-bottom: 0; }
        .acrb-bd-sub { display: block; font-size: 11.5px; color: var(--acrb-checkout-secondary); margin-top: 3px; font-weight: 500; }
        .acrb-bd-val { font-weight: 800; color: var(--acrb-checkout-text); }
        .acrb-summary-total { display: flex; justify-content: space-between; align-items: center; border-top: 2px dashed #cbd5e1; padding-top: 18px; margin-top: 4px; font-weight: 900; color: var(--acrb-checkout-text); font-size: 20px; }
        .acrb-btn-confirm { width: 100%; height: 50px; background: var(--acrb-checkout-primary); color: #ffffff; border: none; border-radius: 10px; font-weight: 800; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-family: inherit; }
        @media (max-width: 900px) { .acrb-checkout-grid { grid-template-columns: 1fr; } .acrb-checkout-steps { display: none; } }
    </style>

    <div class="acrb-checkout-wrapper">
        <div class="acrb-checkout-steps">
            <div class="acrb-step-item"><div class="acrb-step-num">✓</div><span><?php esc_html_e( '1. Select Vehicle', 'awesome-car-rental' ); ?></span></div>
            <div class="acrb-step-item active"><div class="acrb-step-num">2</div><span><?php esc_html_e( '2. Review & Secure Checkout', 'awesome-car-rental' ); ?></span></div>
            <div class="acrb-step-item"><div class="acrb-step-num">3</div><span><?php esc_html_e( '3. Instant Handover Confirmation', 'awesome-car-rental' ); ?></span></div>
        </div>

        <form method="POST" action="<?php echo esc_url( get_permalink() ); ?>" id="acrb-checkout-native-form">
            <?php wp_nonce_field( 'acrb_submit_reservation', 'acrb_checkout_nonce' ); ?>
            <input type="hidden" name="car_id" value="<?php echo absint( $acrb_car_id ); ?>">
            <input type="hidden" name="pickup_date" value="<?php echo esc_attr( $acrb_pickup_date . ' ' . $acrb_pickup_time ); ?>">
            <input type="hidden" name="return_date" value="<?php echo esc_attr( $acrb_return_date . ' ' . $acrb_return_time ); ?>">
            <input type="hidden" name="pickup_location" value="<?php echo esc_attr( $acrb_pickup_loc ); ?>">
            <input type="hidden" name="return_location" value="<?php echo esc_attr( $acrb_return_loc ); ?>">
            <input type="hidden" name="insurance_plan" value="<?php echo esc_attr( $acrb_sel_ins ); ?>">
            <input type="hidden" name="border_permit" value="<?php echo esc_attr( $acrb_sel_border ); ?>">
            <input type="hidden" name="promo_code" id="acrb_hidden_promo_code" value="">
            <input type="hidden" name="order_total" id="acrb_final_total_input" value="<?php echo esc_attr( (string) $acrb_grand_total ); ?>">
            <input type="hidden" name="cus_name" value="<?php echo esc_attr( $acrb_full_name ); ?>">
            <input type="hidden" name="cus_email" value="<?php echo esc_attr( $acrb_current_user->user_email ); ?>">
            <input type="hidden" name="cus_phone" value="<?php echo esc_attr( $acrb_user_phone ); ?>">

            <div class="acrb-checkout-grid">
                
                <!-- Left Column -->
                <div>
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M5 17h14v-5l-2-6H7l-2 6v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
                            <span><?php esc_html_e( 'Review Vehicle & Itinerary', 'awesome-car-rental' ); ?></span>
                        </h2>

                        <div class="acrb-review-box">
                            <div class="acrb-review-vehicle">
                                <div class="acrb-review-thumb">
                                    <?php if ( $acrb_hero_img ) : ?>
                                        <img src="<?php echo esc_url( $acrb_hero_img ); ?>" alt="">
                                    <?php endif; ?>
                                </div>
                                <div class="acrb-review-info">
                                    <h4><?php echo esc_html( $acrb_full_title ); ?></h4>
                                    <div class="acrb-review-meta">
                                        <?php esc_html_e( 'Vehicle Ref:', 'awesome-car-rental' ); ?> <strong>#<?php echo esc_html( $acrb_vin ?: (string) $acrb_car_id ); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="acrb-itinerary-grid">
                                <div class="acrb-itinerary-pill">
                                    <span style="color:var(--acrb-checkout-primary);"><?php esc_html_e( 'Pickup Schedule', 'awesome-car-rental' ); ?></span>
                                    <strong><?php echo esc_html( $acrb_pickup_date . ' @ ' . $acrb_pickup_time ); ?></strong>
                                    <span style="font-size: 11px; font-weight:500; color:var(--acrb-checkout-secondary);">(<?php echo esc_html( $acrb_pickup_loc ); ?>)</span>
                                </div>
                                <div class="acrb-itinerary-pill">
                                    <span style="color:var(--acrb-checkout-primary);"><?php esc_html_e( 'Return Schedule', 'awesome-car-rental' ); ?></span>
                                    <strong><?php echo esc_html( $acrb_return_date . ' @ ' . $acrb_return_time ); ?></strong>
                                    <span style="font-size: 11px; font-weight:500; color:var(--acrb-checkout-secondary);">(<?php echo esc_html( $acrb_return_loc ); ?>)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span><?php esc_html_e( 'Verified Customer Account', 'awesome-car-rental' ); ?></span>
                        </h2>
                        <div class="acrb-checkout-field"><label><?php esc_html_e( 'Full Legal Name', 'awesome-car-rental' ); ?></label><input type="text" value="<?php echo esc_attr( $acrb_full_name ); ?>" readonly class="acrb-checkout-input"></div>
                        <div class="acrb-checkout-field"><label><?php esc_html_e( 'Verified Email Address', 'awesome-car-rental' ); ?></label><input type="email" value="<?php echo esc_attr( $acrb_current_user->user_email ); ?>" readonly class="acrb-checkout-input"></div>
                        <div class="acrb-checkout-field" style="margin-bottom: 0;"><label><?php esc_html_e( 'Registered Phone Number', 'awesome-car-rental' ); ?></label><input type="text" value="<?php echo esc_attr( $acrb_display_phone ); ?>" readonly class="acrb-checkout-input"></div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Payment Methods -->
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            <span><?php esc_html_e( 'Select Payment Method', 'awesome-car-rental' ); ?></span>
                        </h2>
                        <div style="margin-bottom: 0;">
                            <?php foreach ( $acrb_enabled_methods as $acrb_k => $acrb_v ) : ?>
                                <label class="acrb-pay-option <?php echo ( $acrb_pass_method === $acrb_k ) ? 'selected' : ''; ?>">
                                    <input type="radio" name="payment_method" value="<?php echo esc_attr( $acrb_k ); ?>" <?php checked( $acrb_pass_method, $acrb_k ); ?> required>
                                    <span>💳 <?php echo esc_html( $acrb_v ); ?></span>
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

                    <!-- Price Breakdown Summary -->
                    <div class="acrb-checkout-card">
                        <h2 class="acrb-checkout-title">
                            <svg class="acrb-checkout-icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                            <span><?php esc_html_e( 'Price Breakdown Summary', 'awesome-car-rental' ); ?></span>
                        </h2>

                        <div class="acrb-breakdown-card">
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span>🚗 <?php echo esc_html( $acrb_full_title ); ?></span>
                                        <span class="acrb-bd-sub"><?php echo absint( $acrb_days ); ?> <?php echo esc_html( ucfirst( $acrb_rental_unit ) ); ?>(s) × <?php echo esc_html( $acrb_currency . number_format( $acrb_price, 2 ) ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_sum_subtotal"><?php echo esc_html( $acrb_currency . number_format( $acrb_subtotal, 2 ) ); ?></div>
                            </div>

                            <?php if ( ! empty( $acrb_sel_ins ) ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span>🛡️ <?php echo esc_html( $acrb_sel_ins ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Protection Plan', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val">+<?php echo esc_html( $acrb_currency . number_format( $ins_cost, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $acrb_sel_border ) ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span>🌐 <?php echo esc_html( $acrb_sel_border . ' Permit' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Cross-Border Travel', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val">+<?php echo esc_html( $acrb_currency . number_format( $border_cost, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="acrb-bd-item" id="acrb_discount_div" style="display: none; color: var(--acrb-checkout-accent);">
                                <div class="acrb-bd-label" style="color: var(--acrb-checkout-accent);">
                                    <div>
                                        <span>🏷️ <span id="acrb_sum_discount_title"><?php esc_html_e( 'Promo Code Discount', 'awesome-car-rental' ); ?></span></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Special Voucher', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_sum_discount">-<?php echo esc_html( $acrb_currency ); ?>0.00</div>
                            </div>

                            <?php if ( $acrb_security_deposit > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span>🔒 <?php esc_html_e( 'Security Deposit', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Fully Refundable Hold', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $acrb_currency . number_format( $acrb_security_deposit, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ( $acrb_insurance_fee > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span>📋 <?php esc_html_e( 'Mandatory Insurance', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Coverage Protection', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $acrb_currency . number_format( $acrb_insurance_fee, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="acrb-summary-total">
                            <span><?php esc_html_e( 'Total Amount Due', 'awesome-car-rental' ); ?></span>
                            <span style="color: var(--acrb-checkout-primary);"><?php echo esc_html( $acrb_currency ); ?><span id="acrb_sum_grand_total"><?php echo number_format( $acrb_grand_total, 2 ); ?></span></span>
                        </div>

                        <div style="margin: 20px 0 14px 0;">
                            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; color: var(--acrb-checkout-secondary); font-weight: 700;">
                                <input type="checkbox" id="acrb_terms_check" required style="margin-top: 3px; width: 16px; height: 16px; accent-color: var(--acrb-checkout-primary);">
                                <span><?php esc_html_e( 'I agree to the rental terms, driver requirements, and cancellation policy.', 'awesome-car-rental' ); ?></span>
                            </label>
                        </div>

                        <button type="submit" id="acrb_submit_reservation_btn" class="acrb-btn-confirm">
                            <svg class="acrb-checkout-icon" style="width:18px;height:18px;" viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"></path></svg>
                            <span id="acrb_btn_text"><?php esc_html_e( 'Confirm & Place Secure Reservation', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const baseSubtotal = <?php echo floatval( $acrb_subtotal ); ?>;
        const insCost = <?php echo floatval( $ins_cost ); ?>;
        const borderCost = <?php echo floatval( $border_cost ); ?>;
        const deposit = <?php echo floatval( $acrb_security_deposit ); ?>;
        const insurance = <?php echo floatval( $acrb_insurance_fee ); ?>;
        const currencySymbol = '<?php echo esc_js( $acrb_currency ); ?>';

        const sumGrandTotal = document.getElementById('acrb_sum_grand_total');
        const finalTotalInput = document.getElementById('acrb_final_total_input');
        const hiddenPromoInput = document.getElementById('acrb_hidden_promo_code');
        
        const couponBtn = document.getElementById('acrb_apply_coupon');
        const couponInput = document.getElementById('acrb_coupon_code');
        const couponMsg = document.getElementById('acrb_coupon_msg');
        const discountDiv = document.getElementById('acrb_discount_div');
        const sumDiscount = document.getElementById('acrb_sum_discount');

        let promoMap = {};
        try {
            promoMap = JSON.parse('<?php echo wp_json_encode( $promo_map ); ?>');
        } catch(e) {
            promoMap = {};
        }

        let appliedDiscountAmt = 0;

        function recalcCheckout() {
            let computedSub = baseSubtotal + insCost + borderCost;
            let grand = computedSub - appliedDiscountAmt + deposit + insurance;

            sumGrandTotal.textContent = grand.toFixed(2);
            finalTotalInput.value = grand.toFixed(2);
        }

        const payRadios = document.querySelectorAll('input[name="payment_method"]');
        payRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="payment_method"]').forEach(r => {
                    r.closest('.acrb-pay-option').classList.remove('selected');
                });
                if (this.checked) {
                    this.closest('.acrb-pay-option').classList.add('selected');
                }
            });
        });

        if (couponBtn && couponInput) {
            couponBtn.addEventListener('click', function() {
                const code = couponInput.value.trim().toUpperCase();
                const currentDate = new Date().toISOString().split('T')[0];
                let computedSub = baseSubtotal + insCost + borderCost;

                if (promoMap[code]) {
                    const promo = promoMap[code];
                    if (promo.expiry && promo.expiry < currentDate && promo.expiry !== '') {
                        appliedDiscountAmt = 0;
                        hiddenPromoInput.value = '';
                        discountDiv.style.display = 'none';
                        couponMsg.style.color = '#dc2626';
                        couponMsg.textContent = 'Promo code has expired.';
                        couponMsg.style.display = 'block';
                    } else if (computedSub < promo.min_spend) {
                        appliedDiscountAmt = 0;
                        hiddenPromoInput.value = '';
                        discountDiv.style.display = 'none';
                        couponMsg.style.color = '#dc2626';
                        couponMsg.textContent = 'Minimum spend requirement not met (' + currencySymbol + promo.min_spend.toFixed(2) + ').';
                        couponMsg.style.display = 'block';
                    } else {
                        if (promo.type === 'percentage') {
                            appliedDiscountAmt = computedSub * (promo.value / 100);
                        } else {
                            appliedDiscountAmt = promo.value;
                        }
                        if (appliedDiscountAmt > computedSub) appliedDiscountAmt = computedSub;

                        hiddenPromoInput.value = code;
                        sumDiscount.textContent = '-' + currencySymbol + appliedDiscountAmt.toFixed(2);
                        discountDiv.style.display = 'flex';
                        couponMsg.style.color = '#059669';
                        couponMsg.textContent = 'Success! Coupon ' + code + ' applied.';
                        couponMsg.style.display = 'block';
                    }
                    recalcCheckout();
                } else {
                    appliedDiscountAmt = 0;
                    hiddenPromoInput.value = '';
                    discountDiv.style.display = 'none';
                    couponMsg.style.color = '#dc2626';
                    couponMsg.textContent = 'Invalid promo code.';
                    couponMsg.style.display = 'block';
                    recalcCheckout();
                }
            });
        }

        recalcCheckout();
    });
    </script>
    <?php
    return ob_get_clean();
}