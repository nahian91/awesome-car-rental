<?php
/**
 * Availability Calendar Module (`calendar.php`)
 * Interactive fleet booking calendar with color-coded reservation states and asset timelines.
 *
 * @package Awesome_Car_Rental
 * @since   1.3.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$table_cars     =$wpdb->prefix . 'dev_nahian_cars';
$table_bookings =$wpdb->prefix . 'dev_nahian_bookings';

// Resolve current selected month/year or default to today.
$current_year  = isset($_GET['cal_year'] ) ? absint( $_GET['cal_year'] ) : (int) gmdate( 'Y' );$current_month = isset( $_GET['cal_month'] ) ? absint( $_GET['cal_month'] ) : (int) gmdate( 'm' );

if ( $current_month < 1 || $current_month > 12 ) {$current_month = (int) gmdate( 'm' );
}
if ( $current_year < 2020 || $current_year > 2040 ) {$current_year = (int) gmdate( 'Y' );
}

$days_in_month = (int) cal_days_in_month( CAL_GREGORIAN,$current_month, $current_year );$month_start   = sprintf( '%04d-%02d-01 00:00:00', $current_year,$current_month );
$month_end     = sprintf( '\%04d-\%02d-\%02d 23:59:59',$current_year, $current_month,$days_in_month );

// Fetch all registered fleet vehicles.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cars =$wpdb->get_results( "SELECT id, name, brand FROM {$table_cars} ORDER BY name ASC", ARRAY_A );

// Fetch all bookings overlapping with this month.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$bookings = $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM {$table_bookings} 
		WHERE (pickup_date <= %s AND return_date >= %s) AND booking_status != 'cancelled'",
		sprintf( '%04d-%02d-%02d', $current_year, $current_month,$days_in_month ),
		sprintf( '%04d-%02d-01', $current_year,$current_month )
	),
	ARRAY_A
);

// Map bookings by car ID for quick timeline rendering.
$bookings_map = array();
if ( ! empty( $bookings ) ) {
	foreach ( $bookings as$b ) {
		$car_id = absint($b['car_id'] ?? 0 );
		if ( $car_id > 0 ) {$bookings_map[ $car_id ][] =$b;
		}
	}
}

// Navigation month links computation.
$prev_month =$current_month - 1;
$prev_year  =$current_year;
if ( $prev_month < 1 ) {$prev_month = 12;
	$prev_year--;
}

$next_month =$current_month + 1;
$next_year  =$current_year;
if ( $next_month > 12 ) {$next_month = 1;
	$next_year++;
}

$base_calendar_url = add_query_arg(
	array(
		'page' => 'awesome_car_rental',
		'tab'  => 'calendar',
	),
	admin_url( 'admin.php' )
);

$prev_url = add_query_arg( array( 'cal_month' => $prev_month, 'cal_year' =>$prev_year ), $base_calendar_url );$next_url = add_query_arg( array( 'cal_month' => $next_month, 'cal_year' =>$next_year ), $base_calendar_url );$current_month_name = gmdate( 'F Y', mktime( 0, 0, 0, $current_month, 1,$current_year ) );
?>

<style>
	.acrb-calendar-wrap {
		display: flex;
		flex-direction: column;
		gap: 24px;
	}

	.acrb-cal-header-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		flex-wrap: wrap;
		gap: 16px;
		background: #ffffff;
		border: 1px solid var(--acrb-border);
		border-radius: var(--acrb-radius-lg);
		padding: 20px 24px;
		box-shadow: var(--acrb-shadow-card);
	}

	.acrb-cal-title-area h2 {
		margin: 0;
		font-size: 20px;
		font-weight: 800;
		font-family: 'Plus Jakarta Sans', sans-serif;
		color: var(--acrb-text-main);
	}

	.acrb-cal-title-area p {
		margin: 4px 0 0 0;
		font-size: 13px;
		color: var(--acrb-text-muted);
	}

	.acrb-cal-nav-controls {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.acrb-cal-btn {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		height: 38px;
		padding: 0 14px;
		background: #ffffff;
		border: 1px solid var(--acrb-border);
		border-radius: var(--acrb-radius-md);
		color: var(--acrb-text-main);
		font-size: 13px;
		font-weight: 700;
		text-decoration: none;
		transition: all 0.2s ease;
	}

	.acrb-cal-btn:hover {
		background: var(--acrb-bg);
		border-color: var(--acrb-primary);
		color: var(--acrb-primary);
	}

	.acrb-cal-grid-card {
		background: #ffffff;
		border: 1px solid var(--acrb-border);
		border-radius: var(--acrb-radius-lg);
		padding: 24px;
		box-shadow: var(--acrb-shadow-card);
		overflow-x: auto;
	}

	.acrb-timeline-table {
		width: 100%;
		border-collapse: collapse;
		min-width: 1100px;
	}

	.acrb-timeline-table th,
	.acrb-timeline-table td {
		border: 1px solid var(--acrb-border);
		padding: 8px 4px;
		text-align: center;
		font-size: 12px;
	}

	.acrb-timeline-table th {
		background: var(--acrb-bg);
		color: var(--acrb-text-muted);
		font-weight: 700;
	}

	.acrb-timeline-table th.vehicle-col-header {
		text-align: left;
		padding-left: 14px;
		width: 220px;
		min-width: 220px;
	}

	.acrb-timeline-table td.vehicle-cell {
		text-align: left;
		padding-left: 14px;
		font-weight: 700;
		color: var(--acrb-text-main);
		background: #fafafa;
	}

	.acrb-timeline-table td.day-cell {
		height: 44px;
		position: relative;
		transition: background 0.1s ease;
	}

	.acrb-timeline-table td.day-cell:hover {
		background: #f8fafc;
	}

	.acrb-timeline-table td.day-cell.is-weekend {
		background: #f8fafc;
	}

	/* Booking Bar / Pill */
	.acrb-booking-pill {
		position: absolute;
		top: 6px;
		bottom: 6px;
		left: 1px;
		right: 1px;
		background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
		border-radius: 6px;
		color: #ffffff;
		font-size: 11px;
		font-weight: 700;
		display: flex;
		align-items: center;
		padding: 0 8px;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
		z-index: 5;
		text-decoration: none;
	}

	.acrb-booking-pill:hover {
		opacity: 0.95;
		color: #ffffff;
	}

	.acrb-cal-legend {
		display: flex;
		align-items: center;
		gap: 20px;
		font-size: 12px;
		font-weight: 600;
		color: var(--acrb-text-muted);
		margin-top: 14px;
	}

	.acrb-legend-item {
		display: flex;
		align-items: center;
		gap: 6px;
	}

	.acrb-legend-box {
		width: 14px;
		height: 14px;
		border-radius: 4px;
	}
</style>

<div class="acrb-calendar-wrap">

	<!-- Header Bar -->
	<div class="acrb-cal-header-bar">
		<div class="acrb-cal-title-area">
			<h2><?php esc_html_e( 'Fleet Availability Calendar', 'awesome-car-rental' ); ?></h2>
			<p><?php esc_html_e( 'Interactive timeline showing vehicle bookings and reservation windows.', 'awesome-car-rental' ); ?></p>
		</div>
		<div class="acrb-cal-nav-controls">
			<a href="<?php echo esc_url( $prev_url ); ?>" class="acrb-cal-btn">
				<span class="dashicons dashicons-arrow-left-alt2" style="font-size:16px; width:16px; height:16px;"></span>
				<?php esc_html_e( 'Previous', 'awesome-car-rental' ); ?>
			</a>
			<span style="font-size: 15px; font-weight: 800; padding: 0 8px; font-family: 'Plus Jakarta Sans', sans-serif;">
				<?php echo esc_html( $current_month_name ); ?>
			</span>
			<a href="<?php echo esc_url( $next_url ); ?>" class="acrb-cal-btn">
				<?php esc_html_e( 'Next', 'awesome-car-rental' ); ?>
				<span class="dashicons dashicons-arrow-right-alt2" style="font-size:16px; width:16px; height:16px;"></span>
			</a>
		</div>
	</div>

	<!-- Timeline Matrix Card -->
	<div class="acrb-cal-grid-card">
		<?php if ( empty( $cars ) ) : ?>
			<div style="text-align: center; padding: 40px; color: var(--acrb-text-muted);">
				<?php esc_html_e( 'No vehicles registered in the fleet yet.', 'awesome-car-rental' ); ?>
			</div>
		<?php else : ?>
			<table class="acrb-timeline-table">
				<thead>
					<tr>
						<th class="vehicle-col-header"><?php esc_html_e( 'Vehicle Asset', 'awesome-car-rental' ); ?></th>
						<?php for ( $day = 1; $day <= $days_in_month; $day++ ) :
							$date_check = sprintf( '\%04d-\%02d-\%02d',$current_year, $current_month,$day );
							$day_of_week = (int) gmdate( 'N', strtotime($date_check ) );
							$is_wknd = ($day_of_week >= 6 );
							?>
							<th style="<?php echo $is_wknd ? 'background:#f1f5f9;' : ''; ?>">
								<?php echo esc_html( (string) $day ); ?><br>
								<span style="font-size: 9px; opacity: 0.7;"><?php echo esc_html( gmdate( 'D', strtotime( $date_check ) ) ); ?></span>
							</th>
						<?php endfor; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $cars as $car ) :$car_id   = absint( $car['id'] );$car_name = (string) ( $car['name'] ?? __( 'Vehicle', 'awesome-car-rental' ) );$car_bks  = $bookings_map[$car_id ] ?? array();
						?>
						<tr>
							<td class="vehicle-cell">
								<div style="font-weight: 700; font-size: 13px;"><?php echo esc_html( $car_name ); ?></div>
								<div style="font-size: 10px; color: var(--acrb-text-muted); font-weight: 500;"><?php echo esc_html( (string) ( $car['brand'] ?? '' ) ); ?></div>
							</td>
							<?php for ( $day = 1; $day <= $days_in_month; $day++ ) :
								$date_check = sprintf( '\%04d-\%02d-\%02d',$current_year, $current_month,$day );
								$day_timestamp = strtotime($date_check );
								$day_of_week = (int) gmdate( 'N',$day_timestamp );
								$is_wknd = ($day_of_week >= 6 );

								// Check if any booking covers this exact day.
								$active_booking = null;
								foreach ( $car_bks as$b ) {
									$p_time = strtotime( (string)$b['pickup_date'] );
									$r_time = strtotime( (string)$b['return_date'] );
									if ( $day_timestamp >=$p_time && $day_timestamp <=$r_time ) {
										$active_booking =$b;
										break;
									}
								}
								?>
								<td class="day-cell <?php echo $is_wknd ? 'is-weekend' : ''; ?>">
									<?php if ( $active_booking ) :$b_code = esc_html( (string) ( $active_booking['booking_code'] ?? $active_booking['id'] ) );
										$c_name = esc_html( (string) ($active_booking['customer_name'] ?? 'Client' ) );
										$b_url  = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&action=view&booking_id=' . absint( $active_booking['id'] ) );
										
										// Render pill only on start date or first day of table
										$is_start = ( $date_check ===$active_booking['pickup_date'] );
										if ( $is_start ) :
											$p_time = strtotime( (string)$active_booking['pickup_date'] );
											$r_time = strtotime( (string)$active_booking['return_date'] );
											$month_end_ts = strtotime( sprintf( '\%04d-\%02d-\%02d',$current_year, $current_month,$days_in_month ) );
											$month_start_ts = strtotime( sprintf( '\%04d-\%02d-01',$current_year, $current_month ) );$span_start = max( $p_time,$month_start_ts );
											$span_end   = min($r_time, $month_end_ts );$span_days  = max( 1, (int) round( ( $span_end -$span_start ) / 86400 ) + 1 );
											?>
											<a href="<?php echo esc_url( $b_url ); ?>" class="acrb-booking-pill" style="width: calc(<?php echo esc_attr( (string) $span_days ); ?> * 100\% + <?php echo esc_attr( (string) ( ($span_days - 1 ) * 9 ) ); ?>px);" title="<?php echo esc_attr( $c_name . ' (#' . $b_code . ')' ); ?>">
												<span><?php echo esc_html( $c_name ); ?></span>
											</a>
										<?php endif; ?>
									<?php endif; ?>
								</td>
							<?php endfor; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- Legend -->
	<div class="acrb-cal-legend">
		<div class="acrb-legend-item">
			<div class="acrb-legend-box" style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);"></div>
			<span><?php esc_html_e( 'Confirmed / Active Reservation', 'awesome-car-rental' ); ?></span>
		</div>
		<div class="acrb-legend-item">
			<div class="acrb-legend-box" style="background: #f1f5f9; border: 1px solid var(--acrb-border);"></div>
			<span><?php esc_html_e( 'Available Slot', 'awesome-car-rental' ); ?></span>
		</div>
	</div>

</div>