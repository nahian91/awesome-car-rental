<?php
/**
 * [acrb_single_car]
 * Displays a professional single car details page with complete itinerary, drop-off location, extras quantities, and real-time pricing.
 * Location: templates/shortcodes/single-car.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.9.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

add_shortcode( 'acrb_single_car', 'acrb_car_details_page_shortcode' );

function acrb_car_details_page_shortcode(): string {
    if ( ! is_user_logged_in() ) {
        $current_car_url = get_permalink() . ( isset( $_GET['car_id'] ) ? '?car_id=' . absint( wp_unslash( $_GET['car_id'] ) ) : '' );
        $login_page_url  = home_url( '/acrb-login/' );
        $final_url       = add_query_arg( 'redirect_to', rawurlencode( $current_car_url ), $login_page_url );
        return '<script>window.location.href=' . wp_json_encode( esc_url_raw( $final_url ) ) . ';</script><div style="padding:60px; text-align:center; font-family:-apple-system,sans-serif; color:#64748b;">Redirecting to secure login portal...</div>';
    }

    // Get Car ID from URL, with fallback to the latest car if omitted
    $car_id = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
    if ( ! $car_id ) {
        $latest_cars = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => 1, 'post_status' => 'publish' ) );
        if ( ! empty( $latest_cars ) ) {
            $car_id = absint( $latest_cars[0]->ID );
        }
    }

    if ( ! $car_id || 'acrb_cars' !== get_post_type( $car_id ) ) {
        return '<div class="acrb-alert error" style="padding:40px; text-align:center; background:#fef2f2; color:#991b1b; border-radius:14px; font-family:-apple-system,sans-serif; font-weight:600;">' . esc_html__( 'Vehicle not found. Please select a car from the fleet grid.', 'awesome-car-rental' ) . '</div>';
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

    // Comprehensive Price & Currency Fallbacks
    $price = floatval( get_post_meta( $car_id, 'daily_price', true ) 
          ?: get_post_meta( $car_id, 'price_per_day', true ) 
          ?: get_post_meta( $car_id, 'rate_per_day', true ) ?: 0 );

    $security_deposit = floatval( get_post_meta( $car_id, 'acrb_security_deposit', true ) ?: 0 );
    $insurance_fee    = floatval( get_post_meta( $car_id, 'acrb_insurance_fee', true ) ?: 0 );
    
    $currency    = get_option( 'acrb_currency', '$' ); 
    $rental_unit = get_option( 'acrb_rental_unit', 'day' );

    // Load Specs, Amenities & Media
    $vehicle_status  = (string) ( get_post_meta( $car_id, 'acrb_vehicle_status', true ) ?: 'active' );
    $seating_cap     = get_post_meta( $car_id, 'acrb_seating_capacity', true ) ?: '5';
    $baggage_cap     = get_post_meta( $car_id, 'acrb_baggage_capacity', true ) ?: '2';
    $transmission    = (string) ( get_post_meta( $car_id, 'acrb_transmission', true ) ?: 'Automatic' );
    $fuel_type       = (string) ( get_post_meta( $car_id, 'acrb_fuel_type', true ) ?: 'Petrol' );
    $min_rental_days = get_post_meta( $car_id, 'acrb_min_rental_days', true ) ?: '1';

    $features  = get_post_meta( $car_id, 'acrb_car_features', true ) ?: get_post_meta( $car_id, 'car_features', true ) ?: array();
    $amenities = get_post_meta( $car_id, 'acrb_car_amenities', true ) ?: get_post_meta( $car_id, 'car_amenities', true ) ?: array();
    $gallery   = get_post_meta( $car_id, 'acrb_car_gallery', true ) ?: get_post_meta( $car_id, 'gallery', true );
    $vin       = (string) ( get_post_meta( $car_id, 'vin_number', true ) ?: get_post_meta( $car_id, 'vin', true ) );
    $video_url = (string) get_post_meta( $car_id, 'acrb_car_video', true );
    
    $custom_locations = get_option( 'acrb_custom_pickup_locations', array() ) ?: get_option( 'acrb_custom_locations', array() );

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

    // Dynamic Checkout Page Resolution
    $checkout_page_id = get_option( 'acrb_checkout_page' );
    $checkout_page_url = $checkout_page_id ? get_permalink( (int) $checkout_page_id ) : '';
    if ( empty( $checkout_page_url ) ) {
        $checkout_page = get_page_by_path( 'acrb-checkout' );
        $checkout_page_url = $checkout_page ? get_permalink( $checkout_page->ID ) : home_url( '/acrb-checkout/' );
    }

    ob_start();
    ?>

    <style>
        :root {
            --acrb-single-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-single-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-single-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-single-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-single-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-main-container {
            max-width: 1200px;
            margin: 40px auto;
            font-family: var(--acrb-single-font);
            color: var(--acrb-single-text);
            box-sizing: border-box;
        }
        .acrb-main-container *,
        .acrb-main-container *::before,
        .acrb-main-container *::after {
            box-sizing: border-box;
        }

        /* SVG Icon Standards */
        .acrb-single-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        .acrb-flex-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 36px;
            align-items: flex-start;
        }

        .acrb-content-left {
            flex: 1;
            min-width: 320px;
        }

        /* Gallery Stage */
        .acrb-gallery-stage img {
            width: 100%;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            height: 440px;
            object-fit: cover;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
            display: block;
        }

        .acrb-header-box {
            margin-top: 24px;
        }
        .acrb-vin-badge {
            background: #eff6ff;
            display: inline-block;
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 800;
            color: var(--acrb-single-primary);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: 1px solid #dbeafe;
        }
        .acrb-car-title {
            margin: 0;
            font-size: 28px;
            font-weight: 900;
            color: var(--acrb-single-text);
            letter-spacing: -0.02em;
        }

        /* Tabs Navigation */
        .acrb-tabs-nav {
            display: flex;
            gap: 8px;
            margin-top: 32px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            overflow-x: auto;
        }
        .acrb-tab-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 11px 20px;
            font-weight: 700;
            font-size: 13.5px;
            color: var(--acrb-single-secondary);
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.15s ease;
            white-space: nowrap;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
            font-family: inherit;
        }
        .acrb-tab-btn:hover {
            background: #f8fafc;
            color: var(--acrb-single-text);
            border-color: #94a3b8;
        }
        .acrb-tab-btn.active {
            background: #eff6ff;
            color: var(--acrb-single-primary);
            border-color: #c7d2fe;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
        }

        .acrb-tab-pane {
            display: none;
            padding: 28px 0;
        }
        .acrb-tab-pane.active {
            display: block;
            animation: acrbFade 0.25s ease;
        }
        @keyframes acrbFade {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Specification Cards */
        .acrb-spec-wrap {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .acrb-spec-node {
            padding: 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
        }
        .acrb-spec-icon-wrap {
            color: var(--acrb-single-primary);
            margin-bottom: 10px;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            border-radius: 10px;
        }
        .acrb-spec-icon-wrap .acrb-single-icon {
            width: 18px;
            height: 18px;
        }
        .acrb-spec-node-lbl {
            font-size: 11px;
            color: var(--acrb-single-secondary);
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .acrb-spec-node-val {
            font-weight: 900;
            color: var(--acrb-single-text);
            font-size: 14px;
            text-transform: capitalize;
        }

        /* Booking Sidebar */
        .acrb-sidebar-right {
            width: 440px;
            min-width: 320px;
        }
        .acrb-booking-card {
            padding: 32px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            position: sticky;
            top: 30px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06);
        }
        .acrb-price-hero {
            margin-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 18px;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .acrb-label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 800;
            color: var(--acrb-single-text);
        }
        .acrb-input {
            width: 100% !important;
            height: 44px !important;
            padding: 0 14px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 10px !important;
            font-size: 13.5px !important;
            color: var(--acrb-single-text) !important;
            background: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
            transition: border-color 0.15s ease, background-color 0.15s ease !important;
            box-sizing: border-box;
            font-family: inherit !important;
            line-height: 42px !important;
        }
        .acrb-input:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-input:focus {
            background: #ffffff !important;
            border-color: var(--acrb-single-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
            outline: none !important;
        }
        .acrb-select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2220%22%20height%3D%2220%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 18px;
            padding-right: 40px !important;
            cursor: pointer;
        }

        /* Sidebar Extras Selection */
        .acrb-sidebar-extras {
            margin: 20px 0;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .acrb-sidebar-extra-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
        }
        .acrb-sidebar-extra-row:last-child {
            margin-bottom: 0;
        }
        .acrb-sidebar-qty {
            width: 60px !important;
            height: 34px !important;
            padding: 0 8px !important;
            text-align: center;
            font-weight: 800;
        }

        .acrb-pay-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 14px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            background: #ffffff;
            transition: all 0.15s ease;
            margin-bottom: 10px;
        }
        .acrb-pay-option:hover {
            border-color: var(--acrb-single-primary);
            background: #f8fafc;
        }
        .acrb-pay-option input[type="radio"] {
            accent-color: var(--acrb-single-primary);
            width: 18px;
            height: 18px;
            margin-right: 12px;
            cursor: pointer;
        }

        /* Price Breakdown */
        .acrb-breakdown-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .acrb-bd-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 13.5px;
            color: #334155;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .acrb-bd-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .acrb-bd-label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-weight: 700;
        }
        .acrb-bd-sub {
            display: block;
            font-size: 11.5px;
            color: var(--acrb-single-secondary);
            font-weight: 500;
            margin-top: 2px;
        }
        .acrb-bd-val {
            font-weight: 900;
            color: var(--acrb-single-text);
        }
        .acrb-breakdown-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px dashed #cbd5e1;
            padding-top: 14px;
            margin-top: 4px;
            font-weight: 900;
            color: var(--acrb-single-text);
            font-size: 18px;
        }

        .acrb-submit-btn {
            width: 100%;
            height: 50px;
            background: var(--acrb-single-primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-weight: 800;
            font-size: 14.5px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: inherit;
        }
        .acrb-submit-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        /* Video Container */
        .acrb-video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 14px;
            background: #0f172a;
            border: 1px solid #e2e8f0;
        }
        .acrb-video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .acrb-empty-box {
            padding: 50px 20px;
            text-align: center;
            background: #f8fafc;
            border-radius: 14px;
            color: var(--acrb-single-secondary);
            border: 1px dashed #cbd5e1;
        }
        .acrb-empty-box .acrb-single-icon {
            width: 40px;
            height: 40px;
            color: #94a3b8;
            margin-bottom: 12px;
        }
        .acrb-empty-msg {
            color: var(--acrb-single-secondary);
            font-style: italic;
        }

        @media (max-width: 900px) {
            .acrb-sidebar-right,
            .acrb-content-left {
                width: 100% !important;
                min-width: 100% !important;
            }
            .acrb-booking-card {
                position: static;
            }
        }
    </style>

    <div class="acrb-main-container" data-day-rate="<?php echo esc_attr( (string) $price ); ?>" data-deposit="<?php echo esc_attr( (string) $security_deposit ); ?>" data-insurance="<?php echo esc_attr( (string) $insurance_fee ); ?>" data-currency="<?php echo esc_attr( $currency ); ?>">
        <div class="acrb-flex-grid">
            
            <!-- Left Content Column -->
            <div class="acrb-content-left">
                <div class="acrb-gallery-stage">
                    <?php 
                    $hero_img = get_the_post_thumbnail_url( $car_id, 'large' );
                    if ( ! $hero_img && ! empty( $gallery ) ) {
                        $first_gallery_id = is_array( $gallery ) ? reset( $gallery ) : explode( ',', (string) $gallery )[0];
                        $hero_img = wp_get_attachment_image_url( (int) $first_gallery_id, 'large' );
                    }
                    ?>
                    <img src="<?php echo esc_url( $hero_img ); ?>" id="acrb-hero-view" alt="<?php echo esc_attr( get_the_title( $car_id ) ); ?>">
                </div>

                <div class="acrb-header-box">
                    <div class="acrb-vin-badge">
                        <?php esc_html_e( 'VIN Ref:', 'awesome-car-rental' ); ?> #<?php echo esc_html( $vin ?: $car_id ); ?>
                    </div>
                    <h1 class="acrb-car-title"><?php echo esc_html( get_the_title( $car_id ) ); ?></h1>
                </div>

                <!-- Tabs Navigation -->
                <div class="acrb-tabs-nav">
                    <button type="button" class="acrb-tab-btn active" data-tab="overview"><?php esc_html_e( 'Overview', 'awesome-car-rental' ); ?></button>
                    <button type="button" class="acrb-tab-btn" data-tab="specs"><?php esc_html_e( 'Specifications & Extras', 'awesome-car-rental' ); ?></button>
                    <button type="button" class="acrb-tab-btn" data-tab="gallery"><?php esc_html_e( 'Gallery', 'awesome-car-rental' ); ?></button>
                    <button type="button" class="acrb-tab-btn" data-tab="video"><?php esc_html_e( 'Video Walkaround', 'awesome-car-rental' ); ?></button>
                </div>

                <div class="acrb-tabs-content">
                    
                    <!-- Overview Tab -->
                    <div id="tab-overview" class="acrb-tab-pane active">
                        <div class="acrb-description" style="color: var(--acrb-single-secondary); font-size: 15px; line-height: 1.7;">
                            <?php 
                            $content = get_post_field( 'post_content', $car_id );
                            echo ! empty( $content ) ? wp_kses_post( wpautop( $content ) ) : '<p class="acrb-empty-msg">' . esc_html__( 'No description available for this vehicle.', 'awesome-car-rental' ) . '</p>'; 
                            ?>
                        </div>
                    </div>

                    <!-- Specifications Tab -->
                    <div id="tab-specs" class="acrb-tab-pane">
                        <h3 style="margin:0 0 16px 0; font-size:12px; font-weight:900; color:var(--acrb-single-secondary); text-transform:uppercase; letter-spacing:0.04em;"><?php esc_html_e( 'Vehicle Specifications & Status', 'awesome-car-rental' ); ?></h3>
                        <div class="acrb-spec-wrap">
                            
                            <div class="acrb-spec-node">
                                <div class="acrb-spec-icon-wrap">
                                    <svg class="acrb-single-icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                </div>
                                <div class="acrb-spec-node-lbl"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></div>
                                <div class="acrb-spec-node-val"><?php echo esc_html( $vehicle_status ); ?></div>
                            </div>

                            <div class="acrb-spec-node">
                                <div class="acrb-spec-icon-wrap">
                                    <svg class="acrb-single-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87"></path><path d="M16 3.13a4 4 0 010 7.75"></path></svg>
                                </div>
                                <div class="acrb-spec-node-lbl"><?php esc_html_e( 'Seating', 'awesome-car-rental' ); ?></div>
                                <div class="acrb-spec-node-val"><?php echo esc_html( (string) $seating_cap ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></div>
                            </div>

                            <div class="acrb-spec-node">
                                <div class="acrb-spec-icon-wrap">
                                    <svg class="acrb-single-icon" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"></path></svg>
                                </div>
                                <div class="acrb-spec-node-lbl"><?php esc_html_e( 'Baggage', 'awesome-car-rental' ); ?></div>
                                <div class="acrb-spec-node-val"><?php echo esc_html( (string) $baggage_cap ); ?> <?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></div>
                            </div>

                            <div class="acrb-spec-node">
                                <div class="acrb-spec-icon-wrap">
                                    <svg class="acrb-single-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
                                </div>
                                <div class="acrb-spec-node-lbl"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></div>
                                <div class="acrb-spec-node-val"><?php echo esc_html( $transmission ); ?></div>
                            </div>

                            <div class="acrb-spec-node">
                                <div class="acrb-spec-icon-wrap">
                                    <svg class="acrb-single-icon" viewBox="0 0 24 24"><path d="M3 22V5a2 2 0 012-2h8a2 2 0 012 2v17"></path><path d="M15 9h2a2 2 0 012 2v2a2 2 0 002 2 2 2 0 002-2V9.5"></path></svg>
                                </div>
                                <div class="acrb-spec-node-lbl"><?php esc_html_e( 'Fuel / Engine', 'awesome-car-rental' ); ?></div>
                                <div class="acrb-spec-node-val"><?php echo esc_html( $fuel_type ); ?></div>
                            </div>

                            <div class="acrb-spec-node">
                                <div class="acrb-spec-icon-wrap">
                                    <svg class="acrb-single-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </div>
                                <div class="acrb-spec-node-lbl"><?php esc_html_e( 'Min Rental', 'awesome-car-rental' ); ?></div>
                                <div class="acrb-spec-node-val"><?php echo esc_html( (string) $min_rental_days ); ?> <?php echo esc_html( ucfirst( $rental_unit ) ); ?></div>
                            </div>

                        </div>

                        <?php if ( ! empty( $features ) ) : ?>
                        <div style="margin-top:32px; padding-top:24px; border-top:1px solid #e2e8f0;">
                            <h3 style="margin:0 0 16px 0; font-size:12px; font-weight:900; color:var(--acrb-single-secondary); text-transform:uppercase; letter-spacing:0.04em;"><?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></h3>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
                                <?php foreach ( $features as $f ) : 
                                    $f_name    = is_array( $f ) ? ( $f['name'] ?? '' ) : '';
                                    $f_val     = is_array( $f ) ? ( $f['value'] ?? '' ) : $f;
                                    $f_enabled = is_array( $f ) ? ! empty( $f['enabled'] ) : true;

                                    if ( $f_enabled && ! empty( $f_val ) ) : 
                                ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 18px; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <svg class="acrb-single-icon" style="color:var(--acrb-single-primary);" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"></path></svg>
                                            <span style="font-size: 13.5px; font-weight: 700; color: #334155;"><?php echo esc_html( $f_name ); ?></span>
                                        </div>
                                        <strong style="color: var(--acrb-single-text); font-size: 14px;"><?php echo esc_html( $f_val ); ?></strong>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $amenities ) ) : ?>
                        <div style="margin-top:32px; padding-top:24px; border-top:1px solid #e2e8f0;">
                            <h3 style="margin:0 0 16px 0; font-size:12px; font-weight:900; color:var(--acrb-single-secondary); text-transform:uppercase; letter-spacing:0.04em;"><?php esc_html_e( 'Global Add-on Extras & Equipment', 'awesome-car-rental' ); ?></h3>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                                <?php foreach ( $amenities as $a ) : 
                                    $a_name    = is_array( $a ) ? ( $a['name'] ?? '' ) : $a;
                                    $a_enabled = is_array( $a ) ? ! empty( $a['enabled'] ) : true;

                                    if ( $a_enabled && ! empty( $a_name ) ) : 
                                ?>
                                    <div style="display: flex; align-items: center; gap: 10px; color: #334155; background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 18px; border-radius: 12px; font-size: 14px; font-weight: 700; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);">
                                        <svg class="acrb-single-icon" style="color: var(--acrb-single-accent);" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
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
                                $gallery_ids = explode( ',', (string) $gallery );
                            }
                        }
                        ?>
                        <?php if ( ! empty( $gallery_ids ) || $hero_img ) : ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
                                <?php if ( $hero_img ) : ?>
                                    <div style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; aspect-ratio: 16/10;">
                                        <img src="<?php echo esc_url( $hero_img ); ?>" class="acrb-gallery-item" style="width:100%; height:100%; object-fit:cover; cursor:pointer;" alt="">
                                    </div>
                                <?php endif; ?>
                                <?php foreach ( $gallery_ids as $gid ) : 
                                    $gid = trim( (string) $gid );
                                    if ( ! empty( $gid ) ) : 
                                        $img_url = wp_get_attachment_image_url( (int) $gid, 'large' );
                                        if ( $img_url ) :
                                ?>
                                    <div style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; aspect-ratio: 16/10;">
                                        <img src="<?php echo esc_url( $img_url ); ?>" class="acrb-gallery-item" style="width:100%; height:100%; object-fit:cover; cursor:pointer;" alt="">
                                    </div>
                                <?php endif; endif; endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="acrb-empty-box">
                                <svg class="acrb-single-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
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
                                <svg class="acrb-single-icon" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                                <p><?php esc_html_e( 'No video walkthrough available.', 'awesome-car-rental' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar with Itinerary, Extras Quantities & Price Breakdown -->
            <div class="acrb-sidebar-right">
                <div class="acrb-booking-card">
                    <div class="acrb-price-hero">
                        <span style="font-size: 32px; font-weight: 900; color: var(--acrb-single-text); letter-spacing: -0.02em;"><?php echo esc_html( $currency . number_format( $price, 2 ) ); ?></span>
                        <span style="color: var(--acrb-single-secondary); font-size: 13.5px; font-weight: 700;">/ <?php echo esc_html( $rental_unit ); ?></span>
                    </div>

                    <form action="<?php echo esc_url( $checkout_page_url ); ?>" method="GET" id="acrb-booking-form">
                        <input type="hidden" name="car_id" value="<?php echo absint( $car_id ); ?>">

                        <!-- Pickup & Return Dates -->
                        <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                            <div style="flex: 1;">
                                <label class="acrb-label" for="p_date"><?php esc_html_e( 'Pickup Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" id="p_date" name="pdate" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                            <div style="flex: 1;">
                                <label class="acrb-label" for="r_date"><?php esc_html_e( 'Return Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" id="r_date" name="rdate" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                        </div>

                        <!-- Pickup & Return Locations -->
                        <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                            <div style="flex: 1;">
                                <label class="acrb-label" for="acrb_pickup_loc"><?php esc_html_e( 'Pickup Location', 'awesome-car-rental' ); ?></label>
                                <select id="acrb_pickup_loc" name="ploc" required class="acrb-input acrb-select">
                                    <option value=""><?php esc_html_e( 'Select Hub', 'awesome-car-rental' ); ?></option>
                                    <?php foreach ( $custom_locations as $loc ) : 
                                        $loc_name = is_array( $loc ) ? ( $loc['name'] ?? '' ) : $loc;
                                        if ( ! empty( $loc_name ) ) :
                                    ?>
                                        <option value="<?php echo esc_attr( $loc_name ); ?>"><?php echo esc_html( $loc_name ); ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label class="acrb-label" for="acrb_return_loc"><?php esc_html_e( 'Return Location', 'awesome-car-rental' ); ?></label>
                                <select id="acrb_return_loc" name="dloc" required class="acrb-input acrb-select">
                                    <option value=""><?php esc_html_e( 'Select Hub', 'awesome-car-rental' ); ?></option>
                                    <?php foreach ( $custom_locations as $loc ) : 
                                        $loc_name = is_array( $loc ) ? ( $loc['name'] ?? '' ) : $loc;
                                        if ( ! empty( $loc_name ) ) :
                                    ?>
                                        <option value="<?php echo esc_attr( $loc_name ); ?>"><?php echo esc_html( $loc_name ); ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Add-on Extras with Quantities -->
                        <?php if ( ! empty( $available_extras ) ) : ?>
                        <div class="acrb-sidebar-extras">
                            <div style="font-size: 11.5px; font-weight: 900; color: var(--acrb-single-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;">
                                <?php esc_html_e( 'Optional Extras & Quantities', 'awesome-car-rental' ); ?>
                            </div>
                            <?php foreach ( $available_extras as $idx => $ext ) : ?>
                                <div class="acrb-sidebar-extra-row">
                                    <div>
                                        <span><?php echo esc_html( $ext['name'] ); ?></span><br>
                                        <span style="font-size: 11px; color: var(--acrb-single-secondary); font-weight: 600;"><?php echo esc_html( $currency . number_format( $ext['price'], 2 ) ); ?> each</span>
                                    </div>
                                    <input type="number" name="acrb_extra_qty[<?php echo esc_attr( $ext['name'] ); ?>]" value="0" min="0" max="10" data-price="<?php echo esc_attr( (string) $ext['price'] ); ?>" class="acrb-input acrb-sidebar-qty acrb-qty-input">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $enabled_methods ) ) : ?>
                        <div style="margin-bottom: 20px;">
                            <label class="acrb-label"><?php esc_html_e( 'Payment Method', 'awesome-car-rental' ); ?></label>
                            <div>
                                <?php foreach ( $enabled_methods as $k => $v ) : ?>
                                <label class="acrb-pay-option">
                                    <input type="radio" name="pmethod" value="<?php echo esc_attr( $k ); ?>" required>
                                    <span><?php echo esc_html( $v ); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Nice Price Breakdown Box -->
                        <div class="acrb-breakdown-card">
                            <div style="font-size: 11.5px; font-weight: 900; color: var(--acrb-single-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                                <?php esc_html_e( 'Price Breakdown Summary', 'awesome-car-rental' ); ?>
                            </div>
                            
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span><?php esc_html_e( 'Rental Duration:', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub" id="acrb_calc_days">0 Days</span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_calc_subtotal"><?php echo esc_html( $currency ); ?>0.00</div>
                            </div>

                            <div class="acrb-bd-item" id="acrb_sidebar_extras_summary" style="display: none;">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span><?php esc_html_e( 'Add-on Extras:', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Equipment & Protection', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_calc_extras_total"><?php echo esc_html( $currency ); ?>0.00</div>
                            </div>

                            <?php if ( $security_deposit > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span><?php esc_html_e( 'Security Deposit:', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Refundable Hold', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $currency . number_format( $security_deposit, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ( $insurance_fee > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div>
                                        <span><?php esc_html_e( 'Insurance Fee:', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Coverage Protection', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $currency . number_format( $insurance_fee, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="acrb-breakdown-total">
                                <span><?php esc_html_e( 'Estimated Total', 'awesome-car-rental' ); ?></span>
                                <span style="color: var(--acrb-single-primary);"><?php echo esc_html( $currency ); ?><span id="acrb_sum_total">0.00</span></span>
                            </div>
                        </div>

                        <button type="submit" class="acrb-submit-btn">
                            <svg class="acrb-single-icon" style="width:18px;height:18px;" viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"></path></svg>
                            <span><?php esc_html_e( 'Proceed to Secure Checkout', 'awesome-car-rental' ); ?></span>
                        </button>
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
                const target = document.getElementById('tab-' + btn.dataset.tab);
                if (target) target.classList.add('active');
            });
        });

        // Price Breakdown Logic with Quantities & Drop-off
        const pDate = document.getElementById('p_date');
        const rDate = document.getElementById('r_date');
        const pLoc = document.getElementById('acrb_pickup_loc');
        const rLoc = document.getElementById('acrb_return_loc');
        
        const displayTotal = document.getElementById('acrb_sum_total');
        const displayDays = document.getElementById('acrb_calc_days');
        const displaySubtotal = document.getElementById('acrb_calc_subtotal');
        const qtyInputs = document.querySelectorAll('.acrb-qty-input');
        const extrasSummaryRow = document.getElementById('acrb_sidebar_extras_summary');
        const displayExtrasTotal = document.getElementById('acrb_calc_extras_total');
        
        const container = document.querySelector('.acrb-main-container');

        const rate = container ? parseFloat(container.dataset.dayRate) : 0;
        const deposit = container ? parseFloat(container.dataset.deposit) : 0;
        const insurance = container ? parseFloat(container.dataset.insurance) : 0;
        const currency = container ? container.dataset.currency : '$';

        // Auto-sync return location with pickup location if empty
        if (pLoc && rLoc) {
            pLoc.addEventListener('change', function() {
                if (!rLoc.value) rLoc.value = this.value;
            });
        }

        function calc() {
            if (pDate && rDate && pDate.value && rDate.value) {
                const start = new Date(pDate.value);
                const end = new Date(rDate.value);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                
                if (days > 0) {
                    let subtotal = days * rate;
                    let extrasTotal = 0;

                    qtyInputs.forEach(input => {
                        const qty = parseInt(input.value) || 0;
                        const unitPrice = parseFloat(input.dataset.price) || 0;
                        extrasTotal += (qty * unitPrice * days);
                    });

                    if (extrasTotal > 0) {
                        displayExtrasTotal.textContent = currency + extrasTotal.toFixed(2);
                        extrasSummaryRow.style.display = 'flex';
                    } else {
                        extrasSummaryRow.style.display = 'none';
                    }

                    const grandTotal = subtotal + extrasTotal + deposit + insurance;

                    displayDays.textContent = days + (days === 1 ? ' Day' : ' Days');
                    displaySubtotal.textContent = currency + subtotal.toFixed(2);
                    displayTotal.textContent = grandTotal.toFixed(2);
                } else {
                    displayDays.textContent = '0 Days';
                    displaySubtotal.textContent = currency + '0.00';
                    extrasSummaryRow.style.display = 'none';
                    displayTotal.textContent = '0.00';
                }
            }
        }

        if (pDate && rDate) {
            pDate.addEventListener('change', function() {
                rDate.min = this.value;
                if (rDate.value && rDate.value < this.value) {
                    rDate.value = this.value;
                }
                calc();
            });
            rDate.addEventListener('change', calc);
        }

        qtyInputs.forEach(input => {
            input.addEventListener('input', calc);
        });

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
    <?php
    return ob_get_clean();
}