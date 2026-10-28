<?php
/**
 * Customers Sub-Navigation Tab (`customers-tab.php`)
 * Location: admin/customers/customers-tab.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_slug = 'awesome_car_rental';
$sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
$view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
if ( $view_id > 0 ) {
	$sub_state = 'view';
}

$tabs = array(
	'list' => array( 'label' => __( 'Customer Directory', 'awesome-car-rental' ), 'icon' => 'dashicons-groups' ),
	'add'  => array( 'label' => __( 'Register New Client', 'awesome-car-rental' ), 'icon' => 'dashicons-plus-alt2' ),
);
?>

<header class="acrb-header-flex">
	<div class="acrb-title-wrapper">
		<div class="acrb-title-icon">
			<span class="dashicons dashicons-groups"></span>
		</div>
		<div class="acrb-title-content">
			<h1 class="acrb-main-title"><?php esc_html_e( 'Customer Relationship Management', 'awesome-car-rental' ); ?></h1>
			<p class="acrb-sub-title"><?php esc_html_e( 'Manage registered client profiles, track LTV, and review booking histories.', 'awesome-car-rental' ); ?></p>
		</div>
	</div>
</header>

<nav style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
	<?php foreach ( $tabs as $k => $data ) :
		$url = add_query_arg( array( 'page' => $page_slug, 'tab' => 'customers', 'sub' => $k ), admin_url( 'admin.php' ) );
		$active = ( $sub_state === $k ) ? ' background:#eff6ff; color:#2563eb; border-color:#bfdbfe;' : ' background:#fff; color:#475569; border-color:#e2e8f0;';
	?>
		<a href="<?php echo esc_url( $url ); ?>" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:8px; font-weight:600; font-size:13px; text-decoration:none; border:1px solid #e2e8f0;<?php echo esc_attr( $active ); ?>">
			<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" style="font-size:16px; width:16px; height:16px;"></span>
			<span><?php echo esc_html( $data['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>