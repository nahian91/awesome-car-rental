<?php
/**
 * Add / Edit Inspection Component (`inspections-add-edit.php`)
 * Location: admin/inspections/inspections-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   2.6.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$acrb_table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$acrb_table_cars        = $wpdb->prefix . 'dev_nahian_cars';
$acrb_table_staff       = $wpdb->prefix . 'dev_nahian_staff';

// Read-only GET query parameters to fetch record state for editing.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_insp_id  = isset( $_GET['insp_id'] ) ? absint( wp_unslash( $_GET['insp_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$acrb_list_url = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections' );

// 1. Handle Form Submissions.
if ( isset( $_POST['acrb_save_inspection'] ) && check_admin_referer( 'acrb_insp_action', 'acrb_insp_nonce' ) ) {
	$acrb_inspection_id = isset( $_POST['insp_id'] ) ? absint( $_POST['insp_id'] ) : 0;
	$acrb_car_id        = isset( $_POST['insp_car_id'] ) ? absint( $_POST['insp_car_id'] ) : 0;
	$acrb_stage         = sanitize_text_field( wp_unslash( $_POST['insp_stage'] ?? 'Check-Out' ) );
	$acrb_insp_date     = sanitize_text_field( wp_unslash( $_POST['insp_date'] ?? current_time( 'Y-m-d H:i:s' ) ) );
	$acrb_mileage       = sanitize_text_field( wp_unslash( $_POST['insp_mileage'] ?? '' ) );
	$acrb_fuel_level    = sanitize_text_field( wp_unslash( $_POST['insp_fuel'] ?? 'Full (100%)' ) );
	$acrb_cleanliness   = sanitize_text_field( wp_unslash( $_POST['insp_cleanliness'] ?? 'Clean' ) );
	$acrb_damages       = sanitize_textarea_field( wp_unslash( $_POST['insp_damages'] ?? '' ) );
	$acrb_inspector     = sanitize_text_field( wp_unslash( $_POST['insp_inspector'] ?? '' ) );

	if ( $acrb_car_id > 0 ) {
		$acrb_car_name = '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$acrb_car_row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT name FROM {$acrb_table_cars} WHERE id = %d",
				$acrb_car_id
			),
			ARRAY_A
		);

		if ( $acrb_car_row && ! empty( $acrb_car_row['name'] ) ) {
			$acrb_car_name = $acrb_car_row['name'];
		} else {
			$acrb_post_car = get_post( $acrb_car_id );
			if ( $acrb_post_car ) {
				$acrb_car_name = $acrb_post_car->post_title;
			}
		}

		$acrb_data = array(
			'car_id'          => $acrb_car_id,
			'car_name'        => $acrb_car_name,
			'stage'           => $acrb_stage,
			'mileage'         => $acrb_mileage,
			'fuel_level'      => $acrb_fuel_level,
			'cleanliness'     => $acrb_cleanliness,
			'inspector'       => $acrb_inspector,
			'damages'         => $acrb_damages,
			'inspection_date' => $acrb_insp_date,
		);
		$acrb_formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		if ( $acrb_inspection_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $acrb_table_inspections, $acrb_data, array( 'id' => $acrb_inspection_id ), $acrb_formats, array( '%d' ) );
			$acrb_redirect_url = add_query_arg( array( 'updated' => '1' ), $acrb_list_url );
		} else {
			$acrb_data['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $acrb_table_inspections, $acrb_data, array_merge( $acrb_formats, array( '%s' ) ) );
			$acrb_redirect_url = add_query_arg( array( 'created' => '1' ), $acrb_list_url );
		}

		if ( headers_sent() ) {
			echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $acrb_redirect_url ) ) . ');</script>';
			echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $acrb_redirect_url ) . '"></noscript>';
			exit;
		}

		wp_safe_redirect( $acrb_redirect_url );
		exit;
	}
}

// 2. Fetch Existing Inspection Record.
$acrb_insp_record = null;
if ( $acrb_insp_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_insp_record = $wpdb->get_row(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$acrb_table_inspections} WHERE id = %d",
			$acrb_insp_id
		),
		ARRAY_A
	);
}

$acrb_i_car_id    = $acrb_insp_record ? absint( $acrb_insp_record['car_id'] ) : 0;
$acrb_i_stage     = $acrb_insp_record ? (string) $acrb_insp_record['stage'] : 'Check-Out';
$acrb_i_date      = $acrb_insp_record ? (string) $acrb_insp_record['inspection_date'] : current_time( 'Y-m-d\TH:i' );
$acrb_i_mileage   = $acrb_insp_record ? (string) $acrb_insp_record['mileage'] : '';
$acrb_i_fuel      = $acrb_insp_record ? (string) $acrb_insp_record['fuel_level'] : 'Full (100%)';
$acrb_i_clean     = $acrb_insp_record ? (string) $acrb_insp_record['cleanliness'] : 'Clean';
$acrb_i_damages   = $acrb_insp_record ? (string) $acrb_insp_record['damages'] : '';
$acrb_i_inspector = $acrb_insp_record ? (string) $acrb_insp_record['inspector'] : wp_get_current_user()->display_name;

// Fetch Vehicles.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_cars = $wpdb->get_results( "SELECT id, name FROM {$acrb_table_cars} ORDER BY name ASC", ARRAY_A );
if ( empty( $acrb_cars ) ) {
	$acrb_cars_posts = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$acrb_cars       = array();
	foreach ( $acrb_cars_posts as $acrb_cp ) {
		$acrb_cars[] = array( 'id' => $acrb_cp->ID, 'name' => $acrb_cp->post_title );
	}
}

// Fetch Staff.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_staff_members = $wpdb->get_results( "SELECT * FROM {$acrb_table_staff} ORDER BY id DESC", ARRAY_A );
if ( empty( $acrb_staff_members ) ) {
	$acrb_wp_users      = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ) ) );
	$acrb_staff_members = array();
	foreach ( $acrb_wp_users as $acrb_u ) {
		$acrb_staff_members[] = array( 'name' => $acrb_u->display_name );
	}
}
?>

<style>
	.acrb-insp-card {
		max-width: 820px;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		padding: 28px;
		box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		box-sizing: border-box;
	}
	.acrb-insp-card *,
	.acrb-insp-card *::before,
	.acrb-insp-card *::after {
		box-sizing: border-box;
	}
	.acrb-insp-header-title {
		margin: 0 0 24px;
		font-size: 19px;
		font-weight: 800;
		color: #0f172a;
		border-bottom: 1px solid #f1f5f9;
		padding-bottom: 14px;
	}
	.acrb-insp-grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 18px;
		margin-bottom: 18px;
	}
	.acrb-insp-grid-3 {
		display: grid;
		grid-template-columns: 1fr 1fr 1fr;
		gap: 16px;
		margin-bottom: 18px;
	}
	.acrb-insp-form-group {
		margin-bottom: 18px;
	}
	.acrb-insp-form-group-full {
		margin-bottom: 22px;
	}
	.acrb-insp-label {
		display: block;
		font-weight: 700;
		color: #1e293b;
		font-size: 13.5px;
		margin-bottom: 6px;
	}

	/* Standardized Form Inputs */
	.acrb-insp-input,
	.acrb-insp-select,
	.acrb-insp-textarea {
		display: block;
		width: 100% !important;
		max-width: 100% !important;
		background: #f8fafc !important;
		border: 1px solid #cbd5e1 !important;
		border-radius: 8px !important;
		padding: 10px 14px !important;
		font-size: 13.5px !important;
		font-family: inherit !important;
		color: #1e293b !important;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
		transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
	}
	.acrb-insp-input,
	.acrb-insp-select {
		height: 42px !important;
		line-height: 20px !important;
	}
	.acrb-insp-textarea {
		line-height: 1.5 !important;
		resize: vertical;
	}
	.acrb-insp-input:hover,
	.acrb-insp-select:hover,
	.acrb-insp-textarea:hover {
		border-color: #94a3b8 !important;
		background: #ffffff !important;
	}
	.acrb-insp-input:focus,
	.acrb-insp-select:focus,
	.acrb-insp-textarea:focus {
		background: #ffffff !important;
		border-color: #4f46e5 !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
	}

	/* Actions */
	.acrb-insp-btn-actions {
		margin-top: 28px;
		display: flex;
		gap: 12px;
		align-items: center;
		border-top: 1px solid #f1f5f9;
		padding-top: 20px;
	}
	.acrb-insp-btn-submit {
		background: #4f46e5;
		color: #ffffff;
		border: none;
		padding: 11px 24px;
		font-size: 13.5px;
		font-weight: 700;
		border-radius: 8px;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 6px;
		box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
		transition: background 0.15s ease, transform 0.15s ease;
	}
	.acrb-insp-btn-submit:hover {
		background: #4338ca;
		color: #ffffff;
		transform: translateY(-1px);
	}
	.acrb-insp-btn-submit .dashicons {
		font-size: 17px;
		width: 17px;
		height: 17px;
	}
	.acrb-insp-btn-cancel {
		color: #64748b;
		text-decoration: none;
		font-weight: 600;
		font-size: 13px;
		padding: 8px 14px;
		border-radius: 8px;
		transition: color 0.15s ease;
	}
	.acrb-insp-btn-cancel:hover {
		color: #0f172a;
	}

	@media (max-width: 780px) {
		.acrb-insp-grid-2,
		.acrb-insp-grid-3 {
			grid-template-columns: 1fr;
			gap: 0;
		}
	}
</style>

<div class="acrb-insp-card">
	<h2 class="acrb-insp-header-title">
		<?php echo $acrb_insp_id > 0 ? esc_html__( 'Edit Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Create New Inspection Report', 'awesome-car-rental' ); ?>
	</h2>

	<form method="post">
		<?php wp_nonce_field( 'acrb_insp_action', 'acrb_insp_nonce' ); ?>
		<input type="hidden" name="insp_id" value="<?php echo absint( $acrb_insp_id ); ?>">

		<div class="acrb-insp-grid-2">
			<!-- Vehicle Selector -->
			<div class="acrb-insp-form-group">
				<label class="acrb-insp-label" for="insp_car_id"><?php esc_html_e( 'Select Vehicle', 'awesome-car-rental' ); ?></label>
				<select name="insp_car_id" id="insp_car_id" class="acrb-insp-select" required>
					<option value=""><?php esc_html_e( '-- Choose Vehicle --', 'awesome-car-rental' ); ?></option>
					<?php foreach ( $acrb_cars as $acrb_car ) : 
						$acrb_c_id   = absint( $acrb_car['id'] ?? 0 );
						$acrb_c_name = sanitize_text_field( $acrb_car['name'] ?? 'Unnamed Car' );
					?>
						<option value="<?php echo esc_attr( (string) $acrb_c_id ); ?>" <?php selected( $acrb_i_car_id, $acrb_c_id ); ?>>
							<?php echo esc_html( $acrb_c_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Stage -->
			<div class="acrb-insp-form-group">
				<label class="acrb-insp-label" for="insp_stage"><?php esc_html_e( 'Inspection Stage', 'awesome-car-rental' ); ?></label>
				<select name="insp_stage" id="insp_stage" class="acrb-insp-select">
					<option value="Check-Out" <?php selected( $acrb_i_stage, 'Check-Out' ); ?>><?php esc_html_e( 'Check-Out (Before Rental)', 'awesome-car-rental' ); ?></option>
					<option value="Check-In" <?php selected( $acrb_i_stage, 'Check-In' ); ?>><?php esc_html_e( 'Check-In (After Return)', 'awesome-car-rental' ); ?></option>
					<option value="Periodic Check" <?php selected( $acrb_i_stage, 'Periodic Check' ); ?>><?php esc_html_e( 'Periodic Maintenance Audit', 'awesome-car-rental' ); ?></option>
				</select>
			</div>
		</div>

		<div class="acrb-insp-grid-2">
			<!-- Date & Time -->
			<div class="acrb-insp-form-group">
				<label class="acrb-insp-label" for="insp_date"><?php esc_html_e( 'Inspection Date & Time', 'awesome-car-rental' ); ?></label>
				<input type="datetime-local" name="insp_date" id="insp_date" value="<?php echo esc_attr( gmdate( 'Y-m-d\TH:i', (int) strtotime( $acrb_i_date ) ) ); ?>" class="acrb-insp-input">
			</div>

			<!-- Inspector / Staff Member -->
			<div class="acrb-insp-form-group">
				<label class="acrb-insp-label" for="insp_inspector"><?php esc_html_e( 'Inspector / Staff Member', 'awesome-car-rental' ); ?></label>
				<select name="insp_inspector" id="insp_inspector" class="acrb-insp-select" required>
					<option value=""><?php esc_html_e( '-- Select Staff Member --', 'awesome-car-rental' ); ?></option>
					<?php foreach ( $acrb_staff_members as $acrb_staff ) : 
						$acrb_staff_name = sanitize_text_field( $acrb_staff['name'] ?? ( $acrb_staff['full_name'] ?? '' ) );
						if ( empty( $acrb_staff_name ) ) {
							continue;
						}
					?>
						<option value="<?php echo esc_attr( $acrb_staff_name ); ?>" <?php selected( $acrb_i_inspector, $acrb_staff_name ); ?>>
							<?php echo esc_html( $acrb_staff_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="acrb-insp-grid-3">
			<!-- Mileage -->
			<div class="acrb-insp-form-group">
				<label class="acrb-insp-label" for="insp_mileage"><?php esc_html_e( 'Current Mileage', 'awesome-car-rental' ); ?></label>
				<input type="text" name="insp_mileage" id="insp_mileage" value="<?php echo esc_attr( $acrb_i_mileage ); ?>" class="acrb-insp-input" placeholder="<?php esc_attr_e( 'e.g. 45,200 km', 'awesome-car-rental' ); ?>">
			</div>

			<!-- Fuel Level -->
			<div class="acrb-insp-form-group">
				<label class="acrb-insp-label" for="insp_fuel"><?php esc_html_e( 'Fuel Level', 'awesome-car-rental' ); ?></label>
				<select name="insp_fuel" id="insp_fuel" class="acrb-insp-select">
					<option value="Full (100%)" <?php selected( $acrb_i_fuel, 'Full (100%)' ); ?>><?php esc_html_e( 'Full (100%)', 'awesome-car-rental' ); ?></option>
					<option value="3/4 Tank" <?php selected( $acrb_i_fuel, '3/4 Tank' ); ?>><?php esc_html_e( '3/4 Tank', 'awesome-car-rental' ); ?></option>
					<option value="1/2 Tank" <?php selected( $acrb_i_fuel, '1/2 Tank' ); ?>><?php esc_html_e( '1/2 Tank', 'awesome-car-rental' ); ?></option>
					<option value="1/4 Tank" <?php selected( $acrb_i_fuel, '1/4 Tank' ); ?>><?php esc_html_e( '1/4 Tank', 'awesome-car-rental' ); ?></option>
					<option value="Empty" <?php selected( $acrb_i_fuel, 'Empty' ); ?>><?php esc_html_e( 'Empty', 'awesome-car-rental' ); ?></option>
				</select>
			</div>

			<!-- Cleanliness -->
			<div class="acrb-insp-form-group">
				<label class="acrb-insp-label" for="insp_cleanliness"><?php esc_html_e( 'Cleanliness', 'awesome-car-rental' ); ?></label>
				<select name="insp_cleanliness" id="insp_cleanliness" class="acrb-insp-select">
					<option value="Pristine" <?php selected( $acrb_i_clean, 'Pristine' ); ?>><?php esc_html_e( 'Pristine', 'awesome-car-rental' ); ?></option>
					<option value="Clean" <?php selected( $acrb_i_clean, 'Clean' ); ?>><?php esc_html_e( 'Clean', 'awesome-car-rental' ); ?></option>
					<option value="Needs Wash" <?php selected( $acrb_i_clean, 'Needs Wash' ); ?>><?php esc_html_e( 'Needs Wash', 'awesome-car-rental' ); ?></option>
					<option value="Dirty / Muddy" <?php selected( $acrb_i_clean, 'Dirty / Muddy' ); ?>><?php esc_html_e( 'Dirty / Muddy', 'awesome-car-rental' ); ?></option>
				</select>
			</div>
		</div>

		<!-- Damage Log -->
		<div class="acrb-insp-form-group-full">
			<label class="acrb-insp-label" for="insp_damages"><?php esc_html_e( 'Damage Log / Scratches / Dents', 'awesome-car-rental' ); ?></label>
			<textarea name="insp_damages" id="insp_damages" rows="3" class="acrb-insp-textarea" placeholder="<?php esc_attr_e( 'Describe any existing or new scratches, dents, or windshield chips...', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $acrb_i_damages ); ?></textarea>
		</div>

		<!-- Actions -->
		<div class="acrb-insp-btn-actions">
			<button type="submit" name="acrb_save_inspection" class="acrb-insp-btn-submit">
				<span class="dashicons <?php echo $acrb_insp_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>"></span>
				<span><?php echo $acrb_insp_id > 0 ? esc_html__( 'Update Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Save Inspection Report', 'awesome-car-rental' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $acrb_list_url ); ?>" class="acrb-insp-btn-cancel">
				<?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
			</a>
		</div>
	</form>
</div>