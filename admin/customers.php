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

    $page_slug = 'awesome_car_rental';
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $cust_id   = isset( $_GET['cust_id'] ) ? absint( wp_unslash( $_GET['cust_id'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

    if ( $view_id > 0 ) {
        $sub_state = 'view';
        $cust_id   = $view_id;
    }

    $tabs = array(
        'list' => array(
            'label' => __( 'All Customers', 'awesome-car-rental' ),
            'icon'  => 'dashicons-admin-users',
        ),
        'add'  => array(
            'label' => __( 'Add Customer', 'awesome-car-rental' ),
            'icon'  => 'dashicons-plus-alt2',
        ),
    );
    ?>

    <div class="acrb-dash-wrap">
        <!-- Top Header Bar Matching Staff & Other Modules -->
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="acrb-title-content">
                    <h1><?php esc_html_e( 'Customer Directory', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Manage registered client profiles, rental records, and customer dossiers.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Sub-Navigation Tabs Matching Staff & Other Modules -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( array( 'page' => $page_slug, 'tab' => 'customers', 'sub' => $k ), admin_url( 'admin.php' ) );
                $active_class = ( ( 'list' === $k && ( 'list' === $sub_state || 'view' === $sub_state ) ) || $sub_state === $k ) ? ' is-active' : '';
                ?>
                <a href="<?php echo esc_url( $url ); ?>" class="acrb-nav-item<?php echo esc_attr( $active_class ); ?>">
                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
                    <span class="acrb-nav-label"><?php echo esc_html( $data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <main>
            <?php
            switch ( $sub_state ) {
                case 'add':
                case 'edit':
                    if ( file_exists( ACRB_PATH . 'admin/customers/customers-add-edit.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/customers-add-edit.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/customers/acrb-customers-add-edit.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/acrb-customers-add-edit.php';
                    } else {
                        echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer add/edit template missing.', 'awesome-car-rental' ) . '</p></div>';
                    }
                    break;

                case 'view':
                    if ( file_exists( ACRB_PATH . 'admin/customers/customers-view.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/customers-view.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/customers/acrb-customers-view.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/acrb-customers-view.php';
                    } else {
                        echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer view template missing.', 'awesome-car-rental' ) . '</p></div>';
                    }
                    break;

                case 'delete':
                    if ( file_exists( ACRB_PATH . 'admin/customers/customers-delete.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/customers-delete.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/customers/acrb-customers-delete.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/acrb-customers-delete.php';
                    }
                    break;

                case 'list':
                default:
                    if ( file_exists( ACRB_PATH . 'admin/customers/customers-list.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/customers-list.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/customers/acrb-customers-list.php' ) ) {
                        require_once ACRB_PATH . 'admin/customers/acrb-customers-list.php';
                    } else {
                        echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer directory template missing.', 'awesome-car-rental' ) . '</p></div>';
                    }
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}