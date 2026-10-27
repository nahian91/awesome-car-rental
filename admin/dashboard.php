<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dashboard Tab - Awesome Car Rental & Booking
 * Ultra-smooth SaaS design with perfectly aligned button icons.
 */
function acrb_dashboard_tab() {
    // 1. Settings & URLs
    $currency_symbol = get_option( 'acrb_currency', '£' ); 
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );
    
    $plugin_page     = 'awesome_car_rental';
    $bookings_url    = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=bookings' );
    $cars_url        = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=cars' );

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

    // 3. Transient Caching for Gross Revenue
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

    $pending_count = count( $pending_list );
    $total_cars    = wp_count_posts( 'acrb_cars' )->publish;

    // Helper function for clean currency formatting
    $format_price = function( $amount ) use ( $currency_symbol, $currency_pos ) {
        $formatted_number = number_format_i18n( $amount, 2 );
        return ( 'left' === $currency_pos ) 
            ? $currency_symbol . $formatted_number 
            : $formatted_number . $currency_symbol;
    };
    ?>

    <style>
        /* Smooth SaaS Dashboard Theme */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 20px 20px 0 0; }
        
        /* Hero Banner */
        .acrb-hero-box { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; padding: 36px; color: #fff; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.3); margin-bottom: 28px; position: relative; overflow: hidden; }
        .acrb-hero-box::before { content: ""; position: absolute; top: -80px; right: -80px; width: 250px; height: 250px; background: rgba(56, 189, 248, 0.08); border-radius: 50%; pointer-events: none; }
        .acrb-hero-title { font-size: 26px; font-weight: 700; margin: 0 0 8px 0; color: #fff; letter-spacing: -0.5px; }
        .acrb-hero-text { font-size: 14px; margin: 0; color: #94a3b8; line-height: 1.5; }
        .acrb-hero-btns { display: flex; gap: 12px; }
        
        /* Properly Aligned Buttons */
        .acrb-hero-btns .button { 
            background: rgba(255, 255, 255, 0.08); 
            color: #fff; 
            border: 1px solid rgba(255, 255, 255, 0.15); 
            border-radius: 8px; 
            padding: 10px 18px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            line-height: 1.2;
            transition: all 0.25s ease; 
            backdrop-filter: blur(4px); 
        }
        .acrb-hero-btns .button .dashicons { 
            font-size: 18px; 
            width: 18px; 
            height: 18px; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .acrb-hero-btns .button:hover { background: rgba(255, 255, 255, 0.18); color: #fff; border-color: rgba(255, 255, 255, 0.3); transform: translateY(-1px); }
        .acrb-hero-btns .button-primary { background: #3b82f6; border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); }
        .acrb-hero-btns .button-primary:hover { background: #2563eb; }

        /* Stats Grid */
        .acrb-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .acrb-card-stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); display: flex; justify-content: space-between; align-items: center; transition: all 0.25s ease; }
        .acrb-card-stat:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06); border-color: #cbd5e1; }
        .acrb-stat-info span { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 8px; }
        .acrb-stat-info h3 { font-size: 30px; font-weight: 700; margin: 0; color: #0f172a; letter-spacing: -1px; }
        .acrb-stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-amber { background: #fffbeb; color: #f59e0b; }
        .icon-emerald { background: #f0fdf4; color: #10b981; }

        /* Content Section Card */
        .acrb-main-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }
        .acrb-card-header h3 { font-size: 16px; font-weight: 600; margin: 0; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .acrb-card-header h3 .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Pending Items Rows */
        .acrb-list-stack { display: flex; flex-direction: column; gap: 14px; }
        .acrb-list-item { display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; transition: all 0.2s ease; }
        .acrb-list-item:hover { background: #fff; border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        
        .acrb-user-profile { display: flex; align-items: center; gap: 14px; }
        .acrb-user-avatar { width: 42px; height: 42px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #475569; }
        .acrb-user-data strong { display: block; font-size: 14px; color: #0f172a; margin-bottom: 2px; }
        .acrb-user-data span { font-size: 12px; color: #64748b; }
        
        .acrb-badge-pending { background: #fef3c7; color: #d97706; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .acrb-price-tag { font-weight: 700; font-size: 16px; color: #0f172a; }
        
        .acrb-action-btn { background: #3b82f6; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .acrb-action-btn:hover { background: #2563eb; color: #fff; }

        /* Empty State */
        .acrb-empty-state { text-align: center; padding: 50px 20px; color: #64748b; }
        .acrb-empty-state .dashicons { font-size: 48px; width: 48px; height: 48px; color: #10b981; margin-bottom: 12px; }

        @media(max-width: 900px) {
            .acrb-hero-box { flex-direction: column; align-items: flex-start; gap: 24px; }
            .acrb-stats-row { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <!-- Hero Header -->
        <div class="acrb-hero-box">
            <div>
                <h1 class="acrb-hero-title"><?php esc_html_e( 'Fleet Operations Hub', 'awesome-car-rental' ); ?></h1>
                <p class="acrb-hero-text">
                    <?php 
                    printf(
                        /* translators: %s: number of pending reservations */
                        esc_html__( 'Welcome back. You have %s pending reservation requests waiting for approval.', 'awesome-car-rental' ),
                        '<strong style="color: #38bdf8;">' . esc_html( $pending_count ) . '</strong>'
                    ); 
                    ?>
                </p>
            </div>
            <div class="acrb-hero-btns">
                <a href="<?php echo esc_url( $cars_url ); ?>" class="button">
                    <span class="dashicons dashicons-car"></span> 
                    <span><?php esc_html_e( 'Fleet Inventory', 'awesome-car-rental' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $bookings_url . '&filter=pending' ); ?>" class="button button-primary">
                    <span class="dashicons dashicons-clock"></span> 
                    <span><?php esc_html_e( 'Review Pending', 'awesome-car-rental' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Stats Cards Row -->
        <div class="acrb-stats-row">
            <div class="acrb-card-stat">
                <div class="acrb-stat-info">
                    <span><?php esc_html_e( 'Active Fleet', 'awesome-car-rental' ); ?></span>
                    <h3><?php echo esc_html( $total_cars ); ?> <small style="font-size:14px; font-weight:normal; color:#64748b;"><?php esc_html_e( 'Cars', 'awesome-car-rental' ); ?></small></h3>
                </div>
                <div class="acrb-stat-icon icon-blue">
                    <span class="dashicons dashicons-car"></span>
                </div>
            </div>
            
            <div class="acrb-card-stat">
                <div class="acrb-stat-info">
                    <span><?php esc_html_e( 'Pending Requests', 'awesome-car-rental' ); ?></span>
                    <h3 style="color: #d97706;"><?php echo esc_html( $pending_count ); ?> <small style="font-size:14px; font-weight:normal; color:#64748b;"><?php esc_html_e( 'Requests', 'awesome-car-rental' ); ?></small></h3>
                </div>
                <div class="acrb-stat-icon icon-amber">
                    <span class="dashicons dashicons-warning"></span>
                </div>
            </div>

            <div class="acrb-card-stat">
                <div class="acrb-stat-info">
                    <span><?php esc_html_e( 'Total Revenue', 'awesome-car-rental' ); ?></span>
                    <h3 style="color: #10b981;"><?php echo esc_html( $format_price( $revenue_total ) ); ?></h3>
                </div>
                <div class="acrb-stat-icon icon-emerald">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
            </div>
        </div>

        <!-- Pending Requests Section -->
        <div class="acrb-main-card">
            <div class="acrb-card-header">
                <h3>
                    <span class="dashicons dashicons-schedule" style="color:#3b82f6;"></span> 
                    <span><?php esc_html_e( 'Urgent Reservation Requests', 'awesome-car-rental' ); ?></span>
                </h3>
                <a href="<?php echo esc_url( $bookings_url ); ?>" style="font-size:13px; font-weight:600; text-decoration:none; color:#3b82f6;"><?php esc_html_e( 'View All Bookings →', 'awesome-car-rental' ); ?></a>
            </div>

            <?php if ( $pending_list ) : ?>
                <div class="acrb-list-stack">
                    <?php foreach ( $pending_list as $booking ) : 
                        $booking_id    = $booking->ID;
                        $customer_name = get_post_meta( $booking_id, 'customer_name', true ) ?: __( 'Guest User', 'awesome-car-rental' );
                        $car_id        = get_post_meta( $booking_id, 'car_id', true );
                        $car_name      = $car_id ? get_the_title( $car_id ) : __( 'General Rental', 'awesome-car-rental' );
                        $raw_price     = get_post_meta( $booking_id, 'total_price', true );
                        $price_val     = floatval( preg_replace( '/[^\d.]/', '', $raw_price ) );
                        $user_initials = strtoupper( substr( $customer_name, 0, 2 ) );
                        
                        $review_url    = wp_nonce_url( $bookings_url . '&action=edit&booking_id=' . intval( $booking_id ), 'acrb_car_action' );
                    ?>
                        <div class="acrb-list-item">
                            <div class="acrb-user-profile">
                                <div class="acrb-user-avatar"><?php echo esc_html( $user_initials ); ?></div>
                                <div class="acrb-user-data">
                                    <strong><?php echo esc_html( $customer_name ); ?></strong>
                                    <span>
                                        <?php 
                                        printf(
                                            /* translators: 1: vehicle title, 2: time ago */
                                            esc_html__( 'Car: %1$s • %2$s ago', 'awesome-car-rental' ),
                                            '<strong>' . esc_html( $car_name ) . '</strong>',
                                            esc_html( human_time_diff( get_the_time( 'U', $booking_id ), current_time( 'timestamp' ) ) )
                                        );
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div style="display:flex; align-items:center; gap:24px;">
                                <span class="acrb-badge-pending"><?php esc_html_e( 'Pending', 'awesome-car-rental' ); ?></span>
                                <div class="acrb-price-tag"><?php echo esc_html( $format_price( $price_val ) ); ?></div>
                                <a href="<?php echo esc_url( $review_url ); ?>" class="acrb-action-btn">
                                    <?php esc_html_e( 'Review Request', 'awesome-car-rental' ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="acrb-empty-state">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p style="font-weight:600; color:#0f172a; margin:0 0 4px; font-size:16px;"><?php esc_html_e( 'All Caught Up!', 'awesome-car-rental' ); ?></p>
                    <span style="font-size:13px; color:#64748b;"><?php esc_html_e( 'There are currently no pending reservation requests waiting for approval.', 'awesome-car-rental' ); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}