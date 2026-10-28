<?php
/**
 * Inspections List Component (`inspections-list.php`)
 * Location: admin/inspections/inspections-list.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$table_cars        = $wpdb->prefix . 'dev_nahian_cars';
?>

<div class="acrb-table-wrap">
	<table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Stage', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Mileage / Fuel', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Booking Ref', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Inspector', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
				<th class="all" style="text-align:right; width:120px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$reports = $wpdb->get_results( "SELECT * FROM {$table_inspections} ORDER BY id DESC", ARRAY_A );

			if ( ! empty( $reports ) ) :
				foreach ( $reports as $rep ) :
					$rep_id     = absint( $rep['id'] );
					$car_id     = absint( $rep['car_id'] );
					
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$car_title  = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ) ) ?: __( 'Unknown Vehicle', 'awesome-car-rental' );
					
					$booking_id = absint( $rep['booking_id'] );
					$i_type     = (string) ( $rep['insp_type'] ?? 'Check-Out' );
					$i_mileage  = (string) ( $rep['mileage'] ?? '—' );
					$i_fuel     = (string) ( $rep['fuel_level'] ?? '—' );
					$inspector  = (string) ( $rep['inspector'] ?? 'Admin' );
					$i_date     = (string) ( $rep['insp_date'] ?? '' );
					
					$view_url = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=view&insp_id=' . $rep_id );
					$edit_url = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=edit&insp_id=' . $rep_id );
					$del_url  = wp_nonce_url( admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=delete&insp_id=' . $rep_id ), 'acrb_del_insp_' . $rep_id );
			?>
			<tr>
				<td><strong><?php echo esc_html( $car_title ); ?></strong></td>
				<td>
					<?php 
					$badge_bg = '#e0f2fe'; $badge_color = '#0369a1';
					if ( 'Check-In' === $i_type ) { $badge_bg = '#dcfce7'; $badge_color = '#15803d'; } 
					elseif ( 'Periodic Check' === $i_type ) { $badge_bg = '#fef3c7'; $badge_color = '#d97706'; }
					?>
					<span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; text-transform:uppercase;">
						<?php echo esc_html( $i_type ); ?>
					</span>
				</td>
				<td><?php echo esc_html( $i_mileage . ' / ' . $i_fuel ); ?></td>
				<td data-order="<?php echo $booking_id; ?>"><?php echo $booking_id ? sprintf( '#%d', $booking_id ) : '—'; ?></td>
				<td><?php echo esc_html( $inspector ); ?></td>
				<td data-order="<?php echo esc_attr( $i_date ); ?>"><?php echo esc_html( $i_date ); ?></td>
				<td style="text-align:right;">
					<div class="acrb-actions-group">
						<a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</a>
						<a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this inspection report?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
			<tr>
				<td colspan="7" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No inspection reports found.', 'awesome-car-rental' ); ?></td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>