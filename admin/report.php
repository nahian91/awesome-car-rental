<?php
if (!defined('ABSPATH')) exit;

/**
 * Reports Tab - Analytics & Revenue Data
 */
function acrb_reports_tab() {
    // 1. DYNAMIC SETTINGS & FILTERS
    $currency_symbol = get_option('acrb_currency', '£');
    $currency_pos    = get_option('acrb_currency_pos', 'left');
    
    // 1. Check Nonce for Security (Recommended by WPCS)
// If the form was submitted but nonce fails, we stop or reset.
if ( isset( $_GET['fd_from'] ) && ( ! isset( $_REQUEST['acrb_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['acrb_nonce'] ) ), 'acrb_filter_action' ) ) ) {
    wp_die( esc_html__( 'Security check failed. Please refresh the page.', 'awesome-car-rental' ) );
}

// 2. Unslash and Sanitize input, then fall back to gmdate()
$filter_from = isset( $_GET['fd_from'] ) ? sanitize_text_field( wp_unslash( $_GET['fd_from'] ) ) : gmdate( 'Y-m-01' );
$filter_to   = isset( $_GET['fd_to'] )   ? sanitize_text_field( wp_unslash( $_GET['fd_to'] ) )   : gmdate( 'Y-m-d' );

// 3. Display using your required format
// translators: 1: Booking ID, 2: New status name.
$acrb_msg = sprintf( esc_html__( 'Showing results from %1$s to %2$s', 'awesome-car-rental' ), $filter_from, $filter_to );
echo wp_kses_post( $acrb_msg );

    // 2. DATA FETCHING (Strictly Confirmed)
    $args = [
        'post_type'   => 'acrb_bookings',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query'  => [
            ['key' => 'status', 'value' => 'confirmed', 'compare' => '=']
        ],
        'date_query'  => [['after' => $filter_from, 'before' => $filter_to, 'inclusive' => true]],
    ];

    $confirmed_bookings = get_posts($args);

    // 3. AGGREGATION LOGIC
    $chart_data = [];
    $total_rev = 0;
    $day_distribution = ['Mon'=>0,'Tue'=>0,'Wed'=>0,'Thu'=>0,'Fri'=>0,'Sat'=>0,'Sun'=>0];

    foreach ($confirmed_bookings as $o) {
        $price = floatval(get_post_meta($o->ID, 'total_price', true));
        $date  = get_the_date('Y-m-d', $o->ID);
        $day   = get_the_date('D', $o->ID);
        
        $total_rev += $price;
        $chart_data[$date] = ($chart_data[$date] ?? 0) + $price;
        $day_distribution[$day] += $price;
    }
    ksort($chart_data); 
    
    // Helper for currency display
    $format_price = function($amount) use ($currency_symbol, $currency_pos) {
        $formatted = number_format($amount, 2);
        return ('left' === $currency_pos) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
    };
    ?>

    <div class="acrb-rep-wrap">
        <header class="acrb-rep-header">
            <div class="acrb-rep-intro">
                <h1 class="acrb-rep-title"><?php esc_html_e('Revenue Intelligence', 'awesome-car-rental'); ?></h1>
                <?php
               // 1. Fetch or define your variables first
// Example: pulling from a URL parameter or a result object
$acrb_bid        = isset( $booking_id ) ? $booking_id : 0; 
$acrb_new_status = isset( $status_name ) ? $status_name : '';

// 2. The display block
?>
<p class="acrb-rep-subtitle">
    <?php 
    // translators: 1: Booking ID, 2: New status name.
    $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );
    echo wp_kses_post( $acrb_msg ); 
    ?>
</p>
            </div>
            <button onclick="window.print()" class="button acrb-btn-print">
                <span class="dashicons dashicons-printer"></span> <?php esc_html_e('Print Report', 'awesome-car-rental'); ?>
            </button>
        </header>

        <div class="acrb-stats-grid">
            <div class="acrb-stat-card acrb-stat-success">
                <span class="acrb-stat-label"><?php esc_html_e('Confirmed Revenue', 'awesome-car-rental'); ?></span>
                <span class="acrb-stat-val"><?php echo esc_html($format_price($total_rev)); ?></span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e('Confirmed Bookings', 'awesome-car-rental'); ?></span>
                <span class="acrb-stat-val"><?php echo absint(count($confirmed_bookings)); ?></span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e('Avg. Booking Value', 'awesome-car-rental'); ?></span>
                <span class="acrb-stat-val">
                    <?php 
                    $avg = (count($confirmed_bookings) > 0) ? ($total_rev / count($confirmed_bookings)) : 0;
                    echo esc_html($format_price($avg)); 
                    ?>
                </span>
            </div>
        </div>

        <form method="get" class="acrb-rep-filters">
            <input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'awesome_car_rental' ); ?>">
            <input type="hidden" name="tab" value="reports">
            <div class="acrb-filter-group">
                <label class="acrb-filter-label"><?php esc_html_e('Select Date Range', 'awesome-car-rental'); ?></label>
                <div class="acrb-filter-inputs">
                    <input type="date" name="fd_from" value="<?php echo esc_attr($filter_from); ?>">
                    <input type="date" name="fd_to" value="<?php echo esc_attr($filter_to); ?>">
                    <button type="submit" class="acrb-btn-filter"><?php esc_html_e('Filter Analytics', 'awesome-car-rental'); ?></button>
                </div>
            </div>
        </form>

        <div class="acrb-charts-grid">
            <div class="acrb-chart-card acrb-col-2">
                <h3 class="acrb-chart-title"><?php esc_html_e('Revenue Growth (Confirmed)', 'awesome-car-rental'); ?></h3>
                <canvas id="acrbRevenueChart" height="110"></canvas>
            </div>
            <div class="acrb-chart-card">
                <h3 class="acrb-chart-title"><?php esc_html_e('Daily Performance', 'awesome-car-rental'); ?></h3>
                <canvas id="acrbDayChart" height="230"></canvas>
            </div>
        </div>

        <div class="acrb-table-card">
            <h3 class="acrb-chart-title"><?php esc_html_e('Confirmed Transaction Log', 'awesome-car-rental'); ?></h3>
            <table class="widefat acrb-rep-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Order ID', 'awesome-car-rental'); ?></th>
                        <th><?php esc_html_e('Client', 'awesome-car-rental'); ?></th>
                        <th><?php esc_html_e('Vehicle', 'awesome-car-rental'); ?></th>
                        <th><?php esc_html_e('Revenue', 'awesome-car-rental'); ?></th>
                        <th><?php esc_html_e('Completed Date', 'awesome-car-rental'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($confirmed_bookings) : foreach($confirmed_bookings as $o): 
                        $car_id = get_post_meta($o->ID, 'car_id', true);
                        $row_price = floatval(get_post_meta($o->ID, 'total_price', true));
                    ?>
                    <tr>
                        <td><code class="acrb-id-badge">#<?php echo absint($o->ID); ?></code></td>
                        <td><strong><?php echo esc_html(get_post_meta($o->ID, 'customer_name', true)); ?></strong></td>
                        <td><?php echo esc_html(get_the_title($car_id)); ?></td>
                        <td class="acrb-txt-success"><?php echo esc_html($format_price($row_price)); ?></td>
                        <td><?php echo esc_html(get_the_date('M j, Y', $o->ID)); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="acrb-table-empty"><?php esc_html_e('No confirmed revenue found for this date range.', 'awesome-car-rental'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}