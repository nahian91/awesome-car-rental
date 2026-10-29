<?php
/**
 * [acrb_thanks]
 * Ultra-Luxury Elite SaaS Order Confirmation & Receipt Page (Maximum Polish)
 * Location: templates/shortcodes/thanks.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.9.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

add_shortcode( 'acrb_thanks', 'acrb_thank_you_page_shortcode' );

function acrb_thank_you_page_shortcode(): string {
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

    // Validation
    if ( ! $booking_id || 'acrb_bookings' !== get_post_type( $booking_id ) ) {
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
                max-width: 620px;
                margin: 80px auto;
                text-align: center;
                padding: 56px 40px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 20px;
                box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.05);
                font-family: var(--acrb-thanks-font);
                color: var(--acrb-thanks-text);
                box-sizing: border-box;
            }
            .acrb-error-container h3 {
                color: var(--acrb-thanks-text);
                font-size: 22px;
                font-weight: 900;
                margin: 0 0 10px;
                letter-spacing: -0.02em;
            }
            .acrb-error-container p {
                color: var(--acrb-thanks-secondary);
                font-size: 14px;
                margin: 0 0 28px;
                line-height: 1.6;
            }
            .acrb-btn-home {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                text-decoration: none !important;
                background: var(--acrb-thanks-primary);
                color: #ffffff !important;
                padding: 12px 26px;
                border-radius: 10px;
                font-weight: 800;
                font-size: 14px;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
                transition: opacity 0.15s ease;
            }
            .acrb-btn-home:hover {
                opacity: 0.92;
            }
        </style>
        <div class="acrb-error-container">
            <h3><?php esc_html_e( 'No Active Booking Found', 'awesome-car-rental' ); ?></h3>
            <p><?php esc_html_e( 'It looks like you reached this confirmation page directly without a valid reservation identifier.', 'awesome-car-rental' ); ?></p>
            <a href="<?php echo esc_url( home_url() ); ?>" class="acrb-btn-home">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span><?php esc_html_e( 'Return to Fleet Inventory', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    // Fetch Data
    $car_id     = get_post_meta( $booking_id, 'car_id', true );
    $name       = get_post_meta( $booking_id, 'customer_name', true );
    $email      = get_post_meta( $booking_id, 'customer_email', true );
    $phone      = get_post_meta( $booking_id, 'customer_phone', true );
    $pickup     = get_post_meta( $booking_id, 'pickup_date', true );
    $return     = get_post_meta( $booking_id, 'return_date', true );
    $p_loc      = get_post_meta( $booking_id, 'pickup_location', true );
    $d_loc      = get_post_meta( $booking_id, 'return_location', true );
    $total      = get_post_meta( $booking_id, 'total_price', true );
    $method     = get_post_meta( $booking_id, 'payment_method', true );
    
    // Dynamic Currency
    $currency = get_option( 'acrb_currency', '$' );

    // Calculations
    $days = ceil( abs( strtotime( (string) $return ) - strtotime( (string) $pickup ) ) / 86400 );
    $days = ( $days <= 0 ) ? 1 : (int) $days;

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
            max-width: 760px;
            margin: 50px auto;
            font-family: var(--acrb-thanks-font);
            color: var(--acrb-thanks-text);
            box-sizing: border-box;
        }
        .acrb-thanks-wrapper *,
        .acrb-thanks-wrapper *::before,
        .acrb-thanks-wrapper *::after {
            box-sizing: border-box;
        }

        /* SVG Icon Standards */
        .acrb-thanks-icon {
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

        .acrb-thanks-card {
            padding: 48px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.05);
        }
        
        .acrb-thanks-header {
            text-align: center;
            margin-bottom: 36px;
        }
        .acrb-success-icon {
            color: var(--acrb-thanks-accent);
            margin-bottom: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: #ecfdf5;
            border-radius: 50%;
            border: 1px solid #a7f3d0;
            box-shadow: 0 4px 16px rgba(5, 150, 105, 0.1);
        }
        .acrb-success-icon .acrb-thanks-icon {
            width: 36px;
            height: 36px;
        }
        
        .acrb-thanks-title {
            font-size: 28px;
            font-weight: 900;
            color: var(--acrb-thanks-text);
            margin: 0 0 8px 0;
            letter-spacing: -0.02em;
        }
        .acrb-thanks-sub {
            color: var(--acrb-thanks-secondary);
            font-size: 14.5px;
            margin: 0;
            line-height: 1.6;
        }
        
        .acrb-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            padding: 6px 14px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 9999px;
            color: #b45309;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .acrb-status-badge .acrb-thanks-icon {
            width: 15px;
            height: 15px;
        }

        /* Luxury Summary Layout */
        .acrb-summary-box {
            background: #f8fafc;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            border: 1px solid #e2e8f0;
        }
        .acrb-summary-title {
            margin: 0 0 20px 0;
            font-size: 12px;
            font-weight: 900;
            color: var(--acrb-thanks-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .acrb-summary-title .acrb-thanks-icon {
            color: var(--acrb-thanks-primary);
            width: 16px;
            height: 16px;
        }

        .acrb-receipt-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        .acrb-receipt-node {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 16px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .acrb-receipt-lbl {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--acrb-thanks-secondary);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .acrb-receipt-val {
            font-size: 14.5px;
            font-weight: 900;
            color: var(--acrb-thanks-text);
        }

        .acrb-receipt-total-bar {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 18px 22px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .acrb-receipt-total-lbl {
            font-size: 15px;
            font-weight: 800;
            color: var(--acrb-thanks-text);
        }
        .acrb-receipt-total-val {
            font-size: 24px;
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
            gap: 12px;
            flex-wrap: wrap;
        }
        .acrb-btn-action {
            cursor: pointer;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: var(--acrb-thanks-text);
            padding: 12px 22px;
            border-radius: 10px;
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
        }
        .acrb-btn-action.primary {
            background: var(--acrb-thanks-primary);
            border-color: var(--acrb-thanks-primary);
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
        }
        .acrb-btn-action.primary:hover {
            opacity: 0.92;
        }
        .acrb-btn-action .acrb-thanks-icon {
            width: 16px;
            height: 16px;
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
                padding: 28px;
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
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h1 class="acrb-thanks-title"><?php esc_html_e( 'Reservation Request Received!', 'awesome-car-rental' ); ?></h1>
                <p class="acrb-thanks-sub">
                    <?php
                    /* translators: %s: Customer name */
                    $greeting_msg = sprintf( esc_html__( 'Thank you, %s. Your booking has been successfully logged into our system.', 'awesome-car-rental' ), esc_html( (string) $name ) );
                    echo wp_kses_post( $greeting_msg );
                    ?>
                </p>
                <div class="acrb-status-badge">
                    <svg class="acrb-thanks-icon" viewBox="0 0 24 24">
                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span><?php esc_html_e( 'Status: Pending Fleet Confirmation', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <div class="acrb-summary-box">
                <h3 class="acrb-summary-title">
                    <svg class="acrb-thanks-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span><?php esc_html_e( 'Official Reservation Receipt', 'awesome-car-rental' ); ?></span>
                </h3>

                <div class="acrb-receipt-grid">
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Booking Reference', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val">#<?php echo absint( $booking_id ); ?></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Vehicle Model', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val"><?php echo esc_html( get_the_title( (int) $car_id ) ); ?></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Rental Duration', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val"><?php echo absint( $days ); ?> <?php esc_html_e( 'Days', 'awesome-car-rental' ); ?></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Payment Method', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val" style="text-transform: capitalize;"><?php echo esc_html( str_replace( '_', ' ', (string) $method ) ); ?></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Pickup Schedule', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val" style="font-size: 13px;"><?php echo esc_html( $pickup ); ?> <span style="font-weight:600; color:var(--acrb-thanks-secondary); font-size:11px;">(<?php echo esc_html( $p_loc ?: 'Main Terminal' ); ?>)</span></span>
                    </div>
                    <div class="acrb-receipt-node">
                        <span class="acrb-receipt-lbl"><?php esc_html_e( 'Return Schedule', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-receipt-val" style="font-size: 13px;"><?php echo esc_html( $return ); ?> <span style="font-weight:600; color:var(--acrb-thanks-secondary); font-size:11px;">(<?php echo esc_html( $d_loc ?: 'Main Terminal' ); ?>)</span></span>
                    </div>
                </div>

                <div class="acrb-receipt-total-bar">
                    <span class="acrb-receipt-total-lbl"><?php esc_html_e( 'Total Amount Due', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-receipt-total-val"><?php echo esc_html( $currency . number_format( floatval( $total ), 2 ) ); ?></span>
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