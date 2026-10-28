<?php
/**
 * Professional SaaS Edit Booking Interface (Custom DB Architecture)
 * Location: admin/bookings/acrb-bookings-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';

// Ensure $booking_id is available and valid
$acrb_current_bid = isset( $booking_id ) ? absint( $booking_id ) : 0;

// Fetch record directly from custom database table
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking = $wpdb->get_row(
	$wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $acrb_current_bid ),
	ARRAY_A
);

if ( $acrb_current_bid <= 0 || ! $booking ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid Booking Resource or Record Not Found.', 'awesome-car-rental' ) . '</p></div>';
	return;
}

// 1. UPDATE LOGIC
if ( isset( $_POST['acrb_update_booking'] ) ) {
	if ( check_admin_referer( 'acrb_update_booking_action', 'acrb_update_booking_nonce' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			
			$update_data = array();
			$update_formats = array();

			if ( isset( $_POST['acrb_customer_name'] ) ) {
				$update_data['customer_name'] = sanitize_text_field( wp_unslash( $_POST['acrb_customer_name'] ) );
				$update_formats[] = '%s';
			}

			if ( isset( $_POST['acrb_customer_phone'] ) ) {
				$update_data['customer_phone'] = sanitize_text_field( wp_unslash( $_POST['acrb_customer_phone'] ) );
				$update_formats[] = '%s';
			}

			if ( isset( $_POST['acrb_pickup_location'] ) ) {
				// Note: if your database uses pickup_location or you want to store it, ensure column exists. 
				// Mapping to customer_email or notes if custom column isn't present, or standard handling:
				$update_data['customer_email'] = sanitize_email( wp_unslash( $_POST['acrb_pickup_location'] ) );
				$update_formats[] = '%s';
			}

			if ( isset( $_POST['acrb_status'] ) ) {
				$update_data['booking_status'] = sanitize_key( wp_unslash( $_POST['acrb_status'] ) );
				$update_formats[] = '%s';
			}

			if ( ! empty( $update_data ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_bookings,
					$update_data,
					array( 'id' => $acrb_current_bid ),
					$update_formats,
					array( '%d' )
				);
			}

			// Invalidate dashboard transient cache
			delete_transient( 'acrb_dashboard_revenue' );

			// Refresh booking record data after update
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$booking = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $acrb_current_bid ),
				ARRAY_A
			);

			echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0 0 0;">
					<p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . 
					esc_html__( 'Reservation details synchronized successfully in custom database.', 'awesome-car-rental' ) . '</p>
				  </div>';
		}
	}
}

// 2. DATA RETRIEVAL FROM ARRAY
$acrb_edit_name   = $booking['customer_name'] ?? '';
$acrb_edit_phone  = $booking['customer_phone'] ?? '';
$acrb_edit_pickup = $booking['customer_email'] ?? ''; // Mapped cleanly to customer contact slot
$acrb_edit_notes  = ''; // Custom database rows handle core fields
$acrb_edit_status = $booking['booking_status'] ?? 'pending';
$acrb_car_name    = $booking['car_name'] ?? 'Fleet Vehicle';
?>

<style>
	/* Modern SaaS Layout Theme */
	.acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 20px 20px 0 0; }
	
	/* Ultra-Premium Top Header Bar */
	.acrb-header-flex { 
		display: flex; 
		justify-content: space-between; 
		align-items: center; 
		background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); 
		border: 1px solid #e2e8f0; 
		border-top: 3px solid #3b82f6;
		border-radius: 14px; 
		padding: 24px 32px; 
		box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); 
		margin-bottom: 28px; 
		position: relative;
		overflow: hidden;
	}
	.acrb-title-wrapper { display: flex; align-items: center; gap: 16px; }
	.acrb-title-icon { width: 48px; height: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
	.acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
	
	.acrb-main-title { font-size: 22px; font-weight: 700; margin: 0 0 4px 0; color: #0f172a; letter-spacing: -0.5px; }
	.acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; }
	
	/* Header Actions & Back Button */
	.acrb-header-flex .acrb-back-link { 
		background: #ffffff; 
		color: #334155; 
		border: 1px solid #cbd5e1; 
		border-radius: 8px; 
		padding: 10px 18px; 
		font-weight: 600; 
		text-decoration: none; 
		display: inline-flex; 
		align-items: center; 
		gap: 8px; 
		transition: all 0.25s ease; 
		box-shadow: 0 1px 2px rgba(0,0,0,0.02);
	}
	.acrb-header-flex .acrb-back-link .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; }
	.acrb-header-flex .acrb-back-link:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

	/* Grid Layout */
	.acrb-grid-layout { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
	.acrb-main-col, .acrb-sidebar-col { display: flex; flex-direction: column; gap: 24px; }
	.acrb-sidebar-sticky { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 24px; }

	/* Panel Cards */
	.acrb-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); overflow: hidden; }
	.acrb-panel-head { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; background: #fafafa; }
	.acrb-panel-head h2 { font-size: 15px; font-weight: 600; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px; }
	.acrb-panel-head .dashicons { color: #3b82f6; font-size: 18px; width: 18px; height: 18px; }
	.acrb-panel-body { padding: 24px; }

	/* Form Fields */
	.acrb-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
	.acrb-form-group { display: flex; flex-direction: column; gap: 6px; }
	.acrb-label { font-size: 13px; font-weight: 600; color: #1e293b; }
	.acrb-field { width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 13px; color: #1e293b; box-sizing: border-box; background: #fff; }
	textarea.acrb-field { height: auto; padding: 10px 12px; line-height: 1.5; }
	.acrb-field:focus { border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; outline: none; }
	
	.mt-20 { margin-top: 20px; }
	.mb-20 { margin-bottom: 20px; }

	/* Save Button */
	.acrb-btn-save { background: #3b82f6; color: #fff; border: none; width: 100%; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; text-align: center; display: inline-flex; align-items: center; justify-content: center; height: 40px; box-sizing: border-box; }
	.acrb-btn-save:hover { background: #2563eb; color: #fff; }

	@media(max-width: 900px) {
		.acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
		.acrb-grid-layout { grid-template-columns: 1fr; }
		.acrb-form-row { grid-template-columns: 1fr; }
	}
</style>

<div class="acrb-dash-wrap">
	<div class="acrb-header-flex">
		<div class="acrb-title-wrapper">
			<div class="acrb-title-icon">
				<span class="dashicons dashicons-calendar-alt"></span>
			</div>
			<div>
				<h1 class="acrb-main-title"><?php esc_html_e( 'Edit Reservation', 'awesome-car-rental' ); ?></h1>
				<p class="acrb-sub-title">
					<?php 
					/* translators: %d: The ID number of the booking */
					printf( esc_html__( 'Modify records and status for Booking #%d (Vehicle: %s)', 'awesome-car-rental' ), absint( $acrb_current_bid ), esc_html( $acrb_car_name ) ); 
					?>
				</p>
			</div>
		</div>
		
		<div class="acrb-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=bookings' ) ); ?>" class="acrb-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span> 
				<span><?php esc_html_e( 'Back to Directory', 'awesome-car-rental' ); ?></span>
			</a>
		</div>
	</div>

	<form method="post" id="acrb-edit-form">
		<?php wp_nonce_field( 'acrb_update_booking_action', 'acrb_update_booking_nonce' ); ?>

		<div class="acrb-grid-layout">
			
			<div class="acrb-main-col">
				<div class="acrb-panel">
					<div class="acrb-panel-head">
						<span class="dashicons dashicons-admin-users"></span>
						<h2><?php esc_html_e( 'Client Details', 'awesome-car-rental' ); ?></h2>
					</div>
					<div class="acrb-panel-body">
						<div class="acrb-form-row">
							<div class="acrb-form-group">
								<label class="acrb-label"><?php esc_html_e( 'Customer Name', 'awesome-car-rental' ); ?></label>
								<input type="text" name="acrb_customer_name" value="<?php echo esc_attr( $acrb_edit_name ); ?>" class="acrb-field">
							</div>
							<div class="acrb-form-group">
								<label class="acrb-label"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
								<input type="text" name="acrb_customer_phone" value="<?php echo esc_attr( $acrb_edit_phone ); ?>" class="acrb-field">
							</div>
						</div>
						<div class="acrb-form-group mt-20">
							<label class="acrb-label"><?php esc_html_e( 'Customer Email / Contact', 'awesome-car-rental' ); ?></label>
							<input type="text" name="acrb_pickup_location" value="<?php echo esc_attr( $acrb_edit_pickup ); ?>" class="acrb-field">
						</div>
					</div>
				</div>
			</div>

			<div class="acrb-sidebar-col">
				<div class="acrb-sidebar-sticky">
					<div class="acrb-panel">
						<div class="acrb-panel-head">
							<span class="dashicons dashicons-update"></span>
							<h2><?php esc_html_e( 'Status Control', 'awesome-car-rental' ); ?></h2>
						</div>
						<div class="acrb-panel-body">
							<div class="acrb-form-group mb-20">
								<label class="acrb-label"><?php esc_html_e( 'Booking Status', 'awesome-car-rental' ); ?></label>
								<select name="acrb_status" class="acrb-field">
									<option value="pending" <?php selected( $acrb_edit_status, 'pending' ); ?>><?php esc_html_e( '⏳ Pending Request', 'awesome-car-rental' ); ?></option>
									<option value="confirmed" <?php selected( $acrb_edit_status, 'confirmed' ); ?>><?php esc_html_e( '💳 Confirmed', 'awesome-car-rental' ); ?></option>
									<option value="picked_up" <?php selected( $acrb_edit_status, 'picked_up' ); ?>><?php esc_html_e( '🚗 Vehicle Out', 'awesome-car-rental' ); ?></option>
									<option value="returned" <?php selected( $acrb_edit_status, 'returned' ); ?>><?php esc_html_e( '🏁 Returned', 'awesome-car-rental' ); ?></option>
									<option value="cancelled" <?php selected( $acrb_edit_status, 'cancelled' ); ?>><?php esc_html_e( '❌ Cancelled', 'awesome-car-rental' ); ?></option>
								</select>
							</div>
							<button type="submit" name="acrb_update_booking" class="acrb-btn-save">
								<?php esc_html_e( 'Update Reservation', 'awesome-car-rental' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>