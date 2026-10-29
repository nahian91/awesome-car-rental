<?php
/**
 * Fleet Maintenance & Servicing Module Dispatcher (`maintenance.php`)
 * Location: admin/maintenance.php
 *
 * @package Awesome_Car_Rental
 * @since   1.9.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_maintenance_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    global $wpdb;
    $table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';

    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $maint_id  = isset( $_GET['maint_id'] ) ? absint( wp_unslash( $_GET['maint_id'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

    if ( $view_id > 0 ) {
        $sub_state = 'view';
        $maint_id  = $view_id;
    }

    // Handle Form Submissions (Add / Update)
    if ( isset( $_POST['acrb_save_maintenance'] ) && check_admin_referer( 'acrb_maint_action', 'acrb_maint_nonce' ) ) {
        $id           = isset( $_POST['maint_id'] ) ? absint( $_POST['maint_id'] ) : 0;
        $car_name     = sanitize_text_field( wp_unslash( $_POST['maint_car_name'] ?? '' ) );
        $service_type = sanitize_text_field( wp_unslash( $_POST['maint_type'] ?? 'Routine Service' ) );
        $cost         = floatval( wp_unslash( $_POST['maint_cost'] ?? 0.00 ) );
        $date_in      = sanitize_text_field( wp_unslash( $_POST['maint_date'] ?? current_time( 'Y-m-d' ) ) );
        $status       = sanitize_key( wp_unslash( $_POST['maint_status'] ?? 'pending' ) );
        $notes        = sanitize_textarea_field( wp_unslash( $_POST['maint_notes'] ?? '' ) );

        if ( ! empty( $car_name ) ) {
            $data = array(
                'car_name'     => $car_name,
                'service_type' => $service_type,
                'cost'         => $cost,
                'date_in'      => $date_in,
                'status'       => $status,
                'notes'        => $notes,
            );
            $formats = array( '%s', '%s', '%f', '%s', '%s', '%s' );

            if ( $id > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update( $table_maintenance, $data, array( 'id' => $id ), $formats, array( '%d' ) );
                echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Maintenance record updated successfully.', 'awesome-car-rental' ) . '</p></div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_maintenance, $data, array_merge( $formats, array( '%s' ) ) );
                echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'New maintenance record logged successfully.', 'awesome-car-rental' ) . '</p></div>';
            }
            $sub_state = 'list';
        } else {
            echo '<div class="notice notice-error is-dismissible" style="margin:0 0 20px;"><p>' . esc_html__( 'Please enter the vehicle name/model.', 'awesome-car-rental' ) . '</p></div>';
        }
    }
    ?>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-hammer"></span>
                </div>
                <div class="acrb-title-content">
                    <h1><?php esc_html_e( 'Fleet Maintenance & Servicing', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Monitor vehicle health, schedule repairs, and track operational upkeep costs.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php
        // Include sub-navigation tab strip
        if ( file_exists( ACRB_PATH . 'admin/maintenance/maintenance-tab.php' ) ) {
            require_once ACRB_PATH . 'admin/maintenance/maintenance-tab.php';
        }

        // Render appropriate submodule view based on state
        switch ( $sub_state ) {
            case 'add':
            case 'edit':
                if ( file_exists( ACRB_PATH . 'admin/maintenance/maintenance-add-edit.php' ) ) {
                    require_once ACRB_PATH . 'admin/maintenance/maintenance-add-edit.php';
                }
                break;
            case 'view':
                if ( file_exists( ACRB_PATH . 'admin/maintenance/maintenance-view.php' ) ) {
                    require_once ACRB_PATH . 'admin/maintenance/maintenance-view.php';
                }
                break;
            case 'delete':
                if ( file_exists( ACRB_PATH . 'admin/maintenance/maintenance-delete.php' ) ) {
                    require_once ACRB_PATH . 'admin/maintenance/maintenance-delete.php';
                }
                break;
            case 'list':
            default:
                if ( file_exists( ACRB_PATH . 'admin/maintenance/maintenance-list.php' ) ) {
                    require_once ACRB_PATH . 'admin/maintenance/maintenance-list.php';
                }
                break;
        }
        ?>
    </div>
    <?php
}