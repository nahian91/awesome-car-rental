<?php
/**
 * Cars & Fleet Management Module Dispatcher (`cars.php`)
 * Location: admin/cars.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acrb_cars_tab(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
	}

	$acrb_page_slug = 'awesome_car_rental';

	// Routing query parameters for view determination (read-only GET requests).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_car_id    = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

	if ( $acrb_edit_id > 0 ) {
		$acrb_sub_state = 'edit';
		$acrb_car_id    = $acrb_edit_id;
	} elseif ( $acrb_view_id > 0 ) {
		$acrb_sub_state = 'view';
		$acrb_car_id    = $acrb_view_id;
	}

	// Feedback message flags for view notices (read-only GET requests).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_is_created = isset( $_GET['created'] ) && '1' === $_GET['created'];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_is_updated = isset( $_GET['updated'] ) && '1' === $_GET['updated'];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_is_deleted = isset( $_GET['deleted'] ) && '1' === $_GET['deleted'];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_error_code = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';

	// Enqueue native WordPress media scripts for car thumbnail & gallery management.
	wp_enqueue_media();
	?>

	<style>
		.acrb-cars-wrapper {
			width: 100%;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #1e293b;
			box-sizing: border-box;
		}
		.acrb-cars-wrapper *,
		.acrb-cars-wrapper *::before,
		.acrb-cars-wrapper *::after {
			box-sizing: border-box;
		}

		/* Topbar Header Banner */
		.acrb-cars-topbar-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
			border: 1px solid #e2e8f0;
			border-left: 4px solid #4f46e5;
			border-radius: 14px;
			padding: 18px 24px;
			box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
			margin-bottom: 22px;
			gap: 16px;
			flex-wrap: wrap;
		}
		.acrb-cars-topbar-title-group {
			display: flex;
			align-items: center;
			gap: 14px;
		}
		.acrb-cars-topbar-icon {
			width: 44px;
			height: 44px;
			min-width: 44px;
			background: #eef2ff;
			color: #4f46e5;
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 2px 6px rgba(79, 70, 229, 0.15);
			flex-shrink: 0;
		}
		.acrb-cars-topbar-icon .dashicons {
			font-size: 22px;
			width: 22px;
			height: 22px;
		}
		.acrb-cars-topbar-meta h1 {
			font-size: 19px;
			font-weight: 800;
			color: #0f172a;
			margin: 0 0 3px 0;
			letter-spacing: -0.02em;
			line-height: 1.2;
		}
		.acrb-cars-topbar-meta p {
			margin: 0;
			font-size: 13px;
			color: #64748b;
		}

		/* Feedback Alerts */
		.acrb-cars-alert-success {
			background: #ecfdf5;
			border: 1px solid #a7f3d0;
			color: #065f46;
			padding: 12px 18px;
			border-radius: 10px;
			font-size: 13.5px;
			font-weight: 600;
			margin-bottom: 20px;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.acrb-cars-alert-error {
			background: #fef2f2;
			border: 1px solid #fecaca;
			color: #991b1b;
			padding: 12px 18px;
			border-radius: 10px;
			font-size: 13.5px;
			font-weight: 600;
			margin-bottom: 20px;
			display: flex;
			align-items: center;
			gap: 8px;
		}
	</style>

	<div class="acrb-cars-wrapper">

		<!-- Topbar Banner -->
		<header class="acrb-cars-topbar-header">
			<div class="acrb-cars-topbar-title-group">
				<div class="acrb-cars-topbar-icon">
					<span class="dashicons dashicons-car"></span>
				</div>
				<div class="acrb-cars-topbar-meta">
					<h1><?php esc_html_e( 'Fleet & Vehicle Inventory', 'awesome-car-rental' ); ?></h1>
					<p><?php esc_html_e( 'Manage vehicle catalog, specifications, rental pricing tiers, and fleet availability.', 'awesome-car-rental' ); ?></p>
				</div>
			</div>
		</header>

		<!-- Feedback Notices -->
		<?php if ( $acrb_is_created ) : ?>
			<div class="acrb-cars-alert-success">
				<span class="dashicons dashicons-yes-alt"></span>
				<span><?php esc_html_e( 'Vehicle added to fleet successfully.', 'awesome-car-rental' ); ?></span>
			</div>
		<?php elseif ( $acrb_is_updated ) : ?>
			<div class="acrb-cars-alert-success">
				<span class="dashicons dashicons-yes-alt"></span>
				<span><?php esc_html_e( 'Vehicle profile and pricing parameters updated successfully.', 'awesome-car-rental' ); ?></span>
			</div>
		<?php elseif ( $acrb_is_deleted ) : ?>
			<div class="acrb-cars-alert-success">
				<span class="dashicons dashicons-yes-alt"></span>
				<span><?php esc_html_e( 'Vehicle removed from fleet successfully.', 'awesome-car-rental' ); ?></span>
			</div>
		<?php elseif ( ! empty( $acrb_error_code ) ) : ?>
			<div class="acrb-cars-alert-error">
				<span class="dashicons dashicons-warning"></span>
				<span>
					<?php 
					if ( 'nonce' === $acrb_error_code ) {
						esc_html_e( 'Security check failed. Please try again.', 'awesome-car-rental' );
					} elseif ( 'db' === $acrb_error_code ) {
						esc_html_e( 'Database error encountered while executing action.', 'awesome-car-rental' );
					} else {
						esc_html_e( 'An unexpected error occurred.', 'awesome-car-rental' );
					}
					?>
				</span>
			</div>
		<?php endif; ?>

		<!-- Sub-Navigation Tab Strip -->
		<?php
		if ( file_exists( ACRB_PATH . 'admin/cars/cars-tabs.php' ) ) {
			require_once ACRB_PATH . 'admin/cars/cars-tabs.php';
		} elseif ( file_exists( ACRB_PATH . 'admin/cars/cars-tab.php' ) ) {
			require_once ACRB_PATH . 'admin/cars/cars-tab.php';
		}
		?>

		<!-- Submodule Dispatcher -->
		<main>
			<?php
			switch ( $acrb_sub_state ) {
				case 'add':
				case 'edit':
					if ( file_exists( ACRB_PATH . 'admin/cars/cars-add-edit.php' ) ) {
						require_once ACRB_PATH . 'admin/cars/cars-add-edit.php';
					} else {
						echo '<div class="acrb-cars-alert-error">' . esc_html__( 'Vehicle add/edit view template missing.', 'awesome-car-rental' ) . '</div>';
					}
					break;

				case 'view':
					if ( file_exists( ACRB_PATH . 'admin/cars/cars-view.php' ) ) {
						require_once ACRB_PATH . 'admin/cars/cars-view.php';
					} else {
						echo '<div class="acrb-cars-alert-error">' . esc_html__( 'Vehicle details view template missing.', 'awesome-car-rental' ) . '</div>';
					}
					break;

				case 'delete':
					if ( file_exists( ACRB_PATH . 'admin/cars/cars-delete.php' ) ) {
						require_once ACRB_PATH . 'admin/cars/cars-delete.php';
					}
					break;

				case 'list':
				default:
					if ( file_exists( ACRB_PATH . 'admin/cars/cars-list.php' ) ) {
						require_once ACRB_PATH . 'admin/cars/cars-list.php';
					} else {
						echo '<div class="acrb-cars-alert-error">' . esc_html__( 'Vehicle catalog list view template missing.', 'awesome-car-rental' ) . '</div>';
					}
					break;
			}
			?>
		</main>
	</div>
	<?php
}