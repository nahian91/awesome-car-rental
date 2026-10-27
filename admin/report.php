<?php
/**
 * Reports Tab - Analytics & Revenue Data (SaaS Modernized Header)
 * Location: admin/reports.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_reports_tab() {
    // 1. DYNAMIC SETTINGS & FILTERS
    $currency_symbol = get_option( 'acrb_currency', '£' );
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );
    
    // Check Nonce for Security
    if ( isset( $_GET['fd_from'] ) && ( ! isset( $_REQUEST['acrb_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['acrb_nonce'] ) ), 'acrb_filter_action' ) ) ) {
        wp_die( esc_html__( 'Security check failed. Please refresh the page.', 'awesome-car-rental' ) );
    }

    // Unslash and Sanitize input, then fall back to gmdate()
    $filter_from = isset( $_GET['fd_from'] ) ? sanitize_text_field( wp_unslash( $_GET['fd_from'] ) ) : gmdate( 'Y-m-01' );
    $filter_to   = isset( $_GET['fd_to'] )   ? sanitize_text_field( wp_unslash( $_GET['fd_to'] ) )   : gmdate( 'Y-m-d' );

    // 2. DATA FETCHING (Strictly Confirmed)
    $args = array(
        'post_type'   => 'acrb_bookings',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query'  => array(
            array( 'key' => 'status', 'value' => 'confirmed', 'compare' => '=' )
        ),
        'date_query'  => array( array( 'after' => $filter_from, 'before' => $filter_to, 'inclusive' => true ) ),
    );

    $confirmed_bookings = get_posts( $args );

    // 3. AGGREGATION LOGIC
    $chart_data       = array();
    $total_rev        = 0;
    $day_distribution = array( 'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0 );

    foreach ( $confirmed_bookings as $o ) {
        $price = floatval( get_post_meta( $o->ID, 'total_price', true ) );
        $date  = get_the_date( 'Y-m-d', $o->ID );
        $day   = get_the_date( 'D', $o->ID );
        
        $total_rev += $price;
        $chart_data[ $date ] = ( $chart_data[ $date ] ?? 0 ) + $price;
        if ( isset( $day_distribution[ $day ] ) ) {
            $day_distribution[ $day ] += $price;
        }
    }
    ksort( $chart_data ); 
    
    // Helper for currency display
    $format_price = function( $amount ) use ( $currency_symbol, $currency_pos ) {
        $formatted = number_format( $amount, 2 );
        return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
    };
    ?>

    <style>
        /* Modern SaaS Layout Theme */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-dash-wrap *, .acrb-dash-wrap *::before, .acrb-dash-wrap *::after { box-sizing: border-box; }
        
        /* Ultra-Premium Top Header Bar */
        .acrb-header-flex { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); 
            border: 1px solid #e2e8f0; 
            border-top: 3px solid #3b82f6;
            border-radius: 14px; 
            padding: 24px 32px; 
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); 
            margin-bottom: 24px; 
            position: relative;
        }
        .acrb-title-wrapper { display: flex; align-items: center; gap: 18px; width: 100%; }
        .acrb-title-icon { width: 48px; height: 48px; min-width: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
        .acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-title-content { display: flex; flex-direction: column; gap: 4px; }
        .acrb-main-title { font-size: 22px; font-weight: 700; margin: 0; color: #0f172a; letter-spacing: -0.5px; line-height: 1.2; }
        .acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; line-height: 1.4; }
        
        /* Header Actions & Print Button */
        .acrb-header-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .acrb-header-flex .button { 
            background: #ffffff; 
            color: #334155; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 10px 18px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.25s ease; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            height: 38px;
            font-size: 13px;
            cursor: pointer;
        }
        .acrb-header-flex .button .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-header-flex .button:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        /* KPI Stats Grid */
        .acrb-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
        .acrb-stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-stat-label { display: block; color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .acrb-stat-val { display: block; font-size: 26px; font-weight: 700; margin-top: 6px; letter-spacing: -0.5px; color: #0f172a; }
        .acrb-stat-success .acrb-stat-val { color: #15803d; }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-header-actions { width: 100%; justify-content: flex-end; }
            .acrb-stats-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-chart-area"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Revenue Intelligence', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title">
                        <?php 
                        printf(
                            /* translators: 1: Start date, 2: End date */
                            esc_html__( 'Showing results from %1$s to %2$s', 'awesome-car-rental' ),
                            esc_html( $filter_from ),
                            esc_html( $filter_to )
                        ); 
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="acrb-header-actions">
                <button onclick="window.print()" class="button">
                    <span class="dashicons dashicons-printer" style="font-size:14px; width:14px; height:14px;"></span> 
                    <span><?php esc_html_e( 'Print Report', 'awesome-car-rental' ); ?></span>
                </button>
            </div>
        </header>

        <div class="acrb-stats-grid">
            <div class="acrb-stat-card acrb-stat-success">
                <span class="acrb-stat-label"><?php esc_html_e( 'Confirmed Revenue', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-val"><?php echo esc_html( $format_price( $total_rev ) ); ?></span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e( 'Confirmed Bookings', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-val"><?php echo absint( count( $confirmed_bookings ) ); ?></span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e( 'Avg. Booking Value', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-val">
                    <?php 
                    $avg = ( count( $confirmed_bookings ) > 0 ) ? ( $total_rev / count( $confirmed_bookings ) ) : 0;
                    echo esc_html( $format_price( $avg ) ); 
                    ?>
                </span>
            </div>
        </div>

        <form method="get" class="acrb-rep-filters" style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:24px; margin-bottom:24px; box-shadow:0 2px 4px rgba(0,0,0,0.01);">
            <?php wp_nonce_field( 'acrb_filter_action', 'acrb_nonce' ); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'awesome_car_rental' ); ?>">
            <input type="hidden" name="tab" value="reports">
            <div class="acrb-filter-group">
                <label class="acrb-label" style="display:block; font-weight:600; margin-bottom:8px; font-size:13px; color:#334155;"><?php esc_html_e( 'Select Date Range', 'awesome-car-rental' ); ?></label>
                <div class="acrb-filter-inputs" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <input type="date" name="fd_from" value="<?php echo esc_attr( $filter_from ); ?>" class="acrb-input" style="height:38px; border:1px solid #cbd5e1; border-radius:8px; padding:0 12px; font-size:13px;">
                    <input type="date" name="fd_to" value="<?php echo esc_attr( $filter_to ); ?>" class="acrb-input" style="height:38px; border:1px solid #cbd5e1; border-radius:8px; padding:0 12px; font-size:13px;">
                    <button type="submit" class="button button-primary" style="background:#3b82f6; color:#fff; border-color:#3b82f6; height:38px; padding:0 20px; border-radius:8px; font-weight:600; box-shadow:0 4px 12px rgba(59, 130, 246, 0.35);"><?php esc_html_e( 'Filter Analytics', 'awesome-car-rental' ); ?></button>
                </div>
            </div>
        </form>

        <div class="acrb-charts-grid" style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-bottom:24px;">
            <div class="acrb-chart-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:24px; box-shadow:0 2px 4px rgba(0,0,0,0.01);">
                <h3 style="font-size:15px; font-weight:600; margin:0 0 16px 0; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Revenue Growth (Confirmed)', 'awesome-car-rental' ); ?></h3>
                <canvas id="acrbRevenueChart" height="110"></canvas>
            </div>
            <div class="acrb-chart-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:24px; box-shadow:0 2px 4px rgba(0,0,0,0.01);">
                <h3 style="font-size:15px; font-weight:600; margin:0 0 16px 0; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Daily Performance', 'awesome-car-rental' ); ?></h3>
                <canvas id="acrbDayChart" height="230"></canvas>
            </div>
        </div>

        <div class="acrb-table-wrap">
            <div style="padding: 20px 24px 0; background:#fff;">
                <h3 style="margin:0; border-bottom:1px solid #f1f5f9; padding-bottom:14px; font-size:15px; font-weight:600; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Confirmed Transaction Log', 'awesome-car-rental' ); ?></h3>
            </div>
            <table class="widefat striped" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Order ID', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Client', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Revenue', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Completed Date', 'awesome-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $confirmed_bookings ) : foreach ( $confirmed_bookings as $o ) : 
                        $car_id    = get_post_meta( $o->ID, 'car_id', true );
                        $row_price = floatval( get_post_meta( $o->ID, 'total_price', true ) );
                    ?>
                    <tr>
                        <td><code style="background:#f1f5f9; padding:4px 8px; font-size:13px; font-weight:bold; color:#0f172a; border-radius:4px;">#<?php echo absint( $o->ID ); ?></code></td>
                        <td><strong><?php echo esc_html( get_post_meta( $o->ID, 'customer_name', true ) ); ?></strong></td>
                        <td><?php echo esc_html( get_the_title( $car_id ) ); ?></td>
                        <td style="font-weight:600; color:#15803d;"><?php echo esc_html( $format_price( $row_price ) ); ?></td>
                        <td><?php echo esc_html( get_the_date( 'M j, Y', $o->ID ) ); ?></td>
                    </tr>
                    <?php endforeach; else : ?>
                    <tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No confirmed revenue found for this date range.', 'awesome-car-rental' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}