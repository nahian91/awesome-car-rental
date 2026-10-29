<?php
/**
 * Cars Sub-Navigation Tab Strip (`cars-tab.php` / `cars-tabs.php`)
 * Location: admin/cars/cars-tab.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$acrb_page_slug = 'awesome_car_rental';

// Read-only navigation display state; nonces not required for link rendering.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

if ( $acrb_edit_id > 0 ) {
	$acrb_sub_state = 'edit';
} elseif ( $acrb_view_id > 0 ) {
	$acrb_sub_state = 'view';
}

$acrb_tabs = array(
	'list' => array(
		'label' => __( 'All Fleet Vehicles', 'awesome-car-rental' ),
		'icon'  => 'dashicons-car',
	),
	'add'  => array(
		'label' => __( 'Add New Vehicle', 'awesome-car-rental' ),
		'icon'  => 'dashicons-plus-alt2',
	),
);
?>

<style>
	.acrb-cars-nav-wrapper {
		display: flex;
		gap: 8px;
		margin-bottom: 22px;
		border-bottom: 1px solid #e2e8f0;
		padding-bottom: 16px;
		flex-wrap: wrap;
		box-sizing: border-box;
	}
	.acrb-cars-nav-wrapper *,
	.acrb-cars-nav-wrapper *::before,
	.acrb-cars-nav-wrapper *::after {
		box-sizing: border-box;
	}
	.acrb-cars-nav-item {
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
	.acrb-cars-nav-item .dashicons {
		font-size: 16px;
		width: 16px;
		height: 16px;
		color: #64748b;
		transition: color 0.15s ease;
	}
	.acrb-cars-nav-item:hover {
		background: #f8fafc;
		color: #0f172a;
		border-color: #cbd5e1;
	}
	.acrb-cars-nav-item:hover .dashicons {
		color: #0f172a;
	}
	.acrb-cars-nav-item.is-active {
		background: #eff6ff;
		color: #4f46e5;
		border-color: #c7d2fe;
		box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
	}
	.acrb-cars-nav-item.is-active .dashicons {
		color: #4f46e5;
	}
	.acrb-cars-nav-label {
		line-height: 1;
	}
</style>

<nav class="acrb-cars-nav-wrapper">
	<?php
	foreach ( $acrb_tabs as $acrb_k => $acrb_data ) :
		$acrb_tab_url = add_query_arg(
			array(
				'page' => $acrb_page_slug,
				'tab'  => 'cars',
				'sub'  => $acrb_k,
			),
			admin_url( 'admin.php' )
		);

		// Strip contextual item parameters when explicitly navigating tabs.
		$acrb_tab_url = remove_query_arg( array( 'edit', 'view', 'car_id', 'created', 'updated', 'deleted' ), $acrb_tab_url );

		$acrb_is_active   = ( 'list' === $acrb_k && in_array( $acrb_sub_state, array( 'list', 'edit', 'view' ), true ) ) || ( $acrb_sub_state === $acrb_k );
		$acrb_active_attr = $acrb_is_active ? ' is-active' : '';
	?>
		<a href="<?php echo esc_url( $acrb_tab_url ); ?>" class="acrb-cars-nav-item<?php echo esc_attr( $acrb_active_attr ); ?>">
			<span class="dashicons <?php echo esc_attr( $acrb_data['icon'] ); ?>"></span>
			<span class="acrb-cars-nav-label"><?php echo esc_html( $acrb_data['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>