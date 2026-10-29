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

$acrb_table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
$acrb_table_cars     = $wpdb->prefix . 'dev_nahian_cars';

// Ensure $acrb_current_bid is available and valid.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_current_bid = isset( $booking_id ) ? absint( $booking_id ) : ( isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0 );

// Fetch record directly from custom database table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$acrb_booking = $wpdb->get_row(
	$wpdb->prepare(
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		"SELECT * FROM {$acrb_table_bookings} WHERE id = %d",
		$acrb_current_bid
	),
	ARRAY_A
);

if ( $acrb_current_bid <= 0 || ! $acrb_booking ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid Booking Resource or Record Not Found.', 'awesome-car-rental' ) . '</p></div>';
	return;
}

// 1. UPDATE LOGIC.
if ( isset( $_POST['acrb_update_booking'] ) ) {
	if ( check_admin_referer( 'acrb_update_booking_action', 'acrb_update_booking_nonce' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			$acrb_customer_name  = isset( $_POST['acrb_customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_customer_name'] ) ) : '';
			$acrb_customer_email = isset( $_POST['acrb_customer_email'] ) ? sanitize_email( wp_unslash( $_POST['acrb_customer_email'] ) ) : '';
			$acrb_customer_phone = isset( $_POST['acrb_customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_customer_phone'] ) ) : '';
			$acrb_car_id         = isset( $_POST['acrb_car_id'] ) ? absint( $_POST['acrb_car_id'] ) : 0;
			$acrb_input_car_name = isset( $_POST['acrb_car_name'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_car_name'] ) ) : '';
			$acrb_pickup_date    = isset( $_POST['acrb_pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_pickup_date'] ) ) : '';
			$acrb_return_date    = isset( $_POST['acrb_return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_return_date'] ) ) : '';
			$acrb_total_amount   = isset( $_POST['acrb_total_amount'] ) ? floatval( wp_unslash( $_POST['acrb_total_amount'] ) ) : 0.00;
			$acrb_booking_status = isset( $_POST['acrb_status'] ) ? sanitize_key( wp_unslash( $_POST['acrb_status'] ) ) : 'pending';

			$acrb_update_data = array(
				'customer_name'  => $acrb_customer_name,
				'customer_email' => $acrb_customer_email,
				'customer_phone' => $acrb_customer_phone,
				'car_id'         => $acrb_car_id,
				'car_name'       => $acrb_input_car_name,
				'pickup_date'    => ! empty( $acrb_pickup_date ) ? $acrb_pickup_date : null,
				'return_date'    => ! empty( $acrb_return_date ) ? $acrb_return_date : null,
				'total_amount'   => $acrb_total_amount,
				'booking_status' => $acrb_booking_status,
			);

			$acrb_update_formats = array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%f', '%s' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$acrb_table_bookings,
				$acrb_update_data,
				array( 'id' => $acrb_current_bid ),
				$acrb_update_formats,
				array( '%d' )
			);

			delete_transient( 'acrb_dashboard_revenue' );

			// Refresh booking record data.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$acrb_booking = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$acrb_table_bookings} WHERE id = %d",
					$acrb_current_bid
				),
				ARRAY_A
			);

			echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;">
					<p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' .
					esc_html__( 'Reservation details updated successfully.', 'awesome-car-rental' ) . '</p>
				  </div>';
		}
	}
}

// 2. FETCH AVAILABLE VEHICLES & FIELD DATA.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_fleet_cars = $wpdb->get_results( "SELECT id, name FROM {$acrb_table_cars} ORDER BY name ASC", ARRAY_A );

$acrb_edit_name   = $acrb_booking['customer_name'] ?? '';
$acrb_edit_email  = $acrb_booking['customer_email'] ?? '';
$acrb_edit_phone  = $acrb_booking['customer_phone'] ?? '';
$acrb_edit_pickup = $acrb_booking['pickup_date'] ?? '';
$acrb_edit_return = $acrb_booking['return_date'] ?? '';
$acrb_edit_total  = floatval( $acrb_booking['total_amount'] ?? 0.00 );
$acrb_edit_status = $acrb_booking['booking_status'] ?? 'pending';
$acrb_edit_car_id = absint( $acrb_booking['car_id'] ?? 0 );
$acrb_car_name    = $acrb_booking['car_name'] ?? __( 'Fleet Vehicle', 'awesome-car-rental' );
$acrb_currency    = get_option( 'acrb_currency', '£' );
?>

<div class="acrb-edit-shell">
	<form method="post" id="acrb-edit-form">
		<?php wp_nonce_field( 'acrb_update_booking_action', 'acrb_update_booking_nonce' ); ?>

		<div class="acrb-grid-layout">

			<!-- Main Column: Client & Vehicle Schedule -->
			<div class="acrb-main-col">

				<!-- Client Details Panel -->
				<div class="acrb-panel">
					<div class="acrb-panel-head">
						<span class="dashicons dashicons-admin-users"></span>
						<h3><?php esc_html_e( 'Client Contact Details', 'awesome-car-rental' ); ?></h3>
					</div>
					<div class="acrb-panel-body">
						<div class="acrb-form-row">
							<div class="acrb-form-group">
								<label class="acrb-label"><?php esc_html_e( 'Customer Name', 'awesome-car-rental' ); ?></label>
								<input type="text" name="acrb_customer_name" value="<?php echo esc_attr( $acrb_edit_name ); ?>" class="acrb-field" required>
							</div>
							<div class="acrb-form-group">
								<label class="acrb-label"><?php esc_html_e( 'Customer Email', 'awesome-car-rental' ); ?></label>
								<input type="email" name="acrb_customer_email" value="<?php echo esc_attr( $acrb_edit_email ); ?>" class="acrb-field" required>
							</div>
						</div>

						<div class="acrb-form-group mt-18">
							<label class="acrb-label"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
							<input type="text" name="acrb_customer_phone" value="<?php echo esc_attr( $acrb_edit_phone ); ?>" class="acrb-field">
						</div>
					</div>
				</div>

				<!-- Itinerary & Vehicle Panel -->
				<div class="acrb-panel">
					<div class="acrb-panel-head">
						<span class="dashicons dashicons-calendar-alt"></span>
						<h3><?php esc_html_e( 'Vehicle & Schedule', 'awesome-car-rental' ); ?></h3>
					</div>
					<div class="acrb-panel-body">
						<div class="acrb-form-group mb-18">
							<label class="acrb-label"><?php esc_html_e( 'Assigned Fleet Vehicle', 'awesome-car-rental' ); ?></label>
							<div class="acrb-select-wrap">
								<select name="acrb_car_id" id="acrb_car_id_selector" class="acrb-field">
									<option value="0" data-car-name="<?php echo esc_attr( $acrb_car_name ); ?>"><?php echo esc_html( $acrb_car_name ); ?> (<?php esc_html_e( 'Current', 'awesome-car-rental' ); ?>)</option>
									<?php if ( ! empty( $acrb_fleet_cars ) ) : ?>
										<?php foreach ( $acrb_fleet_cars as $acrb_car_item ) : ?>
											<option value="<?php echo absint( $acrb_car_item['id'] ); ?>" data-car-name="<?php echo esc_attr( $acrb_car_item['name'] ); ?>" <?php selected( $acrb_edit_car_id, $acrb_car_item['id'] ); ?>>
												<?php echo esc_html( $acrb_car_item['name'] ); ?>
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
								<span class="acrb-select-chevron">
									<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
									</svg>
								</span>
							</div>
							<input type="hidden" name="acrb_car_name" id="acrb_hidden_car_name" value="<?php echo esc_attr( $acrb_car_name ); ?>">
						</div>

						<div class="acrb-form-row">
							<div class="acrb-form-group">
								<label class="acrb-label"><?php esc_html_e( 'Pick-up Date', 'awesome-car-rental' ); ?></label>
								<input type="date" name="acrb_pickup_date" value="<?php echo esc_attr( $acrb_edit_pickup ); ?>" class="acrb-field">
							</div>
							<div class="acrb-form-group">
								<label class="acrb-label"><?php esc_html_e( 'Return Date', 'awesome-car-rental' ); ?></label>
								<input type="date" name="acrb_return_date" value="<?php echo esc_attr( $acrb_edit_return ); ?>" class="acrb-field">
							</div>
						</div>
					</div>
				</div>

			</div>

			<!-- Sidebar Actions Column -->
			<div class="acrb-sidebar-col">
				<div class="acrb-panel">
					<div class="acrb-panel-head">
						<span class="dashicons dashicons-update"></span>
						<h3><?php esc_html_e( 'Status & Billing', 'awesome-car-rental' ); ?></h3>
					</div>
					<div class="acrb-panel-body">
						<div class="acrb-form-group mb-18">
							<label class="acrb-label"><?php esc_html_e( 'Booking Status', 'awesome-car-rental' ); ?></label>
							<div class="acrb-select-wrap">
								<select name="acrb_status" class="acrb-field">
									<option value="pending" <?php selected( $acrb_edit_status, 'pending' ); ?>><?php esc_html_e( 'Pending Request', 'awesome-car-rental' ); ?></option>
									<option value="confirmed" <?php selected( $acrb_edit_status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'awesome-car-rental' ); ?></option>
									<option value="active" <?php selected( $acrb_edit_status, 'active' ); ?>><?php esc_html_e( 'Active / Vehicle Out', 'awesome-car-rental' ); ?></option>
									<option value="completed" <?php selected( $acrb_edit_status, 'completed' ); ?>><?php esc_html_e( 'Completed / Returned', 'awesome-car-rental' ); ?></option>
									<option value="cancelled" <?php selected( $acrb_edit_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'awesome-car-rental' ); ?></option>
								</select>
								<span class="acrb-select-chevron">
									<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
									</svg>
								</span>
							</div>
						</div>

						<div class="acrb-form-group mb-18">
							<label class="acrb-label">
								<?php
								/* translators: %s: Currency symbol */
								printf( esc_html__( 'Total Amount (%s)', 'awesome-car-rental' ), esc_html( $acrb_currency ) );
								?>
							</label>
							<input type="number" step="0.01" name="acrb_total_amount" value="<?php echo esc_attr( (string) $acrb_edit_total ); ?>" class="acrb-field">
						</div>

						<button type="submit" name="acrb_update_booking" class="acrb-btn-save">
							<span class="dashicons dashicons-saved" style="font-size:17px; width:17px; height:17px;"></span>
							<span><?php esc_html_e( 'Update Reservation', 'awesome-car-rental' ); ?></span>
						</button>
					</div>
				</div>
			</div>

		</div>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	$('#acrb_car_id_selector').on('change', function() {
		var selectedCarName = $(this).find(':selected').data('car-name');
		if (selectedCarName) {
			$('#acrb_hidden_car_name').val(selectedCarName);
		}
	});
});
</script>