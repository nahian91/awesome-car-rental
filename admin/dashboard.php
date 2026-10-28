<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dashboard Tab - Awesome Car Rental & Booking
 * Enterprise-grade SaaS Fleet Dashboard.
 */
function acrb_dashboard_tab() {
    // 1. Settings & URLs
    $currency_symbol = get_option( 'acrb_currency', '£' ); 
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );
    
    $plugin_page     = 'awesome_car_rental';
    $bookings_url    = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=bookings' );
    $cars_url        = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=cars' );
    $extras_url      = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=extras' );
    $reports_url     = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=reports' );

    // 2. Fetch Pending Bookings
    $pending_list = get_posts( array(
        'post_type'     => 'acrb_bookings', 
        'numberposts'   => 5,
        'post_status'   => 'publish',
        'no_found_rows' => true,
        'meta_query'    => array(
            'relation' => 'OR',
            array( array( 'key' => 'status', 'value' => 'pending', 'compare' => '=' ) ),
            array( array( 'key' => 'status', 'compare' => 'NOT EXISTS' ) ) 
        )
    ) );

    // 3. Cached Gross Revenue Computation
    $revenue_total = get_transient( 'acrb_dashboard_revenue' );
    if ( false === $revenue_total ) {
        $confirmed_ids = get_posts( array(
            'post_type'     => 'acrb_bookings',
            'numberposts'   => -1,
            'fields'        => 'ids',
            'no_found_rows' => true,
            'meta_key'      => 'status',
            'meta_value'    => 'confirmed'
        ) );
        
        $revenue_total = 0;
        foreach ( $confirmed_ids as $booking_id ) {
            $raw_price     = get_post_meta( $booking_id, 'total_price', true );
            $clean_price   = preg_replace( '/[^\d.]/', '', $raw_price );
            $revenue_total += floatval( $clean_price );
        }
        set_transient( 'acrb_dashboard_revenue', $revenue_total, HOUR_IN_SECONDS );
    }

    $pending_count   = count( $pending_list );
    $cars_count_obj  = wp_count_posts( 'acrb_cars' );
    $total_cars      = isset( $cars_count_obj->publish ) ? (int) $cars_count_obj->publish : 0;
    
    // Quick Helper for Price Display
    $format_price = function( $amount ) use ( $currency_symbol, $currency_pos ) {
        $formatted_number = number_format_i18n( $amount, 2 );
        return ( 'left' === $currency_pos ) 
            ? $currency_symbol . $formatted_number 
            : $formatted_number . $currency_symbol;
    };
    ?>

    <style>
        .acrb-dash-shell {
            max-width: 100%;
            color: #0f172a;
        }

        /* Hero Banner */
        .acrb-hero-panel {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
            border-radius: 18px;
            padding: 34px 38px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.4);
            margin-bottom: 28px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .acrb-hero-panel::after {
            content: '';
            position: absolute;
            top: -100px;
            right: -60px;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(99, 102, 241, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .acrb-hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            font-size: 11.5px;
            font-weight: 600;
            color: #a5b4fc;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .acrb-hero-heading {
            font-size: 26px;
            font-weight: 800;
            margin: 0 0 8px 0;
            letter-spacing: -0.03em;
            color: #ffffff;
            line-height: 1.2;
        }

        .acrb-hero-lead {
            font-size: 14px;
            color: #cbd5e1;
            margin: 0;
            line-height: 1.5;
            max-width: 620px;
        }

        .acrb-hero-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .acrb-btn-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff !important;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            backdrop-filter: blur(6px);
        }

        .acrb-btn-glass:hover {
            background: rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .acrb-btn-accent {
            background: #4f46e5;
            border: 1px solid #6366f1;
            color: #ffffff !important;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }

        .acrb-btn-accent:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
        }

        /* Metric KPI Grid */
        .acrb-kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .acrb-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 22px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .acrb-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px -4px rgba(15, 23, 42, 0.06);
            border-color: #cbd5e1;
        }

        .acrb-kpi-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 6px;
            display: block;
        }

        .acrb-kpi-val {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .acrb-kpi-sub {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
            display: block;
            font-weight: 500;
        }

        .acrb-kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .acrb-kpi-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .kpi-indigo { background: #eef2ff; color: #4f46e5; }
        .kpi-amber  { background: #fffbeb; color: #d97706; }
        .kpi-emerald{ background: #ecfdf5; color: #059669; }

        /* Quick Action Shortcut Strip */
        .acrb-shortcuts-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        .acrb-shortcut-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #334155;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .acrb-shortcut-btn:hover {
            border-color: #4f46e5;
            color: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08);
        }

        .acrb-shortcut-btn .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
            color: #64748b;
        }

        .acrb-shortcut-btn:hover .dashicons {
            color: #4f46e5;
        }

        /* Main Workspace Content Card */
        .acrb-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px 26px;
            box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.04);
        }

        .acrb-panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 18px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 20px;
        }

        .acrb-panel-head h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .acrb-panel-head h3 .dashicons {
            color: #4f46e5;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .acrb-view-all {
            font-size: 13px;
            font-weight: 600;
            color: #4f46e5;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: gap 0.15s ease;
        }

        .acrb-view-all:hover {
            color: #4338ca;
            gap: 7px;
        }

        /* Stack of Pending Requests */
        .acrb-request-feed {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .acrb-request-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .acrb-request-row:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 14px -2px rgba(15, 23, 42, 0.05);
            transform: translateX(2px);
        }

        .acrb-customer-block {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .acrb-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #334155;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #cbd5e1;
            flex-shrink: 0;
        }

        .acrb-customer-info strong {
            display: block;
            font-size: 14px;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .acrb-customer-info span {
            font-size: 12.5px;
            color: #64748b;
        }

        .acrb-meta-badges {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .acrb-chip-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid #fde68a;
        }

        .acrb-cost {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            min-width: 90px;
            text-align: right;
        }

        .acrb-btn-review {
            background: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .acrb-btn-review:hover {
            background: #4338ca;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
        }

        /* Empty State */
        .acrb-empty-state {
            text-align: center;
            padding: 46px 20px;
        }

        .acrb-empty-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #ecfdf5;
            color: #059669;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .acrb-empty-icon .dashicons {
            font-size: 26px;
            width: 26px;
            height: 26px;
        }

        @media(max-width: 1024px) {
            .acrb-kpi-grid { grid-template-columns: 1fr; }
            .acrb-shortcuts-strip { grid-template-columns: repeat(2, 1fr); }
            .acrb-hero-panel { flex-direction: column; align-items: flex-start; gap: 20px; }
        }

        @media(max-width: 680px) {
            .acrb-request-row { flex-direction: column; align-items: flex-start; gap: 14px; }
            .acrb-meta-badges { width: 100%; justify-content: space-between; }
            .acrb-shortcuts-strip { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-shell">
        <!-- Hero Header -->
        <div class="acrb-hero-panel">
            <div>
                <span class="acrb-hero-tag">
                    <span class="dashicons dashicons-shield"></span>
                    <?php esc_html_e( 'Operations Center', 'awesome-car-rental' ); ?>
                </span>
                <h1 class="acrb-hero-heading"><?php esc_html_e( 'Fleet Operations Hub', 'awesome-car-rental' ); ?></h1>
                <p class="acrb-hero-lead">
                    <?php 
                    printf(
                        /* translators: %s: number of pending reservations */
                        esc_html__( 'System operational. There are %s pending reservation requests awaiting dispatch authorization.', 'awesome-car-rental' ),
                        '<strong style="color: #a5b4fc; font-weight: 700;">' . esc_html( $pending_count ) . '</strong>'
                    ); 
                    ?>
                </p>
            </div>
            <div class="acrb-hero-actions">
                <a href="<?php echo esc_url( $cars_url ); ?>" class="acrb-btn-glass">
                    <span class="dashicons dashicons-car"></span> 
                    <span><?php esc_html_e( 'Fleet Catalog', 'awesome-car-rental' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $bookings_url . '&filter=pending' ); ?>" class="acrb-btn-accent">
                    <span class="dashicons dashicons-clock"></span> 
                    <span><?php esc_html_e( 'Review Requests', 'awesome-car-rental' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="acrb-kpi-grid">
            <div class="acrb-kpi-card">
                <div>
                    <span class="acrb-kpi-label"><?php esc_html_e( 'Total Fleet Capacity', 'awesome-car-rental' ); ?></span>
                    <h3 class="acrb-kpi-val"><?php echo esc_html( $total_cars ); ?></h3>
                    <span class="acrb-kpi-sub"><?php esc_html_e( 'Registered Vehicles', 'awesome-car-rental' ); ?></span>
                </div>
                <div class="acrb-kpi-icon kpi-indigo">
                    <span class="dashicons dashicons-car"></span>
                </div>
            </div>

            <div class="acrb-kpi-card">
                <div>
                    <span class="acrb-kpi-label"><?php esc_html_e( 'Action Required', 'awesome-car-rental' ); ?></span>
                    <h3 class="acrb-kpi-val" style="color: #d97706;"><?php echo esc_html( $pending_count ); ?></h3>
                    <span class="acrb-kpi-sub"><?php esc_html_e( 'Pending Authorizations', 'awesome-car-rental' ); ?></span>
                </div>
                <div class="acrb-kpi-icon kpi-amber">
                    <span class="dashicons dashicons-warning"></span>
                </div>
            </div>

            <div class="acrb-kpi-card">
                <div>
                    <span class="acrb-kpi-label"><?php esc_html_e( 'Confirmed Revenue', 'awesome-car-rental' ); ?></span>
                    <h3 class="acrb-kpi-val" style="color: #059669;"><?php echo esc_html( $format_price( $revenue_total ) ); ?></h3>
                    <span class="acrb-kpi-sub"><?php esc_html_e( 'Paid & Confirmed Bookings', 'awesome-car-rental' ); ?></span>
                </div>
                <div class="acrb-kpi-icon kpi-emerald">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
            </div>
        </div>

        <!-- Quick Access Navigation Cards -->
        <div class="acrb-shortcuts-strip">
            <a href="<?php echo esc_url( $cars_url . '&action=add' ); ?>" class="acrb-shortcut-btn">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span><?php esc_html_e( 'Add New Vehicle', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $bookings_url . '&action=new' ); ?>" class="acrb-shortcut-btn">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span><?php esc_html_e( 'Manual Reservation', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $extras_url ); ?>" class="acrb-shortcut-btn">
                <span class="dashicons dashicons-star-empty"></span>
                <span><?php esc_html_e( 'Rental Add-ons', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $reports_url ); ?>" class="acrb-shortcut-btn">
                <span class="dashicons dashicons-chart-pie"></span>
                <span><?php esc_html_e( 'Revenue Reports', 'awesome-car-rental' ); ?></span>
            </a>
        </div>

        <!-- Urgent Requests Card -->
        <div class="acrb-panel">
            <div class="acrb-panel-head">
                <h3>
                    <span class="dashicons dashicons-hourglass"></span>
                    <span><?php esc_html_e( 'Urgent Dispatch Requests', 'awesome-car-rental' ); ?></span>
                </h3>
                <a href="<?php echo esc_url( $bookings_url ); ?>" class="acrb-view-all">
                    <span><?php esc_html_e( 'Open All Bookings', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-right-alt2" style="font-size:16px;"></span>
                </a>
            </div>

            <?php if ( $pending_list ) : ?>
                <div class="acrb-request-feed">
                    <?php foreach ( $pending_list as $booking ) : 
                        $booking_id    = $booking->ID;
                        $customer_name = get_post_meta( $booking_id, 'customer_name', true ) ?: __( 'Guest User', 'awesome-car-rental' );
                        $car_id        = get_post_meta( $booking_id, 'car_id', true );
                        $car_name      = $car_id ? get_the_title( $car_id ) : __( 'General Rental', 'awesome-car-rental' );
                        $raw_price     = get_post_meta( $booking_id, 'total_price', true );
                        $price_val     = floatval( preg_replace( '/[^\d.]/', '', $raw_price ) );
                        $user_initials = strtoupper( substr( sanitize_text_field( $customer_name ), 0, 2 ) );
                        
                        $review_url    = wp_nonce_url( $bookings_url . '&action=edit&booking_id=' . intval( $booking_id ), 'acrb_car_action' );
                    ?>
                        <div class="acrb-request-row">
                            <div class="acrb-customer-block">
                                <div class="acrb-avatar"><?php echo esc_html( $user_initials ); ?></div>
                                <div class="acrb-customer-info">
                                    <strong><?php echo esc_html( $customer_name ); ?></strong>
                                    <span>
                                        <?php 
                                        printf(
                                            /* translators: 1: vehicle title, 2: human readable time ago */
                                            esc_html__( 'Vehicle: %1$s • %2$s ago', 'awesome-car-rental' ),
                                            '<strong style="color: #334155;">' . esc_html( $car_name ) . '</strong>',
                                            esc_html( human_time_diff( get_the_time( 'U', $booking_id ), current_time( 'timestamp' ) ) )
                                        );
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="acrb-meta-badges">
                                <span class="acrb-chip-pending"><?php esc_html_e( 'Pending Approval', 'awesome-car-rental' ); ?></span>
                                <div class="acrb-cost"><?php echo esc_html( $format_price( $price_val ) ); ?></div>
                                <a href="<?php echo esc_url( $review_url ); ?>" class="acrb-btn-review">
                                    <?php esc_html_e( 'Review Request', 'awesome-car-rental' ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="acrb-empty-state">
                    <div class="acrb-empty-icon">
                        <span class="dashicons dashicons-saved"></span>
                    </div>
                    <h4 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #0f172a;"><?php esc_html_e( 'No Pending Authorizations', 'awesome-car-rental' ); ?></h4>
                    <p style="margin: 0; font-size: 13px; color: #64748b;"><?php esc_html_e( 'All reservation requests have been processed and confirmed.', 'awesome-car-rental' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}