<?php
/**
 * Single Inspection Detail View (`inspections-view.php`)
 * Location: admin/inspections/inspections-view.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$table_cars        = $wpdb->prefix . 'dev_nahian_cars';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_inspections} WHERE id = %d", $insp_id ), ARRAY_A );

if ( ! $record ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Inspection record not found.', 'awesome-car-rental' ) . '</p></div>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$car_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", absint( $record['car_id'] ) ) ) ?: 'Vehicle';
?>

<div class="acrb-card">
	<h2><?php printf( esc_html__( 'Inspection Report #%d Details', 'awesome-car-rental' ), absint( $record['id'] ) ); ?></h2>
	
	<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
		<div>
			<p><strong><?php esc_html_e( 'Vehicle:', 'awesome-car-rental' ); ?></strong> <?php echo esc_html( $car_name ); ?></p>
			<p><strong><?php esc_html_e( 'Inspection Stage:', 'awesome-car-rental' ); ?></strong> <?php echo esc_html( $record['insp_type'] ); ?></p>
			<p><strong><?php esc_html_e( 'Linked Booking:', 'awesome-car-rental' ); ?></strong> <?php echo absint( $record['booking_id'] ) ? sprintf( '#%d', absint( $record['booking_id'] ) ) : 'None'; ?></p>
			<p><strong><?php esc_html_e( 'Date & Time:', 'awesome-car-rental' ); ?></strong> <?php echo esc_html( $record['insp_date'] ); ?></p>
		</div>
		<div>
			<p><strong><?php esc_html_e( 'Current Mileage:', 'awesome-car-rental' ); ?></strong> <?php echo esc_html( $record['mileage'] ); ?></p>
			<p><strong><?php esc_html_e( 'Fuel Level:', 'awesome-car-rental' ); ?></strong> <?php echo esc_html( $record['fuel_level'] ); ?></p>
			<p><strong><?php esc_html_e( 'Cleanliness:', 'awesome-car-rental' ); ?></strong> <?php echo esc_html( $record['cleanliness'] ); ?></p>
			<p><strong><?php esc_html_e( 'Inspector:', 'awesome-car-rental' ); ?></strong> <?php echo esc_html( $record['inspector'] ); ?></p>
		</div>
	</div>

	<div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
		<strong><?php esc_html_e( 'Damage Log & Notes:', 'awesome-car-rental' ); ?></strong>
		<p style="margin: 6px 0 0; color: #334155;"><?php echo nl2br( esc_html( $record['damages'] ?: 'No damages recorded.' ) ); ?></p>
	</div>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=all' ) ); ?>" class="acrb-btn-primary">
		← <?php esc_html_e( 'Back to Inspections Directory', 'awesome-car-rental' ); ?>
	</a>
</div>