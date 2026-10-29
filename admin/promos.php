<?php
/**
 * Promo Codes & Discounts Module Dispatcher (`promos.php`)
 * Location: admin/promos.php
 *
 * @package Awesome_Car_Rental
 * @since   1.9.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_promo_codes_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $page_slug = 'awesome_car_rental';
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $promo_id  = isset( $_GET['promo_id'] ) ? absint( wp_unslash( $_GET['promo_id'] ) ) : 0;

    $tabs = array(
        'all' => array(
            'label' => __( 'All Promo Codes', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-tag',
        ),
        'add' => array(
            'label' => __( 'Create Promo Code', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-plus-alt2',
        ),
    );
    ?>

    <div class="acrb-dash-wrap">
        
        <!-- Header -->
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-tag"></span>
                </div>
                <div class="acrb-title-content">
                    <h1><?php esc_html_e( 'Promo Codes & Discounts', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Create and govern promotional pricing campaigns, voucher rules, and coupon codes.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Sub-Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( array( 'page' => $page_slug, 'tab' => 'promo_codes', 'sub' => $k ), admin_url( 'admin.php' ) );
                $active_class = ( $sub_state === $k || ( 'all' === $k && 'edit' === $sub_state ) ) ? ' is-active' : '';
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
                    if ( file_exists( ACRB_PATH . 'admin/promos/promos-add-edit.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/promos-add-edit.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/promos/acrb-promos-add-edit.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/acrb-promos-add-edit.php';
                    } else {
                        echo '<div class="notice notice-error"><p>' . esc_html__( 'Promo add/edit view template missing.', 'awesome-car-rental' ) . '</p></div>';
                    }
                    break;

                case 'delete':
                    if ( file_exists( ACRB_PATH . 'admin/promos/promos-delete.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/promos-delete.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/promos/acrb-promos-delete.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/acrb-promos-delete.php';
                    }
                    break;

                case 'all':
                default:
                    if ( file_exists( ACRB_PATH . 'admin/promos/promos-list.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/promos-list.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/promos/acrb-promos-list.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/acrb-promos-list.php';
                    } else {
                        echo '<div class="notice notice-error"><p>' . esc_html__( 'Promo list view template missing.', 'awesome-car-rental' ) . '</p></div>';
                    }
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}