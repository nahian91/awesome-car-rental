<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Full Dashboard Tab - Awesome Car Rental
 * Updated to support dynamic currency from General Settings
 */
function acrb_dashboard_tab() {
    // 1. DYNAMIC SETTINGS FETCHING
    // Pulls from General Settings: Default to '£' if not found
    $currency      = get_option( 'acrb_currency', '£' ); 
    $currency_pos  = get_option( 'acrb_currency_pos', 'left' );
    
    $plugin_page   = 'awesome_car_rental';
    $directory_url = admin_url( "admin.php?page=" . esc_attr( $plugin_page ) . "&tab=bookings" );

    // Fetch Pending Bookings
    $pending_bookings = get_posts([
        'post_type'      => 'acrb_bookings', 
        'numberposts'    => 5,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'meta_query'     => [
            'relation' => 'OR',
            [['key' => 'status', 'value' => 'pending', 'compare' => '=']],
            [['key' => 'status', 'compare' => 'NOT EXISTS']] 
        ]
    ]);

    // Fetch Confirmed Bookings for Revenue Calculation
    $all_confirmed = get_posts([
        'post_type'      => 'acrb_bookings',
        'numberposts'    => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => 'status',
        'meta_value'     => 'confirmed'
    ]);
    
    $total_revenue = 0;
    foreach( $all_confirmed as $conf_id ) {
        $raw_price = get_post_meta( $conf_id, 'total_price', true );
        $clean_price = preg_replace( '/[^\d.]/', '', $raw_price );
        $total_revenue += floatval( $clean_price );
    }

    $total_pending = count( $pending_bookings );
    $total_cars    = wp_count_posts( 'acrb_cars' )->publish;

    // Helper function for dynamic currency formatting
    $format_price = function( $amount ) use ( $currency, $currency_pos ) {
        $formatted_number = number_format_i18n( $amount, 2 );
        return ( 'left' === $currency_pos ) 
            ? $currency . $formatted_number 
            : $formatted_number . $currency;
    };

    ?>

    <div class="acrb-dash-wrapper">
        <div class="acrb-hero">
            <div class="acrb-hero-content">
                <h1 class="acrb-hero-title"><?php esc_html_e( 'Car Rental & Booking', 'awesome-car-rental' ); ?></h1>
                <p class="acrb-hero-subtitle">
                    <?php 
                    printf(
                        /* translators: %s: number of pending reservations */
                        esc_html__( 'Welcome back. There are %s new reservations to review.', 'awesome-car-rental' ),
                        '<strong>' . esc_html( $total_pending ) . '</strong>'
                    ); 
                    ?>
                </p>
            </div>
        </div>

        <div class="acrb-stats-grid">
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e( 'Total Inventory', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-value">
                    <?php echo esc_html( $total_cars ); ?> 
                    <small class="acrb-unit"><?php esc_html_e( 'Cars', 'awesome-car-rental' ); ?></small>
                </span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e( 'Action Required', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-value acrb-text-danger"><?php echo esc_html( $total_pending ); ?></span>
            </div>
            <div class="acrb-stat-card">
                <span class="acrb-stat-label"><?php esc_html_e( 'Gross Revenue', 'awesome-car-rental' ); ?></span>
                <span class="acrb-stat-value">
                    <?php echo esc_html( $format_price( $total_revenue ) ); ?>
                </span>
            </div>
        </div>

        <div class="acrb-section-header">
            <span class="dashicons dashicons-warning acrb-icon-indigo"></span> 
            <?php esc_html_e( 'Pending Reservation Requests', 'awesome-car-rental' ); ?>
        </div>

        <?php if ( $pending_bookings ) : ?>
            <div class="acrb-rows-container">
                <?php foreach ( $pending_bookings as $b ) : 
                    $bid           = $b->ID;
                    $customer      = get_post_meta( $bid, 'customer_name', true ) ?: __( 'Guest User', 'awesome-car-rental' );
                    $car_id        = get_post_meta( $bid, 'car_id', true );
                    $raw_price     = get_post_meta( $bid, 'total_price', true );
                    $display_price = floatval( preg_replace( '/[^\d.]/', '', $raw_price ) );
                    
                    $review_url    = wp_nonce_url( $directory_url . "&action=edit&booking_id=" . intval( $bid ), 'acrb_car_action' );
                ?>
                    <div class="acrb-row">
                        <div class="acrb-icon-box">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="acrb-info">
                            <strong class="acrb-info-name"><?php echo esc_html( $customer ); ?></strong>
                            <span class="acrb-info-meta">
                                <?php 
                                printf(
                                    /* translators: 1: vehicle title, 2: time ago */
                                    esc_html__( 'Vehicle: %1$s — %2$s ago', 'awesome-car-rental' ),
                                    esc_html( get_the_title( $car_id ) ),
                                    esc_html( human_time_diff( get_the_time( 'U', $bid ), current_time( 'timestamp' ) ) )
                                );
                                ?>
                            </span>
                        </div>
                        <div class="acrb-price">
                            <?php echo esc_html( $format_price( $display_price ) ); ?>
                        </div>
                        <div class="acrb-pulse"><?php esc_html_e( 'REVIEW', 'awesome-car-rental' ); ?></div>
                        <a href="<?php echo esc_url( $review_url ); ?>" class="acrb-btn-review">
                            <?php esc_html_e( 'Manage Request', 'awesome-car-rental' ); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="acrb-footer-link">
                <a href="<?php echo esc_url( $directory_url ); ?>">
                    <?php esc_html_e( 'View Full Booking Directory →', 'awesome-car-rental' ); ?>
                </a>
            </div>

        <?php else : ?>
            <div class="acrb-empty-state">
                <span class="dashicons dashicons-yes-alt"></span>
                <p><?php esc_html_e( 'Everything caught up! No pending bookings.', 'awesome-car-rental' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}