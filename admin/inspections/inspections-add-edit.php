<?php
/**
 * Add / Edit Inspection Component (`inspections-add-edit.php`)
 * Location: admin/inspections/inspections-add-edit.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$table_cars        = $wpdb->prefix . 'dev_nahian_cars';
$table_bookings    = $wpdb->prefix . 'dev_nahian_bookings';

if ( isset( $_POST['acrb_save_inspection'] ) ) {
	$nonce = isset( $_POST['acrb_insp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_insp_nonce'] ) ) : '';

	if ( wp_verify_nonce( $nonce, 'acrb_insp_action' ) ) {
		$inspection_id = isset( $_POST['insp_id'] ) ? absint( $_POST['insp_id'] ) : 0;
		$car_id        = isset( $_POST['insp_car_id'] ) ? absint( $_POST['insp_car_id'] ) : 0;
		$booking_id    = isset( $_POST['insp_booking_id'] ) ? absint( $_POST['insp_booking_id'] ) : 0;
		$insp_type     = isset( $_POST['insp_type'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_type'] ) ) : 'Check-Out';
		$insp_date     = isset( $_POST['insp_date'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_date'] ) ) : current_time( 'Y-m-d H:i:s' );
		$mileage       = isset( $_POST['insp_mileage'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_mileage'] ) ) : '';
		$fuel_level    = isset( $_POST['insp_fuel'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_fuel'] ) ) : 'Full (100%)';
		$cleanliness   = isset( $_POST['insp_cleanliness'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_cleanliness'] ) ) : 'Clean';
		$damages       = isset( $_POST['insp_damages'] ) ? sanitize_textarea_field( wp_unslash( $_POST['insp_damages'] ) ) : '';
		$inspector     = isset( $_POST['insp_inspector'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_inspector'] ) ) : '';

		if ( $car_id > 0 ) {
			$data = array(
				'car_id'      => $car_id,
				'booking_id'  => $booking_id,
				'insp_type'   => $insp_type,
				'insp_date'   => $insp_date,
				'mileage'     => $mileage,
				'fuel_level'  => $fuel_level,
				'cleanliness' => $cleanliness,
				'damages'     => $damages,
				'inspector'   => $inspector,
			);
			$formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

			if ( $inspection_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $table_inspections, $data, array( 'id' => $inspection_id ), $formats, array( '%d' ) );
				echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>Success:</strong> Inspection updated successfully.</p></div>';
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert( $table_inspections, $data, $formats );
				echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>Success:</strong> New inspection logged.</p></div>';
			}
		}
	}
}

$insp_record = null;
if ( $insp_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$insp_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_inspections} WHERE id = %d", $insp_id ), ARRAY_A );
}

$i_car_id     = $insp_record ? absint( $insp_record['car_id'] ) : 0;
$i_booking_id = $insp_record ? absint( $insp_record['booking_id'] ) : 0;
$i_type       = $insp_record ? (string) $insp_record['insp_type'] : 'Check-Out';
$i_date       = $insp_record ? (string) $insp_record['insp_date'] : current_time( 'Y-m-d H:i:s' );
$i_mileage    = $insp_record ? (string) $insp_record['mileage'] : '';
$i_fuel       = $insp_record ? (string) $insp_record['fuel_level'] : 'Full (100%)';
$i_clean      = $insp_record ? (string) $insp_record['cleanliness'] : 'Clean';
$i_damages    = $insp_record ? (string) $insp_record['damages'] : '';
$i_inspector  = $insp_record ? (string) $insp_record['inspector'] : wp_get_current_user()->display_name;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cars = $wpdb->get_results( "SELECT id, name FROM {$table_cars} ORDER BY name ASC", ARRAY_A );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$bookings = $wpdb->get_results( "SELECT id, customer_name FROM {$table_bookings} ORDER BY id DESC LIMIT 50", ARRAY_A );
?>

<div class="acrb-card">
	<h2><?php echo $insp_id > 0 ? esc_html__( 'Edit Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Create New Inspection Report', 'awesome-car-rental' ); ?></h2>
	
	<form method="post">
		<?php wp_nonce_field( 'acrb_insp_action', 'acrb_insp_nonce' ); ?>
		<input type="hidden" name="insp_id" value="<?php echo absint( $insp_id ); ?>">

		<div class="acrb-form-grid-2">
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Select Vehicle', 'awesome-car-rental' ); ?></label>
				<select name="insp_car_id" class="acrb-select" required>
					<option value=""><?php esc_html_e( '-- Choose Car --', 'awesome-car-rental' ); ?></option>
					<?php foreach ( $cars as $car ) : ?>
						<option value="<?php echo absint( $car['id'] ); ?>" <?php selected( $i_car_id, $car['id'] ); ?>>
							<?php echo esc_html( (string) $car['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Inspection Stage', 'awesome-car-rental' ); ?></label>
				<select name="insp_type" class="acrb-select">
					<option value="Check-Out" <?php selected( $i_type, 'Check-Out' ); ?>><?php esc_html_e( '🚗 Check-Out (Before Rental)', 'awesome-car-rental' ); ?></option>
					<option value="Check-In" <?php selected( $i_type, 'Check-In' ); ?>><?php esc_html_e( '🏁 Check-In (After Return)', 'awesome-car-rental' ); ?></option>
					<option value="Periodic Check" <?php selected( $i_type, 'Periodic Check' ); ?>><?php esc_html_e( '🛠️ Periodic Maintenance Audit', 'awesome-car-rental' ); ?></option>
				</select>
			</div>
		</div>

		<div class="acrb-form-grid-2">
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Linked Booking (Optional)', 'awesome-car-rental' ); ?></label>
				<select name="insp_booking_id" class="acrb-select">
					<option value="0"><?php esc_html_e( '-- None / General Audit --', 'awesome-car-rental' ); ?></option>
					<?php foreach ( $bookings as $b ) : 
						$b_id = absint( $b['id'] );
						$c_name = (string) ( $b['customer_name'] ?? 'Guest' );
					?>
						<option value="<?php echo $b_id; ?>" <?php selected( $i_booking_id, $b_id ); ?>>
							<?php printf( '#%d — %s', $b_id, esc_html( $c_name ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Inspection Date & Time', 'awesome-car-rental' ); ?></label>
				<input type="text" name="insp_date" value="<?php echo esc_attr( $i_date ); ?>" class="acrb-input">
			</div>
		</div>

		<div class="acrb-form-grid-3">
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Current Mileage', 'awesome-car-rental' ); ?></label>
				<input type="text" name="insp_mileage" value="<?php echo esc_attr( $i_mileage ); ?>" class="acrb-input" placeholder="e.g. 45,200 km">
			</div>
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Fuel Level', 'awesome-car-rental' ); ?></label>
				<select name="insp_fuel" class="acrb-select">
					<option value="Full (100%)" <?php selected( $i_fuel, 'Full (100%)' ); ?>>Full (100%)</option>
					<option value="3/4 Tank" <?php selected( $i_fuel, '3/4 Tank' ); ?>>3/4 Tank</option>
					<option value="1/2 Tank" <?php selected( $i_fuel, '1/2 Tank' ); ?>>1/2 Tank</option>
					<option value="1/4 Tank" <?php selected( $i_fuel, '1/4 Tank' ); ?>>1/4 Tank</option>
					<option value="Empty" <?php selected( $i_fuel, 'Empty' ); ?>>Empty</option>
				</select>
			</div>
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Cleanliness', 'awesome-car-rental' ); ?></label>
				<select name="insp_cleanliness" class="acrb-select">
					<option value="Pristine" <?php selected( $i_clean, 'Pristine' ); ?>>Pristine</option>
					<option value="Clean" <?php selected( $i_clean, 'Clean' ); ?>>Clean</option>
					<option value="Needs Wash" <?php selected( $i_clean, 'Needs Wash' ); ?>>Needs Wash</option>
					<option value="Dirty / Muddy" <?php selected( $i_clean, 'Dirty / Muddy' ); ?>>Dirty / Muddy</option>
				</select>
			</div>
		</div>

		<div class="acrb-form-group" style="margin-bottom:20px;">
			<label class="acrb-label"><?php esc_html_e( 'Damage Log / Scratches / Dents', 'awesome-car-rental' ); ?></label>
			<textarea name="insp_damages" rows="3" class="acrb-textarea" placeholder="Describe any existing or new scratches..."><?php echo esc_textarea( $i_damages ); ?></textarea>
		</div>

		<div class="acrb-form-group" style="margin-bottom:24px; max-width:400px;">
			<label class="acrb-label"><?php esc_html_e( 'Inspector Name / Staff', 'awesome-car-rental' ); ?></label>
			<input type="text" name="insp_inspector" value="<?php echo esc_attr( $i_inspector ); ?>" class="acrb-input">
		</div>

		<button type="submit" name="acrb_save_inspection" class="acrb-btn-primary">
			<?php echo $insp_id > 0 ? esc_html__( 'Update Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Save Inspection Report', 'awesome-car-rental' ); ?>
		</button>
	</form>
</div>