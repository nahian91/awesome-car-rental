<?php
/**
 * Add / Edit Maintenance Component (`maintenance-add-edit.php`)
 * Location: admin/maintenance/maintenance-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.6
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$acrb_table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
$acrb_table_cars        = $wpdb->prefix . 'dev_nahian_cars';
$acrb_currency_symbol   = get_option( 'acrb_currency', '$' );

// Read-only GET query parameters to fetch record state for editing.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_maint_id = isset( $_GET['maint_id'] ) ? absint( wp_unslash( $_GET['maint_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$acrb_list_url = admin_url( 'admin.php?page=awesome_car_rental&tab=maintenance' );

// Fetch existing maintenance record.
$acrb_maint_record = null;
if ( $acrb_maint_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_maint_record = $wpdb->get_row(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$acrb_table_maintenance} WHERE id = %d",
			$acrb_maint_id
		),
		ARRAY_A
	);
}

$acrb_m_car_name = $acrb_maint_record['car_name'] ?? '';
$acrb_m_type     = $acrb_maint_record['service_type'] ?? 'Routine Service';
$acrb_m_cost     = $acrb_maint_record['cost'] ?? '';
$acrb_m_date     = $acrb_maint_record['date_in'] ?? current_time( 'Y-m-d' );
$acrb_m_status   = $acrb_maint_record['status'] ?? 'pending';
$acrb_m_notes    = $acrb_maint_record['notes'] ?? '';

// Fetch fleet inventory for vehicle selector.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_cars_list = $wpdb->get_results( "SELECT id, name FROM {$acrb_table_cars} ORDER BY name ASC", ARRAY_A );
if ( empty( $acrb_cars_list ) ) {
	$acrb_cars_posts = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$acrb_cars_list  = array();
	foreach ( $acrb_cars_posts as $acrb_cp ) {
		$acrb_cars_list[] = array( 'name' => $acrb_cp->post_title );
	}
}
?>

<style>
	.acrb-maint-card {
		max-width: 820px;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		padding: 28px;
		box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		box-sizing: border-box;
	}
	.acrb-maint-card *,
	.acrb-maint-card *::before,
	.acrb-maint-card *::after {
		box-sizing: border-box;
	}
	.acrb-maint-header-title {
		margin: 0 0 24px;
		font-size: 19px;
		font-weight: 800;
		color: #0f172a;
		border-bottom: 1px solid #f1f5f9;
		padding-bottom: 14px;
	}
	.acrb-maint-form-grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 18px;
		margin-bottom: 18px;
	}
	.acrb-maint-form-group {
		margin-bottom: 18px;
	}
	.acrb-maint-form-group-full {
		margin-bottom: 22px;
	}
	.acrb-maint-label {
		display: block;
		font-weight: 700;
		color: #1e293b;
		font-size: 13.5px;
		margin-bottom: 6px;
	}

	/* Standardized Input, Select, and Textarea controls */
	.acrb-maint-input,
	.acrb-maint-select,
	.acrb-maint-textarea {
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
	.acrb-maint-input,
	.acrb-maint-select {
		height: 42px !important;
		line-height: 20px !important;
	}
	.acrb-maint-textarea {
		line-height: 1.5 !important;
		resize: vertical;
	}
	.acrb-maint-input:hover,
	.acrb-maint-select:hover,
	.acrb-maint-textarea:hover {
		border-color: #94a3b8 !important;
		background: #ffffff !important;
	}
	.acrb-maint-input:focus,
	.acrb-maint-select:focus,
	.acrb-maint-textarea:focus {
		background: #ffffff !important;
		border-color: #4f46e5 !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
	}

	/* Actions */
	.acrb-maint-btn-actions {
		margin-top: 28px;
		display: flex;
		gap: 12px;
		align-items: center;
		border-top: 1px solid #f1f5f9;
		padding-top: 20px;
	}
	.acrb-maint-btn-submit {
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
	.acrb-maint-btn-submit:hover {
		background: #4338ca;
		color: #ffffff;
		transform: translateY(-1px);
	}
	.acrb-maint-btn-submit .dashicons {
		font-size: 17px;
		width: 17px;
		height: 17px;
	}
	.acrb-maint-btn-cancel {
		color: #64748b;
		text-decoration: none;
		font-weight: 600;
		font-size: 13px;
		padding: 8px 14px;
		border-radius: 8px;
		transition: color 0.15s ease;
	}
	.acrb-maint-btn-cancel:hover {
		color: #0f172a;
	}

	@media (max-width: 780px) {
		.acrb-maint-form-grid-2 {
			grid-template-columns: 1fr;
			gap: 0;
		}
	}
</style>

<div class="acrb-maint-card">
	<h2 class="acrb-maint-header-title">
		<?php echo $acrb_maint_id > 0 ? esc_html__( 'Edit Maintenance Record', 'awesome-car-rental' ) : esc_html__( 'Log New Maintenance Service', 'awesome-car-rental' ); ?>
	</h2>

	<form method="post">
		<?php wp_nonce_field( 'acrb_maint_action', 'acrb_maint_nonce' ); ?>
		<input type="hidden" name="maint_id" value="<?php echo absint( $acrb_maint_id ); ?>">

		<div class="acrb-maint-form-grid-2">
			<!-- Vehicle Selector -->
			<div class="acrb-maint-form-group">
				<label class="acrb-maint-label" for="maint_car_name"><?php esc_html_e( 'Select Vehicle', 'awesome-car-rental' ); ?></label>
				<select name="maint_car_name" id="maint_car_name" class="acrb-maint-select" required>
					<option value=""><?php esc_html_e( '-- Choose Vehicle --', 'awesome-car-rental' ); ?></option>
					<?php foreach ( $acrb_cars_list as $acrb_car ) : 
						$acrb_c_name = sanitize_text_field( $acrb_car['name'] ?? '' );
						if ( empty( $acrb_c_name ) ) {
							continue;
						}
					?>
						<option value="<?php echo esc_attr( $acrb_c_name ); ?>" <?php selected( $acrb_m_car_name, $acrb_c_name ); ?>>
							<?php echo esc_html( $acrb_c_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Service Type -->
			<div class="acrb-maint-form-group">
				<label class="acrb-maint-label" for="maint_type"><?php esc_html_e( 'Service Type', 'awesome-car-rental' ); ?></label>
				<select name="maint_type" id="maint_type" class="acrb-maint-select">
					<option value="Routine Service" <?php selected( $acrb_m_type, 'Routine Service' ); ?>><?php esc_html_e( 'Routine Service / Oil Change', 'awesome-car-rental' ); ?></option>
					<option value="Mechanical Repair" <?php selected( $acrb_m_type, 'Mechanical Repair' ); ?>><?php esc_html_e( 'Mechanical Repair', 'awesome-car-rental' ); ?></option>
					<option value="Tire Replacement" <?php selected( $acrb_m_type, 'Tire Replacement' ); ?>><?php esc_html_e( 'Tire Replacement / Alignment', 'awesome-car-rental' ); ?></option>
					<option value="Bodywork & Detailing" <?php selected( $acrb_m_type, 'Bodywork & Detailing' ); ?>><?php esc_html_e( 'Bodywork & Detailing', 'awesome-car-rental' ); ?></option>
					<option value="Inspection & Safety" <?php selected( $acrb_m_type, 'Inspection & Safety' ); ?>><?php esc_html_e( 'Inspection & Safety', 'awesome-car-rental' ); ?></option>
				</select>
			</div>
		</div>

		<div class="acrb-maint-form-grid-2">
			<!-- Repair Cost -->
			<div class="acrb-maint-form-group">
				<label class="acrb-maint-label" for="maint_cost">
					<?php
					/* translators: %s: Currency symbol */
					printf( esc_html__( 'Repair Cost (%s)', 'awesome-car-rental' ), esc_html( $acrb_currency_symbol ) );
					?>
				</label>
				<input type="number" step="0.01" min="0" name="maint_cost" id="maint_cost" value="<?php echo esc_attr( (string) $acrb_m_cost ); ?>" class="acrb-maint-input" placeholder="0.00">
			</div>

			<!-- Service Date -->
			<div class="acrb-maint-form-group">
				<label class="acrb-maint-label" for="maint_date"><?php esc_html_e( 'Service Date (Date In)', 'awesome-car-rental' ); ?></label>
				<input type="date" name="maint_date" id="maint_date" value="<?php echo esc_attr( $acrb_m_date ); ?>" class="acrb-maint-input">
			</div>
		</div>

		<div class="acrb-maint-form-grid-2">
			<!-- Status -->
			<div class="acrb-maint-form-group">
				<label class="acrb-maint-label" for="maint_status"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
				<select name="maint_status" id="maint_status" class="acrb-maint-select">
					<option value="pending" <?php selected( $acrb_m_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'awesome-car-rental' ); ?></option>
					<option value="in_progress" <?php selected( $acrb_m_status, 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'awesome-car-rental' ); ?></option>
					<option value="completed" <?php selected( $acrb_m_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'awesome-car-rental' ); ?></option>
				</select>
			</div>
		</div>

		<!-- Repair Notes -->
		<div class="acrb-maint-form-group-full">
			<label class="acrb-maint-label" for="maint_notes"><?php esc_html_e( 'Repair Notes & Parts Replaced', 'awesome-car-rental' ); ?></label>
			<textarea name="maint_notes" id="maint_notes" rows="4" class="acrb-maint-textarea" placeholder="<?php esc_attr_e( 'Enter service details, notes, or replacement parts...', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $acrb_m_notes ); ?></textarea>
		</div>

		<!-- Form Actions -->
		<div class="acrb-maint-btn-actions">
			<button type="submit" name="acrb_save_maintenance" class="acrb-maint-btn-submit">
				<span class="dashicons <?php echo $acrb_maint_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>"></span>
				<span><?php echo $acrb_maint_id > 0 ? esc_html__( 'Update Maintenance Record', 'awesome-car-rental' ) : esc_html__( 'Save Maintenance Record', 'awesome-car-rental' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $acrb_list_url ); ?>" class="acrb-maint-btn-cancel">
				<?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
			</a>
		</div>
	</form>
</div>