<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * [acrb_thanks]
 * Displays a professional order summary with Status: Pending (Elite SaaS Modernized)
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */
function acrb_thank_you_page_shortcode() {
    $booking_id = isset( $_GET['booking_id'] ) ? intval( wp_unslash( $_GET['booking_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    // Validation
    if ( ! $booking_id || 'acrb_bookings' !== get_post_type( $booking_id ) ) {
        return '<div class="acrb-error-container" style="text-align:center; padding:80px 20px; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;">' .
            '<h3 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 8px 0; letter-spacing:-0.5px;">' . esc_html__( 'No Active Booking Found', 'awesome-car-rental' ) . '</h3>' .
            '<p style="color:#64748b; font-size:14.5px; margin:0 0 24px 0; line-height:1.5;">' . esc_html__( 'It looks like you reached this page directly. Please book a car first.', 'awesome-car-rental' ) . '</p>' .
            '<a href="' . esc_url( home_url() ) . '" class="acrb-btn-primary" style="display:inline-block; text-decoration:none; background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:#fff; padding:12px 28px; border-radius:10px; font-weight:700; font-size:14px; box-shadow:0 4px 14px rgba(59,130,246,0.35);">' . esc_html__( 'Return to Home', 'awesome-car-rental' ) . '</a>' .
        '</div>';
    }

    // Fetch Data
    $car_id  = get_post_meta( $booking_id, 'car_id', true );
    $name    = get_post_meta( $booking_id, 'customer_name', true );
    $phone   = get_post_meta( $booking_id, 'customer_phone', true );
    $pickup  = get_post_meta( $booking_id, 'pickup_date', true );
    $return  = get_post_meta( $booking_id, 'return_date', true );
    $total   = get_post_meta( $booking_id, 'total_price', true );
    $method  = get_post_meta( $booking_id, 'payment_method', true );
    
    // Dynamic Currency
    $currency = get_option( 'acrb_currency', '£' );

    // Calculations
    $days = ceil( abs( strtotime( $return ) - strtotime( $pickup ) ) / 86400 );
    $days = ( $days <= 0 ) ? 1 : $days;

    ob_start(); ?>
    
    <style>
        .acrb-thanks-wrapper { max-width: 680px; margin: 40px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; box-sizing: border-box; }
        .acrb-thanks-wrapper *, .acrb-thanks-wrapper *::before, .acrb-thanks-wrapper *::after { box-sizing: border-box; }

        .acrb-thanks-card { padding: 48px; border: 1px solid #e2e8f0; border-radius: 24px; background: #fff; box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08); }
        
        .acrb-thanks-header { text-align: center; margin-bottom: 36px; }
        .acrb-success-icon { color: #16a34a; margin-bottom: 16px; display: inline-flex; align-items: center; justify-content: center; width: 72px; height: 72px; background: #dcfce7; border-radius: 50%; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15); }
        .acrb-success-icon .dashicons { font-size: 36px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-thanks-title { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 8px 0; letter-spacing: -0.6px; }
        .acrb-thanks-sub { color: #64748b; font-size: 14.5px; margin: 0; line-height: 1.5; }
        
        .acrb-status-badge { display: inline-flex; align-items: center; gap: 6px; margin-top: 18px; padding: 6px 14px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 20px; color: #92400e; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; }
        .acrb-status-badge .dashicons { font-size: 14px; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }

        .acrb-summary-box { background: #f8fafc; padding: 32px; border-radius: 16px; margin-bottom: 32px; border: 1px solid #e2e8f0; }
        .acrb-summary-title { margin: 0 0 16px 0; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        
        .acrb-summary-table { width: 100%; border-collapse: collapse; }
        .acrb-summary-table td { padding: 12px 0; font-size: 14px; }
        .acrb-summary-table td:first-child { color: #64748b; font-weight: 600; }
        .acrb-summary-table td:last-child { text-align: right; font-weight: 700; color: #1e293b; }
        
        .acrb-total-row { border-top: 1px solid #e2e8f0; }
        .acrb-total-row td { padding-top: 18px !important; }
        .acrb-total-label { font-weight: 800 !important; color: #0f172a !important; font-size: 15px !important; }
        .acrb-total-val { font-weight: 900 !important; color: #3b82f6 !important; font-size: 24px !important; letter-spacing: -0.6px; }

        .acrb-thanks-footer { text-align: center; }
        .acrb-footer-desc { font-size: 13.5px; color: #64748b; margin: 0 0 24px 0; line-height: 1.6; }
        
        .acrb-btn-print { cursor: pointer; background: #fff; border: 1px solid #cbd5e1; color: #334155; padding: 11px 22px; border-radius: 10px; font-weight: 700; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        .acrb-btn-print:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); }
        .acrb-btn-print .dashicons { font-size: 16px; width: 16px; height: 16px; color: #64748b; display: inline-flex; align-items: center; justify-content: center; }

        .acrb-support-link { margin-top: 24px; font-size: 13.5px; color: #64748b; font-weight: 500; }
        .acrb-support-link a { color: #3b82f6; text-decoration: none; font-weight: 700; }
        .acrb-support-link a:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .acrb-thanks-wrapper { margin: 20px; }
            .acrb-thanks-card { padding: 28px; }
        }
    </style>

    <div class="acrb-thanks-wrapper">
        <div class="acrb-thanks-card">
            <div class="acrb-thanks-header">
                <div class="acrb-success-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <h1 class="acrb-thanks-title"><?php esc_html_e( 'Booking Received!', 'awesome-car-rental' ); ?></h1>
                <p class="acrb-thanks-sub">
                    <?php
                    $greeting_msg = sprintf( esc_html__( 'Thank you, %1$s. Your request has been sent.', 'awesome-car-rental' ), esc_html( $name ) );
                    echo wp_kses_post( $greeting_msg );
                    ?>
                </p>
                <div class="acrb-status-badge">
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e( 'Waiting for Admin confirmation', 'awesome-car-rental' ); ?>
                </div>
            </div>

            <div class="acrb-summary-box">
                <h3 class="acrb-summary-title">
                    <?php esc_html_e( 'Reservation Summary', 'awesome-car-rental' ); ?>
                </h3>
                <table class="acrb-summary-table">
                    <tr>
                        <td><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></td>
                        <td style="color: #d97706; font-weight: 800;"><?php esc_html_e( 'Pending', 'awesome-car-rental' ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Booking Reference', 'awesome-car-rental' ); ?></td>
                        <td>#<?php echo esc_html( $booking_id ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></td>
                        <td><?php echo esc_html( get_the_title( $car_id ) ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Duration', 'awesome-car-rental' ); ?></td>
                        <td><?php echo esc_html( $days ); ?> <?php esc_html_e( 'Days', 'awesome-car-rental' ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Payment Method', 'awesome-car-rental' ); ?></td>
                        <td style="text-transform: capitalize;"><?php echo esc_html( str_replace( '_', ' ', $method ) ); ?></td>
                    </tr>
                    <tr class="acrb-total-row">
                        <td class="acrb-total-label"><?php esc_html_e( 'Total Amount', 'awesome-car-rental' ); ?></td>
                        <td class="acrb-total-val">
                            <?php 
                            echo esc_html( $currency . number_format( floatval( $total ), 2 ) ); 
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="acrb-thanks-footer">
                <p class="acrb-footer-desc">
                    <?php esc_html_e( 'An email notification has been sent to our team. We will contact you shortly via phone/email to confirm your booking.', 'awesome-car-rental' ); ?>
                </p>
                <button type="button" onclick="window.print()" class="acrb-btn-print">
                    <span class="dashicons dashicons-printer"></span> 
                    <span><?php esc_html_e( 'Print Receipt', 'awesome-car-rental' ); ?></span>
                </button>
                <p class="acrb-support-link">
                    <?php esc_html_e( 'Need help?', 'awesome-car-rental' ); ?> 
                    <a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"><?php esc_html_e( 'Contact Support', 'awesome-car-rental' ); ?></a>
                </p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'acrb_thanks', 'acrb_thank_you_page_shortcode' );