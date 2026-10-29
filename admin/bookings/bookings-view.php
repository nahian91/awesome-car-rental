<?php
/**
 * ACRB HIGH-END SAAS BOOKING VIEW (Custom Architecture with Meta Fallback)
 * Location: admin/bookings/acrb-bookings-view.php
 *
 * @package Awesome_Car_Rental
 * @since   1.6.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
$table_cars     = $wpdb->prefix . 'dev_nahian_cars';

// 1. DATA RETRIEVAL
$acrb_booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;

if ( $acrb_booking_id <= 0 ) {
    wp_die( esc_html__( 'Invalid booking ID provided.', 'awesome-car-rental' ) );
}

// Check if booking exists in Custom DB table or as WordPress Post (`acrb_bookings`)
$booking = null;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking = $wpdb->get_row(
    $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $acrb_booking_id ),
    ARRAY_A
);

$is_wp_post = false;
if ( ! $booking && 'acrb_bookings' === get_post_type( $acrb_booking_id ) ) {
    $is_wp_post = true;
    $post_obj   = get_post( $acrb_booking_id );
    $car_id     = absint( get_post_meta( $acrb_booking_id, 'car_id', true ) );
    
    $booking = array(
        'id'             => $acrb_booking_id,
        'booking_code'   => 'ACR-' . $acrb_booking_id,
        'customer_name'  => get_post_meta( $acrb_booking_id, 'customer_name', true ) ?: __( 'Guest Client', 'awesome-car-rental' ),
        'customer_email' => get_post_meta( $acrb_booking_id, 'customer_email', true ) ?: '',
        'customer_phone' => get_post_meta( $acrb_booking_id, 'customer_phone', true ) ?: '',
        'pickup_date'    => get_post_meta( $acrb_booking_id, 'pickup_date', true ) ?: '',
        'return_date'    => get_post_meta( $acrb_booking_id, 'return_date', true ) ?: '',
        'booking_status' => get_post_meta( $acrb_booking_id, 'status', true ) ?: 'pending',
        'total_amount'   => floatval( get_post_meta( $acrb_booking_id, 'total_price', true ) ?: 0 ),
        'car_id'         => $car_id,
        'car_name'       => $car_id ? get_the_title( $car_id ) : __( 'Fleet Vehicle', 'awesome-car-rental' ),
        'created_at'     => $post_obj ? $post_obj->post_date : '',
    );
}

if ( ! $booking ) {
    wp_die( esc_html__( 'Booking record not found in database or post storage.', 'awesome-car-rental' ) );
}

// 2. FETCH RECORD FIELDS
$acrb_booking_code   = $booking['booking_code'] ?? ( 'ACR-' . $acrb_booking_id );
$acrb_customer_name  = $booking['customer_name'] ?? __( 'Guest Client', 'awesome-car-rental' );
$acrb_customer_email = $booking['customer_email'] ?? '';
$acrb_customer_phone = $booking['customer_phone'] ?? '';
$acrb_pickup_date    = $booking['pickup_date'] ?? '';
$acrb_return_date    = $booking['return_date'] ?? '';
$acrb_status         = strtolower( $booking['booking_status'] ?? 'pending' );
$acrb_total          = floatval( $booking['total_amount'] ?? 0 );
$acrb_car_id         = absint( $booking['car_id'] ?? 0 );
$acrb_car_title      = $booking['car_name'] ?? __( 'Fleet Vehicle', 'awesome-car-rental' );
$acrb_created_at     = $booking['created_at'] ?? '';
$acrb_currency       = get_option( 'acrb_currency', '£' );

// Fetch associated vehicle specs if present
$car_meta = null;
if ( $acrb_car_id > 0 ) {
    if ( 'acrb_cars' === get_post_type( $acrb_car_id ) ) {
        $car_meta = array(
            'image'        => get_post_thumbnail_id( $acrb_car_id ),
            'transmission' => get_post_meta( $acrb_car_id, 'acrb_transmission', true ),
            'fuel'         => get_post_meta( $acrb_car_id, 'acrb_fuel_type', true ),
            'plate'        => get_post_meta( $acrb_car_id, 'vin', true ),
        );
    } else {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $car_meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cars} WHERE id = %d", $acrb_car_id ), ARRAY_A );
    }
}

// Compute Duration in Days & Daily Rate
$rental_days = 1;
if ( ! empty( $acrb_pickup_date ) && ! empty( $acrb_return_date ) ) {
    try {
        $start_dt = new DateTime( $acrb_pickup_date );
        $end_dt   = new DateTime( $acrb_return_date );
        $rental_days = max( 1, (int) $start_dt->diff( $end_dt )->days );
    } catch ( Exception $e ) {
        $rental_days = 1;
    }
}
$daily_rate   = $acrb_total > 0 ? ( $acrb_total / $rental_days ) : 0;
$phone_digits = preg_replace( '/[^0-9]/', '', $acrb_customer_phone );
?>

<div class="acrb-dossier-wrap">

    <!-- Summary Hero Banner -->
    <div class="acrb-summary-banner" style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px 28px; margin-bottom: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
        <div class="acrb-client-badge-block" style="display: flex; align-items: center; gap: 16px;">
            <div class="acrb-client-initial" style="width: 54px; height: 54px; background: #eff6ml; background: #eff6ff; color: #2563eb; border-radius: 14px; font-size: 22px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                <?php echo esc_html( strtoupper( mb_substr( trim( $acrb_customer_name ), 0, 1 ) ) ?: 'G' ); ?>
            </div>
            <div>
                <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 800; color: #0f172a;"><?php echo esc_html( $acrb_customer_name ); ?></h3>
                <span class="acrb-code-pill" style="font-family: monospace; font-size: 12px; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; color: #475569; font-weight: 700;"><?php echo esc_html( $acrb_booking_code ); ?></span>
            </div>
        </div>

        <div class="acrb-amount-callout" style="text-align: right;">
            <span style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 2px;"><?php esc_html_e( 'Total Amount', 'awesome-car-rental' ); ?></span>
            <strong style="font-size: 24px; font-weight: 900; color: #0f172a; display: block; margin-bottom: 6px;"><?php echo esc_html( $acrb_currency . number_format( $acrb_total, 2 ) ); ?></strong>
            <span class="acrb-status-badge status-<?php echo esc_attr( $acrb_status ); ?>" style="display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: #fef3c7; color: #d97706;">
                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $acrb_status ) ) ); ?>
            </span>
        </div>
    </div>

    <!-- 2-Column Split Content -->
    <div class="acrb-dossier-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 24px;">

        <!-- Left Side: Route, Customer & Billing -->
        <div class="acrb-dossier-col" style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Route & Schedule -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-location" style="color: #4f46e5;"></span> <?php esc_html_e( 'Rental Route & Schedule', 'awesome-car-rental' ); ?>
                    </h4>
                </div>

                <div class="acrb-route-flow" style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="acrb-flow-step">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e( 'Pick-up', 'awesome-car-rental' ); ?></span>
                        <strong style="font-size: 13px; color: #0f172a; display: block;"><?php echo esc_html( $acrb_pickup_date ?: '—' ); ?></strong>
                    </div>

                    <div class="acrb-route-mid" style="text-align: center;">
                        <span style="background: #eef2ff; color: #4f46e5; padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; display: inline-block;"><?php echo absint( $rental_days ); ?> <?php echo esc_html( _n( 'Day', 'Days', $rental_days, 'awesome-car-rental' ) ); ?></span>
                    </div>

                    <div class="acrb-flow-step" style="text-align: right;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e( 'Return', 'awesome-car-rental' ); ?></span>
                        <strong style="font-size: 13px; color: #0f172a; display: block;"><?php echo esc_html( $acrb_return_date ?: '—' ); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Client & Billing Information -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-admin-users" style="color: #4f46e5;"></span> <?php esc_html_e( 'Client & Billing Details', 'awesome-car-rental' ); ?>
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
                    <div class="acrb-data-row" style="display: flex; justify-content: space-between; font-size: 13.5px; border-bottom: 1px solid #f8fafc; padding-bottom: 8px;">
                        <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Rate Calculation', 'awesome-car-rental' ); ?></span>
                        <strong style="color: #1e293b;"><?php echo esc_html( $acrb_currency . number_format( $daily_rate, 2 ) ); ?> / <?php esc_html_e( 'day', 'awesome-car-rental' ); ?></strong>
                    </div>
                    <div class="acrb-data-row" style="display: flex; justify-content: space-between; font-size: 14px;">
                        <span style="color: #0f172a; font-weight: 800;"><?php esc_html_e( 'Grand Total', 'awesome-car-rental' ); ?></span>
                        <strong style="color: #059669; font-size: 16px;"><?php echo esc_html( $acrb_currency . number_format( $acrb_total, 2 ) ); ?></strong>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side: Assigned Vehicle & Contact Actions -->
        <div class="acrb-dossier-col" style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Assigned Car Card -->
            <div class="acrb-dossier-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <div class="acrb-card-heading" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 18px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-car" style="color: #4f46e5;"></span> <?php esc_html_e( 'Assigned Vehicle', 'awesome-car-rental' ); ?>
                    </h4>
                </div>

                <div class="acrb-vehicle-frame" style="display: flex; align-items: center; gap: 14px; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 16px;">
                    <?php
                    $img_src = '';
                    if ( $car_meta && ! empty( $car_meta['image'] ) ) {
                        $img_src = is_numeric( $car_meta['image'] ) ? wp_get_attachment_url( (int) $car_meta['image'] ) : $car_meta['image'];
                    } elseif ( $acrb_car_id > 0 ) {
                        $img_src = get_the_post_thumbnail_url( $acrb_car_id, 'thumbnail' );
                    }
                    ?>

                    <?php if ( ! empty( $img_src ) ) : ?>
                        <img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $acrb_car_title ); ?>" style="width: 70px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <?php else : ?>
                        <div style="width: 70px; height: 50px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                            <span class="dashicons dashicons-car"></span>
                        </div>
                    <?php endif; ?>

                    <div>
                        <strong style="display: block; font-size: 14px; color: #0f172a;"><?php echo esc_html( $acrb_car_title ); ?></strong>
                        <span style="font-size: 12px; color: #64748b;"><?php printf( esc_html__( 'Vehicle ID: #%d', 'awesome-car-rental' ), absint( $acrb_car_id ) ); ?></span>
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

                    <?php if ( ! empty( $phone_digits ) ) : ?>
                        <a href="https://wa.me/<?php echo esc_attr( $phone_digits ); ?>" target="_blank" rel="noopener noreferrer" class="acrb-comms-btn acrb-btn-wa" style="display: flex; align-items: center; gap: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #047857; font-weight: 700; font-size: 13px;">
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