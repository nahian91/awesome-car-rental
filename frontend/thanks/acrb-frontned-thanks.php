<?php 

if ( ! defined( 'ABSPATH' ) ) exit; 

/**
 * [acrb_thank_you]
 * Displays a professional order summary with Status: Pending
 */
function acrb_thank_you_page_shortcode() {
    $booking_id = isset( $_GET['booking_id'] ) ? intval( wp_unslash( $_GET['booking_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    // Validation
    if (!$booking_id || get_post_type($booking_id) !== 'acrb_bookings') {
        return sprintf(
            '<div class="acrb-error-container" style="text-align:center; padding:50px;">
                <h3>%s</h3>
                <p>%s</p>
                <a href="%s" class="acrb-btn-primary" style="display:inline-block; margin-top:15px; text-decoration:none; background:#4f46e5; color:#fff; padding:10px 20px; border-radius:5px;">%s</a>
            </div>',
            esc_html__('No Active Booking Found', 'awesome-car-rental'),
            esc_html__('It looks like you reached this page directly. Please book a car first.', 'awesome-car-rental'),
            esc_url(home_url()),
            esc_html__('Return to Home', 'awesome-car-rental')
        );
    }

    // Fetch Data
    $car_id   = get_post_meta($booking_id, 'car_id', true);
    $name     = get_post_meta($booking_id, 'customer_name', true);
    $phone    = get_post_meta($booking_id, 'customer_phone', true);
    $pickup   = get_post_meta($booking_id, 'pickup_date', true);
    $return   = get_post_meta($booking_id, 'return_date', true);
    $total    = get_post_meta($booking_id, 'total_price', true);
    $method   = get_post_meta($booking_id, 'payment_method', true);
    $currency = '£'; // Hardcoded symbol as per request

    // Calculations
    $days = ceil(abs(strtotime($return) - strtotime($pickup)) / 86400);
    $days = ($days <= 0) ? 1 : $days;

    ob_start(); ?>
    <div class="acrb-thanks-card" style="max-width: 600px; margin: 0 auto; padding: 40px; border: 1px solid #e0e0e0; border-radius: 15px; background: #fff;">
        <div class="acrb-thanks-header" style="text-align:center; margin-bottom: 30px;">
            <div class="acrb-success-icon" style="color: #10b981; margin-bottom: 15px;">
                <span class="dashicons dashicons-clock" style="font-size: 60px; width: 60px; height: 60px;"></span>
            </div>
            <h1 style="font-size: 28px; margin-bottom: 10px;"><?php esc_html_e('Booking Received!', 'awesome-car-rental'); ?></h1>
            <p style="color: #666; font-size: 16px;">
                <?php
// translators: 1: Booking ID, 2: New status name.
$acrb_msg = sprintf( esc_html__( 'Thank you, %1$s. Your request has been sent.', 'awesome-car-rental' ), esc_html( $name ) );
echo wp_kses_post( $acrb_msg );
?>
            </p>
            <div style="display: inline-block; margin-top: 15px; padding: 8px 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; color: #92400e; font-weight: 600; font-size: 14px;">
                <span class="dashicons dashicons-warning" style="font-size: 16px; margin-right: 5px;"></span>
                <?php esc_html_e('Waiting for Admin confirmation', 'awesome-car-rental'); ?>
            </div>
        </div>

        <div class="acrb-summary-box" style="background: #f9fafb; padding: 25px; border-radius: 12px; margin-bottom: 30px;">
            <h3 style="margin-top:0; margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <?php esc_html_e('Reservation Summary', 'awesome-car-rental'); ?>
            </h3>
            <table class="acrb-summary-table" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; color: #666;"><?php esc_html_e('Status', 'awesome-car-rental'); ?></td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700; color: #d97706;"><?php esc_html_e('Pending', 'awesome-car-rental'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #666;"><?php esc_html_e('Booking Reference', 'awesome-car-rental'); ?></td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700;">#<?php echo esc_html($booking_id); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #666;"><?php esc_html_e('Vehicle', 'awesome-car-rental'); ?></td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700;"><?php echo esc_html(get_the_title($car_id)); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #666;"><?php esc_html_e('Duration', 'awesome-car-rental'); ?></td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700;"><?php echo esc_html($days); ?> <?php esc_html_e('Days', 'awesome-car-rental'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #666;"><?php esc_html_e('Payment Method', 'awesome-car-rental'); ?></td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 700; text-transform: capitalize;"><?php echo esc_html(str_replace('_', ' ', $method)); ?></td>
                </tr>
                <tr style="border-top: 1px solid #eee;">
                    <td style="padding: 15px 0; font-weight: 700; color: #000; font-size: 18px;"><?php esc_html_e('Total Amount', 'awesome-car-rental'); ?></td>
                    <td style="padding: 15px 0; text-align: right; font-weight: 900; color: #4f46e5; font-size: 22px;">
                        <?php 
echo '$' . esc_html( number_format( floatval( $total ), 2 ) ); 
?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="acrb-thanks-footer" style="text-align:center;">
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
                <?php esc_html_e('An email notification has been sent to our team. We will contact you shortly via phone/email to confirm your booking.', 'awesome-car-rental'); ?>
            </p>
            <button type="button" onclick="window.print()" style="cursor:pointer; background: #fff; border: 1px solid #ddd; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                <span class="dashicons dashicons-printer" style="font-size: 18px; vertical-align: middle;"></span> <?php esc_html_e('Print Receipt', 'awesome-car-rental'); ?>
            </button>
            <p style="margin-top: 25px; font-size: 13px; color: #999;">
                <?php esc_html_e('Need help?', 'awesome-car-rental'); ?> 
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>" style="color: #4f46e5; text-decoration: none;"><?php esc_html_e('Contact Support', 'awesome-car-rental'); ?></a>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('acrb_thanks', 'acrb_thank_you_page_shortcode');