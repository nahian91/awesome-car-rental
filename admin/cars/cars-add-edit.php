<?php
/**
 * Add / Edit Car Component (`cars-add-edit.php`)
 * Location: admin/cars/cars-add-edit.php
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

$acrb_table_cars       = $wpdb->prefix . 'dev_nahian_cars';
$acrb_table_categories = $wpdb->prefix . 'dev_nahian_categories';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_edit_car_id     = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$acrb_currency_symbol = get_option( 'acrb_currency', '$' );
$acrb_list_url        = admin_url( 'admin.php?page=awesome_car_rental&tab=cars' );
$acrb_error_notice    = '';

// 1. Synchronized Data Loading from Global Extras Options.
$acrb_saved_insurance_plans   = get_option( 'acrb_insurance_plans', array() );
$acrb_saved_border_cross_fees = get_option( 'acrb_border_cross_fees', array() );

// Load Pickup Locations directly from Extras.
$acrb_raw_pickups      = get_option( 'acrb_custom_pickup_locations', get_option( 'acrb_custom_locations', array() ) );
$acrb_pickup_locations = array();
if ( is_array( $acrb_raw_pickups ) ) {
	foreach ( $acrb_raw_pickups as $acrb_loc_item ) {
		$acrb_loc_name = is_array( $acrb_loc_item ) ? ( $acrb_loc_item['name'] ?? '' ) : (string) $acrb_loc_item;
		$acrb_loc_icon = is_array( $acrb_loc_item ) ? ( $acrb_loc_item['icon'] ?? 'dashicons-location' ) : 'dashicons-location';
		if ( ! empty( $acrb_loc_name ) ) {
			$acrb_pickup_locations[] = array(
				'name' => $acrb_loc_name,
				'icon' => $acrb_loc_icon,
			);
		}
	}
}

// Load Drop-off Locations directly from Extras.
$acrb_raw_dropoffs      = get_option( 'acrb_custom_dropoff_locations', get_option( 'acrb_custom_locations', array() ) );
$acrb_dropoff_locations = array();
if ( is_array( $acrb_raw_dropoffs ) ) {
	foreach ( $acrb_raw_dropoffs as $acrb_loc_item ) {
		$acrb_loc_name = is_array( $acrb_loc_item ) ? ( $acrb_loc_item['name'] ?? '' ) : (string) $acrb_loc_item;
		$acrb_loc_icon = is_array( $acrb_loc_item ) ? ( $acrb_loc_item['icon'] ?? 'dashicons-location-alt' ) : 'dashicons-location-alt';
		if ( ! empty( $acrb_loc_name ) ) {
			$acrb_dropoff_locations[] = array(
				'name' => $acrb_loc_name,
				'icon' => $acrb_loc_icon,
			);
		}
	}
}

// Fetch Taxonomy / Database Categories.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_categories_list = $wpdb->get_results( "SELECT * FROM {$acrb_table_categories} ORDER BY name ASC", ARRAY_A );

// Available Vehicle Statuses.
$acrb_status_options = array(
	'active'      => __( 'Available', 'awesome-car-rental' ),
	'draft'       => __( 'Draft / Hidden', 'awesome-car-rental' ),
	'rented'      => __( 'Rented / Active Booking', 'awesome-car-rental' ),
	'maintenance' => __( 'Maintenance / In Service', 'awesome-car-rental' ),
	'reserved'    => __( 'Reserved / Hold', 'awesome-car-rental' ),
	'retired'     => __( 'Retired / Inactive', 'awesome-car-rental' ),
);

// 2. Form Processing & Direct Database Storage (`$wpdb`).
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['acrb_car_name'] ) ) {
	if ( ! isset( $_POST['acrb_car_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acrb_car_nonce'] ) ), 'acrb_save_car_nonce' ) ) {
		$acrb_error_notice = __( 'Security check failed. Please try again.', 'awesome-car-rental' );
	} else {
		$acrb_car_name = sanitize_text_field( wp_unslash( $_POST['acrb_car_name'] ) );

		if ( empty( $acrb_car_name ) ) {
			$acrb_error_notice = __( 'Please specify a vehicle name.', 'awesome-car-rental' );
		} else {
			// Normalize structured arrays to JSON for SQL storage.
			$acrb_clean_pickups   = map_deep( wp_unslash( $_POST['assigned_pickup_locs'] ?? array() ), 'sanitize_text_field' );
			$acrb_clean_dropoffs  = map_deep( wp_unslash( $_POST['assigned_dropoff_locs'] ?? array() ), 'sanitize_text_field' );
			$acrb_clean_insurance = map_deep( wp_unslash( $_POST['car_insurance'] ?? array() ), 'sanitize_text_field' );
			$acrb_clean_border    = map_deep( wp_unslash( $_POST['car_border_cross'] ?? array() ), 'sanitize_text_field' );
			$acrb_clean_seasonal  = map_deep( wp_unslash( $_POST['seasonal_rates'] ?? array() ), 'sanitize_text_field' );

			$acrb_row_data = array(
				'name'              => $acrb_car_name,
				'brand'             => sanitize_text_field( wp_unslash( $_POST['acrb_car_brand'] ?? '' ) ),
				'year'              => absint( $_POST['acrb_car_year'] ?? 2026 ),
				'vin'               => sanitize_text_field( wp_unslash( $_POST['acrb_car_vin'] ?? '' ) ),
				'plate'             => sanitize_text_field( wp_unslash( $_POST['acrb_car_plate'] ?? '' ) ),
				'category'          => sanitize_text_field( wp_unslash( $_POST['acrb_car_category'] ?? '' ) ),
				'color'             => sanitize_text_field( wp_unslash( $_POST['acrb_car_color'] ?? '' ) ),
				'status'            => sanitize_key( wp_unslash( $_POST['acrb_vehicle_status'] ?? 'active' ) ),
				'description'       => isset( $_POST['acrb_car_desc'] ) ? wp_kses_post( wp_unslash( $_POST['acrb_car_desc'] ) ) : '',
				'pickup_locations'  => ! empty( $acrb_clean_pickups ) ? (string) wp_json_encode( array_values( $acrb_clean_pickups ) ) : '[]',
				'dropoff_locations' => ! empty( $acrb_clean_dropoffs ) ? (string) wp_json_encode( array_values( $acrb_clean_dropoffs ) ) : '[]',
				'insurance_plans'   => ! empty( $acrb_clean_insurance ) ? (string) wp_json_encode( array_values( $acrb_clean_insurance ) ) : '[]',
				'border_crossing'   => ! empty( $acrb_clean_border ) ? (string) wp_json_encode( array_values( $acrb_clean_border ) ) : '[]',
				'seasonal_rates'    => ! empty( $acrb_clean_seasonal ) ? (string) wp_json_encode( array_values( $acrb_clean_seasonal ) ) : '[]',
				'transmission'      => sanitize_text_field( wp_unslash( $_POST['acrb_transmission'] ?? 'Automatic' ) ),
				'fuel'              => sanitize_text_field( wp_unslash( $_POST['acrb_fuel_type'] ?? 'Petrol' ) ),
				'seats'             => max( 1, absint( $_POST['acrb_seating_capacity'] ?? 5 ) ),
				'bags'              => absint( $_POST['acrb_baggage_capacity'] ?? 2 ),
				'min_days'          => max( 1, absint( $_POST['acrb_min_rental_days'] ?? 1 ) ),
				'price'             => floatval( wp_unslash( $_POST['acrb_car_price'] ?? 0.00 ) ),
				'image'             => sanitize_text_field( wp_unslash( $_POST['acrb_car_thumbnail_id'] ?? '' ) ),
				'gallery'           => sanitize_text_field( wp_unslash( $_POST['acrb_car_gallery'] ?? '' ) ),
				'video'             => esc_url_raw( wp_unslash( $_POST['acrb_car_video'] ?? '' ) ),
				'notes'             => sanitize_textarea_field( wp_unslash( $_POST['acrb_car_notes'] ?? '' ) ),
			);

			$acrb_data_formats = array(
				'%s', // name
				'%s', // brand
				'%d', // year
				'%s', // vin
				'%s', // plate
				'%s', // category
				'%s', // color
				'%s', // status
				'%s', // description
				'%s', // pickup_locations
				'%s', // dropoff_locations
				'%s', // insurance_plans
				'%s', // border_crossing
				'%s', // seasonal_rates
				'%s', // transmission
				'%s', // fuel
				'%d', // seats
				'%d', // bags
				'%d', // min_days
				'%f', // price
				'%s', // image
				'%s', // gallery
				'%s', // video
				'%s', // notes
			);

			$acrb_is_update = false;

			if ( $acrb_edit_car_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$acrb_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$acrb_table_cars} WHERE id = %d", $acrb_edit_car_id ) );
				if ( $acrb_exists ) {
					$acrb_is_update = true;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->update(
						$acrb_table_cars,
						$acrb_row_data,
						array( 'id' => $acrb_edit_car_id ),
						$acrb_data_formats,
						array( '%d' )
					);
				}
			}

			if ( ! $acrb_is_update ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert(
					$acrb_table_cars,
					$acrb_row_data,
					$acrb_data_formats
				);
				$acrb_edit_car_id = (int) $wpdb->insert_id;
			}

			delete_transient( 'acrb_dashboard_revenue' );

			$acrb_redirect_url = add_query_arg(
				array(
					$acrb_is_update ? 'updated' : 'created' => '1',
					'car_id'                                => $acrb_edit_car_id,
				),
				$acrb_list_url
			);

			if ( headers_sent() ) {
				echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $acrb_redirect_url ) ) . ');</script>';
				echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $acrb_redirect_url ) . '"></noscript>';
				exit;
			}

			wp_safe_redirect( $acrb_redirect_url );
			exit;
		}
	}
}

// 3. Stored Record Retrieval from `$wpdb`.
$acrb_car_record = null;
if ( $acrb_edit_car_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_car_record = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$acrb_table_cars} WHERE id = %d", $acrb_edit_car_id ),
		ARRAY_A
	);
}

// Extract Row Data with Safe Fallbacks.
$acrb_car_name          = (string) ( $acrb_car_record['name'] ?? '' );
$acrb_car_brand         = (string) ( $acrb_car_record['brand'] ?? '' );
$acrb_car_year          = absint( $acrb_car_record['year'] ?? 2026 );
$acrb_car_vin           = (string) ( $acrb_car_record['vin'] ?? '' );
$acrb_car_plate         = (string) ( $acrb_car_record['plate'] ?? '' );
$acrb_car_color         = (string) ( $acrb_car_record['color'] ?? '' );
$acrb_selected_category = (string) ( $acrb_car_record['category'] ?? '' );
$acrb_car_price         = (string) ( $acrb_car_record['price'] ?? '' );
$acrb_vehicle_status    = (string) ( $acrb_car_record['status'] ?? 'active' );
$acrb_car_desc          = (string) ( $acrb_car_record['description'] ?? '' );
$acrb_seating_cap       = absint( $acrb_car_record['seats'] ?? 5 );
$acrb_baggage_cap       = absint( $acrb_car_record['bags'] ?? 2 );
$acrb_transmission      = (string) ( $acrb_car_record['transmission'] ?? 'Automatic' );
$acrb_fuel_type         = (string) ( $acrb_car_record['fuel'] ?? 'Petrol' );
$acrb_min_rental_days   = absint( $acrb_car_record['min_days'] ?? 1 );
$acrb_thumbnail_id      = absint( $acrb_car_record['image'] ?? 0 );
$acrb_gallery_ids       = (string) ( $acrb_car_record['gallery'] ?? '' );
$acrb_video_url         = (string) ( $acrb_car_record['video'] ?? '' );
$acrb_car_notes         = (string) ( $acrb_car_record['notes'] ?? '' );

// JSON Decoded Arrays from Table Columns.
$acrb_saved_pickup_locs    = ! empty( $acrb_car_record['pickup_locations'] ) ? json_decode( (string) $acrb_car_record['pickup_locations'], true ) : array();
$acrb_saved_dropoff_locs   = ! empty( $acrb_car_record['dropoff_locations'] ) ? json_decode( (string) $acrb_car_record['dropoff_locations'], true ) : array();
$acrb_saved_insurance_meta = ! empty( $acrb_car_record['insurance_plans'] ) ? json_decode( (string) $acrb_car_record['insurance_plans'], true ) : array();
$acrb_saved_border_meta    = ! empty( $acrb_car_record['border_crossing'] ) ? json_decode( (string) $acrb_car_record['border_crossing'], true ) : array();
$acrb_seasonal_rates       = ! empty( $acrb_car_record['seasonal_rates'] ) ? json_decode( (string) $acrb_car_record['seasonal_rates'], true ) : array();

if ( ! is_array( $acrb_saved_pickup_locs ) ) {
	$acrb_saved_pickup_locs = array();
}
if ( ! is_array( $acrb_saved_dropoff_locs ) ) {
	$acrb_saved_dropoff_locs = array();
}
if ( ! is_array( $acrb_saved_insurance_meta ) ) {
	$acrb_saved_insurance_meta = array();
}
if ( ! is_array( $acrb_saved_border_meta ) ) {
	$acrb_saved_border_meta = array();
}
if ( ! is_array( $acrb_seasonal_rates ) ) {
	$acrb_seasonal_rates = array();
}
?>

<style>
	.acrb-car-form-wrapper {
		max-width: 960px;
		box-sizing: border-box;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		color: #1e293b;
	}
	.acrb-car-form-wrapper *,
	.acrb-car-form-wrapper *::before,
	.acrb-car-form-wrapper *::after {
		box-sizing: border-box;
	}

	/* Error Alert */
	.acrb-car-alert-error {
		background: #fef2f2;
		border: 1px solid #fecaca;
		color: #991b1b;
		padding: 12px 18px;
		border-radius: 10px;
		font-size: 13.5px;
		font-weight: 600;
		margin-bottom: 20px;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	/* Hero Car Name & Description Input Card */
	.acrb-car-hero-card {
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		padding: 22px 24px;
		margin-bottom: 20px;
		box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
	}
	.acrb-car-hero-label {
		display: block;
		text-transform: uppercase;
		font-size: 11px;
		letter-spacing: 0.06em;
		font-weight: 800;
		color: #64748b;
		margin-bottom: 8px;
	}
	.acrb-car-field-hero {
		width: 100% !important;
		font-size: 18px !important;
		font-weight: 800 !important;
		color: #0f172a !important;
		height: 48px !important;
		background: #f8fafc !important;
		border: 1px solid #cbd5e1 !important;
		border-radius: 10px !important;
		padding: 0 16px !important;
		transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
	}
	.acrb-car-field-hero:focus {
		background: #ffffff !important;
		border-color: #4f46e5 !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
	}

	/* Description Textarea */
	.acrb-car-textarea {
		display: block;
		width: 100% !important;
		max-width: 100% !important;
		background: #f8fafc !important;
		border: 1px solid #cbd5e1 !important;
		border-radius: 8px !important;
		padding: 12px 14px !important;
		font-size: 13.5px !important;
		font-family: inherit !important;
		color: #1e293b !important;
		line-height: 1.6 !important;
		resize: vertical !important;
		min-height: 110px !important;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
		transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
	}
	.acrb-car-textarea:hover {
		border-color: #94a3b8 !important;
		background: #ffffff !important;
	}
	.acrb-car-textarea:focus {
		background: #ffffff !important;
		border-color: #4f46e5 !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
	}

	/* Accordion Panels */
	.acrb-car-panel {
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		margin-bottom: 16px;
		box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
		overflow: hidden;
		transition: border-color 0.15s ease;
	}
	.acrb-car-panel:hover {
		border-color: #cbd5e1;
	}
	.acrb-car-panel-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 16px 22px;
		background: #ffffff;
		cursor: pointer;
		user-select: none;
		border-bottom: 1px solid transparent;
		transition: background-color 0.15s ease;
	}
	.acrb-car-panel-head:hover {
		background: #f8fafc;
	}
	.acrb-car-panel-head-title {
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 14.5px;
		font-weight: 800;
		color: #0f172a;
		margin: 0;
	}
	.acrb-car-panel-head-title .dashicons {
		color: #4f46e5;
		font-size: 18px;
		width: 18px;
		height: 18px;
	}
	.acrb-car-panel-chevron {
		color: #64748b;
		font-size: 18px;
		width: 18px;
		height: 18px;
		transition: transform 0.2s ease;
	}
	.acrb-car-panel.is-open .acrb-car-panel-chevron {
		transform: rotate(180deg);
	}
	.acrb-car-panel.is-open .acrb-car-panel-head {
		border-bottom-color: #f1f5f9;
		background: #fafbff;
	}
	.acrb-car-panel-body {
		padding: 22px;
		display: none;
	}
	.acrb-car-panel.is-open .acrb-car-panel-body {
		display: block;
	}

	/* Grids & Form Controls */
	.acrb-car-grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 18px;
	}
	.acrb-car-grid-3 {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 16px;
	}
	.acrb-car-grid-4 {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 16px;
	}
	.acrb-car-form-group {
		display: flex;
		flex-direction: column;
		margin-bottom: 16px;
	}
	.acrb-car-form-group:last-child {
		margin-bottom: 0;
	}
	.acrb-car-label {
		display: block;
		font-weight: 700;
		color: #1e293b;
		font-size: 13px;
		margin-bottom: 6px;
	}

	.acrb-car-input,
	.acrb-car-select {
		display: block;
		width: 100% !important;
		max-width: 100% !important;
		height: 42px !important;
		background: #f8fafc !important;
		border: 1px solid #cbd5e1 !important;
		border-radius: 8px !important;
		padding: 0 14px !important;
		font-size: 13.5px !important;
		font-family: inherit !important;
		color: #1e293b !important;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
		transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
		line-height: 40px !important;
	}
	.acrb-car-input:hover,
	.acrb-car-select:hover {
		border-color: #94a3b8 !important;
		background: #ffffff !important;
	}
	.acrb-car-input:focus,
	.acrb-car-select:focus {
		background: #ffffff !important;
		border-color: #4f46e5 !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
	}

	/* Select Chevron Wrap */
	.acrb-car-select-wrap {
		position: relative;
		width: 100%;
	}
	.acrb-car-select-wrap select {
		appearance: none;
		-webkit-appearance: none;
		padding-right: 36px !important;
		cursor: pointer;
	}
	.acrb-car-select-arrow {
		position: absolute;
		right: 12px;
		top: 50%;
		transform: translateY(-50%);
		pointer-events: none;
		color: #64748b;
		font-size: 18px;
		width: 18px;
		height: 18px;
	}

	/* Location Subtitle */
	.acrb-location-group-title {
		font-size: 13px;
		font-weight: 800;
		color: #334155;
		margin-bottom: 10px;
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.acrb-location-group-title .dashicons {
		color: #4f46e5;
		font-size: 16px;
		width: 16px;
		height: 16px;
	}

	/* Seasonal Rate Rows */
	.acrb-seasonal-row {
		display: grid;
		grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr 40px;
		gap: 10px;
		align-items: center;
		background: #f8fafc;
		padding: 12px;
		border-radius: 10px;
		border: 1px solid #e2e8f0;
		margin-bottom: 12px;
	}
	.acrb-btn-remove-row {
		height: 42px;
		background: #fee2e2;
		color: #b91c1c;
		border: 1px solid #fecaca;
		border-radius: 8px;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 16px;
		font-weight: 700;
		transition: background 0.15s ease;
	}
	.acrb-btn-remove-row:hover {
		background: #fca5a5;
	}

	/* Cover & Gallery Previews */
	.acrb-thumb-box {
		background: #f8fafc;
		margin-bottom: 12px;
		border-radius: 10px;
		overflow: hidden;
		min-height: 150px;
		border: 1px solid #e2e8f0;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.acrb-thumb-box img {
		width: 100%;
		height: auto;
		display: block;
		object-fit: cover;
	}
	.acrb-gallery-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
		gap: 12px;
		margin-top: 14px;
	}
	.acrb-gallery-item {
		position: relative;
		aspect-ratio: 1;
		border-radius: 8px;
		overflow: hidden;
		border: 1px solid #cbd5e1;
		background: #f8fafc;
	}
	.acrb-gallery-item img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.acrb-gallery-remove {
		position: absolute;
		top: 4px;
		right: 4px;
		background: rgba(220, 38, 38, 0.9);
		color: #ffffff;
		border-radius: 50%;
		width: 20px;
		height: 20px;
		font-size: 13px;
		line-height: 20px;
		text-align: center;
		cursor: pointer;
		font-weight: 800;
		transition: transform 0.1s ease;
	}
	.acrb-gallery-remove:hover {
		transform: scale(1.1);
	}

	/* Switch Component Card */
	.acrb-toggle-card {
		display: flex;
		align-items: center;
		justify-content: space-between;
		background: #f8fafc;
		padding: 12px 16px;
		border-radius: 10px;
		border: 1px solid #e2e8f0;
		margin-bottom: 8px;
		transition: border-color 0.15s ease;
	}
	.acrb-toggle-card:last-child {
		margin-bottom: 0;
	}
	.acrb-toggle-card:hover {
		border-color: #cbd5e1;
	}
	.acrb-toggle-card-info {
		display: flex;
		align-items: center;
		gap: 12px;
	}
	.acrb-switch-ui {
		position: relative;
		display: inline-block;
		width: 44px;
		height: 24px;
		flex-shrink: 0;
	}
	.acrb-switch-ui input {
		opacity: 0;
		width: 0;
		height: 0;
	}
	.acrb-switch-slider {
		position: absolute;
		cursor: pointer;
		inset: 0;
		background-color: #cbd5e1;
		transition: .25s ease;
		border-radius: 24px;
	}
	.acrb-switch-slider:before {
		position: absolute;
		content: "";
		height: 18px;
		width: 18px;
		left: 3px;
		bottom: 3px;
		background-color: white;
		transition: .25s ease;
		border-radius: 50%;
		box-shadow: 0 1px 3px rgba(0,0,0,0.15);
	}
	input:checked + .acrb-switch-slider {
		background-color: #4f46e5;
	}
	input:checked + .acrb-switch-slider:before {
		transform: translateX(20px);
	}

	.acrb-rate-badge {
		font-size: 12px;
		font-weight: 700;
		color: #4f46e5;
		background: #eef2ff;
		padding: 4px 10px;
		border-radius: 6px;
		border: 1px solid #c7d2fe;
		white-space: nowrap;
	}

	/* Buttons */
	.acrb-btn-secondary {
		background: #ffffff;
		color: #475569;
		border: 1px solid #cbd5e1;
		padding: 9px 16px;
		font-size: 13px;
		font-weight: 700;
		border-radius: 8px;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 6px;
		transition: all 0.15s ease;
	}
	.acrb-btn-secondary:hover {
		background: #f8fafc;
		color: #0f172a;
		border-color: #94a3b8;
	}
	.acrb-car-footer-actions {
		margin-top: 24px;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		padding: 18px 24px;
		display: flex;
		justify-content: flex-end;
		align-items: center;
		gap: 12px;
		box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
	}
	.acrb-car-btn-submit {
		background: #4f46e5;
		color: #ffffff;
		border: none;
		padding: 12px 28px;
		font-size: 13.5px;
		font-weight: 700;
		border-radius: 8px;
		cursor: pointer;
		box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
		transition: background 0.15s ease, transform 0.15s ease;
		display: inline-flex;
		align-items: center;
		gap: 8px;
	}
	.acrb-car-btn-submit:hover {
		background: #4338ca;
		transform: translateY(-1px);
	}
	.acrb-car-btn-cancel {
		color: #64748b;
		text-decoration: none;
		font-weight: 600;
		font-size: 13px;
		padding: 8px 14px;
		border-radius: 8px;
		transition: color 0.15s ease;
	}
	.acrb-car-btn-cancel:hover {
		color: #0f172a;
	}

	@media (max-width: 820px) {
		.acrb-car-grid-2,
		.acrb-car-grid-3,
		.acrb-car-grid-4 {
			grid-template-columns: 1fr;
		}
		.acrb-seasonal-row {
			grid-template-columns: 1fr;
		}
	}
</style>

<div class="acrb-car-form-wrapper">

	<?php if ( ! empty( $acrb_error_notice ) ) : ?>
		<div class="acrb-car-alert-error">
			<span class="dashicons dashicons-warning"></span>
			<span><?php echo esc_html( $acrb_error_notice ); ?></span>
		</div>
	<?php endif; ?>

	<form method="post" id="acrb-car-form">
		<?php wp_nonce_field( 'acrb_save_car_nonce', 'acrb_car_nonce' ); ?>

		<!-- Hero Car Name Box -->
		<div class="acrb-car-hero-card">
			<label class="acrb-car-hero-label" for="acrb_car_name"><?php esc_html_e( 'Car Name', 'awesome-car-rental' ); ?></label>
			<input type="text" id="acrb_car_name" name="acrb_car_name" class="acrb-car-field-hero" value="<?php echo esc_attr( $acrb_car_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. BMW 5 Series / Tesla Model 3', 'awesome-car-rental' ); ?>" required>
		</div>

		<!-- Section 1: Price & Category -->
		<div class="acrb-car-panel is-open">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-tag"></span>
					<span><?php esc_html_e( 'Price & Category', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<div class="acrb-car-grid-2">
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_car_price">
							<?php
							// translators: %s: Currency symbol
							printf( esc_html__( 'Daily Price (%s)', 'awesome-car-rental' ), esc_html( $acrb_currency_symbol ) );
							?>
						</label>
						<input type="number" step="0.01" min="0" id="acrb_car_price" name="acrb_car_price" class="acrb-car-input" value="<?php echo esc_attr( $acrb_car_price ); ?>" placeholder="0.00" required>
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_car_category"><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></label>
						<div class="acrb-car-select-wrap">
							<select id="acrb_car_category" name="acrb_car_category" class="acrb-car-select">
								<option value=""><?php esc_html_e( 'Select Category', 'awesome-car-rental' ); ?></option>
								<?php foreach ( $acrb_categories_list as $acrb_cat_row ) : ?>
									<option value="<?php echo esc_attr( $acrb_cat_row['name'] ); ?>" <?php selected( $acrb_selected_category, $acrb_cat_row['name'] ); ?>>
										<?php echo esc_html( $acrb_cat_row['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Section 2: Fleet Identifiers (Brand, Year, VIN, Plate) -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-id"></span>
					<span><?php esc_html_e( 'Fleet Identifiers & Registry', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<div class="acrb-car-grid-4">
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_car_brand"><?php esc_html_e( 'Make / Brand', 'awesome-car-rental' ); ?></label>
						<input type="text" id="acrb_car_brand" name="acrb_car_brand" class="acrb-car-input" value="<?php echo esc_attr( $acrb_car_brand ); ?>" placeholder="<?php esc_attr_e( 'e.g. BMW, Toyota', 'awesome-car-rental' ); ?>">
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_car_year"><?php esc_html_e( 'Model Year', 'awesome-car-rental' ); ?></label>
						<input type="number" id="acrb_car_year" name="acrb_car_year" class="acrb-car-input" value="<?php echo esc_attr( (string) $acrb_car_year ); ?>" min="1990" max="2035">
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_car_plate"><?php esc_html_e( 'License Plate', 'awesome-car-rental' ); ?></label>
						<input type="text" id="acrb_car_plate" name="acrb_car_plate" class="acrb-car-input" value="<?php echo esc_attr( $acrb_car_plate ); ?>" placeholder="<?php esc_attr_e( 'e.g. NYC-4821', 'awesome-car-rental' ); ?>">
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_car_color"><?php esc_html_e( 'Color', 'awesome-car-rental' ); ?></label>
						<input type="text" id="acrb_car_color" name="acrb_car_color" class="acrb-car-input" value="<?php echo esc_attr( $acrb_car_color ); ?>" placeholder="<?php esc_attr_e( 'e.g. Metallic Black', 'awesome-car-rental' ); ?>">
					</div>
				</div>
				<div class="acrb-car-form-group" style="margin-top: 14px;">
					<label class="acrb-car-label" for="acrb_car_vin"><?php esc_html_e( 'VIN Identifier', 'awesome-car-rental' ); ?></label>
					<input type="text" id="acrb_car_vin" name="acrb_car_vin" class="acrb-car-input" value="<?php echo esc_attr( $acrb_car_vin ); ?>" placeholder="<?php esc_attr_e( '17-character VIN', 'awesome-car-rental' ); ?>" style="font-family: ui-monospace, SFMono-Regular, monospace;">
				</div>
			</div>
		</div>

		<!-- Section 3: Description -->
		<div class="acrb-car-panel is-open">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-editor-alignleft"></span>
					<span><?php esc_html_e( 'Description', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<div class="acrb-car-form-group">
					<label class="acrb-car-label" for="acrb_car_desc"><?php esc_html_e( 'Vehicle Overview & Rental Terms', 'awesome-car-rental' ); ?></label>
					<textarea id="acrb_car_desc" name="acrb_car_desc" class="acrb-car-textarea" placeholder="<?php esc_attr_e( 'Write vehicle specifications, features overview, rental highlights, or guidelines for customers...', 'awesome-car-rental' ); ?>" rows="5"><?php echo esc_textarea( $acrb_car_desc ); ?></textarea>
				</div>
			</div>
		</div>

		<!-- Section 4: Specifications -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-performance"></span>
					<span><?php esc_html_e( 'Specifications', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<div class="acrb-car-grid-3" style="margin-bottom: 16px;">
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_vehicle_status"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
						<div class="acrb-car-select-wrap">
							<select id="acrb_vehicle_status" name="acrb_vehicle_status" class="acrb-car-select">
								<?php foreach ( $acrb_status_options as $acrb_s_key => $acrb_s_label ) : ?>
									<option value="<?php echo esc_attr( $acrb_s_key ); ?>" <?php selected( $acrb_vehicle_status, $acrb_s_key ); ?>>
										<?php echo esc_html( $acrb_s_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
						</div>
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_seating_capacity"><?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></label>
						<input type="number" id="acrb_seating_capacity" name="acrb_seating_capacity" class="acrb-car-input" value="<?php echo esc_attr( (string) $acrb_seating_cap ); ?>" min="1" max="50">
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_baggage_capacity"><?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></label>
						<input type="number" id="acrb_baggage_capacity" name="acrb_baggage_capacity" class="acrb-car-input" value="<?php echo esc_attr( (string) $acrb_baggage_cap ); ?>" min="0" max="20">
					</div>
				</div>

				<div class="acrb-car-grid-3">
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_transmission"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></label>
						<div class="acrb-car-select-wrap">
							<select id="acrb_transmission" name="acrb_transmission" class="acrb-car-select">
								<option value="Automatic" <?php selected( $acrb_transmission, 'Automatic' ); ?>><?php esc_html_e( 'Automatic', 'awesome-car-rental' ); ?></option>
								<option value="Manual" <?php selected( $acrb_transmission, 'Manual' ); ?>><?php esc_html_e( 'Manual', 'awesome-car-rental' ); ?></option>
							</select>
							<span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
						</div>
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_fuel_type"><?php esc_html_e( 'Fuel Type', 'awesome-car-rental' ); ?></label>
						<div class="acrb-car-select-wrap">
							<select id="acrb_fuel_type" name="acrb_fuel_type" class="acrb-car-select">
								<option value="Petrol" <?php selected( $acrb_fuel_type, 'Petrol' ); ?>><?php esc_html_e( 'Petrol', 'awesome-car-rental' ); ?></option>
								<option value="Diesel" <?php selected( $acrb_fuel_type, 'Diesel' ); ?>><?php esc_html_e( 'Diesel', 'awesome-car-rental' ); ?></option>
								<option value="Electric" <?php selected( $acrb_fuel_type, 'Electric' ); ?>><?php esc_html_e( 'Electric', 'awesome-car-rental' ); ?></option>
								<option value="Hybrid" <?php selected( $acrb_fuel_type, 'Hybrid' ); ?>><?php esc_html_e( 'Hybrid', 'awesome-car-rental' ); ?></option>
							</select>
							<span class="dashicons dashicons-arrow-down-alt2 acrb-car-select-arrow"></span>
						</div>
					</div>
					<div class="acrb-car-form-group">
						<label class="acrb-car-label" for="acrb_min_rental_days"><?php esc_html_e( 'Minimum Days', 'awesome-car-rental' ); ?></label>
						<input type="number" id="acrb_min_rental_days" name="acrb_min_rental_days" class="acrb-car-input" value="<?php echo esc_attr( (string) $acrb_min_rental_days ); ?>" min="1">
					</div>
				</div>
			</div>
		</div>

		<!-- Section 5: Locations (Auto-loaded from Extras) -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-location-alt"></span>
					<span><?php esc_html_e( 'Locations', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">
					<?php esc_html_e( 'Enable which pickup and drop-off hubs this vehicle operates from.', 'awesome-car-rental' ); ?>
				</p>
				<div class="acrb-car-grid-2">
					<div>
						<div class="acrb-location-group-title">
							<span class="dashicons dashicons-location"></span>
							<span><?php esc_html_e( 'Available Pickup Locations', 'awesome-car-rental' ); ?></span>
						</div>
						<?php if ( ! empty( $acrb_pickup_locations ) ) : ?>
							<?php
							foreach ( $acrb_pickup_locations as $acrb_p_idx => $acrb_p_loc ) :
								$acrb_p_name    = $acrb_p_loc['name'];
								$acrb_p_icon    = $acrb_p_loc['icon'];
								$acrb_p_enabled = 1;
								foreach ( $acrb_saved_pickup_locs as $acrb_saved_p ) {
									if ( ( $acrb_saved_p['name'] ?? '' ) === $acrb_p_name ) {
										$acrb_p_enabled = $acrb_saved_p['enabled'] ?? 0;
										break;
									}
								}
								?>
								<div class="acrb-toggle-card">
									<div class="acrb-toggle-card-info">
										<label class="acrb-switch-ui">
											<input type="checkbox" name="assigned_pickup_locs[<?php echo esc_attr( (string) $acrb_p_idx ); ?>][enabled]" value="1" <?php checked( $acrb_p_enabled, 1 ); ?>>
											<span class="acrb-switch-slider"></span>
										</label>
										<span class="dashicons <?php echo esc_attr( $acrb_p_icon ); ?>" style="color:#64748b; font-size:17px; width:17px; height:17px;"></span>
										<span style="font-size:13px; font-weight:700; color:#334155;"><?php echo esc_html( $acrb_p_name ); ?></span>
									</div>
									<input type="hidden" name="assigned_pickup_locs[<?php echo esc_attr( (string) $acrb_p_idx ); ?>][name]" value="<?php echo esc_attr( $acrb_p_name ); ?>">
									<input type="hidden" name="assigned_pickup_locs[<?php echo esc_attr( (string) $acrb_p_idx ); ?>][icon]" value="<?php echo esc_attr( $acrb_p_icon ); ?>">
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p style="color: #64748b; font-style: italic; margin: 0;"><?php esc_html_e( 'No pickup locations configured in Extras.', 'awesome-car-rental' ); ?></p>
						<?php endif; ?>
					</div>

					<div>
						<div class="acrb-location-group-title">
							<span class="dashicons dashicons-location-alt"></span>
							<span><?php esc_html_e( 'Available Drop-off Locations', 'awesome-car-rental' ); ?></span>
						</div>
						<?php if ( ! empty( $acrb_dropoff_locations ) ) : ?>
							<?php
							foreach ( $acrb_dropoff_locations as $acrb_d_idx => $acrb_d_loc ) :
								$acrb_d_name    = $acrb_d_loc['name'];
								$acrb_d_icon    = $acrb_d_loc['icon'];
								$acrb_d_enabled = 1;
								foreach ( $acrb_saved_dropoff_locs as $acrb_saved_d ) {
									if ( ( $acrb_saved_d['name'] ?? '' ) === $acrb_d_name ) {
										$acrb_d_enabled = $acrb_saved_d['enabled'] ?? 0;
										break;
									}
								}
								?>
								<div class="acrb-toggle-card">
									<div class="acrb-toggle-card-info">
										<label class="acrb-switch-ui">
											<input type="checkbox" name="assigned_dropoff_locs[<?php echo esc_attr( (string) $acrb_d_idx ); ?>][enabled]" value="1" <?php checked( $acrb_d_enabled, 1 ); ?>>
											<span class="acrb-switch-slider"></span>
										</label>
										<span class="dashicons <?php echo esc_attr( $acrb_d_icon ); ?>" style="color:#64748b; font-size:17px; width:17px; height:17px;"></span>
										<span style="font-size:13px; font-weight:700; color:#334155;"><?php echo esc_html( $acrb_d_name ); ?></span>
									</div>
									<input type="hidden" name="assigned_dropoff_locs[<?php echo esc_attr( (string) $acrb_d_idx ); ?>][name]" value="<?php echo esc_attr( $acrb_d_name ); ?>">
									<input type="hidden" name="assigned_dropoff_locs[<?php echo esc_attr( (string) $acrb_d_idx ); ?>][icon]" value="<?php echo esc_attr( $acrb_d_icon ); ?>">
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p style="color: #64748b; font-style: italic; margin: 0;"><?php esc_html_e( 'No drop-off locations configured in Extras.', 'awesome-car-rental' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Section 6: Seasonal Rates -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-calendar-alt"></span>
					<span><?php esc_html_e( 'Seasonal Rates', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">
					<?php esc_html_e( 'Set custom daily rates for specific dates or seasons.', 'awesome-car-rental' ); ?>
				</p>
				<div id="acrb-seasonal-container">
					<?php if ( ! empty( $acrb_seasonal_rates ) && is_array( $acrb_seasonal_rates ) ) : ?>
						<?php foreach ( $acrb_seasonal_rates as $acrb_index => $acrb_rate ) : ?>
							<div class="acrb-seasonal-row">
								<input type="text" name="seasonal_rates[<?php echo esc_attr( (string) $acrb_index ); ?>][name]" class="acrb-car-input" placeholder="<?php esc_attr_e( 'Season Name', 'awesome-car-rental' ); ?>" value="<?php echo esc_attr( $acrb_rate['name'] ?? '' ); ?>">
								<input type="date" name="seasonal_rates[<?php echo esc_attr( (string) $acrb_index ); ?>][start]" class="acrb-car-input" value="<?php echo esc_attr( $acrb_rate['start'] ?? '' ); ?>">
								<input type="date" name="seasonal_rates[<?php echo esc_attr( (string) $acrb_index ); ?>][end]" class="acrb-car-input" value="<?php echo esc_attr( $acrb_rate['end'] ?? '' ); ?>">
								<?php
								// translators: %s: Currency symbol
								$acrb_rate_placeholder = sprintf( __( 'Price (%s)', 'awesome-car-rental' ), $acrb_currency_symbol );
								?>
								<input type="number" step="0.01" name="seasonal_rates[<?php echo esc_attr( (string) $acrb_index ); ?>][price]" class="acrb-car-input" placeholder="<?php echo esc_attr( $acrb_rate_placeholder ); ?>" value="<?php echo esc_attr( $acrb_rate['price'] ?? '' ); ?>">
								<button type="button" class="acrb-btn-remove-row" onclick="jQuery(this).closest('.acrb-seasonal-row').fadeOut(200, function(){jQuery(this).remove();});">&times;</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<button type="button" class="acrb-btn-secondary" id="acrb-add-seasonal-btn">
					<span class="dashicons dashicons-plus-alt2"></span>
					<span><?php esc_html_e( 'Add Rate', 'awesome-car-rental' ); ?></span>
				</button>
			</div>
		</div>

		<!-- Section 7: Photos & Video -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-format-image"></span>
					<span><?php esc_html_e( 'Photos & Video', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<div class="acrb-car-grid-2" style="margin-bottom: 22px;">
					<div>
						<label class="acrb-car-label"><?php esc_html_e( 'Cover Photo', 'awesome-car-rental' ); ?></label>
						<div id="acrb-thumb-preview" class="acrb-thumb-box">
							<?php
							if ( $acrb_thumbnail_id ) {
								echo wp_get_attachment_image( $acrb_thumbnail_id, 'medium', false, array( 'class' => 'acrb-thumb-img' ) );
							} else {
								echo '<span class="dashicons dashicons-car" style="font-size:48px; width:48px; height:48px; color:#cbd5e1;"></span>';
							}
							?>
						</div>
						<input type="hidden" name="acrb_car_thumbnail_id" id="acrb_car_thumbnail_id" value="<?php echo esc_attr( (string) $acrb_thumbnail_id ); ?>">
						<button type="button" class="acrb-btn-secondary" id="acrb_set_thumb">
							<span class="dashicons dashicons-admin-media"></span>
							<span><?php esc_html_e( 'Choose Cover Photo', 'awesome-car-rental' ); ?></span>
						</button>
					</div>

					<div>
						<div class="acrb-car-form-group">
							<label class="acrb-car-label" for="acrb_car_video"><?php esc_html_e( 'Video Link', 'awesome-car-rental' ); ?></label>
							<input type="url" id="acrb_car_video" name="acrb_car_video" class="acrb-car-input" value="<?php echo esc_url( $acrb_video_url ); ?>" placeholder="https://youtube.com/watch?v=...">
						</div>
					</div>
				</div>

				<div>
					<label class="acrb-car-label"><?php esc_html_e( 'Gallery Photos', 'awesome-car-rental' ); ?></label>
					<input type="hidden" name="acrb_car_gallery" id="acrb_gallery_ids" value="<?php echo esc_attr( (string) $acrb_gallery_ids ); ?>">
					<button type="button" id="acrb_manage_gallery" class="acrb-btn-secondary">
						<span class="dashicons dashicons-images-alt2"></span>
						<span><?php esc_html_e( 'Add Photos', 'awesome-car-rental' ); ?></span>
					</button>

					<div id="acrb_gallery_preview" class="acrb-gallery-grid">
						<?php
						if ( $acrb_gallery_ids ) {
							$acrb_gallery_id_list = explode( ',', $acrb_gallery_ids );
							foreach ( $acrb_gallery_id_list as $acrb_single_id ) {
								$acrb_img_src_data = wp_get_attachment_image_src( (int) $acrb_single_id, 'thumbnail' );
								if ( $acrb_img_src_data ) {
									echo '<div class="acrb-gallery-item" data-id="' . esc_attr( (string) $acrb_single_id ) . '">
											<span class="acrb-gallery-remove">&times;</span>
											<img src="' . esc_url( $acrb_img_src_data[0] ) . '" alt="">
										  </div>';
								}
							}
						}
						?>
					</div>
				</div>
			</div>
		</div>

		<!-- Section 8: Insurance Plans (Auto-loaded from Extras) -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-shield"></span>
					<span><?php esc_html_e( 'Insurance Plans', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">
					<?php esc_html_e( 'Enable available insurance and protection plans for this vehicle.', 'awesome-car-rental' ); ?>
				</p>
				<div class="acrb-car-grid-2">
					<?php if ( ! empty( $acrb_saved_insurance_plans ) ) : ?>
						<?php
						foreach ( $acrb_saved_insurance_plans as $acrb_ins_idx => $acrb_ins_item ) :
							$acrb_ins_name    = $acrb_ins_item['name'] ?? '';
							$acrb_ins_price   = $acrb_ins_item['price'] ?? '0.00';
							$acrb_ins_type    = ( $acrb_ins_item['type'] ?? 'day' ) === 'flat' ? __( 'Flat', 'awesome-car-rental' ) : __( 'Day', 'awesome-car-rental' );
							$acrb_ins_enabled = 1;

							foreach ( $acrb_saved_insurance_meta as $acrb_saved_ins ) {
								if ( ( $acrb_saved_ins['name'] ?? '' ) === $acrb_ins_name ) {
									$acrb_ins_enabled = $acrb_saved_ins['enabled'] ?? 0;
									break;
								}
							}
							?>
							<div class="acrb-toggle-card">
								<div class="acrb-toggle-card-info">
									<label class="acrb-switch-ui">
										<input type="checkbox" name="car_insurance[<?php echo esc_attr( (string) $acrb_ins_idx ); ?>][enabled]" value="1" <?php checked( $acrb_ins_enabled, 1 ); ?>>
										<span class="acrb-switch-slider"></span>
									</label>
									<div>
										<span style="font-size:13px; font-weight:700; color:#334155; display:block;"><?php echo esc_html( $acrb_ins_name ); ?></span>
										<span style="font-size:11.5px; color:#64748b;"><?php echo esc_html( $acrb_currency_symbol . $acrb_ins_price . ' / ' . $acrb_ins_type ); ?></span>
									</div>
								</div>
								<input type="hidden" name="car_insurance[<?php echo esc_attr( (string) $acrb_ins_idx ); ?>][name]" value="<?php echo esc_attr( $acrb_ins_name ); ?>">
								<input type="hidden" name="car_insurance[<?php echo esc_attr( (string) $acrb_ins_idx ); ?>][price]" value="<?php echo esc_attr( (string) $acrb_ins_price ); ?>">
								<input type="hidden" name="car_insurance[<?php echo esc_attr( (string) $acrb_ins_idx ); ?>][type]" value="<?php echo esc_attr( $acrb_ins_item['type'] ?? 'day' ); ?>">
								<span class="acrb-rate-badge"><?php echo esc_html( $acrb_currency_symbol . $acrb_ins_price ); ?></span>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p style="color: #64748b; font-style: italic; margin: 0;"><?php esc_html_e( 'No insurance plans configured yet in Extras.', 'awesome-car-rental' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Section 9: Border Cross Permits (Auto-loaded from Extras) -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-admin-network"></span>
					<span><?php esc_html_e( 'Border Crossing', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">
					<?php esc_html_e( 'Enable eligible cross-border travel destinations and permits for this car.', 'awesome-car-rental' ); ?>
				</p>
				<div class="acrb-car-grid-2">
					<?php if ( ! empty( $acrb_saved_border_cross_fees ) ) : ?>
						<?php
						foreach ( $acrb_saved_border_cross_fees as $acrb_border_idx => $acrb_border_item ) :
							$acrb_country_name   = $acrb_border_item['country'] ?? '';
							$acrb_border_fee     = $acrb_border_item['fee'] ?? '0.00';
							$acrb_fee_type       = ( $acrb_border_item['type'] ?? 'flat' ) === 'day' ? __( 'Day', 'awesome-car-rental' ) : __( 'Flat', 'awesome-car-rental' );
							$acrb_border_enabled = 1;

							foreach ( $acrb_saved_border_meta as $acrb_saved_border ) {
								if ( ( $acrb_saved_border['country'] ?? '' ) === $acrb_country_name ) {
									$acrb_border_enabled = $acrb_saved_border['enabled'] ?? 0;
									break;
								}
							}
							?>
							<div class="acrb-toggle-card">
								<div class="acrb-toggle-card-info">
									<label class="acrb-switch-ui">
										<input type="checkbox" name="car_border_cross[<?php echo esc_attr( (string) $acrb_border_idx ); ?>][enabled]" value="1" <?php checked( $acrb_border_enabled, 1 ); ?>>
										<span class="acrb-switch-slider"></span>
									</label>
									<div>
										<span style="font-size:13px; font-weight:700; color:#334155; display:block;"><?php echo esc_html( $acrb_country_name ); ?></span>
										<span style="font-size:11.5px; color:#64748b;"><?php echo esc_html( $acrb_currency_symbol . $acrb_border_fee . ' / ' . $acrb_fee_type ); ?></span>
									</div>
								</div>
								<input type="hidden" name="car_border_cross[<?php echo esc_attr( (string) $acrb_border_idx ); ?>][country]" value="<?php echo esc_attr( $acrb_country_name ); ?>">
								<input type="hidden" name="car_border_cross[<?php echo esc_attr( (string) $acrb_border_idx ); ?>][fee]" value="<?php echo esc_attr( (string) $acrb_border_fee ); ?>">
								<input type="hidden" name="car_border_cross[<?php echo esc_attr( (string) $acrb_border_idx ); ?>][type]" value="<?php echo esc_attr( $acrb_border_item['type'] ?? 'flat' ); ?>">
								<span class="acrb-rate-badge"><?php echo esc_html( $acrb_currency_symbol . $acrb_border_fee ); ?></span>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p style="color: #64748b; font-style: italic; margin: 0;"><?php esc_html_e( 'No border crossing permits configured yet in Extras.', 'awesome-car-rental' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Section 10: Internal Notes -->
		<div class="acrb-car-panel">
			<div class="acrb-car-panel-head">
				<h3 class="acrb-car-panel-head-title">
					<span class="dashicons dashicons-clipboard"></span>
					<span><?php esc_html_e( 'Internal Staff Notes', 'awesome-car-rental' ); ?></span>
				</h3>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-car-panel-chevron"></span>
			</div>
			<div class="acrb-car-panel-body">
				<div class="acrb-car-form-group">
					<label class="acrb-car-label" for="acrb_car_notes"><?php esc_html_e( 'Service History, Damage Logs & Notes', 'awesome-car-rental' ); ?></label>
					<textarea id="acrb_car_notes" name="acrb_car_notes" class="acrb-car-textarea" placeholder="<?php esc_attr_e( 'Log internal mechanical notes, tire changes, or admin remarks...', 'awesome-car-rental' ); ?>" rows="3"><?php echo esc_textarea( $acrb_car_notes ); ?></textarea>
				</div>
			</div>
		</div>

		<!-- Submit Footer Bar -->
		<div class="acrb-car-footer-actions">
			<a href="<?php echo esc_url( $acrb_list_url ); ?>" class="acrb-car-btn-cancel">
				<?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
			</a>
			<button type="submit" class="acrb-car-btn-submit">
				<span class="dashicons dashicons-saved"></span>
				<span><?php echo $acrb_car_record ? esc_html__( 'Update Car', 'awesome-car-rental' ) : esc_html__( 'Save Car', 'awesome-car-rental' ); ?></span>
			</button>
		</div>
	</form>
</div>

<script>
jQuery(document).ready(function($){
	// Accordion Toggle Behavior
	$('.acrb-car-panel-head').on('click', function(){
		$(this).parent('.acrb-car-panel').toggleClass('is-open');
	});

	// Dynamic Seasonal Rate Rows
	$('#acrb-add-seasonal-btn').on('click', function(e){
		e.preventDefault();
		var index = $('#acrb-seasonal-container > div').length;
		<?php
		// translators: %s: Currency symbol
		$acrb_js_rate_placeholder = sprintf( __( 'Price (%s)', 'awesome-car-rental' ), $acrb_currency_symbol );
		?>
		var newRow = '<div class="acrb-seasonal-row">' +
			'<input type="text" name="seasonal_rates['+index+'][name]" class="acrb-car-input" placeholder="<?php echo esc_js( __( 'Season Name', 'awesome-car-rental' ) ); ?>">' +
			'<input type="date" name="seasonal_rates['+index+'][start]" class="acrb-car-input">' +
			'<input type="date" name="seasonal_rates['+index+'][end]" class="acrb-car-input">' +
			'<input type="number" step="0.01" name="seasonal_rates['+index+'][price]" class="acrb-car-input" placeholder="<?php echo esc_js( $acrb_js_rate_placeholder ); ?>">' +
			'<button type="button" class="acrb-btn-remove-row" onclick="jQuery(this).closest(\'.acrb-seasonal-row\').fadeOut(200, function(){jQuery(this).remove();});">&times;</button>' +
			'</div>';
		$('#acrb-seasonal-container').append(newRow);
	});

	// Thumbnail Selection Frame
	$('#acrb_set_thumb').on('click', function(e){
		e.preventDefault();
		var frame = wp.media({ 
			title: <?php echo wp_json_encode( __( 'Choose Cover Photo', 'awesome-car-rental' ) ); ?>, 
			button: { text: <?php echo wp_json_encode( __( 'Set Cover Photo', 'awesome-car-rental' ) ); ?> }, 
			multiple: false 
		});
		frame.on('select', function(){
			var attachment = frame.state().get('selection').first().toJSON();
			$('#acrb_car_thumbnail_id').val(attachment.id);
			$('#acrb-thumb-preview').html('<img src="' + attachment.url + '" style="width:100%;height:auto;display:block;">');
		}).open();
	});

	// Gallery Management Frame
	var galleryFrame;
	$('#acrb_manage_gallery').on('click', function(e){
		e.preventDefault();
		if (galleryFrame) { 
			galleryFrame.open(); 
			return; 
		}

		galleryFrame = wp.media({ 
			title: <?php echo wp_json_encode( __( 'Add Photos to Gallery', 'awesome-car-rental' ) ); ?>, 
			button: { text: <?php echo wp_json_encode( __( 'Add Photos', 'awesome-car-rental' ) ); ?> }, 
			multiple: true 
		});

		galleryFrame.on('select', function(){
			var selection = galleryFrame.state().get('selection');
			var currentIds = $('#acrb_gallery_ids').val() ? $('#acrb_gallery_ids').val().split(',') : [];

			selection.map(function(attachment){
				attachment = attachment.toJSON();
				var idStr = attachment.id.toString();
				if ($.inArray(idStr, currentIds) === -1) {
					currentIds.push(idStr);
					var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
					$('#acrb_gallery_preview').append(
						'<div class="acrb-gallery-item" data-id="' + idStr + '">' +
						'<span class="acrb-gallery-remove">&times;</span>' +
						'<img src="' + thumbUrl + '" alt="">' +
						'</div>'
					);
				}
			});
			$('#acrb_gallery_ids').val(currentIds.join(','));
		});
		galleryFrame.open();
	});

	// Remove Gallery Photo
	$('#acrb_gallery_preview').on('click', '.acrb-gallery-remove', function(){
		$(this).closest('.acrb-gallery-item').remove();
		var ids = [];
		$('#acrb_gallery_preview > .acrb-gallery-item').each(function(){
			ids.push($(this).data('id'));
		});
		$('#acrb_gallery_ids').val(ids.join(','));
	});
});
</script>