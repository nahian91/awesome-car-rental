<?php
/**
 * Cars Sub-Navigation Tab Strip (`cars-tab.php`)
 * Location: admin/cars/cars-tab.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_slug = 'awesome_car_rental';
$sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
$edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
$view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

if ( $edit_id > 0 || $view_id > 0 ) {
    $sub_state = 'edit';
}

$tabs = array(
    'list' => array(
        'label' => __( 'All Cars', 'awesome-car-rental' ),
        'icon'  => 'dashicons-car',
    ),
    'add'  => array(
        'label' => __( 'Add New Car', 'awesome-car-rental' ),
        'icon'  => 'dashicons-plus-alt2',
    ),
);
?>

<nav class="acrb-nav-wrapper">
    <?php foreach ( $tabs as $k => $data ) :
        $url          = add_query_arg( array( 'page' => $page_slug, 'tab' => 'cars', 'sub' => $k ), admin_url( 'admin.php' ) );
        $active_class = ( ( 'list' === $k && ( 'list' === $sub_state || 'edit' === $sub_state || 'view' === $sub_state ) ) || $sub_state === $k ) ? ' is-active' : '';
    ?>
        <a href="<?php echo esc_url( $url ); ?>" class="acrb-nav-item<?php echo esc_attr( $active_class ); ?>">
            <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
            <span class="acrb-nav-label"><?php echo esc_html( $data['label'] ); ?></span>
        </a>
    <?php endforeach; ?>
</nav>