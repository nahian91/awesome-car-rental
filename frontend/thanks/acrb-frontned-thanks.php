<?php
/**
 * [acrb_thanks]
 * Ultra-Luxury Elite SaaS Order Confirmation & Receipt Page with Beautiful Itemized Table
 * Location: templates/shortcodes/thanks.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

add_shortcode( 'acrb_thanks', 'acrb_thank_you_page_shortcode' );

function acrb_thank_you_page_shortcode(): string {
    global $wpdb;
    $table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
    $table_promos   = $wpdb->prefix . 'dev_nahian_promo_codes';

    $booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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

    // Validation against Custom Table
    $booking = null;
    if ( $booking_id > 0 ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $booking_id ), ARRAY_A );
    }

    if ( ! $booking ) {
        ob_start();
        ?>
        <style>
            :root {
                --acrb-thanks-primary: <?php echo esc_html( $primary_color ); ?>;
                --acrb-thanks-secondary: <?php echo esc_html( $secondary_color ); ?>;
                --acrb-thanks-text: <?php echo esc_html( $text_color ); ?>;
                --acrb-thanks-font: <?php echo esc_html( $selected_font ); ?>;
            }
            .acrb-error-container {
                max-width: 580px;
                margin: 90px auto;
                text-align: center;
                padding: 60px 40px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 24px;
                box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
                font-family: var(--acrb-thanks-font);
                color: var(--acrb-thanks-text);
                box-sizing: border-box;
            }
            .acrb-error-container h3 {
                color: var(--acrb-thanks-text);
                font-size: 24px;
                font-weight: 900;
                margin: 0 0 12px;
                letter-spacing: -0.03em;
            }
            .acrb-error-container p {
                color: var(--acrb-thanks-secondary);
                font-size: 14.5px;
                margin: 0 0 32px;
                line-height: 1.6;
            }
            .acrb-btn-home {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                text-decoration: none !important;
                background: var(--acrb-thanks-primary);
                color: #ffffff !important;
                padding: 14px 28px;
                border-radius: 12px;
                font-weight: 800;
                font-size: 14px;
                box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.35);
                transition: transform 0.15s ease, opacity 0.15s ease;
            }
            .acrb-btn-home:hover {
                opacity: 0.92;
                transform: translateY(-1px);
            }
        </style>
        <div class="acrb-error-container">
            <h3><?php esc_html_e( 'No Active Booking Found', 'awesome-car-rental' ); ?></h3>
            <p><?php esc_html_e( 'It looks like you reached this confirmation page directly without a valid reservation identifier.', 'awesome-car-rental' ); ?></p>
            <a href="<?php echo esc_url( home_url() ); ?>" class="acrb-btn-home">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span><?php esc_html_e( 'Return to Fleet Inventory', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    // Extract Record Fields from Database
    $booking_code = $booking['booking_code'] ?? ( 'ACR-' . $booking_id );
    $car_name     = $booking['car_name'] ?? __( 'Fleet Vehicle', 'awesome-car-rental' );
    $name         = $booking['customer_name'] ?? __( 'Valued Guest', 'awesome-car-rental' );
    $email        = $booking['customer_email'] ?? '';
    $phone        = $booking['customer_phone'] ?? '';
    $pickup       = $booking['pickup_date'] ?? ''; 
    $return       = $booking['return_date'] ?? ''; 
    $p_loc        = $booking['pickup_location'] ?? __( 'Main Terminal', 'awesome-car-rental' );
    $d_loc        = $booking['return_location'] ?? __( 'Main Terminal', 'awesome-car-rental' );
    $promo_code   = trim( (string) ( $booking['promo_code'] ?? '' ) );
    $insurance_pl = trim( (string) ( $booking['insurance_plan'] ?? '' ) );
    $border_pe    = trim( (string) ( $booking['border_permit'] ?? '' ) );
    $total        = floatval( $booking['total_amount'] ?? 0 );
    $method_raw   = $booking['payment_method'] ?? 'pay_later';
    $status       = $booking['booking_status'] ?? 'pending';

    // Map Payment Method Key to Readable Label
    $method_labels = array(
        'pay_later' => __( 'Book Now, Pay Later', 'awesome-car-rental' ),
        'bank'      => __( 'Direct Bank Transfer (BACS)', 'awesome-car-rental' ),
        'cash'      => __( 'Cash on Pickup', 'awesome-car-rental' ),
    );
    $method = $method_labels[ $method_raw ] ?? ucwords( str_replace( '_', ' ', $method_raw ) );

    // Dynamic Currency & Rental Unit
    $currency    = get_option( 'acrb_currency', '£' );
    $rental_unit = get_option( 'acrb_rental_unit', 'day' );

    // Calculate rental duration in days
    $days = 1;
    if ( ! empty( $pickup ) && ! empty( $return ) ) {
        try {
            $start_dt = new DateTime( $pickup );
            $end_dt   = new DateTime( $return );
            $diff_hrs = ( $end_dt->getTimestamp() - $start_dt->getTimestamp() ) / 3600;
            $days     = max( 1, (int) ceil( $diff_hrs / 24 ) );
        } catch ( Exception $e ) {
            $days = 1;
        }
    }

    // Lookup Promo details if applied
    $promo_display = '';
    if ( ! empty( $promo_code ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $p_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_promos} WHERE code = %s", $promo_code ), ARRAY_A );
        if ( $p_record ) {
            $p_type = $p_record['discount_type'];
            $p_val  = floatval( $p_record['discount_value'] );
            $promo_display = $promo_code . ' (' . ( 'percentage' === $p_type ? $p_val . '%' : $currency . number_format( $p_val, 2 ) ) . ' OFF)';
        } else {
            $promo_display = $promo_code;
        }
    }

    ob_start();
    ?>
    
    <style>
        :root {
            --acrb-thanks-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-thanks-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-thanks-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-thanks-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-thanks-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-thanks-wrapper {
            max-width: 860px;
            margin: 60px auto;
            font-family: var(--acrb-thanks-font);
            color: var(--acrb-thanks-text);
            box-sizing: border-box;
        }
        .acrb-thanks-wrapper *,
        .acrb-thanks-wrapper *::before,
        .acrb-thanks-wrapper *::after {
            box-sizing: border-box;
        }

        .acrb-thanks-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            stroke-width: 2.2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        .acrb-thanks-card {
            padding: 56px;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        }
        
        .acrb-thanks-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .acrb-success-icon {
            color: var(--acrb-thanks-accent);
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: #ecfdf5;
            border-radius: 50%;
            border: 1px solid #a7f3d0;
            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.12);
        }
        .acrb-success-icon .acrb-thanks-icon {
            width: 40px;
            height: 40px;
        }
        
        .acrb-thanks-title {
            font-size: 32px;
            font-weight: 900;
            color: var(--acrb-thanks-text);
            margin: 0 0 10px 0;
            letter-spacing: -0.03em;
        }
        .acrb-thanks-sub {
            color: var(--acrb-thanks-secondary);
            font-size: 15px;
            margin: 0;
            line-height: 1.6;
        }
        .acrb-thanks-sub strong {
            color: var(--acrb-thanks-text);
        }
        
        .acrb-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 8px 16px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 9999px;
            color: #b45309;
            font-weight: 800;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Summary Sections */
        .acrb-summary-box {
            background: #f8fafc;
            padding: 32px;
            border-radius: 18px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
        }
        .acrb-summary-title {
            margin: 0 0 20px 0;
            font-size: 12.5px;
            font-weight: 900;
            color: var(--acrb-thanks-secondary);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .acrb-receipt-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .acrb-receipt-node {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 16px 18px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .acrb-receipt-lbl {
            font-size: 11px;
            font-weight: 800;
            color: var(--acrb-thanks-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .acrb-receipt-val {
            font-size: 14.5px;
            font-weight: 900;
            color: var(--acrb-thanks-text);
        }

        /* Nice Itemized Table Styles */
        .acrb-table-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
        }
        .acrb-breakdown-table {
            width: 100%;
            border-collapse: collapse;
        }
        .acrb-breakdown-table thead th {
            background: #f1f5f9;
            color: var(--acrb-thanks-secondary);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 18px;
            text-align: left;
        }
        .acrb-breakdown-table thead th:last-child {
            text-align: right;
        }
        .acrb-breakdown-table tr td {
            padding: 16px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
            color: #334155;
            font-weight: 600;
            vertical-align: middle;
        }
        .acrb-breakdown-table tr:last-child td {
            border-bottom: none;
        }
        .acrb-breakdown-table tr td:last-child {
            text-align: right;
            font-weight: 900;
            color: var(--acrb-thanks-text);
        }
        .acrb-item-sub {
            display: block;
            font-size: 11.5px;
            color: var(--acrb-thanks-secondary);
            font-weight: 500;
            margin-top: 3px;
        }

        .acrb-receipt-total-bar {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            padding: 20px 24px;
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }
        .acrb-receipt-total-lbl {
            font-size: 16px;
            font-weight: 900;
            color: var(--acrb-thanks-text);
        }
        .acrb-receipt-total-val {
            font-size: 26px;
            font-weight: 900;
            color: var(--acrb-thanks-primary);
            letter-spacing: -0.02em;
        }

        .acrb-thanks-footer {
            text-align: center;
        }
        .acrb-footer-desc {
            font-size: 13.5px;
            color: var(--acrb-thanks-secondary);
            margin: 0 0 24px 0;
            line-height: 1.6;
        }
        
        .acrb-action-buttons {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .acrb-btn-action {
            cursor: pointer;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: var(--acrb-thanks-text);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
            text-decoration: none !important;
            font-family: inherit;
        }
        .acrb-btn-action:hover {
            background: #f8fafc;
            border-color: var(--acrb-thanks-primary);
            color: var(--acrb-thanks-primary);
            transform: translateY(-1px);
        }
        .acrb-btn-action.primary {
            background: var(--acrb-thanks-primary);
            border-color: var(--acrb-thanks-primary);
            color: #ffffff !important;
            box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.35);
        }
        .acrb-btn-action.primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .acrb-support-link {
            margin-top: 24px;
            font-size: 13px;
            color: var(--acrb-thanks-secondary);
            font-weight: 600;
        }
        .acrb-support-link a {
            color: var(--acrb-thanks-primary);
            text-decoration: none;
            font-weight: 800;
        }
        .acrb-support-link a:hover {
            text-decoration: underline;
        }

        /* Printable Invoice Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .acrb-thanks-wrapper,
            .acrb-thanks-wrapper * {
                visibility: visible;
            }
            .acrb-thanks-wrapper {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
            }
            .acrb-thanks-card {
                box-shadow: none;
                border: none;
                padding: 20px;
            }
            .acrb-action-buttons,
            .acrb-support-link {
                display: none !important;
            }
        }

        @media (max-width: 640px) {
            .acrb-thanks-wrapper {
                margin: 20px;
            }
            .acrb-thanks-card {
                padding: 32px 20px;
            }
            .acrb-receipt-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="acrb-thanks-wrapper">
        <div class="acrb-thanks-card">
            <div class="acrb-thanks-header">
                <div class="acrb-success-icon">
                    <svg class="acrb-thanks-icon" viewBox="0 0 24 24">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h1 class="acrb-thanks-title"><?php esc_html_e( 'Reservation Confirmed!', 'awesome-car-rental' ); ?></h1>
                <p class="acrb-thanks-sub">
                    <?php
                    /* translators: %s: Customer name */
                    $greeting_msg = sprintf( esc_html__( 'Thank you, %s. Your reservation has been successfully processed and logged.', 'awesome-car-rental' ), '<strong>' . esc_html( (string) $name ) . '</strong>' );
                    echo wp_kses_post( $greeting_msg );
                    ?>
                </p>
                <div class="acrb-status-badge">
                    <span><?php esc_html_e( 'Status: Pending Fleet Verification', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <!-- Customer & Booking Info Summary Box -->
            <div class="acrb-summary-box">
                <h3 class="acrb-summary-title">
                    📋 <?php esc_html_e( 'Reservation & Guest Details', 'awesome-car-rental' ); ?>
                </h3>
                <div class="acrb-receipt-grid">
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Booking Reference', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val" style="color: var(--acrb-thanks-primary);"><?php echo esc_html( $booking_code ); ?></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Selected Payment Method', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val">💳 <?php echo esc_html( $method ); ?></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Registered Guest Name', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val"><?php echo esc_html( $name ); ?></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Contact Email / Phone', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val" style="font-size: 13px;"><?php echo esc_html( $email ); ?> <br><span style="color:var(--acrb-thanks-secondary); font-weight:600;"><?php echo esc_html( $phone ?: __( 'No phone provided', 'awesome-car-rental' ) ); ?></span></span>
                    </div>
                </div>
            </div>

            <!-- Itinerary Summary Box with Exact Times -->
            <div class="acrb-summary-box">
                <h3 class="acrb-summary-title">
                    📍 <?php esc_html_e( 'Travel Itinerary & Schedule', 'awesome-car-rental' ); ?>
                </h3>
                <div class="acrb-receipt-grid">
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Pickup Date & Time', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val" style="font-size: 13.5px;"><?php echo esc_html( $pickup ); ?> <span class="acrb-item-sub">(<?php echo esc_html( $p_loc ); ?>)</span></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Return Date & Time', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val" style="font-size: 13.5px;"><?php echo esc_html( $return ); ?> <span class="acrb-item-sub">(<?php echo esc_html( $d_loc ); ?>)</span></span>
                    </div>
                </div>
            </div>

            <!-- Nicely Styled Itemized Financial Breakdown Box -->
            <div class="acrb-summary-box">
                <h3 class="acrb-summary-title">
                    💰 <?php esc_html_e( 'Itemized Financial Breakdown', 'awesome-car-rental' ); ?>
                </h3>

                <div class="acrb-table-container">
                    <table class="acrb-breakdown-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Item Description', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Amount / Status', 'awesome-car-rental' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #eef2ff; color: var(--acrb-thanks-primary); display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">🚗</div>
                                        <div>
                                            <span style="font-weight: 800; color: var(--acrb-thanks-text);"><?php echo esc_html( $car_name ); ?></span>
                                            <span class="acrb-item-sub"><?php echo absint( $days ); ?> <?php echo esc_html( ucfirst( $rental_unit ) ); ?>(s) rental duration</span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $currency . number_format( $total, 2 ) ); ?></td>
                            </tr>

                            <?php if ( ! empty( $insurance_pl ) ) : ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #eef2ff; color: var(--acrb-thanks-primary); display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">🛡️</div>
                                        <div>
                                            <span style="font-weight: 800; color: var(--acrb-thanks-text);"><?php echo esc_html( $insurance_pl ); ?></span>
                                            <span class="acrb-item-sub"><?php esc_html_e( 'Coverage Protection Plan', 'awesome-car-rental' ); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: var(--acrb-thanks-primary);"><?php esc_html_e( 'Included', 'awesome-car-rental' ); ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ( ! empty( $border_pe ) ) : ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #eef2ff; color: var(--acrb-thanks-primary); display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">🌐</div>
                                        <div>
                                            <span style="font-weight: 800; color: var(--acrb-thanks-text);"><?php echo esc_html( $border_pe ); ?> Permit</span>
                                            <span class="acrb-item-sub"><?php esc_html_e( 'Cross-Border Travel Permit', 'awesome-car-rental' ); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: var(--acrb-thanks-primary);"><?php esc_html_e( 'Included', 'awesome-car-rental' ); ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ( ! empty( $promo_display ) ) : ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;">🏷️</div>
                                        <div>
                                            <span style="font-weight: 800; color: var(--acrb-thanks-accent);"><?php esc_html_e( 'Promo Code Voucher', 'awesome-car-rental' ); ?></span>
                                            <span class="acrb-item-sub"><?php echo esc_html( $promo_display ); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: var(--acrb-thanks-accent); font-weight: 900;"><?php esc_html_e( 'Applied', 'awesome-car-rental' ); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="acrb-receipt-total-bar">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="acrb-receipt-total-lbl"><?php esc_html_e( 'Total Amount Due', 'awesome-car-rental' ); ?></span>
                        <span style="font-size: 12px; font-weight: 800; color: var(--acrb-thanks-secondary); background: #f1f5f9; padding: 4px 10px; border-radius: 6px; border: 1px solid #cbd5e1;"><?php echo esc_html( $method ); ?></span>
                    </div>
                    <span class="acrb-receipt-total-val"><?php echo esc_html( $currency . number_format( $total, 2 ) ); ?></span>
                </div>
            </div>

            <div class="acrb-thanks-footer">
                <p class="acrb-footer-desc">
                    <?php esc_html_e( 'An official confirmation email has been dispatched to your registered address. Our fleet manager will contact you shortly to verify handover paperwork.', 'awesome-car-rental' ); ?>
                </p>

                <div class="acrb-action-buttons">
                    <button type="button" onclick="window.print()" class="acrb-btn-action">
                        <svg class="acrb-thanks-icon" viewBox="0 0 24 24">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span><?php esc_html_e( 'Print Official Receipt', 'awesome-car-rental' ); ?></span>
                    </button>
                    <a href="<?php echo esc_url( home_url( '/acrb-cars/' ) ); ?>" class="acrb-btn-action primary">
                        <svg class="acrb-thanks-icon" viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"></path></svg>
                        <span><?php esc_html_e( 'Explore More Vehicles', 'awesome-car-rental' ); ?></span>
                    </a>
                </div>

                <p class="acrb-support-link">
                    <?php esc_html_e( 'Need immediate assistance?', 'awesome-car-rental' ); ?> 
                    <a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"><?php esc_html_e( 'Contact Fleet Support', 'awesome-car-rental' ); ?></a>
                </p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}