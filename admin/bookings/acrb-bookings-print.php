<?php
/**
 * Ultra-Clean Print Template (Body Only)
 * Location: admin/bookings/acrb-bookings-print.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. DATA PREP (Prefixed variables to avoid Global Namespace pollution)
$acrb_booking_id    = isset( $booking_id ) ? absint( $booking_id ) : 0;
$acrb_customer_name = get_post_meta( $acrb_booking_id, 'customer_name', true ) ?: 'Guest';
$acrb_customer_phone = get_post_meta( $acrb_booking_id, 'customer_phone', true ) ?: 'N/A';
$acrb_pickup_loc    = get_post_meta( $acrb_booking_id, 'pickup_location', true ) ?: 'Main Hub';
$acrb_dropoff_loc   = get_post_meta( $acrb_booking_id, 'dropoff_location', true ) ?: 'Main Hub';
$acrb_start_date    = get_post_meta( $acrb_booking_id, 'pickup_date', true ) ?: '--';
$acrb_end_date      = get_post_meta( $acrb_booking_id, 'return_date', true ) ?: '--';
$acrb_vehicle_meta  = get_post_meta( $acrb_booking_id, 'vehicle_info', true ) ?: array();
$acrb_notes         = get_post_meta( $acrb_booking_id, 'notes', true ) ?: '';
$acrb_total         = get_post_meta( $acrb_booking_id, 'total_price', true ) ?: 0;
$acrb_currency      = get_option( 'acrb_currency', '£' );
$acrb_site_name     = get_bloginfo( 'name' );

// Calculations
$acrb_car_name   = ! empty( $acrb_vehicle_meta['name'] ) ? $acrb_vehicle_meta['name'] : 'Rental Vehicle';
$acrb_daily_rate = ! empty( $acrb_vehicle_meta['rate'] ) ? floatval( $acrb_vehicle_meta['rate'] ) : 0;
$acrb_total_days = ! empty( $acrb_vehicle_meta['days'] ) ? intval( $acrb_vehicle_meta['days'] ) : 1;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <title><?php echo esc_attr( 'Print_Voucher_' . absint( $acrb_booking_id ) ); ?></title>
    <style>
        /* Modern Clean Typography & SaaS Aesthetic */
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            color: #1e293b; 
            margin: 0; 
            padding: 40px; 
            background: #f8fafc; 
        }

        /* Container for the Voucher */
        .print-body { 
            max-width: 800px; 
            margin: 0 auto; 
            background: #fff;
            border: 1px solid #e2e8f0; 
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03);
        }

        /* Header Logic */
        .header-flex { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }
        .brand-name { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.5px; }
        .voucher-label { text-align: right; }
        .voucher-label h2 { margin: 0; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .voucher-label span { font-size: 20px; font-weight: 700; color: #3b82f6; }

        /* Content Grids */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .info-title { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0; display: block; margin-bottom: 8px; letter-spacing: 0.5px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f8fafc; text-align: left; padding: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; color: #475569; }
        td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }

        /* Summary */
        .total-row { display: flex; justify-content: flex-end; margin-top: 20px; }
        .total-box { background: #0f172a; color: #fff; padding: 16px 28px; border-radius: 8px; text-align: right; }
        .total-box small { display: block; font-size: 10px; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px; }
        .total-box b { font-size: 22px; font-weight: 700; color: #38bdf8; }

        /* Signatures */
        .sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 100px; margin-top: 60px; }
        .sig-line { border-top: 1px solid #cbd5e1; padding-top: 8px; text-align: center; font-size: 12px; color: #64748b; font-weight: 500; }

        /* Helper Buttons (Hidden on Print) */
        .no-print-zone { 
            position: fixed; top: 20px; left: 20px; 
            display: flex; gap: 10px; z-index: 100;
        }
        .btn { 
            background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 8px 16px; 
            border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none;
            font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: 0.2s;
        }
        .btn:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
        .btn-primary { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .btn-primary:hover { background: #2563eb; color: #fff; }

        @media print {
            body { padding: 0; background: #fff; }
            .print-body { border: none; max-width: 100%; padding: 0; box-shadow: none; border-radius: 0; }
            .no-print-zone { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print-zone">
        <a href="javascript:window.history.back()" class="btn">← <?php esc_html_e( 'Back', 'awesome-car-rental' ); ?></a>
        <button class="btn btn-primary" onclick="window.print()"><?php esc_html_e( 'Print Voucher', 'awesome-car-rental' ); ?></button>
    </div>

    <div class="print-body">
        <div class="header-flex">
            <div>
                <h1 class="brand-name"><?php echo esc_html( $acrb_site_name ); ?></h1>
                <p style="margin:4px 0 0; font-size:12px; color:#64748b;"><?php esc_html_e( 'Official Rental Voucher', 'awesome-car-rental' ); ?></p>
            </div>
            <div class="voucher-label">
                <h2><?php esc_html_e( 'Booking Reference', 'awesome-car-rental' ); ?></h2>
                <span>#<?php echo absint( $acrb_booking_id ); ?></span>
            </div>
        </div>

        <div class="info-grid">
            <div>
                <span class="info-title"><?php esc_html_e( 'Customer Information', 'awesome-car-rental' ); ?></span>
                <strong style="font-size:14px; color:#0f172a;"><?php echo esc_html( $acrb_customer_name ); ?></strong><br>
                <span style="font-size:13px; color:#64748b;"><?php esc_html_e( 'Tel:', 'awesome-car-rental' ); ?> <?php echo esc_html( $acrb_customer_phone ); ?></span>
            </div>
            <div>
                <span class="info-title"><?php esc_html_e( 'Rental Schedule', 'awesome-car-rental' ); ?></span>
                <div style="font-size:13px; color:#334155; line-height:1.6;">
                    <b><?php esc_html_e( 'Pick-up:', 'awesome-car-rental' ); ?></b> <?php echo esc_html( $acrb_start_date ); ?> <span style="color:#64748b;">(<?php echo esc_html( $acrb_pickup_loc ); ?>)</span><br>
                    <b><?php esc_html_e( 'Return:', 'awesome-car-rental' ); ?></b> <?php echo esc_html( $acrb_end_date ); ?> <span style="color:#64748b;">(<?php echo esc_html( $acrb_dropoff_loc ); ?>)</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Description', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Rate', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Days', 'awesome-car-rental' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Total', 'awesome-car-rental' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong style="color:#0f172a;"><?php echo esc_html( $acrb_car_name ); ?></strong><br>
                        <small style="color:#64748b;"><?php esc_html_e( 'Standard Insurance & Taxes Included', 'awesome-car-rental' ); ?></small>
                    </td>
                    <td><?php echo esc_html( $acrb_currency ) . number_format( $acrb_daily_rate, 2 ); ?></td>
                    <td><?php echo absint( $acrb_total_days ); ?></td>
                    <td style="text-align:right;"><strong style="color:#0f172a;"><?php echo esc_html( $acrb_currency ) . number_format( floatval( $acrb_total ), 2 ); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <?php if ( ! empty( $acrb_notes ) ) : ?>
        <div style="margin-top: 24px; font-size: 13px; color: #475569; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <span class="info-title" style="border-bottom:none; margin-bottom:4px;"><?php esc_html_e( 'Notes / Instructions', 'awesome-car-rental' ); ?></span>
            <p style="margin:0; color:#334155;"><?php echo nl2br( esc_html( $acrb_notes ) ); ?></p>
        </div>
        <?php endif; ?>

        <div class="total-row">
            <div class="total-box">
                <small><?php esc_html_e( 'Grand Total', 'awesome-car-rental' ); ?></small>
                <b><?php echo esc_html( $acrb_currency ) . number_format( floatval( $acrb_total ), 2 ); ?></b>
            </div>
        </div>

        <div class="sigs">
            <div class="sig-line"><?php esc_html_e( 'Customer Signature', 'awesome-car-rental' ); ?></div>
            <div class="sig-line"><?php esc_html_e( 'Agent Signature', 'awesome-car-rental' ); ?></div>
        </div>

        <p style="text-align:center; margin-top:50px; font-size:11px; color:#94a3b8;">
            <?php 
            printf(
                /* translators: 1: Site name, 2: Booking ID */
                esc_html__( '%1$s — Official Rental Voucher #%2$d. Generated on %3$s', 'awesome-car-rental' ),
                esc_html( $acrb_site_name ),
                absint( $acrb_booking_id ),
                esc_html( current_time( 'Y-m-d H:i' ) )
            ); 
            ?>
        </p>
    </div>

</body>
</html>
<?php 
exit;