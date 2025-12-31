<?php
/**
 * Ultra-Clean Print Template (Body Only)
 */
if (!defined('ABSPATH')) exit;

// 1. DATA PREP (Prefixed variables to avoid Global Namespace pollution)
$acrb_booking_id   = isset($booking_id) ? absint($booking_id) : 0;
$acrb_customer_name   = get_post_meta($acrb_booking_id, 'customer_name', true) ?: 'Guest';
$acrb_customer_phone  = get_post_meta($acrb_booking_id, 'customer_phone', true) ?: 'N/A';
$acrb_pickup_loc      = get_post_meta($acrb_booking_id, 'pickup_location', true) ?: 'Main Hub';
$acrb_dropoff_loc     = get_post_meta($acrb_booking_id, 'dropoff_location', true) ?: 'Main Hub';
$acrb_start_date      = get_post_meta($acrb_booking_id, 'pickup_date', true) ?: '--';
$acrb_end_date        = get_post_meta($acrb_booking_id, 'return_date', true) ?: '--';
$acrb_vehicle_meta    = get_post_meta($acrb_booking_id, 'vehicle_info', true) ?: [];
$acrb_notes           = get_post_meta($acrb_booking_id, 'notes', true) ?: '';
$acrb_total           = get_post_meta($acrb_booking_id, 'total_price', true) ?: 0;
$acrb_currency        = get_option('acrb_currency', '£');
$acrb_site_name       = get_bloginfo('name');

// Calculations
$acrb_car_name   = !empty($acrb_vehicle_meta['name']) ? $acrb_vehicle_meta['name'] : 'Rental Vehicle';
$acrb_daily_rate = !empty($acrb_vehicle_meta['rate']) ? floatval($acrb_vehicle_meta['rate']) : 0;
$acrb_total_days = !empty($acrb_vehicle_meta['days']) ? intval($acrb_vehicle_meta['days']) : 1;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title>Print_Voucher_<?php echo absint($acrb_booking_id); ?></title>
    <style>
        /* Modern Clean Typography */
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            color: #1a202c; 
            margin: 0; 
            padding: 40px; 
            background: #fff; 
        }

        /* Container for the Voucher */
        .print-body { 
            max-width: 800px; 
            margin: 0 auto; 
            border: 1px solid #e2e8f0; 
            padding: 40px;
            border-radius: 8px;
        }

        /* Header Logic */
        .header-flex { 
            display: flex; 
            justify-content: space-between; 
            border-bottom: 2px solid #2d3748; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }
        .brand-name { font-size: 24px; font-weight: 800; color: #2d3748; margin: 0; }
        .voucher-label { text-align: right; }
        .voucher-label h2 { margin: 0; font-size: 14px; color: #718096; text-transform: uppercase; }
        .voucher-label span { font-size: 20px; font-weight: 700; }

        /* Content Grids */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .info-title { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #4a5568; border-bottom: 1px solid #edf2f7; display: block; margin-bottom: 8px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f7fafc; text-align: left; padding: 12px; font-size: 12px; border-bottom: 2px solid #edf2f7; }
        td { padding: 15px 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }

        /* Summary */
        .total-row { display: flex; justify-content: flex-end; margin-top: 20px; }
        .total-box { background: #2d3748; color: #fff; padding: 15px 30px; border-radius: 4px; text-align: right; }
        .total-box small { display: block; font-size: 10px; opacity: 0.8; }
        .total-box b { font-size: 24px; }

        /* Signatures */
        .sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 100px; margin-top: 60px; }
        .sig-line { border-top: 1px solid #000; padding-top: 8px; text-align: center; font-size: 12px; }

        /* Helper Buttons (Hidden on Print) */
        .no-print-zone { 
            position: fixed; top: 10px; left: 10px; 
        }
        .btn { 
            background: #2d3748; color: white; border: none; padding: 8px 16px; 
            border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none;
            font-size: 12px;
        }

        @media print {
            body { padding: 0; }
            .print-body { border: none; max-width: 100%; padding: 0; }
            .no-print-zone { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print-zone">
        <a href="javascript:window.history.back()" class="btn">← <?php esc_html_e('Back', 'awesome-car-rental'); ?></a>
        <button class="btn" onclick="window.print()"><?php esc_html_e('Print Voucher', 'awesome-car-rental'); ?></button>
    </div>

    <div class="print-body">
        <div class="header-flex">
            <div>
                <h1 class="brand-name"><?php echo esc_html($acrb_site_name); ?></h1>
                <p style="margin:0; font-size:12px; color:#718096;"><?php esc_html_e('Official Rental Voucher', 'awesome-car-rental'); ?></p>
            </div>
            <div class="voucher-label">
                <h2><?php esc_html_e('Booking Reference', 'awesome-car-rental'); ?></h2>
                <span>#<?php echo absint($acrb_booking_id); ?></span>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <span class="info-title"><?php esc_html_e('Customer Information', 'awesome-car-rental'); ?></span>
                <strong><?php echo esc_html($acrb_customer_name); ?></strong><br>
                <?php esc_html_e('Tel:', 'awesome-car-rental'); ?> <?php echo esc_html($acrb_customer_phone); ?>
            </div>
            <div>
                <span class="info-title"><?php esc_html_e('Schedule', 'awesome-car-rental'); ?></span>
                <b><?php esc_html_e('Pick-up:', 'awesome-car-rental'); ?></b> <?php echo esc_html($acrb_start_date); ?> (<?php echo esc_html($acrb_pickup_loc); ?>)<br>
                <b><?php esc_html_e('Return:', 'awesome-car-rental'); ?></b> <?php echo esc_html($acrb_end_date); ?> (<?php echo esc_html($acrb_dropoff_loc); ?>)
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e('Description', 'awesome-car-rental'); ?></th>
                    <th><?php esc_html_e('Rate', 'awesome-car-rental'); ?></th>
                    <th><?php esc_html_e('Days', 'awesome-car-rental'); ?></th>
                    <th style="text-align:right;"><?php esc_html_e('Total', 'awesome-car-rental'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo esc_html($acrb_car_name); ?></strong><br>
                        <small><?php esc_html_e('Standard Insurance & Taxes Included', 'awesome-car-rental'); ?></small>
                    </td>
                    <td><?php echo esc_html($acrb_currency) . number_format($acrb_daily_rate, 2); ?></td>
                    <td><?php echo absint($acrb_total_days); ?></td>
                    <td style="text-align:right;"><strong><?php echo esc_html($acrb_currency) . number_format($acrb_total, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <?php if(!empty($acrb_notes)): ?>
        <div style="margin-bottom: 30px; font-size: 13px; color: #4a5568;">
            <span class="info-title"><?php esc_html_e('Notes / Instructions', 'awesome-car-rental'); ?></span>
            <p style="margin:5px 0;"><?php echo nl2br(esc_html($acrb_notes)); ?></p>
        </div>
        <?php endif; ?>

        <div class="total-row">
            <div class="total-box">
                <small><?php esc_html_e('Grand Total', 'awesome-car-rental'); ?></small>
                <b><?php echo esc_html($acrb_currency) . number_format($acrb_total, 2); ?></b>
            </div>
        </div>

        <div class="sigs">
            <div class="sig-line"><?php esc_html_e('Customer Signature', 'awesome-car-rental'); ?></div>
            <div class="sig-line"><?php esc_html_e('Agent Signature', 'awesome-car-rental'); ?></div>
        </div>

        <p style="text-align:center; margin-top:50px; font-size:10px; color:#a0aec0;">
            <?php /* translators: 1: Booking ID, 2: New status name */
$acrb_msg1 = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );
echo wp_kses_post( $acrb_msg1 ); ?> 
            <?php 
/* translators: %s: The current date and time formatted according to site settings */
// translators: 1: Booking ID, 2: New status name.
$acrb_msg2 = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );

echo wp_kses_post( $acrb_msg2 );
?>
        </p>
    </div>

</body>
</html>
<?php 
exit; 
?>