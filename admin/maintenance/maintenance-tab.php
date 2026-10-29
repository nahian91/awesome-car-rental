<?php
/**
 * Maintenance Sub-Navigation Tab Strip (`maintenance-tab.php`)
 * Location: admin/maintenance/maintenance-tab.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$acrb_page_slug = 'awesome_car_rental';

// Read-only GET query parameters used for tab sub-state highlighting.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : ( isset( $_GET['maint_id'] ) ? absint( wp_unslash( $_GET['maint_id'] ) ) : 0 );

if ( $acrb_view_id > 0 ) {
	$acrb_sub_state = 'view';
} elseif ( $acrb_edit_id > 0 ) {
	$acrb_sub_state = 'edit';
}

$acrb_tabs = array(
	'list' => array(
		'label' => __( 'All Maintenance', 'awesome-car-rental' ),
		'icon'  => 'dashicons-hammer',
	),
	'add'  => array(
		'label' => __( 'Add Maintenance', 'awesome-car-rental' ),
		'icon'  => 'dashicons-plus-alt2',
	),
);
?>

<style>
	.acrb-maint-nav-wrapper {
		display: flex;
		gap: 8px;
		margin-bottom: 22px;
		border-bottom: 1px solid #e2e8f0;
		padding-bottom: 16px;
		flex-wrap: wrap;
		box-sizing: border-box;
	}
	.acrb-maint-nav-item {
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
	.acrb-maint-nav-item .dashicons {
		font-size: 16px;
		width: 16px;
		height: 16px;
		color: #64748b;
		transition: color 0.15s ease;
	}
	.acrb-maint-nav-item:hover {
		background: #f8fafc;
		color: #0f172a;
		border-color: #cbd5e1;
	}
	.acrb-maint-nav-item:hover .dashicons {
		color: #0f172a;
	}
	.acrb-maint-nav-item.is-active {
		background: #eff6ff;
		color: #4f46e5;
		border-color: #c7d2fe;
		box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
	}
	.acrb-maint-nav-item.is-active .dashicons {
		color: #4f46e5;
	}
	.acrb-maint-nav-label {
		line-height: 1;
	}
</style>

<nav class="acrb-maint-nav-wrapper">
	<?php
	foreach ( $acrb_tabs as $acrb_k => $acrb_data ) :
		$acrb_url          = add_query_arg(
			array(
				'page' => $acrb_page_slug,
				'tab'  => 'maintenance',
				'sub'  => $acrb_k,
			),
			admin_url( 'admin.php' )
		);
		$acrb_is_active    = ( 'list' === $acrb_k && in_array( $acrb_sub_state, array( 'list', 'view', 'edit' ), true ) ) || ( $acrb_sub_state === $acrb_k );
		$acrb_active_class = $acrb_is_active ? ' is-active' : '';
	?>
		<a href="<?php echo esc_url( $acrb_url ); ?>" class="acrb-maint-nav-item<?php echo esc_attr( $acrb_active_class ); ?>">
			<span class="dashicons <?php echo esc_attr( $acrb_data['icon'] ); ?>"></span>
			<span class="acrb-maint-nav-label"><?php echo esc_html( $acrb_data['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>