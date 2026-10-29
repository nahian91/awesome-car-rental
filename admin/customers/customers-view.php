<?php
/**
 * Customer CRM View Component (`customers-view.php`)
 * Location: admin/customers/customers-view.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$table_customers = $wpdb->prefix . 'dev_nahian_customers';
$table_bookings  = $wpdb->prefix . 'dev_nahian_bookings';
$currency_symbol = get_option( 'acrb_currency', '£' );
$currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$view_id         = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $view_id ), ARRAY_A );

if ( ! $customer ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer profile not found.', 'awesome-car-rental' ) . '</p></div>';
    return;
}

$cust_name    = esc_html( $customer['name'] ?: __( 'Customer', 'awesome-car-rental' ) );
$cust_email   = esc_html( $customer['email'] ?: '—' );
$cust_phone   = esc_html( $customer['phone'] ?: '—' );
$cust_address = esc_html( $customer['address'] ?: '—' );
$cust_date    = ! empty( $customer['created_at'] ) ? date_i18n( 'M Y', strtotime( $customer['created_at'] ) ) : '—';
$initials     = strtoupper( mb_substr( trim( $cust_name ), 0, 2 ) ?: 'CU' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$orders = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table_bookings} WHERE customer_email = %s OR customer_name = %s ORDER BY id DESC",
        $customer['email'] ?? '',
        $customer['name'] ?? ''
    ),
    ARRAY_A
);
?>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start;">
    <!-- Customer Profile Dossier Card -->
    <div class="acrb-card" style="text-align: center;">
        <div style="width: 72px; height: 72px; background: #eef2ff; color: #4f46e5; font-size: 24px; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; border: 2px solid #e0e7ff;">
            <?php echo esc_html( $initials ); ?>
        </div>
        <h3 style="margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $cust_name ); ?></h3>
        <span style="background: #eff6ff; color: #4f46e5; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
            <?php esc_html_e( 'Registered Client', 'awesome-car-rental' ); ?>
        </span>

        <div style="margin-top: 20px; text-align: left; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Email', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; word-break: break-all;"><?php echo esc_html( $cust_email ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $cust_phone ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Address', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; text-align: right;"><?php echo esc_html( $cust_address ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Joined', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $cust_date ); ?></strong>
            </div>
        </div>
    </div>

    <!-- Booking History Table -->
    <div class="acrb-table-wrap">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: #0f172a; font-size: 14.5px;">
            <?php esc_html_e( 'Booking History', 'awesome-car-rental' ); ?>
        </div>
        <table class="acrb-datatable widefat striped display nowrap" style="width: 100%;">
            <thead>
                <tr>
                    <th width="120"><?php esc_html_e( 'Order ID', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Rental Dates', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Total Amount', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                    <th class="all" width="70" style="text-align: right;" data-orderable="false"><?php esc_html_e( 'Action', 'awesome-car-rental' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ( ! empty( $orders ) ) : 
                    foreach ( $orders as $order ) : 
                        $o_id       = absint( $order['id'] );
                        $code       = esc_html( $order['booking_code'] ?: 'ACR-' . $o_id );
                        $status     = sanitize_key( $order['booking_status'] ?? 'pending' );
                        $raw_price  = floatval( $order['total_amount'] ?? 0 );
                        $car_name   = esc_html( $order['car_name'] ?: __( 'Fleet Unit', 'awesome-car-rental' ) );
                        $pickup     = esc_html( $order['pickup_date'] ?: '—' );
                        $return     = esc_html( $order['return_date'] ?: '—' );

                        $formatted_price = number_format( $raw_price, 2 );
                        $display_price   = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted_price : $formatted_price . $currency_symbol;
                        $edit_url        = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&action=edit&booking_id=' . $o_id );

                        // Status pill styling
                        $is_positive = in_array( $status, array( 'confirmed', 'completed', 'active' ), true );
                        $badge_bg    = $is_positive ? '#ecfdf5' : '#fee2e2';
                        $badge_color = $is_positive ? '#059669' : '#b91c1c';
                        if ( 'pending' === $status ) {
                            $badge_bg    = '#fef9c3';
                            $badge_color = '#a16207';
                        }
                ?>
                <tr>
                    <td><code style="font-family: monospace; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">#<?php echo esc_html( $code ); ?></code></td>
                    <td><strong style="color: #0f172a;"><?php echo esc_html( $car_name ); ?></strong></td>
                    <td style="font-size: 12.5px; color: #64748b;"><?php echo esc_html( $pickup . ' → ' . $return ); ?></td>
                    <td data-order="<?php echo esc_attr( (string) $raw_price ); ?>">
                        <strong style="color: #059669; font-size: 13.5px; white-space: nowrap;">
                            <?php echo esc_html( $display_price ); ?>
                        </strong>
                    </td>
                    <td>
                        <span style="background: <?php echo esc_attr( $badge_bg ); ?>; color: <?php echo esc_attr( $badge_color ); ?>; padding: 3px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; display: inline-block; white-space: nowrap;">
                            ● <?php echo esc_html( $status ); ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div class="acrb-actions-group">
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit Booking', 'awesome-car-rental' ); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                            <?php esc_html_e( 'No rental bookings recorded for this client.', 'awesome-car-rental' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>