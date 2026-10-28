<?php
/**
 * Dashboard Tab - Awesome Car Rental & Booking
 * Enterprise-grade SaaS Fleet Dashboard with 100% internal styling & live telemetry.
 *
 * @package Awesome_Car_Rental
 * @since   1.5.2
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

	// 2. Fetch Fleet Counts & Status Breakdown from Custom Tables
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$total_cars = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_cars}" );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$available_cars = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_cars} WHERE status = 'ready'" );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$maintenance_cars = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_cars} WHERE status = 'maintenance'" );

	$rented_cars = max( 0, $total_cars - $available_cars - $maintenance_cars );

	// 3. Fetch Bookings Telemetry
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$total_bookings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_bookings}" );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$pending_bookings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_bookings} WHERE booking_status = 'pending'" );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$today_pickups = (int) $wpdb->get_var( 
		$wpdb->prepare( "SELECT COUNT(*) FROM {$table_bookings} WHERE pickup_date = %s AND booking_status != 'cancelled'", gmdate( 'Y-m-d' ) ) 
	);

	// 4. Computed Gross Revenue
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$revenue_total = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$table_bookings} WHERE booking_status = 'confirmed'" );

	// 5. Fetch Recent Bookings Feed
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$recent_bookings = $wpdb->get_results( 
		"SELECT * FROM {$table_bookings} ORDER BY id DESC LIMIT 6", 
		ARRAY_A 
	);

	// Quick Helper for Price Display
	$format_price = function( $amount ) use ( $currency_symbol, $currency_pos ) {
		$formatted_number = number_format_i18n( (float) $amount, 2 );
		return ( 'left' === $currency_pos ) 
			? $currency_symbol . $formatted_number 
			: $formatted_number . $currency_symbol;
	};

	$utilization_rate = $total_cars > 0 ? round( ( $rented_cars / $total_cars ) * 100 ) : 0;
	$avg_yield = $format_price( $total_bookings > 0 ? $revenue_total / max( 1, $total_bookings ) : 0 );
	?>

	<style>
		.acrb-dash-shell {
			max-width: 100%;
			color: #0f172a;
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			box-sizing: border-box;
		}

		.acrb-dash-shell *, .acrb-dash-shell *:before, .acrb-dash-shell *:after {
			box-sizing: border-box;
		}

		/* Top Telemetry Live Bar */
		.acrb-telemetry-bar {
			background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
			border-radius: 16px;
			padding: 20px 28px;
			color: #ffffff;
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 24px;
			box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
			border: 1px solid rgba(255, 255, 255, 0.08);
		}

		.acrb-telemetry-title h2 {
			font-family: 'Plus Jakarta Sans', sans-serif;
			font-size: 20px;
			font-weight: 800;
			margin: 0 0 4px 0;
			color: #ffffff;
			letter-spacing: -0.02em;
		}

		.acrb-telemetry-title p {
			margin: 0;
			font-size: 13px;
			color: #94a3b8;
		}

		.acrb-telemetry-pills {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.acrb-badge-live {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(16, 185, 129, 0.15);
			border: 1px solid rgba(16, 185, 129, 0.3);
			color: #34d399;
			padding: 6px 14px;
			border-radius: 9999px;
			font-size: 12px;
			font-weight: 700;
		}

		.acrb-pulse-dot {
			width: 8px;
			height: 8px;
			background: #34d399;
			border-radius: 50%;
			box-shadow: 0 0 10px #34d399;
			animation: acrbPulse 2s infinite;
		}

		@keyframes acrbPulse {
			0% { transform: scale(0.95); opacity: 0.8; }
			50% { transform: scale(1.2); opacity: 1; }
			100% { transform: scale(0.95); opacity: 0.8; }
		}

		.acrb-clock-pill {
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.12);
			padding: 6px 14px;
			border-radius: 8px;
			font-size: 12.5px;
			font-weight: 600;
		}

		.acrb-icon-middle {
			font-size: 15px;
			width: 15px;
			height: 15px;
			vertical-align: middle;
		}

		.acrb-refresh-btn {
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.15);
			color: #ffffff;
			padding: 7px 14px;
			border-radius: 8px;
			font-size: 12.5px;
			font-weight: 600;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			text-decoration: none;
			transition: background 0.2s;
		}

		.acrb-refresh-btn:hover {
			background: rgba(255, 255, 255, 0.15);
			color: #ffffff;
		}

		/* Quick Action Shortcut Strip */
		.acrb-shortcuts-strip {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
			margin-bottom: 24px;
		}

		.acrb-shortcut-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			padding: 16px 20px;
			display: flex;
			align-items: center;
			gap: 14px;
			text-decoration: none;
			color: #0f172a;
			box-shadow: 0 2px 4px rgba(15, 23, 42, 0.02);
			transition: all 0.2s ease;
		}

		.acrb-shortcut-card:hover {
			border-color: #4f46e5;
			transform: translateY(-2px);
			box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.1);
		}

		.acrb-shortcut-icon {
			width: 42px;
			height: 42px;
			border-radius: 10px;
			background: #eef2ff;
			color: #4f46e5;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}

		.acrb-shortcut-icon .dashicons {
			font-size: 20px;
			width: 20px;
			height: 20px;
		}

		.acrb-icon-amber { background: #fffbeb; color: #d97706; }
		.acrb-icon-pink { background: #fdf2f8; color: #db2777; }

		.acrb-shortcut-text strong {
			display: block;
			font-size: 14px;
			font-weight: 700;
			color: #0f172a;
			font-family: 'Plus Jakarta Sans', sans-serif;
		}

		.acrb-shortcut-text span {
			font-size: 12px;
			color: #64748b;
		}

		/* Metric KPI Cards (4 Columns) */
		.acrb-kpi-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 20px;
			margin-bottom: 24px;
		}

		.acrb-kpi-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 22px 24px;
			box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.02);
			position: relative;
			overflow: hidden;
		}

		.acrb-kpi-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 12px;
		}

		.acrb-kpi-label {
			font-size: 12px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #64748b;
		}

		.acrb-kpi-badge {
			font-size: 11px;
			font-weight: 700;
			padding: 3px 8px;
			border-radius: 6px;
		}

		.badge-success { background: #ecfdf5; color: #059669; }
		.badge-warning { background: #fffbeb; color: #d97706; }
		.badge-primary { background: #eef2ff; color: #4f46e5; }

		.acrb-kpi-val {
			font-family: 'Plus Jakarta Sans', sans-serif;
			font-size: 30px;
			font-weight: 800;
			color: #0f172a;
			margin: 0 0 6px 0;
			letter-spacing: -0.03em;
		}

		.acrb-revenue-val { font-size: 26px; color: #059669; }

		.acrb-kpi-footer {
			font-size: 12.5px;
			color: #64748b;
			display: flex;
			align-items: center;
			gap: 6px;
		}

		.acrb-kpi-mini-icon { font-size: 12px; width: 12px; height: 12px; }
		.acrb-text-emerald { color: #059669; font-weight: 700; }
		.acrb-kpi-subtext { margin-left: auto; color: #64748b; }
		.acrb-kpi-subtext-dark { margin-left: auto; font-weight: 700; color: #0f172a; }
		.acrb-text-amber { margin-left: auto; font-weight: 700; color: #d97706; }

		/* Main Content Split Layout */
		.acrb-grid-split {
			display: grid;
			grid-template-columns: 2fr 1fr;
			gap: 24px;
		}

		.acrb-panel {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 24px;
			box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.02);
		}

		.acrb-panel-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
			padding-bottom: 14px;
			border-bottom: 1px solid #f1f5f9;
		}

		.acrb-panel-header h3 {
			font-family: 'Plus Jakarta Sans', sans-serif;
			font-size: 16px;
			font-weight: 800;
			margin: 0;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.acrb-panel-header h3 .dashicons { color: #4f46e5; }

		.acrb-view-all {
			font-size: 13px;
			font-weight: 700;
			color: #4f46e5;
			text-decoration: none;
			transition: color 0.2s;
		}

		.acrb-view-all:hover { color: #3730a3; }

		.acrb-table-responsive { overflow-x: auto; }

		.acrb-table {
			width: 100%;
			border-collapse: collapse;
			text-align: left;
		}

		.acrb-table th {
			background: #f8fafc;
			color: #64748b;
			font-size: 11.5px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			padding: 10px 14px;
			border-bottom: 1px solid #e2e8f0;
		}

		.acrb-table td {
			padding: 14px;
			font-size: 13.5px;
			color: #334155;
			border-bottom: 1px solid #f1f5f9;
			vertical-align: middle;
		}

		.acrb-table tbody tr:hover { background: #fafafa; }

		.acrb-code-pill {
			background: #f1f5f9;
			padding: 3px 6px;
			border-radius: 4px;
			font-weight: 700;
			color: #4f46e5;
		}

		.acrb-table-car-icon {
			font-size: 15px;
			width: 15px;
			height: 15px;
			color: #64748b;
			vertical-align: middle;
		}

		.acrb-status-badge {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}

		.status-confirmed { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
		.status-pending { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
		.status-active { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
		.status-completed { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

		.acrb-empty-box {
			text-align: center;
			padding: 40px;
			color: #64748b;
		}

		.acrb-btn-primary-inline {
			background: #4f46e5;
			display: inline-flex;
			margin-top: 10px;
		}

		.acrb-total-counter {
			font-size: 12px;
			font-weight: 700;
			color: #64748b;
		}

		.acrb-gauge-wrap {
			display: flex;
			flex-direction: column;
			align-items: center;
			padding: 10px 0 20px 0;
		}

		.acrb-gauge-outer {
			width: 130px;
			height: 130px;
			border-radius: 50%;
			background: conic-gradient(#10b981 0% 100%, #f1f5f9 100% 100%);
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.2);
		}

		.acrb-gauge-inner {
			width: 100px;
			height: 100px;
			border-radius: 50%;
			background: #ffffff;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}

		.acrb-gauge-percent {
			font-family: 'Plus Jakarta Sans', sans-serif;
			font-size: 20px;
			font-weight: 800;
			color: #0f172a;
		}

		.acrb-gauge-label {
			font-size: 10px;
			font-weight: 700;
			color: #64748b;
			text-transform: uppercase;
		}

		.acrb-fleet-stats-container {
			background: #f8fafc;
			border-radius: 12px;
			padding: 14px 18px;
			border: 1px solid #f1f5f9;
		}

		.acrb-fleet-stat-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 12px 0;
			border-bottom: 1px solid #f1f5f9;
			font-size: 13.5px;
		}

		.acrb-fleet-stat-row:last-child { border-bottom: none; }

		.acrb-stat-dot {
			width: 10px;
			height: 10px;
			border-radius: 50%;
			display: inline-block;
			margin-right: 8px;
		}

		.dot-emerald { background: #10b981; }
		.dot-indigo { background: #6366f1; }
		.dot-amber { background: #f59e0b; }

		.acrb-calendar-link-wrap { margin-top: 20px; }

		.acrb-calendar-full-btn {
			background: #4f46e5;
			width: 100%;
			justify-content: center;
			padding: 10px;
			font-size: 13.5px;
		}

		@media (max-width: 1200px) {
			.acrb-kpi-grid { grid-template-columns: repeat(2, 1fr); }
			.acrb-shortcuts-strip { grid-template-columns: repeat(2, 1fr); }
			.acrb-grid-split { grid-template-columns: 1fr; }
		}

		@media (max-width: 768px) {
			.acrb-kpi-grid { grid-template-columns: 1fr; }
			.acrb-shortcuts-strip { grid-template-columns: 1fr; }
			.acrb-telemetry-bar { flex-direction: column; align-items: flex-start; gap: 14px; }
		}
	</style>

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
									$b_code   = esc_html( (string) ( $booking['booking_code'] ?? 'DNF-BKG-' . $b_id ) );
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