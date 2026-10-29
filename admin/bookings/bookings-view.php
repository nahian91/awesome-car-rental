<?php
/**
 * ACRB HIGH-END SAAS BOOKING DOSSIER VIEW (Custom Database Architecture)
 * Location: admin/bookings/bookings-view.php
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$acrb_table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
$acrb_table_cars     = $wpdb->prefix . 'dev_nahian_cars';
$acrb_table_promos   = $wpdb->prefix . 'dev_nahian_promo_codes';

// 1. DATA RETRIEVAL
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;

if ( $acrb_booking_id <= 0 ) {
    wp_die( esc_html__( 'Invalid booking ID provided.', 'awesome-car-rental' ) );
}

// Fetch booking record directly from custom database table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$acrb_booking = $wpdb->get_row(
    $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        "SELECT * FROM {$acrb_table_bookings} WHERE id = %d",
        $acrb_booking_id
    ),
    ARRAY_A
);

if ( ! $acrb_booking ) {
    wp_die( esc_html__( 'Booking record not found in custom database table.', 'awesome-car-rental' ) );
}

// 2. FETCH RECORD FIELDS
$acrb_booking_code   = $acrb_booking['booking_code'] ?? ( 'ACR-' . $acrb_booking_id );
$acrb_customer_name  = $acrb_booking['customer_name'] ?? __( 'Guest Client', 'awesome-car-rental' );
$acrb_customer_email = $acrb_booking['customer_email'] ?? '';
$acrb_customer_phone = $acrb_booking['customer_phone'] ?? '';
$acrb_pickup_date    = $acrb_booking['pickup_date'] ?? '';
$acrb_return_date    = $acrb_booking['return_date'] ?? '';
$acrb_pickup_loc     = $acrb_booking['pickup_location'] ?? __( 'Main Terminal', 'awesome-car-rental' );
$acrb_return_loc     = $acrb_booking['return_location'] ?? __( 'Main Terminal', 'awesome-car-rental' );
$acrb_insurance      = trim( (string) ( $acrb_booking['insurance_plan'] ?? '' ) );
$acrb_border         = trim( (string) ( $acrb_booking['border_permit'] ?? '' ) );
$acrb_promo_code     = trim( (string) ( $acrb_booking['promo_code'] ?? '' ) );
$acrb_status         = strtolower( $acrb_booking['booking_status'] ?? 'pending' );
$acrb_total          = floatval( $acrb_booking['total_amount'] ?? 0 );
$acrb_method_raw     = $acrb_booking['payment_method'] ?? 'pay_later';
$acrb_car_id         = absint( $acrb_booking['car_id'] ?? 0 );
$acrb_car_title      = $acrb_booking['car_name'] ?? __( 'Fleet Vehicle', 'awesome-car-rental' );
$acrb_created_at     = $acrb_booking['created_at'] ?? '';
$acrb_currency       = get_option( 'acrb_currency', '£' );
$acrb_rental_unit    = get_option( 'acrb_rental_unit', 'day' );

// Map Payment Method Key to Readable Label
$method_labels = array(
    'pay_later' => __( 'Book Now, Pay Later', 'awesome-car-rental' ),
    'bank'      => __( 'Direct Bank Transfer (BACS)', 'awesome-car-rental' ),
    'cash'      => __( 'Cash on Pickup', 'awesome-car-rental' ),
);
$acrb_method = $method_labels[ $acrb_method_raw ] ?? ucwords( str_replace( '_', ' ', $acrb_method_raw ) );

// Fetch associated vehicle specs directly from custom cars table.
$acrb_car_meta = null;
if ( $acrb_car_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $acrb_car_meta = $wpdb->get_row(
        $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT * FROM {$acrb_table_cars} WHERE id = %d",
            $acrb_car_id
        ),
        ARRAY_A
    );
}

// Compute Duration in Days & Daily Rate.
$acrb_rental_days = 1;
if ( ! empty( $acrb_pickup_date ) && ! empty( $acrb_return_date ) ) {
    try {
        $acrb_start_dt    = new DateTime( $acrb_pickup_date );
        $acrb_end_dt      = new DateTime( $acrb_return_date );
        $acrb_diff_hrs    = ( $acrb_end_dt->getTimestamp() - $acrb_start_dt->getTimestamp() ) / 3600;
        $acrb_rental_days = max( 1, (int) ceil( $acrb_diff_hrs / 24 ) );
    } catch ( Exception $e ) {
        $acrb_rental_days = 1;
    }
}
$acrb_daily_rate   = $acrb_total > 0 ? ( $acrb_total / $acrb_rental_days ) : 0;
$acrb_phone_digits = preg_replace( '/[^0-9]/', '', $acrb_customer_phone );

// Lookup Promo details if applied
$promo_display = '';
if ( ! empty( $acrb_promo_code ) ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $p_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$acrb_table_promos} WHERE code = %s", $acrb_promo_code ), ARRAY_A );
    if ( $p_record ) {
        $p_type = $p_record['discount_type'];
        $p_val  = floatval( $p_record['discount_value'] );
        $promo_display = $acrb_promo_code . ' (' . ( 'percentage' === $p_type ? $p_val . '%' : $acrb_currency . number_format( $p_val, 2 ) ) . ' OFF)';
    } else {
        $promo_display = $acrb_promo_code;
    }
}
?>

<div class="acrb-dossier-wrap" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b;">

    <!-- Summary Hero Banner -->
    <div class="acrb-summary-banner" style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px 28px; margin-bottom: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
        <div class="acrb-client-badge-block" style="display: flex; align-items: center; gap: 16px;">
            <div class="acrb-client-initial" style="width: 54px; height: 54px; background: #eff6ff; color: #2563eb; border-radius: 14px; font-size: 22px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                <?php echo esc_html( strtoupper( mb_substr( trim( $acrb_customer_name ), 0, 1 ) ) ?: 'G' ); ?>
            </div>
            <div>
                <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 800; color: #0f172a;"><?php echo esc_html( $acrb_customer_name ); ?></h3>
                <span class="acrb-code-pill" style="font-family: monospace; font-size: 12px; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; color: #475569; font-weight: 700;"><?php echo esc_html( $acrb_booking_code ); ?></span>
                <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Logged: <?php echo esc_html( $acrb_created_at ); ?></span>
            </div>
        </div>

        <div class="acrb-amount-callout" style="text-align: right;">
            <span style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 2px;"><?php esc_html_e( 'Total Amount Due', 'awesome-car-rental' ); ?></span>
            <strong style="font-size: 26px; font-weight: 900; color: #0f172a; display: block; margin-bottom: 6px;"><?php echo esc_html( $acrb_currency . number_format( $acrb_total, 2 ) ); ?></strong>
            <span class="acrb-status-badge status-<?php echo esc_attr( $acrb_status ); ?>" style="display: inline-block; padding: 3px 12px; border-radius: 99px; font-size: 11.5px; font-weight: 800; text-transform: uppercase; background: #fef3c7; color: #d97706;">
                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $acrb_status ) ) ); ?>
            </span>
        </div>
    </div>

    <!-- 2-Column Split Content -->
    <div class="acrb-dossier-grid" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px;">

        <!-- Left Side: Route, Financial Breakdown & Guest Details -->
        <div class="acrb-dossier-col" style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Route & Schedule -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-location" style="color: #4f46e5;"></span> <?php esc_html_e( 'Travel Itinerary & Schedule', 'awesome-car-rental' ); ?>
                    </h4>
                </div>

                <div class="acrb-route-flow" style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="acrb-flow-step">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e( 'Pick-up Schedule', 'awesome-car-rental' ); ?></span>
                        <strong style="font-size: 13px; color: #0f172a; display: block;"><?php echo esc_html( $acrb_pickup_date ?: '—' ); ?></strong>
                        <span style="font-size: 11px; color: #475569; display: block; margin-top: 2px;">(<?php echo esc_html( $acrb_pickup_loc ); ?>)</span>
                    </div>

                    <div class="acrb-route-mid" style="text-align: center;">
                        <span style="background: #eef2ff; color: #4f46e5; padding: 4px 12px; border-radius: 99px; font-size: 11.5px; font-weight: 800; display: inline-block;"><?php echo absint( $acrb_rental_days ); ?> <?php echo esc_html( ucfirst( $acrb_rental_unit ) ); ?>(s)</span>
                    </div>

                    <div class="acrb-flow-step" style="text-align: right;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e( 'Return Schedule', 'awesome-car-rental' ); ?></span>
                        <strong style="font-size: 13px; color: #0f172a; display: block;"><?php echo esc_html( $acrb_return_date ?: '—' ); ?></strong>
                        <span style="font-size: 11px; color: #475569; display: block; margin-top: 2px;">(<?php echo esc_html( $acrb_return_loc ); ?>)</span>
                    </div>
                </div>
            </div>

            <!-- Itemized Financial Breakdown Box -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-money-alt" style="color: #059669;"></span> <?php esc_html_e( 'Itemized Financial Breakdown', 'awesome-car-rental' ); ?>
                    </h4>
                </div>

                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13.5px; color: #334155; font-weight: 700;">
                            🚗 <?php echo esc_html( $acrb_car_title ); ?>
                            <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;"><?php echo absint( $acrb_rental_days ); ?> <?php echo esc_html( ucfirst( $acrb_rental_unit ) ); ?>(s) × <?php echo esc_html( $acrb_currency . number_format( $acrb_daily_rate, 2 ) ); ?></span>
                        </td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 800; color: #0f172a;"><?php echo esc_html( $acrb_currency . number_format( $acrb_total, 2 ) ); ?></td>
                    </tr>

                    <?php if ( ! empty( $acrb_insurance ) ) : ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13.5px; color: #334155; font-weight: 700;">
                            🛡️ <?php echo esc_html( $acrb_insurance ); ?>
                            <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;"><?php esc_html_e( 'Protection Plan', 'awesome-car-rental' ); ?></span>
                        </td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 800; color: #2563eb;"><?php esc_html_e( 'Included', 'awesome-car-rental' ); ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ( ! empty( $acrb_border ) ) : ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13.5px; color: #334155; font-weight: 700;">
                            🌐 <?php echo esc_html( $acrb_border ); ?> Permit
                            <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;"><?php esc_html_e( 'Cross-Border Travel Permit', 'awesome-car-rental' ); ?></span>
                        </td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 800; color: #2563eb;"><?php esc_html_e( 'Included', 'awesome-car-rental' ); ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ( ! empty( $promo_display ) ) : ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13.5px; color: #059669; font-weight: 700;">
                            🏷️ Promo Code Voucher
                            <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;"><?php echo esc_html( $promo_display ); ?></span>
                        </td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 900; color: #059669;"><?php esc_html_e( 'Applied', 'awesome-car-rental' ); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>

        <!-- Right Side: Assigned Vehicle, Guest Info & Dispatch Actions -->
        <div class="acrb-dossier-col" style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Assigned Car Card -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-car" style="color: #4f46e5;"></span> <?php esc_html_e( 'Assigned Vehicle', 'awesome-car-rental' ); ?>
                    </h4>
                </div>

                <div class="acrb-vehicle-frame" style="display: flex; align-items: center; gap: 14px; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <?php
                    $acrb_img_src = '';
                    if ( $acrb_car_meta && ! empty( $acrb_car_meta['image'] ) ) {
                        $raw_img = $acrb_car_meta['image'];
                        if ( is_numeric( $raw_img ) && absint( $raw_img ) > 0 ) {
                            $acrb_img_src = wp_get_attachment_image_url( absint( $raw_img ), 'thumbnail' ) ?: '';
                        } elseif ( filter_var( $raw_img, FILTER_VALIDATE_URL ) ) {
                            $acrb_img_src = $raw_img;
                        }
                    }
                    ?>

                    <?php if ( ! empty( $acrb_img_src ) ) : ?>
                        <img src="<?php echo esc_url( $acrb_img_src ); ?>" alt="<?php echo esc_attr( $acrb_car_title ); ?>" style="width: 70px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <?php else : ?>
                        <div style="width: 70px; height: 50px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                            <span class="dashicons dashicons-car"></span>
                        </div>
                    <?php endif; ?>

                    <div>
                        <strong style="display: block; font-size: 14px; color: #0f172a;"><?php echo esc_html( $acrb_car_title ); ?></strong>
                        <span style="font-size: 12px; color: #64748b;">
                            <?php
                            /* translators: %d: Associated vehicle ID */
                            printf( esc_html__( 'Vehicle ID: #%d', 'awesome-car-rental' ), absint( $acrb_car_id ) );
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Client & Payment Information -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-admin-users" style="color: #4f46e5;"></span> <?php esc_html_e( 'Client & Settlement', 'awesome-car-rental' ); ?>
                    </h4>
                </div>

                <div class="acrb-data-list" style="display: flex; flex-direction: column; gap: 12px;">
                    <div class="acrb-data-row" style="display: flex; justify-content: space-between; font-size: 13.5px; border-bottom: 1px solid #f8fafc; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Client Name', 'awesome-car-rental' ); ?></span>
                        <strong style="color: #1e293b;"><?php echo esc_html( $acrb_customer_name ); ?></strong>
                    </div>
                    <div class="acrb-data-row" style="display: flex; justify-content: space-between; font-size: 13.5px; border-bottom: 1px solid #f8fafc; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></span>
                        <strong style="color: #1e293b;"><?php echo esc_html( $acrb_customer_email ?: '—' ); ?></strong>
                    </div>
                    <div class="acrb-data-row" style="display: flex; justify-content: space-between; font-size: 13.5px; border-bottom: 1px solid #f8fafc; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></span>
                        <strong style="color: #1e293b;"><?php echo esc_html( $acrb_customer_phone ?: '—' ); ?></strong>
                    </div>
                    <div class="acrb-data-row" style="display: flex; justify-content: space-between; font-size: 13.5px;">
                        <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Settlement Method', 'awesome-car-rental' ); ?></span>
                        <strong style="color: #2563eb;">💳 <?php echo esc_html( $acrb_method ); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Customer Communication -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-share-alt2" style="color: #4f46e5;"></span> <?php esc_html_e( 'Customer Dispatch', 'awesome-car-rental' ); ?>
                    </h4>
                </div>

                <div class="acrb-comms-stack" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if ( ! empty( $acrb_customer_email ) ) : ?>
                        <a href="mailto:<?php echo esc_attr( $acrb_customer_email ); ?>?subject=<?php echo rawurlencode( 'Your Reservation #' . $acrb_booking_code ); ?>" class="acrb-comms-btn" style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #334155; font-weight: 700; font-size: 13px;">
                            <span class="dashicons dashicons-email" style="color: #3b82f6;"></span>
                            <span><?php esc_html_e( 'Send Confirmation Email', 'awesome-car-rental' ); ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ( ! empty( $acrb_phone_digits ) ) : ?>
                        <a href="https://wa.me/<?php echo esc_attr( $acrb_phone_digits ); ?>" target="_blank" rel="noopener noreferrer" class="acrb-comms-btn acrb-btn-wa" style="display: flex; align-items: center; gap: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #047857; font-weight: 700; font-size: 13px;">
                            <span class="dashicons dashicons-whatsapp" style="color: #059669;"></span>
                            <span><?php esc_html_e( 'Message on WhatsApp', 'awesome-car-rental' ); ?></span>
                        </a>
                        <a href="tel:<?php echo esc_attr( $acrb_customer_phone ); ?>" class="acrb-comms-btn" style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #334155; font-weight: 700; font-size: 13px;">
                            <span class="dashicons dashicons-phone" style="color: #3b82f6;"></span>
                            <span><?php esc_html_e( 'Call Directly', 'awesome-car-rental' ); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</div>