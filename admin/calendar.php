<?php
/**
 * Availability Calendar Module (`calendar.php`)
 * Full 12-Month multi-grid view with color-coded daily states, car selector, and year filter.
 *
 * @package Awesome_Car_Rental
 * @since   2.2.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';

// Resolve selected year and car filter (Month is looped 1 to 12)
$current_year = isset( $_GET['cal_year'] ) ? absint( $_GET['cal_year'] ) : (int) gmdate( 'Y' );
$selected_car = isset( $_GET['car_id'] ) ? absint( $_GET['car_id'] ) : 0;

if ( $current_year < 2020 || $current_year > 2040 ) {
    $current_year = (int) gmdate( 'Y' );
}

$year_start = sprintf( '%04d-01-01', $current_year );
$year_end   = sprintf( '%04d-12-31', $current_year );

// 1. LOAD ALL VEHICLES FROM CUSTOM POST TYPE 'acrb_cars'
$car_posts = get_posts( array(
    'post_type'   => 'acrb_cars',
    'numberposts' => -1,
    'post_status' => 'publish',
    'orderby'     => 'title',
    'order'       => 'ASC',
) );

$cars = array();
if ( ! empty( $car_posts ) ) {
    foreach ( $car_posts as $cp ) {
        $c_id       = absint( $cp->ID );
        $c_title    = esc_html( $cp->post_title );
        $terms      = wp_get_post_terms( $c_id, 'acrb_categories', array( 'fields' => 'names' ) );
        $meta_brand = get_post_meta( $c_id, 'brand', true );
        $brand      = ! empty( $terms ) && ! is_wp_error( $terms ) ? esc_html( $terms[0] ) : ( ! empty( $meta_brand ) ? esc_html( $meta_brand ) : __( 'Fleet Vehicle', 'awesome-car-rental' ) );

        $cars[] = array(
            'id'    => $c_id,
            'name'  => $c_title,
            'brand' => $brand,
        );
    }
}

// 2. FETCH ALL BOOKINGS OVERLAPPING WITH THIS ENTIRE YEAR (CPT + Meta Fallback)
$cpt_bookings = get_posts( array(
    'post_type'   => 'acrb_bookings',
    'numberposts' => -1,
    'post_status' => 'publish',
) );

$bookings = array();
if ( ! empty( $cpt_bookings ) ) {
    foreach ( $cpt_bookings as $cb ) {
        $b_id     = $cb->ID;
        $b_car_id = absint( get_post_meta( $b_id, 'car_id', true ) );
        $p_date   = get_post_meta( $b_id, 'pickup_date', true );
        $r_date   = get_post_meta( $b_id, 'return_date', true );
        $b_status = get_post_meta( $b_id, 'status', true ) ?: 'pending';
        $c_name   = get_post_meta( $b_id, 'customer_name', true ) ?: 'Client';

        if ( 'cancelled' !== $b_status && ! empty( $p_date ) && ! empty( $r_date ) ) {
            if ( $p_date <= $year_end && $r_date >= $year_start ) {
                if ( 0 === $selected_car || $selected_car === $b_car_id ) {
                    $bookings[] = array(
                        'id'            => $b_id,
                        'booking_code'  => 'ACR-' . $b_id,
                        'car_id'        => $b_car_id,
                        'customer_name' => $c_name,
                        'pickup_date'   => $p_date,
                        'return_date'   => $r_date,
                    );
                }
            }
        }
    }
} else {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $bookings_query = "SELECT * FROM {$table_bookings} 
                       WHERE (pickup_date <= %s AND return_date >= %s) 
                       AND booking_status != 'cancelled'";
    $query_args = array( $year_end, $year_start );

    if ( $selected_car > 0 ) {
        $bookings_query .= " AND car_id = %d";
        $query_args[]    = $selected_car;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $bookings = $wpdb->get_results( $wpdb->prepare( $bookings_query, ...$query_args ), ARRAY_A );
}

// Year navigation computations
$prev_year_val = $current_year - 1;
$next_year_val = $current_year + 1;

$base_calendar_url = add_query_arg(
    array(
        'page'   => 'awesome_car_rental',
        'tab'    => 'calendar',
        'car_id' => $selected_car,
    ),
    admin_url( 'admin.php' )
);

$prev_year_url = add_query_arg( array( 'cal_year' => $prev_year_val ), $base_calendar_url );
$next_year_url = add_query_arg( array( 'cal_year' => $next_year_val ), $base_calendar_url );
?>

<div class="acrb-calendar-wrap" style="width: 100%; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Header Bar with Vehicle Selector, Year Selector & Quick Year Navigation -->
    <div class="acrb-cal-header-bar" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; background: #ffffff; padding: 20px 24px; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
        <div class="acrb-cal-title-area">
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Fleet Availability Full Year Matrix', 'awesome-car-rental' ); ?></h2>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;"><?php printf( esc_html__( 'Displaying all 12 months for year %d with live reservation tracking.', 'awesome-car-rental' ), $current_year ); ?></p>
        </div>

        <!-- Filter Form containing both Car & Year Selectors -->
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <form method="get" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <input type="hidden" name="page" value="awesome_car_rental">
                <input type="hidden" name="tab" value="calendar">
                
                <!-- Vehicle Selector Dropdown -->
                <select name="car_id" onchange="this.form.submit()" style="height: 38px; font-size: 13px; min-width: 180px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; cursor: pointer;">
                    <option value="0"><?php esc_html_e( '🚗 All Fleet Vehicles', 'awesome-car-rental' ); ?></option>
                    <?php if ( ! empty( $cars ) ) : ?>
                        <?php foreach ( $cars as $c ) : ?>
                            <option value="<?php echo absint( $c['id'] ); ?>" <?php selected( $selected_car, $c['id'] ); ?>>
                                <?php echo esc_html( $c['name'] . ( ! empty( $c['brand'] ) ? ' (' . $c['brand'] . ')' : '' ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <!-- Year Selector Dropdown -->
                <select name="cal_year" onchange="this.form.submit()" style="height: 38px; font-size: 13px; width: 100px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; background: #fff; cursor: pointer;">
                    <?php for ( $y = (int) gmdate( 'Y' ) - 2; $y <= (int) gmdate( 'Y' ) + 5; $y++ ) : ?>
                        <option value="<?php echo absint( $y ); ?>" <?php selected( $current_year, $y ); ?>>
                            <?php echo absint( $y ); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>

            <!-- Year Jump Buttons -->
            <div style="display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 3px;">
                <a href="<?php echo esc_url( $prev_year_url ); ?>" style="padding: 5px 10px; text-decoration: none; color: #334155; font-weight: bold; font-size: 12px;" title="Previous Year">‹ <?php echo absint( $prev_year_val ); ?></a>
                <span style="font-weight: 800; padding: 0 8px; color: #0f172a; font-size: 13px;"><?php echo absint( $current_year ); ?></span>
                <a href="<?php echo esc_url( $next_year_url ); ?>" style="padding: 5px 10px; text-decoration: none; color: #334155; font-weight: bold; font-size: 12px;" title="Next Year"><?php echo absint( $next_year_val ); ?> ›</a>
            </div>
        </div>
    </div>

    <!-- 12-Month Grid Container -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
        <?php 
        for ( $m = 1; $m <= 12; $m++ ) :
            $month_name    = gmdate( 'F', mktime( 0, 0, 0, $m, 1, $current_year ) );
            $days_in_m     = (int) cal_days_in_month( CAL_GREGORIAN, $m, $current_year );
            $first_day_w   = (int) gmdate( 'N', strtotime( sprintf( '%04d-%02d-01', $current_year, $m ) ) );
        ?>
            <div class="acrb-card" style="padding: 18px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                <!-- Month Card Header -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <h3 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-calendar-alt" style="color: #4f46e5; font-size: 16px;"></span>
                        <?php echo esc_html( $month_name ); ?>
                    </h3>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; background: #f8fafc; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0;"><?php echo absint( $current_year ); ?></span>
                </div>

                <!-- Month Grid -->
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center;">
                    <?php 
                    $days_short = array( 'M', 'T', 'W', 'T', 'F', 'S', 'S' );
                    foreach ( $days_short as $ds ) : 
                    ?>
                        <div style="font-size: 10px; font-weight: 700; color: #94a3b8; padding-bottom: 4px;"><?php echo esc_html( $ds ); ?></div>
                    <?php endforeach; ?>

                    <?php 
                    for ( $i = 1; $i < $first_day_w; $i++ ) {
                        echo '<div></div>';
                    }

                    for ( $d = 1; $d <= $days_in_m; $d++ ) :
                        $date_check_full = sprintf( '%04d-%02d-%02d', $current_year, $m, $d );
                        $d_timestamp     = strtotime( $date_check_full );
                        
                        // Check if day is booked
                        $is_day_booked = false;
                        if ( ! empty( $bookings ) ) {
                            foreach ( $bookings as $b ) {
                                $p_t = strtotime( (string) $b['pickup_date'] );
                                $r_t = strtotime( (string) $b['return_date'] );
                                if ( $d_timestamp >= $p_t && $d_timestamp <= $r_t ) {
                                    if ( 0 === $selected_car || (int) $b['car_id'] === $selected_car ) {
                                        $is_day_booked = true;
                                        break;
                                    }
                                }
                            }
                        }

                        // Colors: Indigo for Booked, Soft Green for Available
                        if ( $is_day_booked ) {
                            $cell_bg    = '#4f46e5';
                            $cell_color = '#ffffff';
                            $cell_title = sprintf( __( 'Booked Day: %s', 'awesome-car-rental' ), $date_check_full );
                        } else {
                            $cell_bg    = '#ecfdf5';
                            $cell_color = '#059669';
                            $cell_title = sprintf( __( 'Available Day: %s', 'awesome-car-rental' ), $date_check_full );
                        }
                    ?>
                        <div title="<?php echo esc_attr( $cell_title ); ?>" style="font-size: 11px; font-weight: 700; color: <?php echo esc_attr( $cell_color ); ?>; background: <?php echo esc_attr( $cell_bg ); ?>; border-radius: 5px; padding: 6px 0; border: 1px solid rgba(0,0,0,0.03);">
                            <?php echo absint( $d ); ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Legend Bar -->
    <div class="acrb-cal-legend" style="display: flex; gap: 24px; align-items: center; margin-top: 24px; background: #ffffff; padding: 14px 20px; border-radius: 8px; border: 1px solid #e2e8f0; width: fit-content;">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #475569;">
            <div style="width: 18px; height: 18px; background: #4f46e5; border-radius: 4px;"></div>
            <span><?php esc_html_e( 'Booked / Reserved Day', 'awesome-car-rental' ); ?></span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #475569;">
            <div style="width: 18px; height: 18px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #059669; font-size: 11px;">✓</div>
            <span><?php esc_html_e( 'Available Slot', 'awesome-car-rental' ); ?></span>
        </div>
    </div>

</div>