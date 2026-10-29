<?php
/**
 * Staff Management Module Dispatcher (`staff.php`)
 * Location: admin/staff.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_staff_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    global $wpdb;
    $table_staff = $wpdb->prefix . 'dev_nahian_staff';

    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $staff_id  = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

    if ( $view_id > 0 ) {
        $sub_state = 'view';
        $staff_id  = $view_id;
    }

    // Enqueue WordPress media uploader for profile pictures if needed
    wp_enqueue_media();

    // Handle Form Submissions (Add / Update)
    if ( isset( $_POST['acrb_save_staff'] ) && check_admin_referer( 'acrb_staff_action', 'acrb_staff_nonce' ) ) {
        $s_id   = isset( $_POST['staff_record_id'] ) ? absint( $_POST['staff_record_id'] ) : 0;
        $name   = sanitize_text_field( wp_unslash( $_POST['staff_name'] ?? '' ) );
        $email  = sanitize_email( wp_unslash( $_POST['staff_email'] ?? '' ) );
        $phone  = sanitize_text_field( wp_unslash( $_POST['staff_phone'] ?? '' ) );
        $role   = sanitize_text_field( wp_unslash( $_POST['staff_designation'] ?? 'Front Desk Agent' ) );
        $status = sanitize_key( wp_unslash( $_POST['staff_status'] ?? 'active' ) );

        if ( ! empty( $name ) && ! empty( $email ) ) {
            $data = array(
                'name'   => $name,
                'email'  => $email,
                'phone'  => $phone,
                'role'   => $role,
                'status' => $status,
            );
            $formats = array( '%s', '%s', '%s', '%s', '%s' );

            if ( $s_id > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update( $table_staff, $data, array( 'id' => $s_id ), $formats, array( '%d' ) );
                echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Staff member updated successfully.', 'awesome-car-rental' ) . '</p></div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_staff, $data, array_merge( $formats, array( '%s' ) ) );
                echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'New staff member added successfully.', 'awesome-car-rental' ) . '</p></div>';
            }
            $sub_state = 'list';
        } else {
            echo '<div class="notice notice-error is-dismissible" style="margin:0 0 20px;"><p>' . esc_html__( 'Please provide both staff name and a valid email address.', 'awesome-car-rental' ) . '</p></div>';
        }
    }
    ?>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-id-alt"></span>
                </div>
                <div class="acrb-title-content">
                    <h1><?php esc_html_e( 'Fleet Operations Staff', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Manage front-desk operators, chauffeurs, fleet dispatchers, and maintenance technicians.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php
        // Include sub-navigation tab strip
        if ( file_exists( ACRB_PATH . 'admin/staff/staff-tab.php' ) ) {
            require_once ACRB_PATH . 'admin/staff/staff-tab.php';
        }

        // Render appropriate submodule view based on state
        switch ( $sub_state ) {
            case 'add':
            case 'edit':
                if ( file_exists( ACRB_PATH . 'admin/staff/staff-add-edit.php' ) ) {
                    require_once ACRB_PATH . 'admin/staff/staff-add-edit.php';
                }
                break;
            case 'view':
                if ( file_exists( ACRB_PATH . 'admin/staff/staff-view.php' ) ) {
                    require_once ACRB_PATH . 'admin/staff/staff-view.php';
                }
                break;
            case 'delete':
                if ( file_exists( ACRB_PATH . 'admin/staff/staff-delete.php' ) ) {
                    require_once ACRB_PATH . 'admin/staff/staff-delete.php';
                }
                break;
            case 'list':
            default:
                if ( file_exists( ACRB_PATH . 'admin/staff/staff-list.php' ) ) {
                    require_once ACRB_PATH . 'admin/staff/staff-list.php';
                }
                break;
        }
        ?>
    </div>
    <?php
}