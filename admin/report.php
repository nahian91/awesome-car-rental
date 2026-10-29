<?php
/**
 * Reports Tab - Analytics & Revenue Data (SaaS Modernized)
 * Location: admin/reports.php
 *
 * @package Awesome_Car_Rental
 * @since   3.1.7
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acrb_reports_tab(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
	}

	global $wpdb;

	$acrb_table_bookings = $wpdb->prefix . 'dev_nahian_bookings';

	// 1. Dynamic Currency Configurations & Formatting.
	$acrb_currency_symbol = get_option( 'acrb_currency', '$' );
	$acrb_currency_pos    = get_option( 'acrb_currency_pos', 'left' );

	// 2. Validate Filter Nonce.
	if ( isset( $_GET['fd_from'] ) && ( ! isset( $_REQUEST['acrb_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['acrb_nonce'] ) ), 'acrb_filter_action' ) ) ) {
		wp_die( esc_html__( 'Security check failed. Please refresh the page.', 'awesome-car-rental' ), 403 );
	}

	$acrb_filter_from = isset( $_GET['fd_from'] ) ? sanitize_text_field( wp_unslash( $_GET['fd_from'] ) ) : gmdate( 'Y-m-01' );
	$acrb_filter_to   = isset( $_GET['fd_to'] )   ? sanitize_text_field( wp_unslash( $_GET['fd_to'] ) )   : gmdate( 'Y-m-d' );

	// 3. Data Fetching.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_confirmed_bookings = $wpdb->get_results(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$acrb_table_bookings} 
			 WHERE booking_status IN ('confirmed', 'active', 'completed')
			 AND DATE(pickup_date) >= %s 
			 AND DATE(pickup_date) <= %s 
			 ORDER BY pickup_date ASC",
			$acrb_filter_from,
			$acrb_filter_to
		),
		ARRAY_A
	);

	// 4. Data Aggregations.
	$acrb_chart_data       = array();
	$acrb_total_rev        = 0.00;
	$acrb_day_distribution = array(
		'Mon' => 0.00,
		'Tue' => 0.00,
		'Wed' => 0.00,
		'Thu' => 0.00,
		'Fri' => 0.00,
		'Sat' => 0.00,
		'Sun' => 0.00,
	);

	if ( ! empty( $acrb_confirmed_bookings ) ) {
		foreach ( $acrb_confirmed_bookings as $acrb_b ) {
			$acrb_price = floatval( $acrb_b['total_amount'] ?? 0 );
			$acrb_date  = ! empty( $acrb_b['pickup_date'] ) ? gmdate( 'Y-m-d', (int) strtotime( (string) $acrb_b['pickup_date'] ) ) : gmdate( 'Y-m-d' );
			$acrb_day   = ! empty( $acrb_b['pickup_date'] ) ? gmdate( 'D', (int) strtotime( (string) $acrb_b['pickup_date'] ) ) : gmdate( 'D' );

			$acrb_total_rev += $acrb_price;
			$acrb_chart_data[ $acrb_date ] = ( $acrb_chart_data[ $acrb_date ] ?? 0.00 ) + $acrb_price;

			if ( isset( $acrb_day_distribution[ $acrb_day ] ) ) {
				$acrb_day_distribution[ $acrb_day ] += $acrb_price;
			}
		}
	}
	ksort( $acrb_chart_data );

	$acrb_booking_count = count( $acrb_confirmed_bookings );
	$acrb_avg_booking   = $acrb_booking_count > 0 ? ( $acrb_total_rev / $acrb_booking_count ) : 0.00;

	$acrb_format_price = static function( float $amount ) use ( $acrb_currency_symbol, $acrb_currency_pos ): string {
		$formatted = number_format( $amount, 2 );
		return ( 'left' === $acrb_currency_pos ) ? $acrb_currency_symbol . $formatted : $formatted . $acrb_currency_symbol;
	};

	// Enqueue Chart.js bundled locally or register script handle.
	if ( ! wp_script_is( 'chartjs', 'enqueued' ) ) {
		wp_enqueue_script(
			'chartjs',
			plugins_url( 'assets/js/chart.umd.min.js', dirname( __FILE__ ) ),
			array(),
			'4.4.1',
			true
		);
	}
	?>

	<style>
		.acrb-reports-wrapper {
			width: 100%;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #1e293b;
			box-sizing: border-box;
		}
		.acrb-reports-wrapper *,
		.acrb-reports-wrapper *::before,
		.acrb-reports-wrapper *::after {
			box-sizing: border-box;
		}

		/* Topbar Header Banner */
		.acrb-reports-topbar-header {
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
		.acrb-reports-topbar-title-group {
			display: flex;
			align-items: center;
			gap: 14px;
		}
		.acrb-reports-topbar-icon {
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
		.acrb-reports-topbar-icon .dashicons {
			font-size: 22px;
			width: 22px;
			height: 22px;
		}
		.acrb-reports-topbar-meta h1 {
			font-size: 19px;
			font-weight: 800;
			color: #0f172a;
			margin: 0 0 3px 0;
			letter-spacing: -0.02em;
			line-height: 1.2;
		}
		.acrb-reports-topbar-meta p {
			margin: 0;
			font-size: 13px;
			color: #64748b;
		}

		/* KPI Cards Grid */
		.acrb-reports-kpi-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
			gap: 18px;
			margin-bottom: 22px;
		}
		.acrb-reports-kpi-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			padding: 20px 22px;
			box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			gap: 6px;
		}
		.acrb-reports-kpi-label {
			font-size: 11.5px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #64748b;
			display: flex;
			align-items: center;
			gap: 6px;
		}
		.acrb-reports-kpi-label .dashicons {
			font-size: 15px;
			width: 15px;
			height: 15px;
		}
		.acrb-reports-kpi-val {
			font-size: 26px;
			font-weight: 800;
			letter-spacing: -0.02em;
			line-height: 1.2;
			color: #0f172a;
		}
		.acrb-kpi-revenue {
			color: #059669;
		}
		.acrb-reports-kpi-sub {
			font-size: 12px;
			color: #94a3b8;
		}

		/* Date Interval Filter Toolbar */
		.acrb-reports-filter-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px 20px;
			margin-bottom: 24px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 16px;
			flex-wrap: wrap;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
		}
		.acrb-filter-title {
			font-weight: 700;
			font-size: 13.5px;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 6px;
		}
		.acrb-filter-title .dashicons {
			color: #4f46e5;
			font-size: 17px;
			width: 17px;
			height: 17px;
		}
		.acrb-filter-controls-group {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
		}
		.acrb-input-date {
			height: 40px !important;
			background: #f8fafc !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 12px !important;
			font-size: 13px !important;
			color: #1e293b !important;
			font-family: inherit !important;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
			transition: border-color 0.15s ease, background-color 0.15s ease !important;
		}
		.acrb-input-date:hover {
			border-color: #94a3b8 !important;
			background: #ffffff !important;
		}
		.acrb-input-date:focus {
			background: #ffffff !important;
			border-color: #4f46e5 !important;
			outline: none !important;
			box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
		}
		.acrb-date-sep {
			color: #64748b;
			font-weight: 700;
			font-size: 12px;
			text-transform: uppercase;
		}
		.acrb-btn-filter {
			background: #4f46e5;
			color: #ffffff;
			border: none;
			height: 40px;
			padding: 0 20px;
			font-size: 13px;
			font-weight: 700;
			border-radius: 8px;
			cursor: pointer;
			box-shadow: 0 2px 6px rgba(79, 70, 229, 0.22);
			transition: background 0.15s ease, transform 0.15s ease;
			display: inline-flex;
			align-items: center;
			gap: 6px;
		}
		.acrb-btn-filter:hover {
			background: #4338ca;
			transform: translateY(-1px);
		}

		/* Charts Grid */
		.acrb-charts-grid {
			display: grid;
			grid-template-columns: 2fr 1fr;
			gap: 20px;
			margin-bottom: 24px;
		}
		.acrb-chart-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
			padding: 22px;
		}
		.acrb-chart-title {
			margin: 0 0 16px 0;
			font-size: 14.5px;
			font-weight: 800;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 8px;
			border-bottom: 1px solid #f1f5f9;
			padding-bottom: 12px;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}
		.acrb-chart-title .dashicons {
			color: #4f46e5;
			font-size: 18px;
			width: 18px;
			height: 18px;
		}

		/* Transactions Table */
		.acrb-table-wrap {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
		}
		.acrb-table-header {
			padding: 18px 22px;
			background: #f8fafc;
			border-bottom: 1px solid #e2e8f0;
		}
		.acrb-table-header h3 {
			margin: 0;
			font-size: 14.5px;
			font-weight: 800;
			color: #0f172a;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}
		.acrb-rep-table {
			width: 100% !important;
			border-collapse: collapse;
			font-family: inherit;
		}
		.acrb-rep-table thead th {
			background: #f8fafc;
			color: #475569;
			font-weight: 700;
			font-size: 12px;
			text-transform: uppercase;
			letter-spacing: 0.03em;
			padding: 13px 18px;
			border-bottom: 1px solid #e2e8f0;
			text-align: left;
		}
		.acrb-rep-table tbody td {
			vertical-align: middle;
			padding: 14px 18px;
			border-bottom: 1px solid #f1f5f9;
			color: #334155;
			font-size: 13px;
		}
		.acrb-rep-table tbody tr:hover {
			background-color: #fcfdfe;
		}

		/* Table Cell Badges */
		.acrb-id-pill {
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
			font-weight: 700;
			color: #475569;
			background: #f1f5f9;
			padding: 3px 8px;
			border-radius: 5px;
			font-size: 11.5px;
			border: 1px solid #e2e8f0;
			display: inline-block;
		}
		.acrb-rep-client {
			color: #0f172a;
			font-weight: 700;
		}
		.acrb-rep-amount {
			color: #059669;
			font-size: 13.5px;
			font-weight: 800;
		}
		.acrb-rep-dates {
			font-size: 12.5px;
			color: #64748b;
		}
		.acrb-rep-empty {
			text-align: center;
			padding: 44px 20px;
			color: #64748b;
			font-size: 13.5px;
		}

		@media (max-width: 960px) {
			.acrb-charts-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<div class="acrb-reports-wrapper">

		<!-- Header -->
		<header class="acrb-reports-topbar-header">
			<div class="acrb-reports-topbar-title-group">
				<div class="acrb-reports-topbar-icon">
					<span class="dashicons dashicons-chart-area"></span>
				</div>
				<div class="acrb-reports-topbar-meta">
					<h1><?php esc_html_e( 'Revenue Intelligence & Analytics', 'awesome-car-rental' ); ?></h1>
					<p>
					<?php 
					// translators: 1: Filter start date, 2: Filter end date
					printf(
						esc_html__( 'Reporting financial analytics and reservation settlement from %1$s to %2$s', 'awesome-car-rental' ),
						esc_html( $acrb_filter_from ),
						esc_html( $acrb_filter_to )
					); 
					?>
				</p>
				</div>
			</div>
		</header>

		<!-- KPI Grid -->
		<div class="acrb-reports-kpi-grid">
			<div class="acrb-reports-kpi-card">
				<span class="acrb-reports-kpi-label">
					<span class="dashicons dashicons-money-alt"></span>
					<?php esc_html_e( 'Settled Revenue', 'awesome-car-rental' ); ?>
				</span>
				<span class="acrb-reports-kpi-val acrb-kpi-revenue"><?php echo esc_html( $acrb_format_price( $acrb_total_rev ) ); ?></span>
				<span class="acrb-reports-kpi-sub"><?php esc_html_e( 'Confirmed & completed rental gross revenue', 'awesome-car-rental' ); ?></span>
			</div>
			<div class="acrb-reports-kpi-card">
				<span class="acrb-reports-kpi-label">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Bookings Closed', 'awesome-car-rental' ); ?>
				</span>
				<span class="acrb-reports-kpi-val"><?php echo absint( $acrb_booking_count ); ?></span>
				<span class="acrb-reports-kpi-sub"><?php esc_html_e( 'Transactions processed in selected window', 'awesome-car-rental' ); ?></span>
			</div>
			<div class="acrb-reports-kpi-card">
				<span class="acrb-reports-kpi-label">
					<span class="dashicons dashicons-calculator"></span>
					<?php esc_html_e( 'Avg. Booking Value', 'awesome-car-rental' ); ?>
				</span>
				<span class="acrb-reports-kpi-val"><?php echo esc_html( $acrb_format_price( $acrb_avg_booking ) ); ?></span>
				<span class="acrb-reports-kpi-sub"><?php esc_html_e( 'Average settlement yield per reservation', 'awesome-car-rental' ); ?></span>
			</div>
		</div>

		<!-- Filter Bar -->
		<form method="get" class="acrb-reports-filter-bar">
			<?php wp_nonce_field( 'acrb_filter_action', 'acrb_nonce' ); ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'awesome_car_rental' ); ?>">
			<input type="hidden" name="tab" value="reports">
			
			<div class="acrb-filter-title">
				<span class="dashicons dashicons-calendar"></span>
				<span><?php esc_html_e( 'Filter Date Interval', 'awesome-car-rental' ); ?></span>
			</div>

			<div class="acrb-filter-controls-group">
				<input type="date" name="fd_from" value="<?php echo esc_attr( $acrb_filter_from ); ?>" class="acrb-input-date" required>
				<span class="acrb-date-sep"><?php esc_html_e( 'to', 'awesome-car-rental' ); ?></span>
				<input type="date" name="fd_to" value="<?php echo esc_attr( $acrb_filter_to ); ?>" class="acrb-input-date" required>
				<button type="submit" class="acrb-btn-filter">
					<span class="dashicons dashicons-filter"></span>
					<span><?php esc_html_e( 'Apply Filter', 'awesome-car-rental' ); ?></span>
				</button>
			</div>
		</form>

		<!-- Charts Grid -->
		<div class="acrb-charts-grid">
			<div class="acrb-chart-card">
				<h3 class="acrb-chart-title">
					<span class="dashicons dashicons-chart-line"></span>
					<span><?php esc_html_e( 'Revenue Trajectory', 'awesome-car-rental' ); ?></span>
				</h3>
				<canvas id="acrbRevenueChart" height="110"></canvas>
			</div>
			<div class="acrb-chart-card">
				<h3 class="acrb-chart-title">
					<span class="dashicons dashicons-calendar-alt"></span>
					<span><?php esc_html_e( 'Day Distribution', 'awesome-car-rental' ); ?></span>
				</h3>
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
						<th style="width: 130px;"><?php esc_html_e( 'Booking ID', 'awesome-car-rental' ); ?></th>
						<th><?php esc_html_e( 'Client', 'awesome-car-rental' ); ?></th>
						<th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
						<th><?php esc_html_e( 'Settlement', 'awesome-car-rental' ); ?></th>
						<th><?php esc_html_e( 'Rental Window', 'awesome-car-rental' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $acrb_confirmed_bookings ) ) : foreach ( $acrb_confirmed_bookings as $acrb_b ) : 
						$acrb_b_id      = absint( $acrb_b['id'] );
						$acrb_code      = esc_html( $acrb_b['booking_code'] ?: 'ACR-' . $acrb_b_id );
						$acrb_row_price = floatval( $acrb_b['total_amount'] ?? 0 );
						$acrb_client    = esc_html( $acrb_b['customer_name'] ?: __( 'Guest Client', 'awesome-car-rental' ) );
						$acrb_vehicle   = esc_html( $acrb_b['car_name'] ?: __( 'Fleet Unit', 'awesome-car-rental' ) );
						$acrb_p_date    = esc_html( $acrb_b['pickup_date'] ?: '—' );
						$acrb_r_date    = esc_html( $acrb_b['return_date'] ?: '—' );
					?>
						<tr>
							<td><span class="acrb-id-pill">#<?php echo esc_html( $acrb_code ); ?></span></td>
							<td class="acrb-rep-client"><?php echo esc_html( $acrb_client ); ?></td>
							<td><?php echo esc_html( $acrb_vehicle ); ?></td>
							<td><strong class="acrb-rep-amount"><?php echo esc_html( $acrb_format_price( $acrb_row_price ) ); ?></strong></td>
							<td class="acrb-rep-dates"><?php echo esc_html( $acrb_p_date . ' → ' . $acrb_r_date ); ?></td>
						</tr>
					<?php endforeach; else : ?>
						<tr>
							<td colspan="5" class="acrb-rep-empty">
								<?php esc_html_e( 'No revenue transactions recorded within this date range.', 'awesome-car-rental' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

	</div>

	<!-- Chart.js Engine -->
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (typeof Chart === 'undefined') return;

		// Line Chart: Revenue Curve
		var revCtx = document.getElementById('acrbRevenueChart');
		if (revCtx) {
			new Chart(revCtx, {
				type: 'line',
				data: {
					labels: <?php echo wp_json_encode( array_keys( $acrb_chart_data ) ); ?>,
					datasets: [{
						<?php /* translators: %s: Currency symbol */ ?>
						label: <?php echo wp_json_encode( sprintf( __( 'Revenue (%s)', 'awesome-car-rental' ), $acrb_currency_symbol ) ); ?>,
						data: <?php echo wp_json_encode( array_values( $acrb_chart_data ) ); ?>,
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
					labels: <?php echo wp_json_encode( array_keys( $acrb_day_distribution ) ); ?>,
					datasets: [{
						data: <?php echo wp_json_encode( array_values( $acrb_day_distribution ) ); ?>,
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