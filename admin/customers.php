<?php
/**
 * Customer Directory Dispatcher & Comprehensive Viewer (`customers.php`)
 * Location: admin/customers.php
 *
 * Pulls all customer details, communication endpoints, and rental telemetry from bookings.
 *
 * @package Awesome_Car_Rental
 * @since   2.9.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_customers_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    global $wpdb;

    $table_bookings  = $wpdb->prefix . 'dev_nahian_bookings';
    $currency_symbol = get_option( 'acrb_currency', '$' );
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );

    // 1. Fetch Aggregated Customer Records From Custom DB Table
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $raw_db_customers = $wpdb->get_results(
        "SELECT 
            MAX(id) AS latest_booking_id,
            customer_name,
            customer_email,
            MAX(customer_phone) AS customer_phone,
            MAX(booking_code) AS latest_booking_code,
            MAX(car_name) AS latest_car_name,
            MAX(car_id) AS latest_car_id,
            COUNT(id) AS total_bookings,
            SUM(total_amount) AS total_spent,
            MAX(pickup_date) AS latest_pickup_date,
            MAX(return_date) AS latest_return_date,
            MAX(created_at) AS first_seen_date
        FROM {$table_bookings}
        WHERE (customer_email IS NOT NULL AND customer_email != '')
           OR (customer_name IS NOT NULL AND customer_name != '')
        GROUP BY customer_email, customer_name
        ORDER BY latest_booking_id DESC",
        ARRAY_A
    );

    // 2. Fetch Any Bookings Stored As WordPress Custom Post Type (`acrb_bookings`)
    $post_bookings = get_posts( array(
        'post_type'      => 'acrb_bookings',
        'post_status'    => 'any',
        'posts_per_page' => 200,
        'fields'         => 'ids',
    ) );

    $roster = array();

    if ( ! empty( $raw_db_customers ) ) {
        foreach ( $raw_db_customers as $row ) {
            $email = trim( (string) ( $row['customer_email'] ?? '' ) );
            $name  = trim( (string) ( $row['customer_name'] ?? '' ) );
            $key   = ! empty( $email ) ? strtolower( $email ) : 'name_' . sanitize_title( $name );

            $roster[ $key ] = array(
                'latest_booking_id'   => absint( $row['latest_booking_id'] ),
                'name'                => $name ?: __( 'Guest Client', 'awesome-car-rental' ),
                'email'               => $email,
                'phone'               => trim( (string) ( $row['customer_phone'] ?? '' ) ),
                'latest_booking_code' => trim( (string) ( $row['latest_booking_code'] ?? ( 'ACR-' . $row['latest_booking_id'] ) ) ),
                'latest_car_name'     => trim( (string) ( $row['latest_car_name'] ?? '' ) ),
                'latest_car_id'       => absint( $row['latest_car_id'] ?? 0 ),
                'total_bookings'      => absint( $row['total_bookings'] ),
                'total_spent'         => floatval( $row['total_spent'] ?? 0 ),
                'latest_pickup'       => trim( (string) ( $row['latest_pickup_date'] ?? '' ) ),
                'latest_return'       => trim( (string) ( $row['latest_return_date'] ?? '' ) ),
                'first_seen'          => trim( (string) ( $row['first_seen_date'] ?? '' ) ),
            );
        }
    }

    if ( ! empty( $post_bookings ) ) {
        foreach ( $post_bookings as $post_id ) {
            $p_email = trim( (string) get_post_meta( $post_id, 'customer_email', true ) );
            $p_name  = trim( (string) get_post_meta( $post_id, 'customer_name', true ) );
            $p_phone = trim( (string) get_post_meta( $post_id, 'customer_phone', true ) );
            $p_total = floatval( get_post_meta( $post_id, 'total_price', true ) ?: 0 );
            $p_car   = trim( (string) get_post_meta( $post_id, 'car_name', true ) );

            if ( empty( $p_email ) && empty( $p_name ) ) {
                continue;
            }

            $key = ! empty( $p_email ) ? strtolower( $p_email ) : 'name_' . sanitize_title( $p_name );

            if ( isset( $roster[ $key ] ) ) {
                $roster[ $key ]['total_bookings']++;
                $roster[ $key ]['total_spent'] += $p_total;
                if ( empty( $roster[ $key ]['phone'] ) && ! empty( $p_phone ) ) {
                    $roster[ $key ]['phone'] = $p_phone;
                }
            } else {
                $roster[ $key ] = array(
                    'latest_booking_id'   => $post_id,
                    'name'                => $p_name ?: __( 'Guest Client', 'awesome-car-rental' ),
                    'email'               => $p_email,
                    'phone'               => $p_phone,
                    'latest_booking_code' => 'ACR-' . $post_id,
                    'latest_car_name'     => $p_car ?: __( 'Fleet Unit', 'awesome-car-rental' ),
                    'latest_car_id'       => absint( get_post_meta( $post_id, 'car_id', true ) ),
                    'total_bookings'      => 1,
                    'total_spent'         => $p_total,
                    'latest_pickup'       => (string) get_post_meta( $post_id, 'pickup_date', true ),
                    'latest_return'       => (string) get_post_meta( $post_id, 'return_date', true ),
                    'first_seen'          => get_the_date( 'Y-m-d H:i:s', $post_id ),
                );
            }
        }
    }
    ?>
    <style>
        .acrb-cust-wrapper {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-cust-wrapper *,
        .acrb-cust-wrapper *::before,
        .acrb-cust-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-cust-topbar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .acrb-cust-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-cust-topbar-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            background: #eef2ff;
            color: #4f46e5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.15);
            flex-shrink: 0;
        }
        .acrb-cust-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-cust-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-cust-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Table Card Container */
        .acrb-table-wrap {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            box-sizing: border-box;
        }
        .acrb-cust-table {
            width: 100% !important;
            border-collapse: collapse;
            font-family: inherit;
        }
        .acrb-cust-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 13px 16px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .acrb-cust-table tbody td {
            vertical-align: middle;
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .acrb-cust-table tbody tr:hover {
            background-color: #fcfdfe;
        }

        /* Identity Cell */
        .acrb-cust-meta-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .acrb-cust-avatar-circle {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: #4338ca;
            font-size: 13px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid #c7d2fe;
            flex-shrink: 0;
        }
        .acrb-cust-name-text {
            color: #0f172a;
            display: block;
            line-height: 1.25;
            font-size: 14px;
            font-weight: 700;
        }
        .acrb-cust-order-code {
            font-size: 11px;
            color: #475569;
            background: #f1f5f9;
            padding: 2px 7px;
            border-radius: 5px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            display: inline-block;
            margin-top: 3px;
            border: 1px solid #e2e8f0;
        }

        /* Contact Details */
        .acrb-contact-stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 12.5px;
        }
        .acrb-contact-item {
            color: #334155;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .acrb-contact-item:hover {
            color: #4f46e5;
        }
        .acrb-contact-item .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
            color: #94a3b8;
        }
        .acrb-contact-empty {
            color: #94a3b8;
            font-style: italic;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Rental Vehicle & Telemetry */
        .acrb-car-info-stack {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .acrb-car-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .acrb-car-dates {
            font-size: 11.5px;
            color: #64748b;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Stats & Spend Summary */
        .acrb-stats-stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .acrb-booking-count {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
            padding: 2px 9px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            white-space: nowrap;
            width: fit-content;
        }
        .acrb-spend-val {
            color: #059669;
            font-size: 14px;
            font-weight: 800;
            white-space: nowrap;
            letter-spacing: -0.01em;
        }

        /* Action Buttons */
        .acrb-th-actions {
            text-align: right !important;
            width: 150px;
        }
        .acrb-cell-actions {
            text-align: right !important;
        }
        .acrb-actions-row {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }
        .acrb-btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: #475569;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .acrb-btn-action:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .acrb-btn-action .dashicons {
            font-size: 15px;
            width: 15px;
            height: 15px;
        }
        .acrb-btn-wa {
            color: #059669;
            border-color: #a7f3d0;
            background: #ecfdf5;
        }
        .acrb-btn-wa:hover {
            background: #d1fae5;
            color: #047857;
            border-color: #6ee7b7;
        }
        .acrb-btn-view {
            color: #4f46e5;
            border-color: #c7d2fe;
            background: #eef2ff;
        }
        .acrb-btn-view:hover {
            background: #e0e7ff;
            color: #3730a3;
            border-color: #a5b4fc;
        }

        /* Empty State */
        .acrb-cust-empty {
            text-align: center;
            padding: 48px 20px;
            color: #64748b;
            font-size: 14px;
        }
    </style>

    <div class="acrb-cust-wrapper">
        <header class="acrb-cust-topbar-header">
            <div class="acrb-cust-topbar-title-group">
                <div class="acrb-cust-topbar-icon">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="acrb-cust-topbar-meta">
                    <h1><?php esc_html_e( 'Customer Directory', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Comprehensive client dossiers, rental history, and dispatch channels compiled from all booking records.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <div class="acrb-table-wrap">
            <table class="acrb-datatable acrb-cust-table widefat striped display nowrap">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?php esc_html_e( 'Client / Order', 'awesome-car-rental' ); ?></th>
                        <th style="width: 23%;"><?php esc_html_e( 'Contact Info', 'awesome-car-rental' ); ?></th>
                        <th style="width: 24%;"><?php esc_html_e( 'Recent Vehicle & Dates', 'awesome-car-rental' ); ?></th>
                        <th style="width: 14%;"><?php esc_html_e( 'Rental Summary', 'awesome-car-rental' ); ?></th>
                        <th class="all acrb-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ( ! empty( $roster ) ) :
                        foreach ( $roster as $cust ) :
                            $name         = esc_html( $cust['name'] );
                            $email        = esc_html( $cust['email'] );
                            $phone        = esc_html( $cust['phone'] );
                            $order_code   = esc_html( $cust['latest_booking_code'] );
                            $car_name     = esc_html( $cust['latest_car_name'] ?: __( 'Fleet Unit', 'awesome-car-rental' ) );
                            $rentals      = absint( $cust['total_bookings'] );
                            $raw_spend    = floatval( $cust['total_spent'] );
                            $formatted    = number_format( $raw_spend, 2 );
                            $display_amt  = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
                            
                            $pickup       = ! empty( $cust['latest_pickup'] ) ? esc_html( $cust['latest_pickup'] ) : '';
                            $return       = ! empty( $cust['latest_return'] ) ? esc_html( $cust['latest_return'] ) : '';
                            $phone_digits = preg_replace( '/[^0-9]/', '', $phone );
                            $initials     = strtoupper( mb_substr( trim( $name ), 0, 1 ) ?: 'G' );

                            // Navigation Links
                            $history_url  = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&search=' . rawurlencode( $cust['email'] ?: $cust['name'] ) );
                            $view_url     = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&action=view&booking_id=' . absint( $cust['latest_booking_id'] ) );
                    ?>
                    <tr>
                        <!-- 1. Client Identity & Latest Order Code -->
                        <td>
                            <div class="acrb-cust-meta-cell">
                                <div class="acrb-cust-avatar-circle">
                                    <?php echo esc_html( $initials ); ?>
                                </div>
                                <div>
                                    <strong class="acrb-cust-name-text"><?php echo esc_html( $name ); ?></strong>
                                    <span class="acrb-cust-order-code">#<?php echo esc_html( $order_code ); ?></span>
                                </div>
                            </div>
                        </td>

                        <!-- 2. Contact Channels -->
                        <td>
                            <div class="acrb-contact-stack">
                                <?php if ( ! empty( $email ) ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $email ); ?>" class="acrb-contact-item" title="<?php esc_attr_e( 'Send Email', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-email"></span>
                                        <span><?php echo esc_html( $email ); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span class="acrb-contact-empty">
                                        <span class="dashicons dashicons-email"></span>
                                        <span><?php esc_html_e( 'No email on record', 'awesome-car-rental' ); ?></span>
                                    </span>
                                <?php endif; ?>

                                <?php if ( ! empty( $phone ) ) : ?>
                                    <a href="tel:<?php echo esc_attr( $phone ); ?>" class="acrb-contact-item" title="<?php esc_attr_e( 'Direct Call', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-phone"></span>
                                        <span><?php echo esc_html( $phone ); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span class="acrb-contact-empty">
                                        <span class="dashicons dashicons-phone"></span>
                                        <span><?php esc_html_e( 'No phone on record', 'awesome-car-rental' ); ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 3. Assigned Vehicle & Schedule -->
                        <td>
                            <div class="acrb-car-info-stack">
                                <span class="acrb-car-name"><?php echo esc_html( $car_name ); ?></span>
                                <?php if ( ! empty( $pickup ) || ! empty( $return ) ) : ?>
                                    <span class="acrb-car-dates">
                                        <span class="dashicons dashicons-calendar-alt" style="font-size:13px;width:13px;height:13px;color:#94a3b8;"></span>
                                        <?php echo esc_html( ( $pickup ?: '—' ) . ' → ' . ( $return ?: '—' ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 4. Aggregated Telemetry (Rentals & Lifetime Spend) -->
                        <td data-order="<?php echo esc_attr( (string) $raw_spend ); ?>">
                            <div class="acrb-stats-stack">
                                <span class="acrb-booking-count">
                                    <?php printf( esc_html( _n( '%d Rental', '%d Rentals', $rentals, 'awesome-car-rental' ) ), $rentals ); ?>
                                </span>
                                <strong class="acrb-spend-val"><?php echo esc_html( $display_amt ); ?></strong>
                            </div>
                        </td>

                        <!-- 5. Action Bar (WhatsApp, Call, Email, Order Dossier) -->
                        <td class="acrb-cell-actions">
                            <div class="acrb-actions-row">
                                <?php if ( ! empty( $phone_digits ) ) : ?>
                                    <a href="https://wa.me/<?php echo esc_attr( $phone_digits ); ?>" target="_blank" rel="noopener noreferrer" class="acrb-btn-action acrb-btn-wa" title="<?php esc_attr_e( 'Message on WhatsApp', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-whatsapp"></span>
                                    </a>
                                    <a href="tel:<?php echo esc_attr( $phone ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Call Directly', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-phone"></span>
                                    </a>
                                <?php endif; ?>

                                <?php if ( ! empty( $email ) ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $email ); ?>?subject=<?php echo rawurlencode( 'Your Reservation #' . $order_code ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Send Email', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-email"></span>
                                    </a>
                                <?php endif; ?>

                                <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action acrb-btn-view" title="<?php esc_attr_e( 'View Latest Booking Dossier', 'awesome-car-rental' ); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>

                                <a href="<?php echo esc_url( $history_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'All Customer Orders', 'awesome-car-rental' ); ?>">
                                    <span class="dashicons dashicons-list-view"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else : ?>
                        <tr>
                            <td colspan="5" class="acrb-cust-empty">
                                <?php esc_html_e( 'No customer bookings or client profiles found in the database.', 'awesome-car-rental' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}