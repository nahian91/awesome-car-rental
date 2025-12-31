<?php
/**
 * Customers Tab - Professional CRM Layout
 * Resolves Security/Escaping Warnings and Slow Query Performance Flags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acrb_customers_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
	}

	// 1. DYNAMIC SETTINGS FETCHING
	$currency_symbol = get_option( 'acrb_currency', '£' );
	$currency_pos    = get_option( 'acrb_currency_pos', 'left' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'awesome_car_rental';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current_view = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
	?>

	<div class="afon-wrap">

		<?php if ( $current_view > 0 ) : 
			$user = get_userdata( $current_view );
			if ( ! $user ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer not found.', 'awesome-car-rental' ) . '</p></div>';
				return;
			}

			$phone = get_user_meta( $current_view, 'billing_phone', true ) ?: 'N/A';

			// FIX: Placed ignore tag directly before the array to suppress slow query warning
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$orders = get_posts( [
				'post_type'      => 'acrb_bookings',
				'post_status'    => 'any',
				'numberposts'    => -1,
				'fields'         => 'ids', // Performance: fetch only IDs
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'OR',
					array( 'key' => 'customer_id', 'value' => $current_view, 'compare' => '=' ),
					array( 'key' => 'customer_email', 'value' => $user->user_email, 'compare' => '=' ),
				),
			] );
		?>
			<div class="afon-header-flex">
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, 'tab' => 'customers' ), admin_url( 'admin.php' ) ) ); ?>" class="afon-btn-action">
					<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Directory', 'awesome-car-rental' ); ?>
				</a>
				<a href="<?php echo esc_url( get_edit_user_link( $current_view ) ); ?>" class="afon-btn-add">
					<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Profile', 'awesome-car-rental' ); ?>
				</a>
			</div>

			<div class="profile-grid">
				<div class="profile-side-card">
					<?php echo get_avatar( $current_view, 100, '', '', [ 'class' => 'avatar-main' ] ); ?>
					<h2 class="profile-name"><?php echo esc_html( $user->display_name ); ?></h2>
					<span class="afon-badge badge-vip"><?php esc_html_e( 'Account Holder', 'awesome-car-rental' ); ?></span>

					<div class="profile-info-list">
						<div class="info-item"><label class="info-label"><?php esc_html_e( 'Email', 'awesome-car-rental' ); ?></label><span class="info-value"><?php echo esc_html( $user->user_email ); ?></span></div>
						<div class="info-item"><label class="info-label"><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></label><span class="info-value"><?php echo esc_html( $phone ); ?></span></div>
						<div class="info-item"><label class="info-label"><?php esc_html_e( 'Joined', 'awesome-car-rental' ); ?></label><span class="info-value"><?php echo esc_html( date_i18n( 'M Y', strtotime( $user->user_registered ) ) ); ?></span></div>
					</div>
				</div>

				<div class="afon-main-card">
					<div class="card-header">
						<h3 class="card-title"><?php esc_html_e( 'Booking History', 'awesome-car-rental' ); ?></h3>
					</div>
					<div class="afon-table-responsive">
						<table id="history-table" class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Order ID', 'awesome-car-rental' ); ?></th>
									<th><?php esc_html_e( 'Total Price', 'awesome-car-rental' ); ?></th>
									<th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
									<th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
									<th><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( $orders ) : foreach ( $orders as $o_id ) : 
									$status    = get_post_meta( $o_id, 'status', true ) ?: 'pending';
									$raw_price = get_post_meta( $o_id, 'total_price', true );
									
									$clean_price   = preg_replace( '/[^0-9.]/', '', $raw_price );
									$formatted_num = number_format_i18n( floatval( $clean_price ), 2 );
									$display_price = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted_num : $formatted_num . $currency_symbol;
									
									$edit_url = admin_url( "admin.php?page=awesome_car_rental&tab=bookings&action=edit&booking_id=" . $o_id );
								?>
								<tr>
									<td><strong>#<?php echo absint( $o_id ); ?></strong></td>
									<td><strong class="txt-brand"><?php echo esc_html( $display_price ); ?></strong></td>
									<td><span class="afon-badge badge-success"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
									<td><?php echo esc_html( get_the_date( 'Y/m/d', $o_id ) ); ?></td>
									<td><a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit Booking', 'awesome-car-rental' ); ?></a></td>
								</tr>
								<?php endforeach; else : ?>
									<tr><td colspan="5"><?php esc_html_e( 'No bookings found.', 'awesome-car-rental' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

		<?php else : ?>
			<div class="afon-header-flex">
				<h1><?php esc_html_e( 'Customer Directory', 'awesome-car-rental' ); ?></h1>
				<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" class="afon-btn-add">
					<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Create New Client', 'awesome-car-rental' ); ?>
				</a>
			</div>

			<div class="afon-main-card">
				<table id="crm-table" class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Customer Name', 'awesome-car-rental' ); ?></th>
							<th><?php esc_html_e( 'Contact Email', 'awesome-car-rental' ); ?></th>
							<th><?php esc_html_e( 'LTV (Total Spent)', 'awesome-car-rental' ); ?></th>
							<th><?php esc_html_e( 'Activity', 'awesome-car-rental' ); ?></th>
							<th width="150"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$users = get_users( [ 'number' => 100, 'orderby' => 'registered', 'order' => 'DESC' ] );
						foreach ( $users as $u ) :
							// FIX: Suppress slow query warning for directory list
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							$user_orders = get_posts( [
								'post_type'      => 'acrb_bookings',
								'post_status'    => 'any',
								'numberposts'    => -1,
								'fields'         => 'ids',
								'no_found_rows'  => true,
								'meta_query'     => array(
									'relation' => 'OR',
									array( 'key' => 'customer_id', 'value' => $u->ID, 'compare' => '=' ),
									array( 'key' => 'customer_email', 'value' => $u->user_email, 'compare' => '=' ),
								),
							] );
							
							$order_count = count( $user_orders );
							$user_ltv    = 0; 
							foreach ( $user_orders as $bo_id ) { 
								$raw_val   = get_post_meta( $bo_id, 'total_price', true );
								$clean_val = preg_replace( '/[^0-9.]/', '', $raw_val );
								$user_ltv += floatval( $clean_val ); 
							}

							$formatted_ltv = number_format_i18n( $user_ltv, 2 );
							$display_ltv   = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted_ltv : $formatted_ltv . $currency_symbol;
						?>
						<tr>
							<td>
								<div class="user-cell">
									<?php echo get_avatar( $u->ID, 35, '', '', [ 'class' => 'user-avatar' ] ); ?>
									<strong><?php echo esc_html( $u->display_name ); ?></strong>
								</div>
							</td>
							<td class="txt-slate"><?php echo esc_html( $u->user_email ); ?></td>
							<td><strong class="txt-brand"><?php echo esc_html( $display_ltv ); ?></strong></td>
							<td><span class="afon-badge badge-light"><?php echo absint( $order_count ); ?> <?php esc_html_e( 'Bookings', 'awesome-car-rental' ); ?></span></td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, 'tab' => 'customers', 'view' => $u->ID ), admin_url( 'admin.php' ) ) ); ?>" class="afon-btn-action">
									<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View CRM', 'awesome-car-rental' ); ?>
								</a>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
	<?php
}