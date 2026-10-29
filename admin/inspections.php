<?php
/**
 * Fleet Inspections & Condition Reports Dispatcher (`inspections.php`)
 * Location: admin/inspections.php
 *
 * @package Awesome_Car_Rental
 * @since   2.6.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acrb_inspections_tab(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
	}

	// Read-only GET query parameters used for tab routing and view dispatching.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_insp_id   = isset( $_GET['insp_id'] ) ? absint( wp_unslash( $_GET['insp_id'] ) ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acrb_view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

	if ( $acrb_view_id > 0 ) {
		$acrb_sub_state = 'view';
		$acrb_insp_id   = $acrb_view_id;
	}

	$acrb_tabs = array(
		'all' => array(
			'label' => __( 'All Inspections', 'awesome-car-rental' ),
			'icon'  => 'dashicons-clipboard',
		),
		'add' => array(
			'label' => __( 'New Inspection', 'awesome-car-rental' ),
			'icon'  => 'dashicons-plus-alt2',
		),
	);
	?>
	<style>
		.acrb-insp-wrapper {
			width: 100%;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #1e293b;
			box-sizing: border-box;
		}
		.acrb-insp-wrapper *,
		.acrb-insp-wrapper *::before,
		.acrb-insp-wrapper *::after {
			box-sizing: border-box;
		}

		/* Topbar Header Banner */
		.acrb-insp-topbar-header {
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
		.acrb-insp-topbar-title-group {
			display: flex;
			align-items: center;
			gap: 14px;
		}
		.acrb-insp-topbar-icon {
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
		.acrb-insp-topbar-icon .dashicons {
			font-size: 22px;
			width: 22px;
			height: 22px;
		}
		.acrb-insp-topbar-meta h1 {
			font-size: 19px;
			font-weight: 800;
			color: #0f172a;
			margin: 0 0 3px 0;
			letter-spacing: -0.02em;
			line-height: 1.2;
		}
		.acrb-insp-topbar-meta p {
			margin: 0;
			font-size: 13px;
			color: #64748b;
		}

		/* Sub-Navigation Strip */
		.acrb-insp-nav-wrapper {
			display: flex;
			gap: 8px;
			margin-bottom: 22px;
			border-bottom: 1px solid #e2e8f0;
			padding-bottom: 16px;
			flex-wrap: wrap;
		}
		.acrb-insp-nav-item {
			background: #ffffff;
			color: #475569;
			border: 1px solid #e2e8f0;
			border-radius: 9px;
			padding: 9px 16px;
			font-weight: 600;
			font-size: 13px;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			gap: 8px;
			transition: all 0.15s ease;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
		}
		.acrb-insp-nav-item .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
			color: #64748b;
			transition: color 0.15s ease;
		}
		.acrb-insp-nav-item:hover {
			background: #f8fafc;
			color: #0f172a;
			border-color: #cbd5e1;
		}
		.acrb-insp-nav-item:hover .dashicons {
			color: #0f172a;
		}
		.acrb-insp-nav-item.is-active {
			background: #eff6ff;
			color: #4f46e5;
			border-color: #c7d2fe;
			box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
		}
		.acrb-insp-nav-item.is-active .dashicons {
			color: #4f46e5;
		}
		.acrb-insp-nav-label {
			line-height: 1;
		}

		/* Success & Status Notifications */
		.acrb-insp-alert-success {
			background: #ecfdf5;
			border: 1px solid #a7f3d0;
			color: #065f46;
			padding: 12px 18px;
			border-radius: 10px;
			font-size: 13.5px;
			font-weight: 600;
			margin-bottom: 20px;
		}
	</style>

	<div class="acrb-insp-wrapper">
		<!-- Topbar Heading Banner -->
		<header class="acrb-insp-topbar-header">
			<div class="acrb-insp-topbar-title-group">
				<div class="acrb-insp-topbar-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<div class="acrb-insp-topbar-meta">
					<h1><?php esc_html_e( 'Fleet Inspections & Condition Reports', 'awesome-car-rental' ); ?></h1>
					<p><?php esc_html_e( 'Record mileage, fuel levels, cleanliness, and pre/post-rental damage logs.', 'awesome-car-rental' ); ?></p>
				</div>
			</div>
		</header>

		<?php
		// Read-only GET query parameters for checking notification status banners.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['created'] ) && '1' === $_GET['created'] ) :
		?>
			<div class="acrb-insp-alert-success">
				✓ <?php esc_html_e( 'New vehicle inspection logged successfully.', 'awesome-car-rental' ); ?>
			</div>
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		elseif ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) :
		?>
			<div class="acrb-insp-alert-success">
				✓ <?php esc_html_e( 'Inspection report updated successfully.', 'awesome-car-rental' ); ?>
			</div>
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		elseif ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) :
		?>
			<div class="acrb-insp-alert-success">
				✓ <?php esc_html_e( 'Inspection record deleted successfully.', 'awesome-car-rental' ); ?>
			</div>
		<?php endif; ?>

		<!-- Sub-Navigation Strip -->
		<nav class="acrb-insp-nav-wrapper">
			<?php
			foreach ( $acrb_tabs as $acrb_k => $acrb_data ) :
				$acrb_url          = add_query_arg(
					array(
						'page' => 'awesome_car_rental',
						'tab'  => 'inspections',
						'sub'  => $acrb_k,
					),
					admin_url( 'admin.php' )
				);
				$acrb_is_active    = ( 'all' === $acrb_k && in_array( $acrb_sub_state, array( 'all', 'view' ), true ) ) || ( $acrb_sub_state === $acrb_k );
				$acrb_active_class = $acrb_is_active ? ' is-active' : '';
			?>
				<a href="<?php echo esc_url( $acrb_url ); ?>" class="acrb-insp-nav-item<?php echo esc_attr( $acrb_active_class ); ?>">
					<span class="dashicons <?php echo esc_attr( $acrb_data['icon'] ); ?>"></span>
					<span class="acrb-insp-nav-label"><?php echo esc_html( $acrb_data['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<main>
			<?php
			switch ( $acrb_sub_state ) {
				case 'add':
				case 'edit':
					if ( file_exists( ACRB_PATH . 'admin/inspections/inspections-add-edit.php' ) ) {
						require_once ACRB_PATH . 'admin/inspections/inspections-add-edit.php';
					}
					break;

				case 'view':
					if ( file_exists( ACRB_PATH . 'admin/inspections/inspections-view.php' ) ) {
						require_once ACRB_PATH . 'admin/inspections/inspections-view.php';
					}
					break;

				case 'delete':
					if ( file_exists( ACRB_PATH . 'admin/inspections/inspections-delete.php' ) ) {
						require_once ACRB_PATH . 'admin/inspections/inspections-delete.php';
					}
					break;

				case 'all':
				default:
					if ( file_exists( ACRB_PATH . 'admin/inspections/inspections-list.php' ) ) {
						require_once ACRB_PATH . 'admin/inspections/inspections-list.php';
					}
					break;
			}
			?>
		</main>
	</div>
	<?php
}