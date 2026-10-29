<?php
/**
 * Availability Calendar Module (`calendar.php`)
 * Full 12-Month multi-grid view with color-coded daily states, car selector, and year filter.
 * Location: admin/calendar.php
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$acrb_table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
$acrb_table_cars     = $wpdb->prefix . 'dev_nahian_cars';

// Resolve selected year and vehicle filter (read-only query parameters).
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_cal_year_param = isset( $_GET['cal_year'] ) ? absint( wp_unslash( $_GET['cal_year'] ) ) : (int) gmdate( 'Y' );
$acrb_current_year   = ( $acrb_cal_year_param >= 2020 && $acrb_cal_year_param <= 2040 ) ? $acrb_cal_year_param : (int) gmdate( 'Y' );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_selected_car   = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;

$acrb_year_start = sprintf( '%04d-01-01', $acrb_current_year );
$acrb_year_end   = sprintf( '%04d-12-31', $acrb_current_year );
$acrb_today_str  = gmdate( 'Y-m-d' );

// 1. Fetch All Fleet Vehicles from Custom Table for the Filter Dropdown.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_db_cars = $wpdb->get_results( "SELECT id, name, brand FROM {$acrb_table_cars} WHERE status != 'retired' ORDER BY brand ASC, name ASC", ARRAY_A );

$acrb_cars = array();
if ( ! empty( $acrb_db_cars ) ) {
    foreach ( $acrb_db_cars as $acrb_c ) {
        $acrb_cars[] = array(
            'id'    => absint( $acrb_c['id'] ),
            'name'  => esc_html( $acrb_c['name'] ),
            'brand' => esc_html( $acrb_c['brand'] ?? __( 'Fleet Vehicle', 'awesome-car-rental' ) ),
        );
    }
}

// 2. Fetch Active Bookings Overlapping with This Year from Custom Table.
$acrb_raw_intervals = array();

$acrb_bookings_query = "SELECT pickup_date, return_date FROM {$acrb_table_bookings} 
                        WHERE (pickup_date <= %s AND return_date >= %s) 
                        AND booking_status != 'cancelled'";
$acrb_query_args     = array( $acrb_year_end, $acrb_year_start );

if ( $acrb_selected_car > 0 ) {
    $acrb_bookings_query .= ' AND car_id = %d';
    $acrb_query_args[]    = $acrb_selected_car;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_db_bookings = $wpdb->get_results( $wpdb->prepare( $acrb_bookings_query, ...$acrb_query_args ), ARRAY_A );

if ( ! empty( $acrb_db_bookings ) ) {
    foreach ( $acrb_db_bookings as $acrb_dbb ) {
        $acrb_raw_intervals[] = array(
            'start' => max( (string) $acrb_dbb['pickup_date'], $acrb_year_start ),
            'end'   => min( (string) $acrb_dbb['return_date'], $acrb_year_end ),
        );
    }
}

// 3. Build a fast O(1) hash map of booked day strings (YYYY-MM-DD).
$acrb_booked_days_map = array();
foreach ( $acrb_raw_intervals as $acrb_interval ) {
    $acrb_cur = strtotime( $acrb_interval['start'] );
    $acrb_end = strtotime( $acrb_interval['end'] );
    if ( $acrb_cur && $acrb_end ) {
        while ( $acrb_cur <= $acrb_end ) {
            $acrb_booked_days_map[ gmdate( 'Y-m-d', $acrb_cur ) ] = true;
            $acrb_cur += 86400;
        }
    }
}

// 4. Compute Year Jump Links.
$acrb_prev_year_val     = $acrb_current_year - 1;
$acrb_next_year_val     = $acrb_current_year + 1;
$acrb_base_calendar_url = admin_url( 'admin.php?page=awesome_car_rental&tab=calendar' );

if ( $acrb_selected_car > 0 ) {
    $acrb_base_calendar_url = add_query_arg( 'car_id', $acrb_selected_car, $acrb_base_calendar_url );
}

$acrb_prev_year_url = add_query_arg( 'cal_year', $acrb_prev_year_val, $acrb_base_calendar_url );
$acrb_next_year_url = add_query_arg( 'cal_year', $acrb_next_year_val, $acrb_base_calendar_url );
?>

<style>
    .acrb-cal-wrapper {
        width: 100%;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #1e293b;
    }
    .acrb-cal-wrapper *,
    .acrb-cal-wrapper *::before,
    .acrb-cal-wrapper *::after {
        box-sizing: border-box;
    }

    /* Topbar Header Banner */
    .acrb-cal-topbar-header {
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
    .acrb-cal-topbar-title-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .acrb-cal-topbar-icon {
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
    .acrb-cal-topbar-icon .dashicons {
        font-size: 22px;
        width: 22px;
        height: 22px;
    }
    .acrb-cal-topbar-meta h1 {
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 3px 0;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .acrb-cal-topbar-meta p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }

    /* Controls Bar */
    .acrb-cal-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .acrb-cal-filter-form {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    .acrb-cal-select-wrap {
        position: relative;
    }
    .acrb-cal-select {
        height: 40px !important;
        font-size: 13px !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 0 36px 0 12px !important;
        background: #ffffff !important;
        color: #1e293b !important;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
        line-height: 38px !important;
        font-family: inherit !important;
    }
    .acrb-cal-select:hover {
        border-color: #94a3b8 !important;
    }
    .acrb-cal-select:focus {
        border-color: #4f46e5 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    }
    .acrb-cal-select-car {
        min-width: 220px;
    }
    .acrb-cal-select-year {
        width: 104px;
    }
    .acrb-cal-select-arrow {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #64748b;
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    /* Year Jump Buttons */
    .acrb-year-jump-group {
        display: inline-flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 3px;
        height: 40px;
    }
    .acrb-year-jump-btn {
        padding: 6px 12px;
        text-decoration: none;
        color: #475569;
        font-weight: 700;
        font-size: 12.5px;
        border-radius: 6px;
        transition: all 0.15s ease;
    }
    .acrb-year-jump-btn:hover {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }
    .acrb-year-current-label {
        font-weight: 800;
        padding: 0 10px;
        color: #0f172a;
        font-size: 13.5px;
    }

    /* 12-Month Matrix Grid */
    .acrb-months-matrix {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .acrb-month-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        padding: 18px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .acrb-month-card:hover {
        box-shadow: 0 6px 24px -4px rgba(15, 23, 42, 0.08);
    }
    .acrb-month-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
    }
    .acrb-month-name {
        margin: 0;
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .acrb-month-name .dashicons {
        color: #4f46e5;
        font-size: 17px;
        width: 17px;
        height: 17px;
    }
    .acrb-month-year-pill {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        background: #f8fafc;
        padding: 2px 8px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    /* Month Calendar Grid */
    .acrb-month-days-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        text-align: center;
    }
    .acrb-weekday-label {
        font-size: 10.5px;
        font-weight: 800;
        color: #94a3b8;
        padding-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Day Cells */
    .acrb-day-cell {
        font-size: 11.5px;
        font-weight: 700;
        border-radius: 6px;
        padding: 7px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.1s ease;
        position: relative;
    }
    .acrb-day-cell:hover {
        transform: scale(1.08);
        z-index: 2;
    }
    .acrb-day-available {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    .acrb-day-booked {
        background: #4f46e5;
        color: #ffffff;
        border: 1px solid #4338ca;
        box-shadow: 0 1px 3px rgba(79, 70, 229, 0.25);
    }
    .acrb-day-today {
        outline: 2px solid #0f172a;
        outline-offset: 1px;
    }
    .acrb-day-empty {
        background: transparent;
    }

    /* Legend Bar */
    .acrb-cal-legend {
        display: flex;
        gap: 24px;
        align-items: center;
        background: #ffffff;
        padding: 14px 20px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        width: fit-content;
        flex-wrap: wrap;
    }
    .acrb-legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .acrb-legend-box {
        width: 18px;
        height: 18px;
        border-radius: 5px;
    }
    .acrb-legend-box-booked {
        background: #4f46e5;
        border: 1px solid #4338ca;
    }
    .acrb-legend-box-available {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #059669;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
    }
    .acrb-legend-box-today {
        background: #ffffff;
        border: 2px solid #0f172a;
    }
</style>

<div class="acrb-cal-wrapper">

    <!-- Topbar Banner & Controls -->
    <header class="acrb-cal-topbar-header">
        <div class="acrb-cal-topbar-title-group">
            <div class="acrb-cal-topbar-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="acrb-cal-topbar-meta">
                <h1><?php esc_html_e( 'Fleet Availability Full Year Matrix', 'awesome-car-rental' ); ?></h1>
                <p>
                    <?php
                    /* translators: %d: Selected calendar year */
                    printf( esc_html__( 'Displaying all 12 months for year %d with live reservation tracking.', 'awesome-car-rental' ), absint( $acrb_current_year ) );
                    ?>
                </p>
            </div>
        </div>

        <div class="acrb-cal-controls">
            <form method="get" class="acrb-cal-filter-form">
                <input type="hidden" name="page" value="awesome_car_rental">
                <input type="hidden" name="tab" value="calendar">

                <!-- Vehicle Selector Dropdown -->
                <div class="acrb-cal-select-wrap">
                    <select name="car_id" class="acrb-cal-select acrb-cal-select-car" onchange="this.form.submit()">
                        <option value="0"><?php esc_html_e( 'All Fleet Vehicles', 'awesome-car-rental' ); ?></option>
                        <?php if ( ! empty( $acrb_cars ) ) : ?>
                            <?php foreach ( $acrb_cars as $acrb_c ) : ?>
                                <option value="<?php echo absint( $acrb_c['id'] ); ?>" <?php selected( $acrb_selected_car, $acrb_c['id'] ); ?>>
                                    <?php echo esc_html( ( ! empty( $acrb_c['brand'] ) ? $acrb_c['brand'] . ' ' : '' ) . $acrb_c['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <span class="dashicons dashicons-arrow-down-alt2 acrb-cal-select-arrow"></span>
                </div>

                <!-- Year Selector Dropdown -->
                <div class="acrb-cal-select-wrap">
                    <select name="cal_year" class="acrb-cal-select acrb-cal-select-year" onchange="this.form.submit()">
                        <?php 
                        $acrb_cur_cal_year = (int) gmdate( 'Y' );
                        for ( $acrb_y = $acrb_cur_cal_year - 2; $acrb_y <= $acrb_cur_cal_year + 5; $acrb_y++ ) : 
                        ?>
                            <option value="<?php echo absint( $acrb_y ); ?>" <?php selected( $acrb_current_year, $acrb_y ); ?>>
                                <?php echo absint( $acrb_y ); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <span class="dashicons dashicons-arrow-down-alt2 acrb-cal-select-arrow"></span>
                </div>
            </form>

            <!-- Year Jump Navigation -->
            <div class="acrb-year-jump-group">
                <a href="<?php echo esc_url( $acrb_prev_year_url ); ?>" class="acrb-year-jump-btn" title="<?php esc_attr_e( 'Previous Year', 'awesome-car-rental' ); ?>">&larr; <?php echo absint( $acrb_prev_year_val ); ?></a>
                <span class="acrb-year-current-label"><?php echo absint( $acrb_current_year ); ?></span>
                <a href="<?php echo esc_url( $acrb_next_year_url ); ?>" class="acrb-year-jump-btn" title="<?php esc_attr_e( 'Next Year', 'awesome-car-rental' ); ?>"><?php echo absint( $acrb_next_year_val ); ?> &rarr;</a>
            </div>
        </div>
    </header>

    <!-- 12-Month Grid Matrix -->
    <div class="acrb-months-matrix">
        <?php 
        $acrb_days_short = array(
            __( 'M', 'awesome-car-rental' ),
            __( 'T', 'awesome-car-rental' ),
            __( 'W', 'awesome-car-rental' ),
            __( 'T', 'awesome-car-rental' ),
            __( 'F', 'awesome-car-rental' ),
            __( 'S', 'awesome-car-rental' ),
            __( 'S', 'awesome-car-rental' ),
        );

        for ( $acrb_m = 1; $acrb_m <= 12; $acrb_m++ ) :
            $acrb_month_name  = gmdate( 'F', mktime( 0, 0, 0, $acrb_m, 1, $acrb_current_year ) );
            $acrb_days_in_m   = (int) cal_days_in_month( CAL_GREGORIAN, $acrb_m, $acrb_current_year );
            $acrb_first_day_w = (int) gmdate( 'N', strtotime( sprintf( '%04d-%02d-01', $acrb_current_year, $acrb_m ) ) );
        ?>
            <div class="acrb-month-card">
                <div class="acrb-month-header">
                    <h3 class="acrb-month-name">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo esc_html( $acrb_month_name ); ?></span>
                    </h3>
                    <span class="acrb-month-year-pill"><?php echo absint( $acrb_current_year ); ?></span>
                </div>

                <div class="acrb-month-days-grid">
                    <?php foreach ( $acrb_days_short as $acrb_ds ) : ?>
                        <div class="acrb-weekday-label"><?php echo esc_html( $acrb_ds ); ?></div>
                    <?php endforeach; ?>

                    <?php 
                    // Blank cells preceding month start day.
                    for ( $acrb_pad = 1; $acrb_pad < $acrb_first_day_w; $acrb_pad++ ) {
                        echo '<div class="acrb-day-cell acrb-day-empty"></div>';
                    }

                    // Month days loop.
                    for ( $acrb_d = 1; $acrb_d <= $acrb_days_in_m; $acrb_d++ ) :
                        $acrb_date_check_full = sprintf( '%04d-%02d-%02d', $acrb_current_year, $acrb_m, $acrb_d );
                        $acrb_is_booked       = ! empty( $acrb_booked_days_map[ $acrb_date_check_full ] );
                        $acrb_is_today        = ( $acrb_date_check_full === $acrb_today_str );

                        $acrb_day_classes = 'acrb-day-cell ' . ( $acrb_is_booked ? 'acrb-day-booked' : 'acrb-day-available' );
                        if ( $acrb_is_today ) {
                            $acrb_day_classes .= ' acrb-day-today';
                        }

                        if ( $acrb_is_booked ) {
                            /* translators: %s: Formatted date string (YYYY-MM-DD) */
                            $acrb_title_tip = sprintf( __( 'Booked: %s', 'awesome-car-rental' ), $acrb_date_check_full );
                        } else {
                            /* translators: %s: Formatted date string (YYYY-MM-DD) */
                            $acrb_title_tip = sprintf( __( 'Available: %s', 'awesome-car-rental' ), $acrb_date_check_full );
                        }
                    ?>
                        <div class="<?php echo esc_attr( $acrb_day_classes ); ?>" title="<?php echo esc_attr( $acrb_title_tip ); ?>">
                            <?php echo absint( $acrb_d ); ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Legend Bar -->
    <div class="acrb-cal-legend">
        <div class="acrb-legend-item">
            <div class="acrb-legend-box acrb-legend-box-booked"></div>
            <span><?php esc_html_e( 'Booked / Reserved Day', 'awesome-car-rental' ); ?></span>
        </div>
        <div class="acrb-legend-item">
            <div class="acrb-legend-box acrb-legend-box-available">&#10003;</div>
            <span><?php esc_html_e( 'Available Slot', 'awesome-car-rental' ); ?></span>
        </div>
        <div class="acrb-legend-item">
            <div class="acrb-legend-box acrb-legend-box-today"></div>
            <span><?php esc_html_e( 'Today', 'awesome-car-rental' ); ?></span>
        </div>
    </div>

</div>