<?php
/**
 * Main Bookings Tab Logic & Router
 * Routes actions to dedicated booking modules using the custom database table architecture.
 *
 * @package Awesome_Car_Rental
 * @since   1.2.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acrb_bookings_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
	}

	global $wpdb;

	$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
	$action         = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$booking_id     = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
	$base_url       = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings' );

	/*--------------------------------------------------------------
	# 1. Action Handler (Destructive Actions)
	--------------------------------------------------------------*/
	if ( 'delete' === $action && $booking_id > 0 ) {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		// Verify specific nonce for this booking ID
		if ( ! wp_verify_nonce( $nonce, 'acrb_delete_booking_' . $booking_id ) && ! wp_verify_nonce( $nonce, 'acrb_booking_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'awesome-car-rental' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table_bookings,
			array( 'id' => $booking_id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			wp_die( esc_html__( 'Failed to delete the booking record from the database.', 'awesome-car-rental' ) );
		}

		// Invalidate cached stats
		delete_transient( 'acrb_dashboard_revenue' );
		delete_transient( 'acrb_booking_counts' );

		$redirect_url = add_query_arg(
			array(
				'page' => 'awesome_car_rental',
				'tab'  => 'bookings',
				'msg'  => ( $deleted > 0 ) ? 'deleted' : 'not_found',
			),
			admin_url( 'admin.php' )
		);

		if ( ! headers_sent() ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}

		echo '<script>window.location.href=' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ';</script>';
		exit;
	}

	/*--------------------------------------------------------------
	# 2. Cached Quick Stats for Header Badges
	--------------------------------------------------------------*/
	$counts = get_transient( 'acrb_booking_counts' );

	if ( false === $counts ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_bookings}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_bookings} WHERE booking_status IN ('confirmed', 'active')" );

		$counts = array(
			'total'  => $total_count,
			'active' => $active_count,
		);

		set_transient( 'acrb_booking_counts', $counts, 10 * MINUTE_IN_SECONDS );
	}

	$total_count  = $counts['total'];
	$active_count = $counts['active'];

	/*--------------------------------------------------------------
	# 3. Flash Feedback Messages
	--------------------------------------------------------------*/
	$msg = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
	?>

	<div class="acrb-dash-wrap">

		<?php if ( 'deleted' === $msg ) : ?>
			<div class="notice notice-success is-dismissible" style="margin: 15px 0;">
				<p><?php esc_html_e( 'Booking record deleted successfully.', 'awesome-car-rental' ); ?></p>
			</div>
		<?php elseif ( 'not_found' === $msg ) : ?>
			<div class="notice notice-warning is-dismissible" style="margin: 15px 0;">
				<p><?php esc_html_e( 'The requested booking record was not found or already deleted.', 'awesome-car-rental' ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Styled Topbar -->
		<div class="acrb-topbar-header">
			<div class="acrb-topbar-left">
				<div class="acrb-topbar-icon">
					<span class="dashicons dashicons-calendar-alt"></span>
				</div>
				<div class="acrb-topbar-text">
					<h1>
						<?php
						if ( 'edit' === $action ) {
							esc_html_e( 'Edit Reservation', 'awesome-car-rental' );
						} elseif ( 'view' === $action ) {
							esc_html_e( 'Reservation Details', 'awesome-car-rental' );
						} elseif ( 'print' === $action ) {
							esc_html_e( 'Print Document', 'awesome-car-rental' );
						} else {
							esc_html_e( 'Bookings & Reservations', 'awesome-car-rental' );
						}
						?>
					</h1>
					<p>
						<?php
						if ( ! empty( $action ) && $booking_id > 0 ) {
							printf(
								/* translators: %d: Booking ID number */
								esc_html__( 'Managing details and parameters for record #%d', 'awesome-car-rental' ),
								absint( $booking_id )
							);
						} else {
							esc_html_e( 'Manage vehicle schedules, monitor customer reservations, and track dispatch status.', 'awesome-car-rental' );
						}
						?>
					</p>
				</div>
			</div>

			<div class="acrb-topbar-actions">
				<?php if ( ! empty( $action ) ) : ?>
					<a href="<?php echo esc_url( $base_url ); ?>" class="acrb-topbar-btn button">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<span><?php esc_html_e( 'Back to List', 'awesome-car-rental' ); ?></span>
					</a>
				<?php else : ?>
					<div class="acrb-stat-pill">
						<span><?php esc_html_e( 'Active:', 'awesome-car-rental' ); ?></span>
						<strong><?php echo esc_html( (string) $active_count ); ?></strong>
						<span style="color:#cbd5e1; margin: 0 4px;">|</span>
						<span><?php esc_html_e( 'Total:', 'awesome-car-rental' ); ?></span>
						<strong><?php echo esc_html( (string) $total_count ); ?></strong>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Main Content Router -->
		<main class="acrb-main-container">
			<?php
			switch ( $action ) {
				case 'edit':
					if ( function_exists( 'acrb_render_booking_form' ) ) {
						acrb_render_booking_form( $booking_id );
					} elseif ( file_exists( ACRB_PATH . 'admin/bookings/bookings-edit.php' ) ) {
						require_once ACRB_PATH . 'admin/bookings/bookings-edit.php';
					} else {
						acrb_load_default_booking_list();
					}
					break;

				case 'view':
					if ( function_exists( 'acrb_render_booking_view' ) ) {
						acrb_render_booking_view( $booking_id );
					} elseif ( file_exists( ACRB_PATH . 'admin/bookings/bookings-view.php' ) ) {
						require_once ACRB_PATH . 'admin/bookings/bookings-view.php';
					} else {
						acrb_load_default_booking_list();
					}
					break;

				case 'print':
					if ( function_exists( 'acrb_render_booking_print' ) ) {
						acrb_render_booking_print( $booking_id );
					} elseif ( file_exists( ACRB_PATH . 'admin/bookings/bookings-print.php' ) ) {
						require_once ACRB_PATH . 'admin/bookings/bookings-print.php';
					} else {
						acrb_load_default_booking_list();
					}
					break;

				default:
					acrb_load_default_booking_list();
					break;
			}
			?>
		</main>
	</div>
	<?php
}

/**
 * Helper to safely resolve the default list view without duplicated require calls
 */
function acrb_load_default_booking_list() {
	if ( function_exists( 'acrb_render_booking_list' ) ) {
		acrb_render_booking_list();
	} elseif ( file_exists( ACRB_PATH . 'admin/bookings/acrb-bookings-list.php' ) ) {
		require_once ACRB_PATH . 'admin/bookings/acrb-bookings-list.php';
	} elseif ( file_exists( ACRB_PATH . 'admin/bookings/bookings-list.php' ) ) {
		require_once ACRB_PATH . 'admin/bookings/bookings-list.php';
	} else {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Error: Bookings list template file could not be found.', 'awesome-car-rental' ) . '</p></div>';
	}
}