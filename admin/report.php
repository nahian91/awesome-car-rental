<?php
/**
 * Reports Tab - Analytics & Revenue Data (SaaS Modernized)
 * Location: admin/reports.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_reports_tab() {
    global $wpdb;

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $table_bookings = $wpdb->prefix . 'dev_nahian_bookings';

    // 1. DYNAMIC SETTINGS & FILTERS
    $currency_symbol = get_option( 'acrb_currency', '£' );
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );

    // Check Nonce for Security on Filter
    if ( isset( $_GET['fd_from'] ) && ( ! isset( $_REQUEST['acrb_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['acrb_nonce'] ) ), 'acrb_filter_action' ) ) ) {
        wp_die( esc_html__( 'Security check failed. Please refresh the page.', 'awesome-car-rental' ) );
    }

    $filter_from = isset( $_GET['fd_from'] ) ? sanitize_text_field( wp_unslash( $_GET['fd_from'] ) ) : gmdate( 'Y-m-01' );
    $filter_to   = isset( $_GET['fd_to'] )   ? sanitize_text_field( wp_unslash( $_GET['fd_to'] ) )   : gmdate( 'Y-m-d' );

    // 2. DATA FETCHING (From Custom Database Table)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $confirmed_bookings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table_bookings} 
             WHERE booking_status IN ('confirmed', 'active', 'completed')
             AND DATE(pickup_date) >= %s 
             AND DATE(pickup_date) <= %s 
             ORDER BY pickup_date ASC",
            $filter_from,
            $filter_to
        ),
        ARRAY_A
    );

    // 3. AGGREGATION LOGIC
    $chart_data       = array();
    $total_rev        = 0.00;
    $day_distribution = array( 'Mon' => 0.00, 'Tue' => 0.00, 'Wed' => 0.00, 'Thu' => 0.00, 'Fri' => 0.00, 'Sat' => 0.00, 'Sun' => 0.00 );

    if ( ! empty( $confirmed_bookings ) ) {
        foreach ( $confirmed_bookings as $b ) {
            $price = floatval( $b['total_amount'] ?? 0 );
            $date  = ! empty( $b['pickup_date'] ) ? gmdate( 'Y-m-d', strtotime( $b['pickup_date'] ) ) : gmdate( 'Y-m-d' );
            $day   = ! empty( $b['pickup_date'] ) ? gmdate( 'D', strtotime( $b['pickup_date'] ) ) : gmdate( 'D' );

            $total_rev += $price;
            $chart_data[ $date ] = ( $chart_data[ $date ] ?? 0.00 ) + $price;

            if ( isset( $day_distribution[ $day ] ) ) {
                $day_distribution[ $day ] += $price;
            }
        }
    }
    ksort( $chart_data );

    $booking_count = count( $confirmed_bookings );
    $avg_booking   = $booking_count > 0 ? ( $total_rev / $booking_count ) : 0.00;

    $format_price = function( float $amount ) use ( $currency_symbol, $currency_pos ): string {
        $formatted = number_format( $amount, 2 );
        return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
    };
    ?>

    <div class="acrb-dash-wrap">
        
        <!-- Header -->
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-chart-area"></span>
                </div>
                <div class="acrb-title-content">
                    <h1><?php esc_html_e( 'Revenue Intelligence', 'awesome-car-rental' ); ?></h1>
                    <p>
                        <?php 
                        printf(
                            /* translators: 1: Start date, 2: End date */
                            esc_html__( 'Showing performance analytics from %1$s to %2$s', 'awesome-car-rental' ),
                            esc_html( $filter_from ),
                            esc_html( $filter_to )
                        ); 
                        ?>
                    </p>
                </div>
            </div>
        </header>

        <!-- KPI Grid -->
        <div class="acrb-stats-grid">
            <div class="acrb-stat-card acrb-stat-success">
                <span class="acrb-stat-label"><?php esc_html_e( 'Settled Revenue', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-val"><?php echo esc_html( $format_price( $total_rev ) ); ?></span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e( 'Bookings Closed', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-val"><?php echo absint( $booking_count ); ?></span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e( 'Avg. Booking Value', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-val"><?php echo esc_html( $format_price( $avg_booking ) ); ?></span>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="get" class="acrb-rep-filters">
            <?php wp_nonce_field( 'acrb_filter_action', 'acrb_nonce' ); ?>
            <input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'awesome_car_rental' ); ?>">
            <input type="hidden" name="tab" value="reports">
            
            <div style="font-weight:700; font-size:13.5px; color:#0f172a;">
                <?php esc_html_e( 'Filter Date Interval', 'awesome-car-rental' ); ?>
            </div>

            <div class="acrb-filter-inputs">
                <input type="date" name="fd_from" value="<?php echo esc_attr( $filter_from ); ?>" class="acrb-input-date">
                <span style="color:#64748b; font-weight:600; font-size:12.5px;"><?php esc_html_e( 'to', 'awesome-car-rental' ); ?></span>
                <input type="date" name="fd_to" value="<?php echo esc_attr( $filter_to ); ?>" class="acrb-input-date">
                <button type="submit" class="acrb-btn-filter"><?php esc_html_e( 'Apply Filter', 'awesome-car-rental' ); ?></button>
            </div>
        </form>

        <!-- Charts Grid -->
        <div class="acrb-charts-grid">
            <div class="acrb-chart-card">
                <h3><span class="dashicons dashicons-chart-line" style="color:#4f46e5;"></span> <?php esc_html_e( 'Revenue Trajectory', 'awesome-car-rental' ); ?></h3>
                <canvas id="acrbRevenueChart" height="110"></canvas>
            </div>
            <div class="acrb-chart-card">
                <h3><span class="dashicons dashicons-calendar-alt" style="color:#4f46e5;"></span> <?php esc_html_e( 'Day Distribution', 'awesome-car-rental' ); ?></h3>
                <canvas id="acrbDayChart" height="220"></canvas>
            </div>
        </div>

        <!-- Transaction Log -->
        <div class="acrb-table-wrap">
            <div class="acrb-table-header">
                <h3><?php esc_html_e( 'Settled Transactions', 'awesome-car-rental' ); ?></h3>
            </div>
            <table class="acrb-rep-table">
                <thead>
                    <tr>
                        <th width="120"><?php esc_html_e( 'Booking ID', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Client', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Rental Dates', 'awesome-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $confirmed_bookings ) ) : foreach ( $confirmed_bookings as $b ) : 
                        $b_id      = absint( $b['id'] );
                        $code      = esc_html( $b['booking_code'] ?: 'ACR-' . $b_id );
                        $row_price = floatval( $b['total_amount'] ?? 0 );
                        $client    = esc_html( $b['customer_name'] ?: __( 'Guest Client', 'awesome-car-rental' ) );
                        $vehicle   = esc_html( $b['car_name'] ?: __( 'Fleet Unit', 'awesome-car-rental' ) );
                        $p_date    = esc_html( $b['pickup_date'] ?: '—' );
                        $r_date    = esc_html( $b['return_date'] ?: '—' );
                    ?>
                        <tr>
                            <td><span class="acrb-id-pill">#<?php echo esc_html( $code ); ?></span></td>
                            <td><strong><?php echo esc_html( $client ); ?></strong></td>
                            <td><?php echo esc_html( $vehicle ); ?></td>
                            <td><strong style="color:#059669;"><?php echo esc_html( $format_price( $row_price ) ); ?></strong></td>
                            <td style="font-size:12.5px; color:#64748b;"><?php echo esc_html( $p_date . ' → ' . $r_date ); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:36px; color:#64748b;">
                                <?php esc_html_e( 'No revenue transactions recorded within this date range.', 'awesome-car-rental' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Chart.js Engine -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Line Chart: Revenue Curve
        var revCtx = document.getElementById('acrbRevenueChart');
        if (revCtx) {
            new Chart(revCtx, {
                type: 'line',
                data: {
                    labels: <?php echo wp_json_encode( array_keys( $chart_data ) ); ?>,
                    datasets: [{
                        label: '<?php echo esc_js( sprintf( __( 'Revenue (%s)', 'awesome-car-rental' ), $currency_symbol ) ); ?>',
                        data: <?php echo wp_json_encode( array_values( $chart_data ) ); ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#4f46e5',
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 11 } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // Bar Chart: Day Distribution
        var dayCtx = document.getElementById('acrbDayChart');
        if (dayCtx) {
            new Chart(dayCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo wp_json_encode( array_keys( $day_distribution ) ); ?>,
                    datasets: [{
                        data: <?php echo wp_json_encode( array_values( $day_distribution ) ); ?>,
                        backgroundColor: '#6366f1',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 10 } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php
}