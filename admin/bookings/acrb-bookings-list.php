<?php
/**
 * Fleet Booking Directory Logic
 * Location: admin/bookings-directory.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Handle Quick Status Updates
if ( isset( $_GET['acrb_action'] ) && 'set_status' === $_GET['acrb_action'] && isset( $_GET['bid'] ) ) {
    if ( check_admin_referer( 'acrb_status_nonce' ) ) {
        $acrb_bid        = intval( $_GET['bid'] );
        $acrb_new_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
        update_post_meta( $acrb_bid, 'status', $acrb_new_status );

        echo '<div class="notice notice-success is-dismissible"><p>';
        /* translators: 1: Booking ID, 2: New status name */
       /* translators: 1: Booking ID, 2: New status name */
$acrb_msg1 = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );

echo wp_kses_post( $acrb_msg1 );
        echo '</p></div>';
    }
}

// 2. Handle Delete Feature
if ( isset( $_GET['acrb_action'] ) && 'delete_booking' === $_GET['acrb_action'] && isset( $_GET['bid'] ) ) {
	if ( check_admin_referer( 'acrb_delete_nonce' ) ) {
		$acrb_bid        = intval( $_GET['bid'] );
		$acrb_del_result = wp_trash_post( $acrb_bid );

		if ( $acrb_del_result ) {
			echo '<div class="notice notice-warning is-dismissible"><p>';
			/* translators: %d: The ID number of the booking being trashed */
			/* translators: 1: Booking ID, 2: New status name */
$acrb_msg2 = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );
echo wp_kses_post( $acrb_msg2 );
			echo '</p></div>';
		}
	}
}

// 3. Fetch Data
$acrb_bookings_list = get_posts(
	array(
		'post_type'   => 'acrb_bookings',
		'numberposts' => -1,
		'post_status' => 'any',
		'orderby'     => 'date',
		'order'       => 'DESC',
	)
);

$acrb_currency     = get_option( 'acrb_currency', '£' );
$acrb_base_tab_url = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings' );
?>

<div class="wrap acrb-dashboard">
	<div class="acrb-header-flex">
		<div>
			<h1 class="acrb-main-title"><?php esc_html_e( 'Fleet Booking Directory', 'awesome-car-rental' ); ?></h1>
			<p class="acrb-sub-title"><?php esc_html_e( 'Manage and monitor all incoming vehicle reservations.', 'awesome-car-rental' ); ?></p>
		</div>
	</div>

	<table id="acrb-bookings-table" class="widefat">
		<thead>
			<tr>
				<th width="80"><?php esc_html_e( 'ID', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Client Details', 'awesome-car-rental' ); ?></th>
				<th width="20%"><?php esc_html_e( 'Rental Schedule', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Vehicle Info', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Total Price', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
				<th width="220" class="acrb-text-right"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $acrb_bookings_list ) ) : ?>
				<?php
				foreach ( $acrb_bookings_list as $acrb_post ) :
					$acrb_bid            = $acrb_post->ID;
					$acrb_customer_name  = get_post_meta( $acrb_bid, 'customer_name', true );
					$acrb_customer_email = get_post_meta( $acrb_bid, 'customer_email', true );
					$acrb_car_id         = get_post_meta( $acrb_bid, 'car_id', true );
					$acrb_total_val      = get_post_meta( $acrb_bid, 'total_price', true );
					$acrb_total_numeric  = is_numeric( $acrb_total_val ) ? floatval( $acrb_total_val ) : 0;
					$acrb_status_val     = get_post_meta( $acrb_bid, 'status', true ) ?: 'pending';
					$acrb_start_date     = get_post_meta( $acrb_bid, 'pickup_date', true );
					$acrb_end_date       = get_post_meta( $acrb_bid, 'return_date', true );
					$acrb_car_title      = $acrb_car_id ? get_the_title( $acrb_car_id ) : __( 'Deleted Vehicle', 'awesome-car-rental' );

					$acrb_delete_url = wp_nonce_url( $acrb_base_tab_url . '&acrb_action=delete_booking&bid=' . $acrb_bid, 'acrb_delete_nonce' );
					?>
					<tr>
						<td><span class="acrb-id-badge">#<?php echo absint( $acrb_bid ); ?></span></td>
						<td>
							<strong class="acrb-client-name"><?php echo esc_html( $acrb_customer_name ?: __( 'Guest User', 'awesome-car-rental' ) ); ?></strong><br>
							<span class="acrb-client-email"><?php echo esc_html( $acrb_customer_email ); ?></span>
						</td>
						<td>
							<div class="acrb-rental-meta">
								<span class="acrb-date-tag">📅 <strong>In:</strong> <?php echo esc_html( $acrb_start_date ?: '—' ); ?></span>
								<span class="acrb-date-tag">🏁 <strong>Out:</strong> <?php echo esc_html( $acrb_end_date ?: '—' ); ?></span>
							</div>
						</td>
						<td>
							<span class="acrb-car-title"><?php echo esc_html( $acrb_car_title ); ?></span><br>
							<span class="acrb-ref-tag">
								<?php
								/* translators: %s: date-based reference string */
								printf( esc_html__( 'Ref: %s', 'awesome-car-rental' ), esc_html( get_the_date( 'Ymd-Hi', $acrb_bid ) ) );
								?>
							</span>
						</td>
						<td>
							<strong class="acrb-price-display">
								<?php echo esc_html( $acrb_currency ) . esc_html( number_format( $acrb_total_numeric, 2 ) ); ?>
							</strong>
						</td>
						<td>
							<span class="acrb-status status-<?php echo esc_attr( $acrb_status_val ); ?>">
								<?php echo esc_html( ucfirst( $acrb_status_val ) ); ?>
							</span>
						</td>
						<td class="acrb-text-right">
    <div class="acrb-actions-group">
        <?php 
        // Generate secure URLs for View and Edit
        $view_url = wp_nonce_url( $acrb_base_tab_url . '&action=view&booking_id=' . absint( $acrb_bid ), 'acrb_car_action' );
        $edit_url = wp_nonce_url( $acrb_base_tab_url . '&action=edit&booking_id=' . absint( $acrb_bid ), 'acrb_car_action' );
        ?>

        <a class="acrb-btn" href="<?php echo esc_url( $view_url ); ?>" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
            <span class="dashicons dashicons-visibility"></span>
        </a>
        
        <a class="acrb-btn" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit Booking', 'awesome-car-rental' ); ?>">
            <span class="dashicons dashicons-edit"></span>
        </a>

        <a class="acrb-btn acrb-btn-delete" 
           href="<?php echo esc_url( $acrb_delete_url ); ?>" 
           onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to move this booking to trash?', 'awesome-car-rental' ); ?>');"
           style="color: #d63638;">
            <span class="dashicons dashicons-trash"></span>
        </a>
    </div>
</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="7" style="text-align:center; padding: 20px;"><?php esc_html_e( 'No bookings found.', 'awesome-car-rental' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>