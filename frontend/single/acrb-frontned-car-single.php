<?php
/**
 * [acrb_single_car]
 * Displays a professional single car details page with itinerary, time selection, insurance, border crossing, and real-time pricing (No Payment Gateways or Extras).
 * Location: templates/shortcodes/single-car.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
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

    global $wpdb;
    $table_cars   = $wpdb->prefix . 'dev_nahian_cars';
    $table_promos = $wpdb->prefix . 'dev_nahian_promo_codes';

    // Get Car ID from URL, with fallback to the latest car from custom table if omitted
    $car_id = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
    
    $car = null;
    if ( $car_id > 0 ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $car = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cars} WHERE id = %d AND status != 'retired'", $car_id ), ARRAY_A );
    }

    if ( ! $car ) {
        // Fallback to latest available vehicle from custom table
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $car = $wpdb->get_row( "SELECT * FROM {$table_cars} WHERE status != 'retired' ORDER BY id DESC LIMIT 1", ARRAY_A );
        if ( $car ) {
            $car_id = absint( $car['id'] );
        }
    }

    if ( ! $car ) {
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

    // Comprehensive Price & Currency Values from Custom Table Record
    $price            = floatval( $car['price'] ?? 0.00 );
    $security_deposit = floatval( $car['security_deposit'] ?? 0.00 );
    $insurance_fee    = floatval( $car['insurance_fee'] ?? 0.00 );
    
    $currency    = get_option( 'acrb_currency', '£' ); 
    $rental_unit = get_option( 'acrb_rental_unit', 'day' );

    // Load Specs, Amenities & Media from Custom Table Record
    $vehicle_status  = (string) ( $car['status'] ?: 'active' );
    $seating_cap     = absint( $car['seats'] ?: 5 );
    $baggage_cap     = absint( $car['bags'] ?: 2 );
    $transmission    = (string) ( $car['transmission'] ?: 'Automatic' );
    $fuel_type       = (string) ( $car['fuel'] ?: 'Petrol' );
    $min_rental_days = absint( $car['min_days'] ?: 1 );
    $vin             = (string) ( $car['vin'] ?: '' );
    $video_url       = (string) ( $car['video'] ?: '' );
    $car_name        = esc_html( $car['name'] );
    $car_brand       = esc_html( $car['brand'] ?? '' );
    $full_title      = trim( ( $car_brand ? $car_brand . ' ' : '' ) . $car_name );
    $car_description = (string) ( $car['description'] ?? '' );
    
    // Resolve Image
    $raw_img  = (string) ( $car['image'] ?? '' );
    $hero_img = '';
    if ( is_numeric( $raw_img ) && absint( $raw_img ) > 0 ) {
        $hero_img = wp_get_attachment_image_url( absint( $raw_img ), 'large' ) ?: '';
    } elseif ( ! empty( $raw_img ) && filter_var( $raw_img, FILTER_VALIDATE_URL ) ) {
        $hero_img = $raw_img;
    }

    // Gallery resolution
    $gallery_ids = array();
    $raw_gallery = (string) ( $car['gallery'] ?? '' );
    if ( ! empty( $raw_gallery ) ) {
        $gallery_ids = explode( ',', $raw_gallery );
    }

    $custom_locations = get_option( 'acrb_custom_pickup_locations', array() ) ?: get_option( 'acrb_custom_locations', array() );

    // Decode Insurance Plans and Border Crossing assigned to this specific car
    $car_insurance_json = ! empty( $car['insurance_plans'] ) ? json_decode( (string) $car['insurance_plans'], true ) : array();
    $car_border_json    = ! empty( $car['border_crossing'] ) ? json_decode( (string) $car['border_crossing'], true ) : array();

    // Filter enabled insurance plans for this car
    $available_insurance = array();
    if ( ! empty( $car_insurance_json ) && is_array( $car_insurance_json ) ) {
        foreach ( $car_insurance_json as $ins ) {
            if ( ! empty( $ins['enabled'] ) && ! empty( $ins['name'] ) ) {
                $available_insurance[] = array(
                    'name'  => $ins['name'],
                    'price' => floatval( $ins['price'] ?? 0 ),
                    'type'  => $ins['type'] ?? 'day',
                );
            }
        }
    }

    // Filter enabled border crossing options for this car
    $available_borders = array();
    if ( ! empty( $car_border_json ) && is_array( $car_border_json ) ) {
        foreach ( $car_border_json as $border ) {
            if ( ! empty( $border['enabled'] ) && ! empty( $border['country'] ) ) {
                $available_borders[] = array(
                    'country' => $border['country'],
                    'fee'     => floatval( $border['fee'] ?? 0 ),
                    'type'    => $border['type'] ?? 'flat',
                );
            }
        }
    }

    // Fetch active promotional codes from database for JavaScript validation
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $db_promos = $wpdb->get_results( "SELECT * FROM {$table_promos} WHERE status = 'active'", ARRAY_A );
    $promo_map = array();
    if ( ! empty( $db_promos ) ) {
        foreach ( $db_promos as $p ) {
            $promo_map[ strtoupper( trim( $p['code'] ) ) ] = array(
                'type'      => $p['discount_type'], // percentage or fixed
                'value'     => floatval( $p['discount_value'] ),
                'min_spend' => floatval( $p['min_spend'] ),
                'expiry'    => $p['expiry_date'],
            );
        }
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

        /* Luxury Selectable Radio Card Lists (Insurance & Border Crossing) */
        .acrb-luxury-options-group {
            margin: 22px 0;
        }
        .acrb-group-title {
            font-size: 11.5px;
            font-weight: 900;
            color: var(--acrb-single-secondary);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
        }
        .acrb-luxury-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-bottom: 8px;
        }
        .acrb-luxury-card:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }
        .acrb-luxury-card.selected {
            border-color: var(--acrb-single-primary);
            background: #eef2ff;
        }
        .acrb-luxury-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
        }
        .acrb-luxury-card input[type="radio"] {
            accent-color: var(--acrb-single-primary);
            width: 17px;
            height: 17px;
            cursor: pointer;
        }
        .acrb-luxury-price {
            font-size: 13px;
            font-weight: 900;
            color: var(--acrb-single-primary);
        }

        /* Ultra-Premium Price Breakdown Card */
        .acrb-breakdown-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin: 24px 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 4px 16px -4px rgba(15, 23, 42, 0.04);
        }
        .acrb-breakdown-header {
            font-size: 12px;
            font-weight: 900;
            color: var(--acrb-single-secondary);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .acrb-breakdown-header .acrb-single-icon {
            color: var(--acrb-single-primary);
            width: 16px;
            height: 16px;
        }
        .acrb-bd-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 13.5px;
            color: #334155;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .acrb-bd-item:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
        }
        .acrb-bd-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-weight: 700;
        }
        .acrb-bd-icon-badge {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #eef2ff;
            color: var(--acrb-single-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
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
            padding-top: 16px;
            margin-top: 6px;
            font-weight: 900;
            color: var(--acrb-single-text);
            font-size: 19px;
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
        }
    </style>

    <div class="acrb-main-container" data-day-rate="<?php echo esc_attr( (string) $price ); ?>" data-deposit="<?php echo esc_attr( (string) $security_deposit ); ?>" data-insurance="<?php echo esc_attr( (string) $insurance_fee ); ?>" data-currency="<?php echo esc_attr( $currency ); ?>" data-promos="<?php echo esc_attr( wp_json_encode( $promo_map ) ); ?>">
        <div class="acrb-flex-grid">
            
            <!-- Left Content Column -->
            <div class="acrb-content-left">
                <div class="acrb-gallery-stage">
                    <?php if ( ! empty( $hero_img ) ) : ?>
                        <img src="<?php echo esc_url( $hero_img ); ?>" id="acrb-hero-view" alt="<?php echo esc_attr( $full_title ); ?>">
                    <?php else : ?>
                        <div class="acrb-empty-box" style="height: 440px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <svg class="acrb-single-icon" viewBox="0 0 24 24"><path d="M5 17h14v-5l-2-6H7l-2 6v5z"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="16.5" cy="17.5" r="2.5"></circle></svg>
                            <p><?php esc_html_e( 'No photo available', 'awesome-car-rental' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="acrb-header-box">
                    <div class="acrb-vin-badge">
                        <?php esc_html_e( 'VIN Ref:', 'awesome-car-rental' ); ?> #<?php echo esc_html( $vin ?: $car_id ); ?>
                    </div>
                    <h1 class="acrb-car-title"><?php echo esc_html( $full_title ); ?></h1>
                </div>

                <!-- Tabs Navigation -->
                <div class="acrb-tabs-nav">
                    <button type="button" class="acrb-tab-btn active" data-tab="overview"><?php esc_html_e( 'Overview', 'awesome-car-rental' ); ?></button>
                    <button type="button" class="acrb-tab-btn" data-tab="specs"><?php esc_html_e( 'Specifications', 'awesome-car-rental' ); ?></button>
                    <button type="button" class="acrb-tab-btn" data-tab="gallery"><?php esc_html_e( 'Gallery', 'awesome-car-rental' ); ?></button>
                    <button type="button" class="acrb-tab-btn" data-tab="video"><?php esc_html_e( 'Video Walkaround', 'awesome-car-rental' ); ?></button>
                </div>

                <div class="acrb-tabs-content">
                    <!-- Overview Tab -->
                    <div id="tab-overview" class="acrb-tab-pane active">
                        <div class="acrb-description" style="color: var(--acrb-single-secondary); font-size: 15px; line-height: 1.7;">
                            <?php 
                            echo ! empty( $car_description ) ? wp_kses_post( wpautop( $car_description ) ) : '<p class="acrb-empty-msg">' . esc_html__( 'No description available for this vehicle.', 'awesome-car-rental' ) . '</p>'; 
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
                    </div>

                    <!-- Gallery Tab -->
                    <div id="tab-gallery" class="acrb-tab-pane">
                        <?php if ( ! empty( $gallery_ids ) || ! empty( $hero_img ) ) : ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
                                <?php if ( ! empty( $hero_img ) ) : ?>
                                    <div style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; aspect-ratio: 16/10;">
                                        <img src="<?php echo esc_url( $hero_img ); ?>" class="acrb-gallery-item" style="width:100%; height:100%; object-fit:cover; cursor:pointer;" alt="">
                                    </div>
                                <?php endif; ?>
                                <?php foreach ( $gallery_ids as $gid ) : 
                                    $gid = trim( (string) $gid );
                                    if ( ! empty( $gid ) ) : 
                                        $img_url = '';
                                        if ( is_numeric( $gid ) && absint( $gid ) > 0 ) {
                                            $img_url = wp_get_attachment_image_url( absint( $gid ), 'large' ) ?: '';
                                        } elseif ( filter_var( $gid, FILTER_VALIDATE_URL ) ) {
                                            $img_url = $gid;
                                        }
                                        if ( ! empty( $img_url ) ) :
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

            <!-- Booking Sidebar (No Payment Selection Required Here) -->
            <div class="acrb-sidebar-right">
                <div class="acrb-booking-card">
                    <div class="acrb-price-hero">
                        <span style="font-size: 32px; font-weight: 900; color: var(--acrb-single-text); letter-spacing: -0.02em;"><?php echo esc_html( $currency . number_format( $price, 2 ) ); ?></span>
                        <span style="color: var(--acrb-single-secondary); font-size: 13.5px; font-weight: 700;">/ <?php echo esc_html( $rental_unit ); ?></span>
                    </div>

                    <form action="<?php echo esc_url( $checkout_page_url ); ?>" method="GET" id="acrb-booking-form">
                        <input type="hidden" name="car_id" value="<?php echo absint( $car_id ); ?>">

                        <!-- Pickup Date & Time -->
                        <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                            <div style="flex: 2;">
                                <label class="acrb-label" for="p_date"><?php esc_html_e( 'Pickup Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" id="p_date" name="pdate" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                            <div style="flex: 1.2;">
                                <label class="acrb-label" for="p_time"><?php esc_html_e( 'Time', 'awesome-car-rental' ); ?></label>
                                <input type="time" id="p_time" name="ptime" value="10:00" required class="acrb-input">
                            </div>
                        </div>

                        <!-- Return Date & Time -->
                        <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                            <div style="flex: 2;">
                                <label class="acrb-label" for="r_date"><?php esc_html_e( 'Return Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" id="r_date" name="rdate" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                            <div style="flex: 1.2;">
                                <label class="acrb-label" for="r_time"><?php esc_html_e( 'Time', 'awesome-car-rental' ); ?></label>
                                <input type="time" id="r_time" name="rtime" value="10:00" required class="acrb-input">
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

                        <!-- PROTECTION & INSURANCE PLANS (Luxury Radio Cards) -->
                        <?php if ( ! empty( $available_insurance ) ) : ?>
                        <div class="acrb-luxury-options-group">
                            <div class="acrb-group-title">🛡️ <?php esc_html_e( 'Protection & Insurance Plans', 'awesome-car-rental' ); ?></div>
                            <label class="acrb-luxury-card selected">
                                <div class="acrb-luxury-info">
                                    <input type="radio" name="acrb_selected_insurance" value="" data-price="0" data-type="flat" class="acrb-insurance-radio" checked>
                                    <span><?php esc_html_e( 'Standard Basic Protection', 'awesome-car-rental' ); ?></span>
                                </div>
                                <span class="acrb-luxury-price"><?php echo esc_html( $currency ); ?>0.00</span>
                            </label>
                            <?php foreach ( $available_insurance as $ins ) : ?>
                                <label class="acrb-luxury-card">
                                    <div class="acrb-luxury-info">
                                        <input type="radio" name="acrb_selected_insurance" value="<?php echo esc_attr( $ins['name'] ); ?>" data-price="<?php echo esc_attr( (string) $ins['price'] ); ?>" data-type="<?php echo esc_attr( $ins['type'] ); ?>" class="acrb-insurance-radio">
                                        <span>🛡️ <?php echo esc_html( $ins['name'] ); ?></span>
                                    </div>
                                    <span class="acrb-luxury-price">+<?php echo esc_html( $currency . number_format( $ins['price'], 2 ) . ( $ins['type'] === 'day' ? '/day' : ' (flat)' ) ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- CROSS-BORDER PERMITS (Luxury Radio Cards) -->
                        <?php if ( ! empty( $available_borders ) ) : ?>
                        <div class="acrb-luxury-options-group">
                            <div class="acrb-group-title">🌍 <?php esc_html_e( 'Cross-Border Permits', 'awesome-car-rental' ); ?></div>
                            <label class="acrb-luxury-card selected">
                                <div class="acrb-luxury-info">
                                    <input type="radio" name="acrb_selected_border" value="" data-fee="0" data-type="flat" class="acrb-border-radio" checked>
                                    <span><?php esc_html_e( 'No Border Crossing', 'awesome-car-rental' ); ?></span>
                                </div>
                                <span class="acrb-luxury-price"><?php echo esc_html( $currency ); ?>0.00</span>
                            </label>
                            <?php foreach ( $available_borders as $border ) : ?>
                                <label class="acrb-luxury-card">
                                    <div class="acrb-luxury-info">
                                        <input type="radio" name="acrb_selected_border" value="<?php echo esc_attr( $border['country'] ); ?>" data-fee="<?php echo esc_attr( (string) $border['fee'] ); ?>" data-type="<?php echo esc_attr( $border['type'] ); ?>" class="acrb-border-radio">
                                        <span>🌐 <?php echo esc_html( $border['country'] ); ?></span>
                                    </div>
                                    <span class="acrb-luxury-price">+<?php echo esc_html( $currency . number_format( $border['fee'], 2 ) . ( $border['type'] === 'day' ? '/day' : ' (flat)' ) ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- ULTRA-PREMIUM PRICE BREAKDOWN SUMMARY -->
                        <div class="acrb-breakdown-card">
                            <div class="acrb-breakdown-header">
                                <svg class="acrb-single-icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                                <span><?php esc_html_e( 'Price Breakdown Summary', 'awesome-car-rental' ); ?></span>
                            </div>
                            
                            <!-- Vehicle Rental Subtotal -->
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div class="acrb-bd-icon-badge">🚗</div>
                                    <div>
                                        <span><?php esc_html_e( 'Vehicle Rental', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub" id="acrb_calc_days">0 Days selected</span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_calc_subtotal"><?php echo esc_html( $currency ); ?>0.00</div>
                            </div>

                            <!-- Selected Insurance (Dynamic Row) -->
                            <div class="acrb-bd-item" id="acrb_bd_insurance_row" style="display: none;">
                                <div class="acrb-bd-label">
                                    <div class="acrb-bd-icon-badge">🛡️</div>
                                    <div>
                                        <span id="acrb_bd_insurance_title"><?php esc_html_e( 'Insurance Plan', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Selected Coverage', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_bd_insurance_val">+<?php echo esc_html( $currency ); ?>0.00</div>
                            </div>

                            <!-- Selected Border Crossing (Dynamic Row) -->
                            <div class="acrb-bd-item" id="acrb_bd_border_row" style="display: none;">
                                <div class="acrb-bd-label">
                                    <div class="acrb-bd-icon-badge">🌐</div>
                                    <div>
                                        <span id="acrb_bd_border_title"><?php esc_html_e( 'Cross-Border Permit', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'International Travel', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val" id="acrb_bd_border_val">+<?php echo esc_html( $currency ); ?>0.00</div>
                            </div>

                            <!-- Security Deposit -->
                            <?php if ( $security_deposit > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div class="acrb-bd-icon-badge">🔒</div>
                                    <div>
                                        <span><?php esc_html_e( 'Security Deposit', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Fully Refundable Hold', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $currency . number_format( $security_deposit, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <!-- Mandatory Insurance Fee -->
                            <?php if ( $insurance_fee > 0 ) : ?>
                            <div class="acrb-bd-item">
                                <div class="acrb-bd-label">
                                    <div class="acrb-bd-icon-badge">📋</div>
                                    <div>
                                        <span><?php esc_html_e( 'Mandatory Insurance', 'awesome-car-rental' ); ?></span>
                                        <span class="acrb-bd-sub"><?php esc_html_e( 'Base Fleet Protection', 'awesome-car-rental' ); ?></span>
                                    </div>
                                </div>
                                <div class="acrb-bd-val"><?php echo esc_html( $currency . number_format( $insurance_fee, 2 ) ); ?></div>
                            </div>
                            <?php endif; ?>

                            <!-- Grand Total Bar -->
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

        // Luxury Radio Card Active State Handler
        function setupLuxuryCards(selector) {
            const radios = document.querySelectorAll(selector);
            radios.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('input[name="' + this.name + '"]').forEach(r => {
                        r.closest('.acrb-luxury-card').classList.remove('selected');
                    });
                    if (this.checked) {
                        this.closest('.acrb-luxury-card').classList.add('selected');
                    }
                    calc();
                });
            });
        }
        setupLuxuryCards('input[name="acrb_selected_insurance"]');
        setupLuxuryCards('input[name="acrb_selected_border"]');

        // Price Breakdown Logic with Dates, Times, Insurance & Border Crossing
        const pDate = document.getElementById('p_date');
        const pTime = document.getElementById('p_time');
        const rDate = document.getElementById('r_date');
        const rTime = document.getElementById('r_time');
        const pLoc = document.getElementById('acrb_pickup_loc');
        const rLoc = document.getElementById('acrb_return_loc');
        
        const displayTotal = document.getElementById('acrb_sum_total');
        const displayDays = document.getElementById('acrb_calc_days');
        const displaySubtotal = document.getElementById('acrb_calc_subtotal');
        
        // Dynamic breakdown rows
        const insuranceRow = document.getElementById('acrb_bd_insurance_row');
        const insuranceTitle = document.getElementById('acrb_bd_insurance_title');
        const insuranceVal = document.getElementById('acrb_bd_insurance_val');

        const borderRow = document.getElementById('acrb_bd_border_row');
        const borderTitle = document.getElementById('acrb_bd_border_title');
        const borderVal = document.getElementById('acrb_bd_border_val');

        const container = document.querySelector('.acrb-main-container');
        const rate = container ? parseFloat(container.dataset.dayRate) : 0;
        const deposit = container ? parseFloat(container.dataset.deposit) : 0;
        const insuranceBase = container ? parseFloat(container.dataset.insurance) : 0;
        const currency = container ? container.dataset.currency : '$';

        // Auto-sync return location with pickup location if empty
        if (pLoc && rLoc) {
            pLoc.addEventListener('change', function() {
                if (!rLoc.value) rLoc.value = this.value;
            });
        }

        function calc() {
            if (pDate && rDate && pDate.value && rDate.value && pTime && rTime) {
                const startStr = pDate.value + 'T' + (pTime.value || '10:00');
                const endStr = rDate.value + 'T' + (rTime.value || '10:00');
                
                const start = new Date(startStr);
                const end = new Date(endStr);
                
                const diffHours = (end - start) / (1000 * 60 * 60);
                
                if (diffHours > 0) {
                    const days = Math.max(1, Math.ceil(diffHours / 24));
                    let subtotal = days * rate;
                    let optionsTotal = 0;

                    // 1. Insurance Calculation & UI Update
                    const selectedIns = document.querySelector('input[name="acrb_selected_insurance"]:checked');
                    let insCost = 0;
                    if (selectedIns && selectedIns.value) {
                        const price = parseFloat(selectedIns.dataset.price) || 0;
                        const type = selectedIns.dataset.type;
                        insCost = (type === 'day' ? price * days : price);
                        optionsTotal += insCost;

                        insuranceTitle.textContent = selectedIns.value;
                        insuranceVal.textContent = '+' + currency + insCost.toFixed(2);
                        insuranceRow.style.display = 'flex';
                    } else {
                        insuranceRow.style.display = 'none';
                    }

                    // 2. Border Crossing Calculation & UI Update
                    const selectedBorder = document.querySelector('input[name="acrb_selected_border"]:checked');
                    let borderCost = 0;
                    if (selectedBorder && selectedBorder.value) {
                        const fee = parseFloat(selectedBorder.dataset.fee) || 0;
                        const type = selectedBorder.dataset.type;
                        borderCost = (type === 'day' ? fee * days : fee);
                        optionsTotal += borderCost;

                        borderTitle.textContent = selectedBorder.value + ' Permit';
                        borderVal.textContent = '+' + currency + borderCost.toFixed(2);
                        borderRow.style.display = 'flex';
                    } else {
                        borderRow.style.display = 'none';
                    }

                    const grandTotal = subtotal + optionsTotal + deposit + insuranceBase;

                    displayDays.textContent = days + (days === 1 ? ' Day selected' : ' Days selected');
                    displaySubtotal.textContent = currency + subtotal.toFixed(2);
                    displayTotal.textContent = grandTotal.toFixed(2);
                } else {
                    displayDays.textContent = '0 Days selected';
                    displaySubtotal.textContent = currency + '0.00';
                    insuranceRow.style.display = 'none';
                    borderRow.style.display = 'none';
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

        if (pTime && rTime) {
            pTime.addEventListener('change', calc);
            rTime.addEventListener('change', calc);
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
    <?php
    return ob_get_clean();
}