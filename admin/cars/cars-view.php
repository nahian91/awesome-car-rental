<?php
/**
 * Car CRM View Component (`cars-view.php`)
 * Location: admin/cars/cars-view.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$acrb_table_cars      = $wpdb->prefix . 'dev_nahian_cars';
// Read-only profile view ID; nonces not required for displaying details.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_car_id          = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : ( isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0 );
$acrb_currency_symbol = get_option( 'acrb_currency', '$' );
$acrb_currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$acrb_rental_unit     = get_option( 'acrb_rental_unit', 'day' );
$acrb_page_slug       = 'awesome_car_rental';
$acrb_base_url        = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=cars' );
$acrb_back_url        = add_query_arg( array( 'sub' => 'list' ), $acrb_base_url );

$acrb_format_price = static function( float $val ) use ( $acrb_currency_symbol, $acrb_currency_pos ): string {
	$formatted = number_format( $val, 2 );
	return ( 'left' === $acrb_currency_pos ) ? $acrb_currency_symbol . $formatted : $formatted . $acrb_currency_symbol;
};

// Retrieve vehicle record directly from custom SQL table.
$acrb_car = null;
if ( $acrb_car_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_car = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$acrb_table_cars} WHERE id = %d", $acrb_car_id ),
		ARRAY_A
	);
}

if ( ! $acrb_car ) {
	?>
	<div class="acrb-car-view-notfound">
		<p><?php esc_html_e( 'Vehicle profile not found or has been removed from the fleet.', 'awesome-car-rental' ); ?></p>
		<a href="<?php echo esc_url( $acrb_back_url ); ?>" class="acrb-car-btn-back">
			&larr; <?php esc_html_e( 'Back to Fleet Inventory', 'awesome-car-rental' ); ?>
		</a>
	</div>
	<?php
	return;
}

// Basic Metadata Extraction.
$acrb_c_name        = (string) ( $acrb_car['name'] ?? '' );
$acrb_c_brand       = (string) ( $acrb_car['brand'] ?? '' );
$acrb_c_year        = absint( $acrb_car['year'] ?? 2026 );
$acrb_c_title       = trim( ( $acrb_c_brand ? $acrb_c_brand . ' ' : '' ) . $acrb_c_name );
$acrb_c_price       = floatval( $acrb_car['price'] ?? 0.00 );
$acrb_c_vin         = (string) ( $acrb_car['vin'] ?: '—' );
$acrb_c_plate       = (string) ( $acrb_car['plate'] ?: '—' );
$acrb_c_color       = (string) ( $acrb_car['color'] ?: '—' );
$acrb_c_category    = (string) ( $acrb_car['category'] ?: __( 'General Fleet', 'awesome-car-rental' ) );
$acrb_c_status      = sanitize_key( (string) ( $acrb_car['status'] ?: 'active' ) );
$acrb_seats         = absint( $acrb_car['seats'] ?? 5 );
$acrb_bags          = absint( $acrb_car['bags'] ?? 2 );
$acrb_trans         = (string) ( $acrb_car['transmission'] ?: 'Automatic' );
$acrb_fuel          = (string) ( $acrb_car['fuel'] ?: 'Petrol' );
$acrb_min_rental    = absint( $acrb_car['min_days'] ?? 1 );
$acrb_video_url     = (string) ( $acrb_car['video'] ?? '' );
$acrb_gallery_ids   = (string) ( $acrb_car['gallery'] ?? '' );
$acrb_description   = (string) ( $acrb_car['description'] ?? '' );
$acrb_notes         = (string) ( $acrb_car['notes'] ?? '' );
$acrb_raw_img       = (string) ( $acrb_car['image'] ?? '' );
$acrb_thumb_url     = '';

// Resolve primary image from attachment ID or direct URL.
if ( is_numeric( $acrb_raw_img ) && absint( $acrb_raw_img ) > 0 ) {
	$acrb_thumb_url = wp_get_attachment_image_url( absint( $acrb_raw_img ), 'large' ) ?: '';
} elseif ( ! empty( $acrb_raw_img ) && filter_var( $acrb_raw_img, FILTER_VALIDATE_URL ) ) {
	$acrb_thumb_url = $acrb_raw_img;
}

// Decode JSON collections.
$acrb_assigned_pickup_locs  = ! empty( $acrb_car['pickup_locations'] ) ? json_decode( (string) $acrb_car['pickup_locations'], true ) : array();
$acrb_assigned_dropoff_locs = ! empty( $acrb_car['dropoff_locations'] ) ? json_decode( (string) $acrb_car['dropoff_locations'], true ) : array();
$acrb_assigned_insurance    = ! empty( $acrb_car['insurance_plans'] ) ? json_decode( (string) $acrb_car['insurance_plans'], true ) : array();
$acrb_assigned_border       = ! empty( $acrb_car['border_crossing'] ) ? json_decode( (string) $acrb_car['border_crossing'], true ) : array();
$acrb_seasonal_rates        = ! empty( $acrb_car['seasonal_rates'] ) ? json_decode( (string) $acrb_car['seasonal_rates'], true ) : array();

if ( ! is_array( $acrb_assigned_pickup_locs ) ) {
	$acrb_assigned_pickup_locs = array();
}
if ( ! is_array( $acrb_assigned_dropoff_locs ) ) {
	$acrb_assigned_dropoff_locs = array();
}
if ( ! is_array( $acrb_assigned_insurance ) ) {
	$acrb_assigned_insurance = array();
}
if ( ! is_array( $acrb_assigned_border ) ) {
	$acrb_assigned_border = array();
}
if ( ! is_array( $acrb_seasonal_rates ) ) {
	$acrb_seasonal_rates = array();
}

$acrb_edit_url = add_query_arg( array( 'sub' => 'edit', 'car_id' => $acrb_car_id ), $acrb_base_url );
$acrb_del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'car_id' => $acrb_car_id ), $acrb_base_url ), 'acrb_del_car_' . $acrb_car_id );

// Map all flexible status keys.
$acrb_status_map = array(
	'active'      => array( 'class' => 'acrb-car-status-active', 'label' => __( 'Available', 'awesome-car-rental' ) ),
	'draft'       => array( 'class' => 'acrb-car-status-draft', 'label' => __( 'Draft', 'awesome-car-rental' ) ),
	'rented'      => array( 'class' => 'acrb-car-status-rented', 'label' => __( 'Rented', 'awesome-car-rental' ) ),
	'maintenance' => array( 'class' => 'acrb-car-status-maintenance', 'label' => __( 'Maintenance', 'awesome-car-rental' ) ),
	'reserved'    => array( 'class' => 'acrb-car-status-reserved', 'label' => __( 'Reserved', 'awesome-car-rental' ) ),
	'retired'     => array( 'class' => 'acrb-car-status-retired', 'label' => __( 'Retired', 'awesome-car-rental' ) ),
);

$acrb_status_class = $acrb_status_map[ $acrb_c_status ]['class'] ?? 'acrb-car-status-active';
$acrb_status_label = $acrb_status_map[ $acrb_c_status ]['label'] ?? ucfirst( $acrb_c_status );
?>

<style>
	.acrb-car-view-wrapper {
		width: 100%;
		box-sizing: border-box;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		color: #1e293b;
	}
	.acrb-car-view-wrapper *,
	.acrb-car-view-wrapper *::before,
	.acrb-car-view-wrapper *::after {
		box-sizing: border-box;
	}

	/* Top Bar Navigation Link */
	.acrb-car-view-nav-bar {
		margin-bottom: 20px;
	}
	.acrb-car-btn-back {
		background: #ffffff;
		color: #475569;
		border: 1px solid #e2e8f0;
		border-radius: 9px;
		padding: 9px 16px;
		font-weight: 600;
		font-size: 13px;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		gap: 6px;
		transition: all 0.15s ease;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
	}
	.acrb-car-btn-back:hover {
		background: #f8fafc;
		color: #0f172a;
		border-color: #cbd5e1;
	}
	.acrb-car-btn-back .dashicons {
		font-size: 16px;
		width: 16px;
		height: 16px;
	}

	/* Main Grid Layout */
	.acrb-car-view-grid {
		display: grid;
		grid-template-columns: 340px 1fr;
		gap: 24px;
		align-items: start;
	}

	/* Card Scaffolding */
	.acrb-car-view-card {
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		padding: 24px;
		box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
	}
	.acrb-car-view-card-title {
		margin: 0 0 16px 0;
		font-size: 15px;
		font-weight: 800;
		color: #0f172a;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		border-bottom: 1px solid #f1f5f9;
		padding-bottom: 12px;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.acrb-car-view-card-title .dashicons {
		color: #4f46e5;
		font-size: 18px;
		width: 18px;
		height: 18px;
	}

	/* Left Dossier Card */
	.acrb-car-dossier {
		text-align: center;
	}
	.acrb-car-dossier-thumb {
		width: 100%;
		height: 200px;
		border-radius: 10px;
		overflow: hidden;
		border: 1px solid #e2e8f0;
		background: #f8fafc;
		margin-bottom: 16px;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.acrb-car-dossier-thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}
	.acrb-car-dossier-thumb-placeholder {
		color: #cbd5e1;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.acrb-car-dossier-thumb-placeholder .dashicons {
		font-size: 52px;
		width: 52px;
		height: 52px;
	}
	.acrb-car-dossier-name {
		margin: 0 0 8px 0;
		font-size: 18px;
		font-weight: 800;
		color: #0f172a;
		line-height: 1.3;
	}

	/* Badges */
	.acrb-car-category-badge {
		background: #eff6ff;
		color: #4f46e5;
		border: 1px solid #dbeafe;
		padding: 3px 10px;
		border-radius: 9999px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		display: inline-block;
	}
	.acrb-car-status-badge {
		padding: 3px 10px;
		border-radius: 9999px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		display: inline-block;
		white-space: nowrap;
	}
	.acrb-car-status-active {
		background: #ecfdf5;
		color: #059669;
		border: 1px solid #a7f3d0;
	}
	.acrb-car-status-draft {
		background: #f1f5f9;
		color: #64748b;
		border: 1px solid #cbd5e1;
	}
	.acrb-car-status-rented {
		background: #eef2ff;
		color: #4338ca;
		border: 1px solid #c7d2fe;
	}
	.acrb-car-status-maintenance {
		background: #fffbeb;
		color: #b45309;
		border: 1px solid #fde68a;
	}
	.acrb-car-status-reserved {
		background: #fdf4ff;
		color: #a21caf;
		border: 1px solid #f0abfc;
	}
	.acrb-car-status-retired {
		background: #fef2f2;
		color: #991b1b;
		border: 1px solid #fecaca;
	}

	/* Meta Rows */
	.acrb-car-meta-list {
		margin-top: 20px;
		text-align: left;
		border-top: 1px solid #f1f5f9;
		padding-top: 16px;
		display: flex;
		flex-direction: column;
		gap: 12px;
		font-size: 13px;
	}
	.acrb-car-meta-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 8px;
	}
	.acrb-car-meta-label {
		color: #64748b;
		font-weight: 500;
		flex-shrink: 0;
	}
	.acrb-car-meta-val {
		color: #0f172a;
		font-weight: 700;
		text-align: right;
	}
	.acrb-car-rate-highlight {
		color: #059669;
		font-size: 15px;
		font-weight: 800;
	}

	/* Action Buttons */
	.acrb-car-dossier-actions {
		margin-top: 20px;
		border-top: 1px solid #f1f5f9;
		padding-top: 16px;
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.acrb-car-btn-edit {
		background: #4f46e5;
		color: #ffffff;
		border: none;
		padding: 11px 18px;
		font-size: 13px;
		font-weight: 700;
		border-radius: 8px;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
		transition: background 0.15s ease, transform 0.15s ease;
	}
	.acrb-car-btn-edit:hover {
		background: #4338ca;
		color: #ffffff;
		transform: translateY(-1px);
	}
	.acrb-car-btn-edit .dashicons {
		font-size: 16px;
		width: 16px;
		height: 16px;
	}
	.acrb-car-btn-del {
		color: #dc2626;
		background: #fff5f5;
		border: 1px solid #fee2e2;
		padding: 9px 14px;
		font-size: 12.5px;
		font-weight: 700;
		border-radius: 8px;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		transition: background 0.15s ease, border-color 0.15s ease;
	}
	.acrb-car-btn-del:hover {
		background: #fee2e2;
		color: #991b1b;
		border-color: #fca5a5;
	}
	.acrb-car-btn-del .dashicons {
		font-size: 15px;
		width: 15px;
		height: 15px;
	}

	/* Right Column Details Stack */
	.acrb-car-details-stack {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	/* Specifications Matrix */
	.acrb-car-spec-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 14px;
	}
	.acrb-car-spec-tile {
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 10px;
		padding: 14px 16px;
	}
	.acrb-car-spec-label {
		font-size: 11px;
		font-weight: 700;
		color: #64748b;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		display: block;
	}
	.acrb-car-spec-val {
		font-size: 15px;
		font-weight: 800;
		color: #0f172a;
		margin-top: 4px;
	}

	/* Two-Column Grid for Items */
	.acrb-car-grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 16px;
	}

	/* Subtitles */
	.acrb-view-section-subtitle {
		font-size: 12px;
		font-weight: 800;
		color: #475569;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		margin-bottom: 10px;
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.acrb-view-section-subtitle .dashicons {
		font-size: 15px;
		width: 15px;
		height: 15px;
		color: #4f46e5;
	}

	/* List Rows & Badges */
	.acrb-view-row-list {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.acrb-view-row-card {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: #f8fafc;
		padding: 10px 14px;
		border-radius: 8px;
		border: 1px solid #e2e8f0;
		font-size: 13px;
	}
	.acrb-view-row-card-left {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.acrb-view-row-card-left .dashicons {
		color: #64748b;
		font-size: 16px;
		width: 16px;
		height: 16px;
	}
	.acrb-view-row-title {
		color: #0f172a;
		font-weight: 700;
	}
	.acrb-view-rate-badge {
		font-size: 12px;
		font-weight: 700;
		color: #4f46e5;
		background: #eef2ff;
		padding: 3px 9px;
		border-radius: 6px;
		border: 1px solid #c7d2fe;
		white-space: nowrap;
	}

	/* Seasonal Overrides List */
	.acrb-seasonal-list {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.acrb-seasonal-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: #f8fafc;
		padding: 10px 14px;
		border-radius: 8px;
		border: 1px solid #e2e8f0;
		font-size: 13px;
	}
	.acrb-seasonal-name {
		color: #0f172a;
		font-weight: 700;
	}
	.acrb-seasonal-range {
		color: #64748b;
		margin-left: 8px;
		font-size: 12px;
	}
	.acrb-seasonal-rate {
		color: #059669;
		font-weight: 800;
	}

	/* Gallery Grid */
	.acrb-gallery-preview-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
		gap: 12px;
	}
	.acrb-gallery-photo-box {
		aspect-ratio: 1;
		border-radius: 8px;
		overflow: hidden;
		border: 1px solid #e2e8f0;
		background: #f8fafc;
	}
	.acrb-gallery-photo-box img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	/* Overview Content */
	.acrb-car-overview-text {
		color: #475569;
		font-size: 13.5px;
		line-height: 1.6;
	}
	.acrb-car-overview-text p:first-child {
		margin-top: 0;
	}
	.acrb-car-overview-text p:last-child {
		margin-bottom: 0;
	}

	/* Empty text helper */
	.acrb-muted-empty {
		font-size: 13px;
		color: #94a3b8;
		font-style: italic;
		margin: 0;
	}

	/* Not Found Box */
	.acrb-car-view-notfound {
		background: #fef2f2;
		border: 1px solid #fecaca;
		color: #991b1b;
		padding: 16px 20px;
		border-radius: 10px;
		font-size: 14px;
		font-weight: 600;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	@media (max-width: 960px) {
		.acrb-car-view-grid {
			grid-template-columns: 1fr;
		}
		.acrb-car-spec-grid,
		.acrb-car-grid-2 {
			grid-template-columns: 1fr;
		}
	}
</style>

<div class="acrb-car-view-wrapper">

	<!-- Back to Directory Navigation -->
	<div class="acrb-car-view-nav-bar">
		<a href="<?php echo esc_url( $acrb_back_url ); ?>" class="acrb-car-btn-back">
			<span class="dashicons dashicons-arrow-left-alt"></span>
			<span><?php esc_html_e( 'Back to Fleet Inventory', 'awesome-car-rental' ); ?></span>
		</a>
	</div>

	<div class="acrb-car-view-grid">

		<!-- Vehicle Profile Dossier Card -->
		<div class="acrb-car-view-card acrb-car-dossier">
			<div class="acrb-car-dossier-thumb">
				<?php if ( ! empty( $acrb_thumb_url ) ) : ?>
					<img src="<?php echo esc_url( $acrb_thumb_url ); ?>" alt="<?php echo esc_attr( $acrb_c_title ); ?>">
				<?php else : ?>
					<div class="acrb-car-dossier-thumb-placeholder">
						<span class="dashicons dashicons-car"></span>
					</div>
				<?php endif; ?>
			</div>

			<h3 class="acrb-car-dossier-name"><?php echo esc_html( $acrb_c_title ); ?></h3>
			<span class="acrb-car-category-badge"><?php echo esc_html( $acrb_c_category ); ?></span>

			<div class="acrb-car-meta-list">
				<div class="acrb-car-meta-row">
					<span class="acrb-car-meta-label"><?php esc_html_e( 'Daily Rate', 'awesome-car-rental' ); ?></span>
					<strong class="acrb-car-meta-val acrb-car-rate-highlight">
						<?php echo esc_html( $acrb_format_price( $acrb_c_price ) ); ?> <span style="font-size:11.5px; font-weight:500; color:#64748b;">/ <?php echo esc_html( $acrb_rental_unit ); ?></span>
					</strong>
				</div>

				<div class="acrb-car-meta-row">
					<span class="acrb-car-meta-label"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></span>
					<span class="acrb-car-status-badge <?php echo esc_attr( $acrb_status_class ); ?>">
						● <?php echo esc_html( $acrb_status_label ); ?>
					</span>
				</div>

				<div class="acrb-car-meta-row">
					<span class="acrb-car-meta-label"><?php esc_html_e( 'Model Year', 'awesome-car-rental' ); ?></span>
					<strong class="acrb-car-meta-val"><?php echo esc_html( (string) $acrb_c_year ); ?></strong>
				</div>

				<div class="acrb-car-meta-row">
					<span class="acrb-car-meta-label"><?php esc_html_e( 'VIN Identifier', 'awesome-car-rental' ); ?></span>
					<strong class="acrb-car-meta-val" style="font-family:ui-monospace,SFMono-Regular,monospace;"><?php echo esc_html( $acrb_c_vin ); ?></strong>
				</div>

				<div class="acrb-car-meta-row">
					<span class="acrb-car-meta-label"><?php esc_html_e( 'License Plate', 'awesome-car-rental' ); ?></span>
					<strong class="acrb-car-meta-val" style="font-family:ui-monospace,SFMono-Regular,monospace;"><?php echo esc_html( $acrb_c_plate ); ?></strong>
				</div>

				<div class="acrb-car-meta-row">
					<span class="acrb-car-meta-label"><?php esc_html_e( 'Exterior Color', 'awesome-car-rental' ); ?></span>
					<strong class="acrb-car-meta-val"><?php echo esc_html( $acrb_c_color ); ?></strong>
				</div>

				<div class="acrb-car-meta-row">
					<span class="acrb-car-meta-label"><?php esc_html_e( 'Min. Rental Duration', 'awesome-car-rental' ); ?></span>
					<strong class="acrb-car-meta-val"><?php echo esc_html( (string) $acrb_min_rental ); ?> <?php echo esc_html( ucfirst( $acrb_rental_unit ) . ( $acrb_min_rental > 1 ? 's' : '' ) ); ?></strong>
				</div>

				<?php if ( ! empty( $acrb_video_url ) ) : ?>
					<div class="acrb-car-meta-row">
						<span class="acrb-car-meta-label"><?php esc_html_e( 'Video Walkaround', 'awesome-car-rental' ); ?></span>
						<a href="<?php echo esc_url( $acrb_video_url ); ?>" target="_blank" rel="noopener noreferrer" style="color:#4f46e5; font-weight:700; text-decoration:none;">
							<?php esc_html_e( 'Watch Video &rarr;', 'awesome-car-rental' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>

			<div class="acrb-car-dossier-actions">
				<a href="<?php echo esc_url( $acrb_edit_url ); ?>" class="acrb-car-btn-edit">
					<span class="dashicons dashicons-edit"></span>
					<span><?php esc_html_e( 'Edit Fleet Vehicle', 'awesome-car-rental' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $acrb_del_url ); ?>" class="acrb-car-btn-del" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this fleet vehicle?', 'awesome-car-rental' ); ?>');">
					<span class="dashicons dashicons-trash"></span>
					<span><?php esc_html_e( 'Delete Vehicle', 'awesome-car-rental' ); ?></span>
				</a>
			</div>
		</div>

		<!-- Specifications & Details Column -->
		<div class="acrb-car-details-stack">

			<!-- Fleet Specifications -->
			<div class="acrb-car-view-card">
				<h3 class="acrb-car-view-card-title">
					<span class="dashicons dashicons-performance"></span>
					<span><?php esc_html_e( 'Specifications', 'awesome-car-rental' ); ?></span>
				</h3>

				<div class="acrb-car-spec-grid">
					<div class="acrb-car-spec-tile">
						<span class="acrb-car-spec-label"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></span>
						<div class="acrb-car-spec-val"><?php echo esc_html( $acrb_trans ); ?></div>
					</div>
					<div class="acrb-car-spec-tile">
						<span class="acrb-car-spec-label"><?php esc_html_e( 'Engine & Fuel', 'awesome-car-rental' ); ?></span>
						<div class="acrb-car-spec-val"><?php echo esc_html( $acrb_fuel ); ?></div>
					</div>
					<div class="acrb-car-spec-tile">
						<span class="acrb-car-spec-label"><?php esc_html_e( 'Passenger Capacity', 'awesome-car-rental' ); ?></span>
						<div class="acrb-car-spec-val"><?php echo esc_html( (string) $acrb_seats ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></div>
					</div>
					<div class="acrb-car-spec-tile">
						<span class="acrb-car-spec-label"><?php esc_html_e( 'Luggage Allowance', 'awesome-car-rental' ); ?></span>
						<div class="acrb-car-spec-val"><?php echo esc_html( (string) $acrb_bags ); ?> <?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Assigned Locations (Pickup & Drop-off) -->
			<div class="acrb-car-view-card">
				<h3 class="acrb-car-view-card-title">
					<span class="dashicons dashicons-location-alt"></span>
					<span><?php esc_html_e( 'Operating Locations', 'awesome-car-rental' ); ?></span>
				</h3>

				<div class="acrb-car-grid-2">
					<div>
						<div class="acrb-view-section-subtitle">
							<span class="dashicons dashicons-location"></span>
							<span><?php esc_html_e( 'Pickup Hubs', 'awesome-car-rental' ); ?></span>
						</div>
						<div class="acrb-view-row-list">
							<?php
							$acrb_has_pickups = false;
							if ( ! empty( $acrb_assigned_pickup_locs ) && is_array( $acrb_assigned_pickup_locs ) ) :
								foreach ( $acrb_assigned_pickup_locs as $acrb_ploc ) :
									if ( empty( $acrb_ploc['enabled'] ) ) {
										continue;
									}
									$acrb_has_pickups = true;
									?>
									<div class="acrb-view-row-card">
										<div class="acrb-view-row-card-left">
											<span class="dashicons <?php echo esc_attr( $acrb_ploc['icon'] ?? 'dashicons-location' ); ?>"></span>
											<span class="acrb-view-row-title"><?php echo esc_html( $acrb_ploc['name'] ?? '' ); ?></span>
										</div>
									</div>
									<?php
								endforeach;
							endif;
							if ( ! $acrb_has_pickups ) :
								?>
								<p class="acrb-muted-empty"><?php esc_html_e( 'No pickup locations assigned.', 'awesome-car-rental' ); ?></p>
							<?php endif; ?>
						</div>
					</div>

					<div>
						<div class="acrb-view-section-subtitle">
							<span class="dashicons dashicons-location-alt"></span>
							<span><?php esc_html_e( 'Drop-off Hubs', 'awesome-car-rental' ); ?></span>
						</div>
						<div class="acrb-view-row-list">
							<?php
							$acrb_has_dropoffs = false;
							if ( ! empty( $acrb_assigned_dropoff_locs ) && is_array( $acrb_assigned_dropoff_locs ) ) :
								foreach ( $acrb_assigned_dropoff_locs as $acrb_dloc ) :
									if ( empty( $acrb_dloc['enabled'] ) ) {
										continue;
									}
									$acrb_has_dropoffs = true;
									?>
									<div class="acrb-view-row-card">
										<div class="acrb-view-row-card-left">
											<span class="dashicons <?php echo esc_attr( $acrb_dloc['icon'] ?? 'dashicons-location-alt' ); ?>"></span>
											<span class="acrb-view-row-title"><?php echo esc_html( $acrb_dloc['name'] ?? '' ); ?></span>
										</div>
									</div>
									<?php
								endforeach;
							endif;
							if ( ! $acrb_has_dropoffs ) :
								?>
								<p class="acrb-muted-empty"><?php esc_html_e( 'No drop-off locations assigned.', 'awesome-car-rental' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Insurance & Border Cross Plans -->
			<div class="acrb-car-view-card">
				<h3 class="acrb-car-view-card-title">
					<span class="dashicons dashicons-shield"></span>
					<span><?php esc_html_e( 'Insurance & Border Crossing', 'awesome-car-rental' ); ?></span>
				</h3>

				<div class="acrb-car-grid-2">
					<div>
						<div class="acrb-view-section-subtitle">
							<span class="dashicons dashicons-shield"></span>
							<span><?php esc_html_e( 'Insurance Plans', 'awesome-car-rental' ); ?></span>
						</div>
						<div class="acrb-view-row-list">
							<?php
							$acrb_has_ins = false;
							if ( ! empty( $acrb_assigned_insurance ) && is_array( $acrb_assigned_insurance ) ) :
								foreach ( $acrb_assigned_insurance as $acrb_ins_item ) :
									if ( empty( $acrb_ins_item['enabled'] ) ) {
										continue;
									}
									$acrb_has_ins  = true;
									$acrb_ins_type = ( ( $acrb_ins_item['type'] ?? 'day' ) === 'flat' ) ? __( 'flat', 'awesome-car-rental' ) : __( 'day', 'awesome-car-rental' );
									?>
									<div class="acrb-view-row-card">
										<span class="acrb-view-row-title"><?php echo esc_html( $acrb_ins_item['name'] ?? '' ); ?></span>
										<span class="acrb-view-rate-badge">
											<?php echo esc_html( $acrb_currency_symbol . ( $acrb_ins_item['price'] ?? '0.00' ) . ' / ' . $acrb_ins_type ); ?>
										</span>
									</div>
									<?php
								endforeach;
							endif;
							if ( ! $acrb_has_ins ) :
								?>
								<p class="acrb-muted-empty"><?php esc_html_e( 'No insurance plans enabled.', 'awesome-car-rental' ); ?></p>
							<?php endif; ?>
						</div>
					</div>

					<div>
						<div class="acrb-view-section-subtitle">
							<span class="dashicons dashicons-admin-network"></span>
							<span><?php esc_html_e( 'Border Crossing Permits', 'awesome-car-rental' ); ?></span>
						</div>
						<div class="acrb-view-row-list">
							<?php
							$acrb_has_border = false;
							if ( ! empty( $acrb_assigned_border ) && is_array( $acrb_assigned_border ) ) :
								foreach ( $acrb_assigned_border as $acrb_border_item ) :
									if ( empty( $acrb_border_item['enabled'] ) ) {
										continue;
									}
									$acrb_has_border  = true;
									$acrb_border_type = ( ( $acrb_border_item['type'] ?? 'flat' ) === 'day' ) ? __( 'day', 'awesome-car-rental' ) : __( 'flat', 'awesome-car-rental' );
									?>
									<div class="acrb-view-row-card">
										<span class="acrb-view-row-title"><?php echo esc_html( $acrb_border_item['country'] ?? '' ); ?></span>
										<span class="acrb-view-rate-badge">
											<?php echo esc_html( $acrb_currency_symbol . ( $acrb_border_item['fee'] ?? '0.00' ) . ' / ' . $acrb_border_type ); ?>
										</span>
									</div>
									<?php
								endforeach;
							endif;
							if ( ! $acrb_has_border ) :
								?>
								<p class="acrb-muted-empty"><?php esc_html_e( 'No border permits enabled.', 'awesome-car-rental' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Seasonal Rate Overrides -->
			<?php if ( ! empty( $acrb_seasonal_rates ) && is_array( $acrb_seasonal_rates ) ) : ?>
				<div class="acrb-car-view-card">
					<h3 class="acrb-car-view-card-title">
						<span class="dashicons dashicons-calendar-alt"></span>
						<span><?php esc_html_e( 'Seasonal Rate Overrides', 'awesome-car-rental' ); ?></span>
					</h3>
					<div class="acrb-seasonal-list">
						<?php foreach ( $acrb_seasonal_rates as $acrb_rate ) : ?>
							<div class="acrb-seasonal-item">
								<div>
									<strong class="acrb-seasonal-name"><?php echo esc_html( $acrb_rate['name'] ?? __( 'Season', 'awesome-car-rental' ) ); ?></strong>
									<span class="acrb-seasonal-range">(<?php echo esc_html( $acrb_rate['start'] ?? '' ); ?> &rarr; <?php echo esc_html( $acrb_rate['end'] ?? '' ); ?>)</span>
								</div>
								<strong class="acrb-seasonal-rate">
									<?php echo esc_html( $acrb_format_price( floatval( $acrb_rate['price'] ?? 0 ) ) ); ?> / <?php echo esc_html( $acrb_rental_unit ); ?>
								</strong>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Photo Gallery Showcase -->
			<?php
			if ( ! empty( $acrb_gallery_ids ) ) :
				$acrb_img_ids = explode( ',', $acrb_gallery_ids );
				?>
				<div class="acrb-car-view-card">
					<h3 class="acrb-car-view-card-title">
						<span class="dashicons dashicons-images-alt2"></span>
						<span><?php esc_html_e( 'Vehicle Gallery', 'awesome-car-rental' ); ?></span>
					</h3>
					<div class="acrb-gallery-preview-grid">
						<?php
						foreach ( $acrb_img_ids as $acrb_img_id ) :
							$acrb_img_src = '';
							$clean_img_id = trim( (string) $acrb_img_id );
							if ( is_numeric( $clean_img_id ) && absint( $clean_img_id ) > 0 ) {
								$acrb_img_src = wp_get_attachment_image_url( absint( $clean_img_id ), 'medium' ) ?: '';
							} elseif ( filter_var( $clean_img_id, FILTER_VALIDATE_URL ) ) {
								$acrb_img_src = $clean_img_id;
							}
							if ( ! empty( $acrb_img_src ) ) :
								?>
								<div class="acrb-gallery-photo-box">
									<img src="<?php echo esc_url( $acrb_img_src ); ?>" alt="<?php echo esc_attr( $acrb_c_title ); ?>">
								</div>
								<?php
							endif;
						endforeach;
						?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Overview Description -->
			<?php if ( ! empty( $acrb_description ) ) : ?>
				<div class="acrb-car-view-card">
					<h3 class="acrb-car-view-card-title">
						<span class="dashicons dashicons-editor-alignleft"></span>
						<span><?php esc_html_e( 'Overview & Description', 'awesome-car-rental' ); ?></span>
					</h3>
					<div class="acrb-car-overview-text">
						<?php echo wp_kses_post( wpautop( $acrb_description ) ); ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Internal Staff Notes -->
			<?php if ( ! empty( $acrb_notes ) ) : ?>
				<div class="acrb-car-view-card">
					<h3 class="acrb-car-view-card-title">
						<span class="dashicons dashicons-clipboard"></span>
						<span><?php esc_html_e( 'Internal Staff Notes', 'awesome-car-rental' ); ?></span>
					</h3>
					<div class="acrb-car-overview-text">
						<?php echo wp_kses_post( wpautop( $acrb_notes ) ); ?>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</div>
</div>