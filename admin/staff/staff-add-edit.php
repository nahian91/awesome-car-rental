<?php
/**
 * Add / Edit Staff Component (`staff-add-edit.php`)
 * Location: admin/staff/staff-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   2.9.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$acrb_table_staff = $wpdb->prefix . 'dev_nahian_staff';

// Read-only query parameters used to resolve the editing record.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_staff_id = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$acrb_list_url = admin_url( 'admin.php?page=awesome_car_rental&tab=staff' );

$acrb_staff = null;
if ( $acrb_staff_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_staff = $wpdb->get_row(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$acrb_table_staff} WHERE id = %d",
			$acrb_staff_id
		),
		ARRAY_A
	);
}

$acrb_s_name   = $acrb_staff['name'] ?? '';
$acrb_s_email  = $acrb_staff['email'] ?? '';
$acrb_s_phone  = $acrb_staff['phone'] ?? '';
$acrb_s_role   = $acrb_staff['role'] ?? 'Front Desk Agent';
$acrb_s_status = $acrb_staff['status'] ?? 'active';
?>

<style>
	.acrb-staff-card {
		max-width: 820px;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		padding: 28px;
		box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		box-sizing: border-box;
	}
	.acrb-staff-card *,
	.acrb-staff-card *::before,
	.acrb-staff-card *::after {
		box-sizing: border-box;
	}
	.acrb-staff-header-title {
		margin: 0 0 24px;
		font-size: 19px;
		font-weight: 800;
		color: #0f172a;
		border-bottom: 1px solid #f1f5f9;
		padding-bottom: 14px;
	}
	.acrb-staff-grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 18px;
		margin-bottom: 18px;
	}
	.acrb-staff-form-group {
		display: flex;
		flex-direction: column;
	}
	.acrb-staff-label {
		display: block;
		font-weight: 700;
		color: #1e293b;
		font-size: 13.5px;
		margin-bottom: 6px;
	}

	/* Form Controls */
	.acrb-staff-input,
	.acrb-staff-select {
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
	.acrb-staff-input:hover,
	.acrb-staff-select:hover {
		border-color: #94a3b8 !important;
		background: #ffffff !important;
	}
	.acrb-staff-input:focus,
	.acrb-staff-select:focus {
		background: #ffffff !important;
		border-color: #4f46e5 !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
	}

	/* Select Dropdown Wrap */
	.acrb-staff-select-wrap {
		position: relative;
		width: 100%;
	}
	.acrb-staff-select-wrap select {
		appearance: none;
		-webkit-appearance: none;
		padding-right: 36px !important;
		cursor: pointer;
	}
	.acrb-staff-select-arrow {
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

	/* Button Actions */
	.acrb-staff-btn-actions {
		margin-top: 28px;
		display: flex;
		gap: 12px;
		align-items: center;
		border-top: 1px solid #f1f5f9;
		padding-top: 20px;
	}
	.acrb-staff-btn-submit {
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
	.acrb-staff-btn-submit:hover {
		background: #4338ca;
		color: #ffffff;
		transform: translateY(-1px);
	}
	.acrb-staff-btn-submit .dashicons {
		font-size: 17px;
		width: 17px;
		height: 17px;
	}
	.acrb-staff-btn-cancel {
		color: #64748b;
		text-decoration: none;
		font-weight: 600;
		font-size: 13px;
		padding: 8px 14px;
		border-radius: 8px;
		transition: color 0.15s ease;
	}
	.acrb-staff-btn-cancel:hover {
		color: #0f172a;
	}

	@media (max-width: 780px) {
		.acrb-staff-grid-2 {
			grid-template-columns: 1fr;
			gap: 14px;
		}
	}
</style>

<div class="acrb-staff-card">
	<h2 class="acrb-staff-header-title">
		<?php
		if ( $acrb_staff_id > 0 ) {
			/* translators: %s: Staff member full name */
			printf( esc_html__( 'Edit Staff Profile: %s', 'awesome-car-rental' ), esc_html( $acrb_s_name ) );
		} else {
			esc_html_e( 'Add New Staff Member', 'awesome-car-rental' );
		}
		?>
	</h2>

	<form method="post">
		<?php wp_nonce_field( 'acrb_staff_action', 'acrb_staff_nonce' ); ?>
		<input type="hidden" name="staff_record_id" value="<?php echo absint( $acrb_staff_id ); ?>">

		<div class="acrb-staff-grid-2">
			<!-- Full Name -->
			<div class="acrb-staff-form-group">
				<label class="acrb-staff-label" for="staff_name"><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></label>
				<input type="text" id="staff_name" name="staff_name" value="<?php echo esc_attr( $acrb_s_name ); ?>" class="acrb-staff-input" placeholder="<?php esc_attr_e( 'e.g. Liam Wilson', 'awesome-car-rental' ); ?>" required>
			</div>

			<!-- Email Address -->
			<div class="acrb-staff-form-group">
				<label class="acrb-staff-label" for="staff_email"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
				<input type="email" id="staff_email" name="staff_email" value="<?php echo esc_attr( $acrb_s_email ); ?>" class="acrb-staff-input" placeholder="<?php esc_attr_e( 'e.g. liam@example.com', 'awesome-car-rental' ); ?>" required>
			</div>
		</div>

		<div class="acrb-staff-grid-2">
			<!-- Phone Number -->
			<div class="acrb-staff-form-group">
				<label class="acrb-staff-label" for="staff_phone"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
				<input type="text" id="staff_phone" name="staff_phone" value="<?php echo esc_attr( $acrb_s_phone ); ?>" class="acrb-staff-input" placeholder="<?php esc_attr_e( '+1 (555) 000-0000', 'awesome-car-rental' ); ?>">
			</div>

			<!-- Designation / Role -->
			<div class="acrb-staff-form-group">
				<label class="acrb-staff-label" for="staff_designation"><?php esc_html_e( 'Designation / Role', 'awesome-car-rental' ); ?></label>
				<div class="acrb-staff-select-wrap">
					<select id="staff_designation" name="staff_designation" class="acrb-staff-select">
						<option value="Front Desk Agent" <?php selected( $acrb_s_role, 'Front Desk Agent' ); ?>><?php esc_html_e( 'Front Desk Agent', 'awesome-car-rental' ); ?></option>
						<option value="Chauffeur / Driver" <?php selected( $acrb_s_role, 'Chauffeur / Driver' ); ?>><?php esc_html_e( 'Chauffeur / Driver', 'awesome-car-rental' ); ?></option>
						<option value="Fleet Manager" <?php selected( $acrb_s_role, 'Fleet Manager' ); ?>><?php esc_html_e( 'Fleet Manager', 'awesome-car-rental' ); ?></option>
						<option value="Maintenance Technician" <?php selected( $acrb_s_role, 'Maintenance Technician' ); ?>><?php esc_html_e( 'Maintenance Technician', 'awesome-car-rental' ); ?></option>
					</select>
					<span class="dashicons dashicons-arrow-down-alt2 acrb-staff-select-arrow"></span>
				</div>
			</div>
		</div>

		<div class="acrb-staff-grid-2">
			<!-- Staff Status -->
			<div class="acrb-staff-form-group">
				<label class="acrb-staff-label" for="staff_status"><?php esc_html_e( 'Staff Status', 'awesome-car-rental' ); ?></label>
				<div class="acrb-staff-select-wrap">
					<select id="staff_status" name="staff_status" class="acrb-staff-select">
						<option value="active" <?php selected( $acrb_s_status, 'active' ); ?>><?php esc_html_e( 'Active', 'awesome-car-rental' ); ?></option>
						<option value="inactive" <?php selected( $acrb_s_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive / On Leave', 'awesome-car-rental' ); ?></option>
					</select>
					<span class="dashicons dashicons-arrow-down-alt2 acrb-staff-select-arrow"></span>
				</div>
			</div>

			<!-- Empty Column for balanced grid -->
			<div></div>
		</div>

		<div class="acrb-staff-btn-actions">
			<button type="submit" name="acrb_save_staff" class="acrb-staff-btn-submit">
				<span class="dashicons <?php echo $acrb_staff_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>"></span>
				<span><?php echo $acrb_staff_id > 0 ? esc_html__( 'Update Staff Member', 'awesome-car-rental' ) : esc_html__( 'Save Staff Member', 'awesome-car-rental' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $acrb_list_url ); ?>" class="acrb-staff-btn-cancel">
				<?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
			</a>
		</div>
	</form>
</div>