<?php
/**
 * Customers Module Dispatcher (`customers.php`)
 * Location: admin/customers.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acrb_customers_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
	}

	$sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
	$cust_id   = isset( $_GET['cust_id'] ) ? absint( wp_unslash( $_GET['cust_id'] ) ) : 0;
	$view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

	if ( $view_id > 0 ) {
		$sub_state = 'view';
	}
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

		/* Common Component Styles */
		.acrb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
		.acrb-card h2 { font-size: 15px; font-weight: 600; margin: 0 0 20px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
		.acrb-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
		.acrb-form-group { display: flex; flex-direction: column; }
		.acrb-input, .acrb-select { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 14px; color: #1e293b; background: #fff; height: 42px; }
		.acrb-input:focus, .acrb-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; }
		.acrb-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #334155; }
		.acrb-btn-primary { background: #3b82f6; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none; }
		.acrb-btn-primary:hover { background: #2563eb; color: #fff; }

		/* Table Wrap */
		.acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
		.acrb-table-wrap .dataTables_wrapper { padding: 16px 20px; }
		.acrb-table-wrap table.widefat { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; width: 100% !important; }
		.acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
		.acrb-table-wrap table.widefat tbody td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
		.acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
		.acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
		.acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
		.acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
	</style>

	<div class="acrb-dash-wrap">
		<?php
		if ( file_exists( ACRB_PATH . 'admin/customers/customers-tab.php' ) ) {
			require_once ACRB_PATH . 'admin/customers/customers-tab.php';
		}

		switch ( $sub_state ) {
			case 'add':
			case 'edit':
				if ( file_exists( ACRB_PATH . 'admin/customers/customers-add-edit.php' ) ) {
					require_once ACRB_PATH . 'admin/customers/customers-add-edit.php';
				}
				break;
			case 'view':
				if ( file_exists( ACRB_PATH . 'admin/customers/customers-view.php' ) ) {
					require_once ACRB_PATH . 'admin/customers/customers-view.php';
				}
				break;
			case 'delete':
				if ( file_exists( ACRB_PATH . 'admin/customers/customers-delete.php' ) ) {
					require_once ACRB_PATH . 'admin/customers/customers-delete.php';
				}
				break;
			case 'list':
			default:
				if ( file_exists( ACRB_PATH . 'admin/customers/customers-list.php' ) ) {
					require_once ACRB_PATH . 'admin/customers/customers-list.php';
				}
				break;
		}
		?>
	</div>
	<?php
}