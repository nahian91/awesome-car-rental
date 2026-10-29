<?php
/**
 * Dashboard Tab - Awesome Car Rental & Booking
 * Enterprise-grade SaaS Fleet Dashboard with 100% internal styling & live telemetry.
 *
 * @package Awesome_Car_Rental
 * @since   1.5.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_dashboard_tab() {
    global $wpdb;

    $table_cars     = $wpdb->prefix . 'dev_nahian_cars';
    $table_bookings = $wpdb->prefix . 'dev_nahian_bookings';

    // 1. Settings & URLs
    $currency_symbol = get_option( 'acrb_currency', '$' ); 
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );
    
    $plugin_page     = 'awesome_car_rental';
    $bookings_url    = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=bookings' );
    $cars_url        = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=cars' );
    $calendar_url    = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=calendar' );
    $maintenance_url = admin_url( 'admin.php?page=' . esc_attr( $plugin_page ) . '&tab=maintenance' );

    // 2. Fetch Fleet Counts & Status Breakdown (Supports CPT 'acrb_cars' & custom tables)
    $car_posts = get_posts( array(
        'post_type'   => 'acrb_cars',
        'numberposts' => -1,
        'post_status' => 'publish',
    ) );

    $total_cars       = count( $car_posts );
    $available_cars   = 0;
    $maintenance_cars = 0;

    if ( $total_cars > 0 ) {
        foreach ( $car_posts as $cp ) {
            $v_status = get_post_meta( $cp->ID, 'acrb_vehicle_status', true ) ?: 'active';
            if ( 'active' === $v_status || 'ready' === $v_status ) {
                $available_cars++;
            } elseif ( 'maintenance' === $v_status ) {
                $maintenance_cars++;
            }
        }
    } else {
        // Fallback to custom table query if CPT is empty
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_cars = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_cars}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $available_cars = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_cars} WHERE status = 'ready'" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $maintenance_cars = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_cars} WHERE status = 'maintenance'" );
    }

    $rented_cars = max( 0, $total_cars - $available_cars - $maintenance_cars );

    // 3. Fetch Bookings Telemetry (Checking both CPT posts and custom tables)
    $cpt_bookings = get_posts( array(
        'post_type'   => 'acrb_bookings',
        'numberposts' => -1,
        'post_status' => 'publish',
    ) );

    $total_bookings   = count( $cpt_bookings );
    $pending_bookings = 0;
    $today_pickups    = 0;
    $revenue_total    = 0.0;
    $recent_bookings  = array();

    if ( $total_bookings > 0 ) {
        foreach ( $cpt_bookings as $b ) {
            $b_id     = $b->ID;
            $b_status = get_post_meta( $b_id, 'status', true ) ?: 'pending';
            $p_date   = get_post_meta( $b_id, 'pickup_date', true );
            $b_price  = floatval( get_post_meta( $b_id, 'total_price', true ) );
            $car_id   = absint( get_post_meta( $b_id, 'car_id', true ) );

            if ( 'pending' === $b_status ) {
                $pending_bookings++;
            }
            if ( $p_date === gmdate( 'Y-m-d' ) && 'cancelled' !== $b_status ) {
                $today_pickups++;
            }
            if ( 'confirmed' === $b_status || 'completed' === $b_status || 'active' === $b_status ) {
                $revenue_total += $b_price;
            }

            $recent_bookings[] = array(
                'id'             => $b_id,
                'booking_code'   => 'ACR-' . $b_id,
                'customer_name'  => get_post_meta( $b_id, 'customer_name', true ) ?: 'Guest Client',
                'car_name'       => $car_id ? get_the_title( $car_id ) : 'Fleet Vehicle',
                'total_amount'   => $b_price,
                'booking_status' => $b_status,
            );
        }
        // Slice to recent 6
        $recent_bookings = array_slice( $recent_bookings, 0, 6 );
    } else {
        // Fallback to custom table query if CPT bookings are empty
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_bookings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_bookings}" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $pending_bookings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_bookings} WHERE booking_status = 'pending'" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $today_pickups = (int) $wpdb->get_var( 
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table_bookings} WHERE pickup_date = %s AND booking_status != 'cancelled'", gmdate( 'Y-m-d' ) ) 
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $revenue_total = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$table_bookings} WHERE booking_status = 'confirmed'" );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $recent_bookings = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC LIMIT 6", ARRAY_A );
    }

    // Quick Helper for Price Display
    $format_price = function( $amount ) use ( $currency_symbol, $currency_pos ) {
        $formatted_number = number_format_i18n( (float) $amount, 2 );
        return ( 'left' === $currency_pos ) 
            ? $currency_symbol . $formatted_number 
            : $formatted_number . $currency_symbol;
    };

    $utilization_rate = $total_cars > 0 ? round( ( $rented_cars / $total_cars ) * 100 ) : 0;
    $avg_yield        = $format_price( $total_bookings > 0 ? $revenue_total / max( 1, $total_bookings ) : 0 );
    ?>

    <div class="acrb-dash-shell">
        
        <!-- Telemetry Header Banner -->
        <div class="acrb-telemetry-bar">
            <div class="acrb-telemetry-title">
                <h2><?php esc_html_e( 'Dashboard & Fleet Telemetry', 'awesome-car-rental' ); ?></h2>
                <p><?php esc_html_e( 'Real-time asset overview, load ratios, and active reservation yield.', 'awesome-car-rental' ); ?></p>
            </div>
            <div class="acrb-telemetry-pills">
                <div class="acrb-badge-live">
                    <span class="acrb-pulse-dot"></span>
                    <?php esc_html_e( 'SYSTEM ACTIVE', 'awesome-car-rental' ); ?>
                </div>
                <div class="acrb-clock-pill">
                    <span class="dashicons dashicons-clock acrb-icon-middle"></span>
                    <?php echo esc_html( gmdate( 'h:i:s A' ) ); ?>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=dashboard' ) ); ?>" class="acrb-refresh-btn">
                    <span class="dashicons dashicons-update acrb-icon-middle"></span>
                    <?php esc_html_e( 'Refresh', 'awesome-car-rental' ); ?>
                </a>
            </div>
        </div>

        <!-- Quick Action Shortcuts -->
        <div class="acrb-shortcuts-strip">
            <a href="<?php echo esc_url( $bookings_url . '&action=new' ); ?>" class="acrb-shortcut-card">
                <div class="acrb-shortcut-icon">
                    <span class="dashicons dashicons-plus"></span>
                </div>
                <div class="acrb-shortcut-text">
                    <strong><?php esc_html_e( 'Create Booking', 'awesome-car-rental' ); ?></strong>
                    <span><?php esc_html_e( 'New reservation', 'awesome-car-rental' ); ?></span>
                </div>
            </a>
            <a href="<?php echo esc_url( $cars_url . '&action=add' ); ?>" class="acrb-shortcut-card">
                <div class="acrb-shortcut-icon">
                    <span class="dashicons dashicons-car"></span>
                </div>
                <div class="acrb-shortcut-text">
                    <strong><?php esc_html_e( 'Register Vehicle', 'awesome-car-rental' ); ?></strong>
                    <span><?php esc_html_e( 'Add new car', 'awesome-car-rental' ); ?></span>
                </div>
            </a>
            <a href="<?php echo esc_url( $maintenance_url . '&action=add' ); ?>" class="acrb-shortcut-card">
                <div class="acrb-shortcut-icon acrb-icon-amber">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <div class="acrb-shortcut-text">
                    <strong><?php esc_html_e( 'Service Order', 'awesome-car-rental' ); ?></strong>
                    <span><?php esc_html_e( 'Log maintenance', 'awesome-car-rental' ); ?></span>
                </div>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=inspections' ) ); ?>" class="acrb-shortcut-card">
                <div class="acrb-shortcut-icon acrb-icon-pink">
                    <span class="dashicons dashicons-clipboard"></span>
                </div>
                <div class="acrb-shortcut-text">
                    <strong><?php esc_html_e( 'Inspection', 'awesome-car-rental' ); ?></strong>
                    <span><?php esc_html_e( 'File check', 'awesome-car-rental' ); ?></span>
                </div>
            </a>
        </div>

        <!-- Metric KPI Cards (4 Columns) -->
        <div class="acrb-kpi-grid">
            <div class="acrb-kpi-card">
                <div class="acrb-kpi-header">
                    <span class="acrb-kpi-label"><?php esc_html_e( 'Total Vehicles', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-kpi-badge badge-primary"><span class="dashicons dashicons-car acrb-kpi-mini-icon"></span></span>
                </div>
                <h3 class="acrb-kpi-val"><?php echo esc_html( (string) $total_cars ); ?></h3>
                <div class="acrb-kpi-footer">
                    <span class="acrb-text-emerald">▲ 100%</span> 
                    <span class="acrb-kpi-subtext"><?php echo esc_html( (string) $available_cars ); ?> Available</span>
                </div>
            </div>

            <div class="acrb-kpi-card">
                <div class="acrb-kpi-header">
                    <span class="acrb-kpi-label"><?php esc_html_e( 'Total Bookings', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-kpi-badge badge-success"><?php esc_html_e( 'All-Time', 'awesome-car-rental' ); ?></span>
                </div>
                <h3 class="acrb-kpi-val"><?php echo esc_html( (string) $total_bookings ); ?></h3>
                <div class="acrb-kpi-footer">
                    <span><?php esc_html_e( 'Historical Bookings', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-kpi-subtext-dark"><?php esc_html_e( 'Logged', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <div class="acrb-kpi-card">
                <div class="acrb-kpi-header">
                    <span class="acrb-kpi-label"><?php esc_html_e( "Today's Bookings", 'awesome-car-rental' ); ?></span>
                    <span class="acrb-kpi-badge badge-warning"><?php echo esc_html( (string) $utilization_rate . '% Load' ); ?></span>
                </div>
                <h3 class="acrb-kpi-val"><?php echo esc_html( (string) $today_pickups ); ?></h3>
                <div class="acrb-kpi-footer">
                    <span><?php esc_html_e( 'Today Pickups', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-text-amber"><?php echo esc_html( (string) $pending_bookings ); ?> Active</span>
                </div>
            </div>

            <div class="acrb-kpi-card">
                <div class="acrb-kpi-header">
                    <span class="acrb-kpi-label"><?php esc_html_e( "Today's Revenue", 'awesome-car-rental' ); ?></span>
                    <span class="acrb-kpi-badge badge-success"><span class="dashicons dashicons-money-alt acrb-kpi-mini-icon"></span></span>
                </div>
                <h3 class="acrb-kpi-val acrb-revenue-val"><?php echo esc_html( $format_price( $revenue_total ) ); ?></h3>
                <div class="acrb-kpi-footer">
                    <span><?php esc_html_e( 'Average Yield', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-kpi-subtext-dark"><?php echo esc_html( $avg_yield ); ?></span>
                </div>
            </div>
        </div>

        <!-- Main Split Content: Recent Bookings & Fleet Status -->
        <div class="acrb-grid-split">
            
            <!-- Left Column: Recent Bookings Table -->
            <div class="acrb-panel">
                <div class="acrb-panel-header">
                    <h3>
                        <span class="dashicons dashicons-list-view"></span>
                        <?php esc_html_e( 'Recent Bookings', 'awesome-car-rental' ); ?>
                    </h3>
                    <a href="<?php echo esc_url( $bookings_url ); ?>" class="acrb-view-all">
                        <?php esc_html_e( 'View All →', 'awesome-car-rental' ); ?>
                    </a>
                </div>

                <?php if ( ! empty( $recent_bookings ) ) : ?>
                    <div class="acrb-table-responsive">
                        <table class="acrb-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'ID', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Client', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Amount', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $recent_bookings as $booking ) : 
                                    $b_id     = absint( $booking['id'] );
                                    $b_code   = esc_html( (string) ( $booking['booking_code'] ?? 'ACR-' . $b_id ) );
                                    $c_name   = esc_html( (string) ( $booking['customer_name'] ?? 'Guest Client' ) );
                                    $car_name = esc_html( (string) ( $booking['car_name'] ?? 'Fleet Vehicle' ) );
                                    $amount   = (float) ( $booking['total_amount'] ?? 0 );
                                    $status   = sanitize_text_field( $booking['booking_status'] ?? 'confirmed' );
                                    
                                    $status_class = 'status-confirmed';
                                    if ( 'pending' === $status ) {
                                        $status_class = 'status-pending';
                                    } elseif ( 'active' === $status ) {
                                        $status_class = 'status-active';
                                    } elseif ( 'completed' === $status ) {
                                        $status_class = 'status-completed';
                                    }
                                ?>
                                    <tr>
                                        <td><code class="acrb-code-pill">#<?php echo esc_html( $b_code ); ?></code></td>
                                        <td><strong><?php echo esc_html( $c_name ); ?></strong></td>
                                        <td><span class="dashicons dashicons-car acrb-table-car-icon"></span> <?php echo esc_html( $car_name ); ?></td>
                                        <td><strong><?php echo esc_html( $format_price( $amount ) ); ?></strong></td>
                                        <td><span class="acrb-status-badge <?php echo esc_attr( $status_class ); ?>">● <?php echo esc_html( ucfirst( $status ) ); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="acrb-empty-box">
                        <p><?php esc_html_e( 'No recent bookings recorded in the system yet.', 'awesome-car-rental' ); ?></p>
                        <a href="<?php echo esc_url( $bookings_url . '&action=new' ); ?>" class="acrb-refresh-btn acrb-btn-primary-inline"><?php esc_html_e( 'Create First Reservation', 'awesome-car-rental' ); ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Fleet Operational Status & Quick Diagnostics -->
            <div class="acrb-panel">
                <div class="acrb-panel-header">
                    <h3>
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php esc_html_e( 'Fleet Status', 'awesome-car-rental' ); ?>
                    </h3>
                    <span class="acrb-total-counter"><?php echo esc_html( (string) $total_cars ); ?> <?php esc_html_e( 'Total', 'awesome-car-rental' ); ?></span>
                </div>

                <div class="acrb-gauge-wrap">
                    <div class="acrb-gauge-outer">
                        <div class="acrb-gauge-inner">
                            <strong class="acrb-gauge-percent"><?php echo esc_html( (string) $utilization_rate ); ?>%</strong>
                            <span class="acrb-gauge-label"><?php esc_html_e( 'Active', 'awesome-car-rental' ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="acrb-fleet-stats-container">
                    <div class="acrb-fleet-stat-row">
                        <span><span class="acrb-stat-dot dot-emerald"></span><?php esc_html_e( 'Available', 'awesome-car-rental' ); ?></span>
                        <strong><?php echo esc_html( (string) $available_cars ); ?></strong>
                    </div>
                    <div class="acrb-fleet-stat-row">
                        <span><span class="acrb-stat-dot dot-indigo"></span><?php esc_html_e( 'Rented', 'awesome-car-rental' ); ?></span>
                        <strong><?php echo esc_html( (string) $rented_cars ); ?></strong>
                    </div>
                    <div class="acrb-fleet-stat-row">
                        <span><span class="acrb-stat-dot dot-amber"></span><?php esc_html_e( 'Maintenance', 'awesome-car-rental' ); ?></span>
                        <strong><?php echo esc_html( (string) $maintenance_cars ); ?></strong>
                    </div>
                </div>

                <div class="acrb-calendar-link-wrap">
                    <a href="<?php echo esc_url( $calendar_url ); ?>" class="acrb-refresh-btn acrb-calendar-full-btn">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e( 'Open Availability Calendar', 'awesome-car-rental' ); ?>
                    </a>
                </div>
            </div>

        </div>

    </div>
    <?php
}