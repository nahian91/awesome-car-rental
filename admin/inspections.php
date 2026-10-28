<?php
/**
 * Fleet Inspections & Condition Reports Dispatcher (`inspections.php`)
 * Location: admin/inspections.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acrb_inspections_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
	}

	$page_slug = 'awesome_car_rental';
	$sub_state = isset($_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';$insp_id   = isset( $_GET['insp_id'] ) ? absint( wp_unslash( $_GET['insp_id'] ) ) : 0;

	$tabs = array(
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
		.acrb-dash-wrap { width: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
		.acrb-dash-wrap *, .acrb-dash-wrap *::before, .acrb-dash-wrap *::after { box-sizing: border-box; }
		
		/* Ultra-Premium Top Header Bar */
		.acrb-header-flex { 
			display: flex; 
			justify-content: space-between; 
			align-items: center; 
			background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); 
			border: 1px solid #e2e8f0; 
			border-top: 3px solid #3b82f6; 
			border-radius: 14px; 
			padding: 24px 32px; 
			box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); 
			margin-bottom: 24px; 
			position: relative;
		}
		.acrb-title-wrapper { display: flex; align-items: center; gap: 18px; width: 100%; }
		.acrb-title-icon { width: 48px; height: 48px; min-width: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
		.acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
		
		.acrb-title-content { display: flex; flex-direction: column; gap: 4px; }
		.acrb-main-title { font-size: 22px; font-weight: 700; margin: 0; color: #0f172a; letter-spacing: -0.5px; line-height: 1.2; }
		.acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; line-height: 1.4; }
		
		/* Modern Sub-Navigation Tabs */
		.acrb-nav-wrapper { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
		.acrb-nav-item { 
			background: #fff; 
			color: #475569; 
			border: 1px solid #e2e8f0; 
			border-radius: 8px; 
			padding: 10px 18px; 
			font-weight: 600; 
			font-size: 13px; 
			text-decoration: none; 
			display: inline-flex; 
			align-items: center; 
			gap: 8px; 
			transition: all 0.2s ease; 
			box-shadow: 0 1px 2px rgba(0,0,0,0.01);
		}
		.acrb-nav-item .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; color: #64748b; }
		.acrb-nav-item:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
		.acrb-nav-item.is-active { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.1); }
		.acrb-nav-item.is-active .dashicons { color: #3b82f6; }

		/* Common Component Styles */
		.acrb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
		.acrb-card h2 { font-size: 15px; font-weight: 600; margin: 0 0 20px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
		.acrb-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
		.acrb-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
		.acrb-form-group { display: flex; flex-direction: column; }
		.acrb-input, .acrb-select, .acrb-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
		.acrb-input, .acrb-select { height: 42px; }
		.acrb-textarea { padding: 12px 14px; height: auto; }
		.acrb-input:focus, .acrb-select:focus, .acrb-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; }
		.acrb-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #334155; }
		.acrb-btn-primary { background: #3b82f6; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none; }
		.acrb-btn-primary:hover { background: #2563eb; color: #fff; }

		/* Table Wrap */
		.acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
		.acrb-table-wrap .dataTables_wrapper { padding: 16px 20px; }
		.acrb-table-wrap .dataTables_length { float: left; margin-bottom: 16px; font-size: 13px; color: #64748b; }
		.acrb-table-wrap .dataTables_filter { float: right; margin-bottom: 16px; }
		.acrb-table-wrap .dataTables_filter input { width: 240px; height: 36px; padding: 0 14px !important; border-radius: 8px !important; border: 1px solid #cbd5e1 !important; }
		.acrb-table-wrap table.widefat { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; width: 100% !important; }
		.acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
		.acrb-table-wrap table.widefat tbody td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
		.acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
		.acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
		.acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
		.acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
	</style>

	<div class="acrb-dash-wrap">
		<header class="acrb-header-flex">
			<div class="acrb-title-wrapper">
				<div class="acrb-title-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<div class="acrb-title-content">
					<h1 class="acrb-main-title"><?php esc_html_e( 'Fleet Inspections & Condition Reports', 'awesome-car-rental' ); ?></h1>
					<p class="acrb-sub-title"><?php esc_html_e( 'Record mileage, fuel levels, cleanliness, and pre/post-rental damage logs.', 'awesome-car-rental' ); ?></p>
				</div>
			</div>
		</header>

		<!-- Sub-Navigation Tabs -->
		<nav class="acrb-nav-wrapper">
			<?php foreach ( $tabs as $k =>$data ) :
				$url = add_query_arg( array( 'page' =>$page_slug, 'tab' => 'inspections', 'sub' => $k ), admin_url( 'admin.php' ) );$active_class = ( $sub_state ===$k || ( 'all' === $k && 'edit' ===$sub_state ) ) ? ' is-active' : '';
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="acrb-nav-item<?php echo esc_attr( $active_class ); ?>">
					<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
					<span><?php echo esc_html( $data['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<main>
			<?php
			switch ( $sub_state ) {
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