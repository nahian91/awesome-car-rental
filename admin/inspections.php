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