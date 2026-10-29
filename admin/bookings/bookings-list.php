<?php
/**
 * Fleet Booking Directory Logic
 * Location: admin/bookings/acrb-bookings-list.php
 *
 * @package Awesome_Car_Rental
 * @since   2.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$acrb_table_bookings =$wpdb->prefix . 'dev_nahian_bookings';

// 1. Handle Quick Status Updates.
if ( isset( $_GET['acrb_action'] ) && 'set_status' === $_GET['acrb_action'] && isset($_GET['bid'] ) ) {
	if ( check_admin_referer( 'acrb_status_nonce' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			$acrb_booking_id = absint( $_GET['bid'] );$acrb_new_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update($acrb_table_bookings,
				array( 'booking_status' => $acrb_new_status ),
				array( 'id' => $acrb_booking_id ),
				array( '%s' ),
				array( '%d' )
			);

			delete_transient( 'acrb_dashboard_revenue' );

			echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0 0 0;"><p>';
			/* translators: 1: Booking ID number, 2: New status label */
			$acrb_success_msg1 = sprintf( esc_html__( 'Booking #%1$d updated to \%2$s.', 'awesome-car-rental' ), absint( $acrb_booking_id ), esc_html( ucfirst( str_replace( '_', ' ',$acrb_new_status ) ) ) );
			echo wp_kses_post( $acrb_success_msg1 );
			echo '</p></div>';
		}
	}
}

// 2. Handle Delete Feature.
if ( isset( $_GET['acrb_action'] ) && 'delete_booking' === $_GET['acrb_action'] && isset($_GET['bid'] ) ) {
	if ( check_admin_referer( 'acrb_delete_nonce' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			$acrb_booking_id = absint($_GET['bid'] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$acrb_delete_result = $wpdb->delete($acrb_table_bookings,
				array( 'id' => $acrb_booking_id ),
				array( '%d' )
			);

			if ( false !== $acrb_delete_result ) {
				delete_transient( 'acrb_dashboard_revenue' );

				echo '<div class="notice notice-warning is-dismissible" style="margin: 20px 0 0 0;"><p>';
				/* translators: %d: Booking ID number */
				$acrb_success_msg2 = sprintf( esc_html__( 'Booking #\%d deleted successfully.', 'awesome-car-rental' ), absint( $acrb_booking_id ) );
				echo wp_kses_post( $acrb_success_msg2 );
				echo '</p></div>';
			}
		}
	}
}

// 3. Fetch Bookings from Custom Database Table (`{$wpdb->prefix}dev_nahian_bookings`).
$acrb_filter_status = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : '';

$acrb_sql = "SELECT * FROM {$acrb_table_bookings}";
$acrb_sql_args = array();

if ( ! empty( $acrb_filter_status ) ) {$acrb_sql .= ' WHERE booking_status = %s';
	$acrb_sql_args[] =$acrb_filter_status;
}

$acrb_sql .= ' ORDER BY id DESC';

if ( ! empty( $acrb_sql_args ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	$acrb_bookings_list = $wpdb->get_results($wpdb->prepare( $acrb_sql, ...$acrb_sql_args ), ARRAY_A );
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$acrb_bookings_list = $wpdb->get_results($acrb_sql, ARRAY_A );
}

$acrb_currency     = get_option( 'acrb_currency', '$' );$acrb_base_tab_url = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings' );
?>

<div class="acrb-dash-wrap">
	<div class="acrb-table-wrap">
		<table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
			<thead>
				<tr>
					<th width="70" class="all"><?php esc_html_e( 'ID', 'awesome-car-rental' ); ?></th>
					<th><?php esc_html_e( 'Client Details', 'awesome-car-rental' ); ?></th>
					<th><?php esc_html_e( 'Vehicle Info', 'awesome-car-rental' ); ?></th>
					<th><?php esc_html_e( 'Total Price', 'awesome-car-rental' ); ?></th>
					<th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
					<th class="all" width="120" style="text-align:right;" data-orderable="false"><?php esc_html_e( 'Action', 'awesome-car-rental' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $acrb_bookings_list ) ) : ?>
					<?php
					foreach ( $acrb_bookings_list as$acrb_row ) :
						$acrb_booking_id    = absint($acrb_row['id'] );
						$acrb_booking_code  = esc_html($acrb_row['booking_code'] ?? '' );
						$acrb_customer_name = esc_html($acrb_row['customer_name'] ?? __( 'Guest User', 'awesome-car-rental' ) );
						$acrb_customer_mail = esc_html($acrb_row['customer_email'] ?? '—' );

						$acrb_car_name      = esc_html($acrb_row['car_name'] ?? __( 'Fleet Vehicle', 'awesome-car-rental' ) );

						$acrb_total_numeric = floatval($acrb_row['total_amount'] ?? 0 );
						$acrb_status_val    = sanitize_key($acrb_row['booking_status'] ?? 'pending' );

						$acrb_delete_url = wp_nonce_url( $acrb_base_tab_url . '&acrb_action=delete_booking&bid=' .$acrb_booking_id, 'acrb_delete_nonce' );
						$acrb_view_url   = wp_nonce_url($acrb_base_tab_url . '&action=view&booking_id=' . $acrb_booking_id, 'acrb_car_action' );$acrb_edit_url   = wp_nonce_url( $acrb_base_tab_url . '&action=edit&booking_id=' .$acrb_booking_id, 'acrb_car_action' );
						?>
						<tr>
							<td data-order="<?php echo absint( $acrb_booking_id ); ?>">
								<span class="acrb-id-badge">#<?php echo absint( $acrb_booking_id ); ?></span>
							</td>
							<td>
								<strong class="acrb-client-name"><?php echo esc_html( $acrb_customer_name ); ?></strong><br>
								<span class="acrb-client-email"><?php echo esc_html( $acrb_customer_mail ); ?></span>
							</td>
							<td>
								<span class="acrb-car-title"><?php echo esc_html( $acrb_car_name ); ?></span><br>
								<span class="acrb-ref-tag">
									<?php
									if ( ! empty( $acrb_booking_code ) ) {
										/* translators: %s: Booking code string */
										printf( esc_html__( 'Code: %s', 'awesome-car-rental' ), esc_html( $acrb_booking_code ) );
									} else {
										/* translators: %d: Booking ID number */
										printf( esc_html__( 'Ref: #%d', 'awesome-car-rental' ), absint( $acrb_booking_id ) );
									}
									?>
								</span>
							</td>
							<td data-order="<?php echo esc_attr( (string) $acrb_total_numeric ); ?>">
								<strong class="acrb-price-display">
									<?php echo esc_html( $acrb_currency ) . esc_html( number_format( $acrb_total_numeric, 2 ) ); ?>
								</strong>
							</td>
							<td>
								<span class="acrb-status status-<?php echo esc_attr( $acrb_status_val ); ?>">
									<?php echo esc_html( ucfirst( str_replace( '_', ' ', $acrb_status_val ) ) ); ?>
								</span>
							</td>
							<td style="text-align:right;">
								<div class="acrb-actions-group">
									<a class="acrb-btn-action" href="<?php echo esc_url( $acrb_view_url ); ?>" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
										<span class="dashicons dashicons-visibility"></span>
									</a>
									<a class="acrb-btn-action" href="<?php echo esc_url( $acrb_edit_url ); ?>" title="<?php esc_attr_e( 'Edit Booking', 'awesome-car-rental' ); ?>">
										<span class="dashicons dashicons-edit"></span>
									</a>
									<a class="acrb-btn-action acrb-btn-delete" 
										href="<?php echo esc_url( $acrb_delete_url ); ?>" 
										onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this booking?', 'awesome-car-rental' ); ?>');"
										title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>"
										style="color: #b91c1c;">
										<span class="dashicons dashicons-trash"></span>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6" style="text-align:center; padding: 40px; color:#64748b;"><?php esc_html_e( 'No bookings found.', 'awesome-car-rental' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>