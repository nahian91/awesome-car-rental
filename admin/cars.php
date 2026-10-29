<?php
/**
 * Cars & Fleet Management Module Dispatcher (`cars.php`)
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_cars_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $page_slug = 'awesome_car_rental';
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $car_id    = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;
    $edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

    if ( $edit_id > 0 ) {
        $sub_state = 'edit';
        $car_id    = $edit_id;
    } elseif ( $view_id > 0 ) {
        $sub_state = 'view';
        $car_id    = $view_id;
    }

    wp_enqueue_media();
    ?>
    <div class="acrb-dash-wrap">
        <!-- Header & Tabs -->
        <?php
        if ( file_exists( ACRB_PATH . 'admin/cars/cars-tabs.php' ) ) {
            require_once ACRB_PATH . 'admin/cars/cars-tabs.php';
        }

        switch ( $sub_state ) {
            case 'add':
            case 'edit':
                if ( file_exists( ACRB_PATH . 'admin/cars/cars-add-edit.php' ) ) {
                    require_once ACRB_PATH . 'admin/cars/cars-add-edit.php';
                }
                break;
            case 'view':
                if ( file_exists( ACRB_PATH . 'admin/cars/cars-view.php' ) ) {
                    require_once ACRB_PATH . 'admin/cars/cars-view.php';
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
                }
                break;
        }
        ?>
    </div>
    <?php
}