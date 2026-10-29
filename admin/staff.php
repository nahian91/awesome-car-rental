<?php
/**
 * Staff Management Module Dispatcher (`staff.php`)
 * Location: admin/staff.php
 *
 * @package Awesome_Car_Rental
 * @since   2.9.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_staff_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    global $wpdb;
    $table_staff = $wpdb->prefix . 'dev_nahian_staff';
    $page_slug   = 'awesome_car_rental';
    $base_url    = admin_url( 'admin.php?page=' . $page_slug . '&tab=staff' );

    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $staff_id  = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
    $edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

    if ( $view_id > 0 ) {
        $sub_state = 'view';
        $staff_id  = $view_id;
    } elseif ( $edit_id > 0 ) {
        $sub_state = 'edit';
        $staff_id  = $edit_id;
    }

    // Enqueue WordPress media uploader for profile pictures if needed
    wp_enqueue_media();

    // 1. Handle Form Submissions (Add / Update)
    if ( isset( $_POST['acrb_save_staff'] ) && check_admin_referer( 'acrb_staff_action', 'acrb_staff_nonce' ) ) {
        $s_id   = isset( $_POST['staff_record_id'] ) ? absint( $_POST['staff_record_id'] ) : 0;
        $name   = sanitize_text_field( wp_unslash( $_POST['staff_name'] ?? '' ) );
        $email  = sanitize_email( wp_unslash( $_POST['staff_email'] ?? '' ) );
        $phone  = sanitize_text_field( wp_unslash( $_POST['staff_phone'] ?? '' ) );
        $role   = sanitize_text_field( wp_unslash( $_POST['staff_designation'] ?? 'Front Desk Agent' ) );
        $status = sanitize_key( wp_unslash( $_POST['staff_status'] ?? 'active' ) );

        if ( ! empty( $name ) && ! empty( $email ) && is_email( $email ) ) {
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
                $redirect_url = add_query_arg( array( 'updated' => '1' ), $base_url );
            } else {
                $data['created_at'] = current_time( 'mysql' );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_staff, $data, array_merge( $formats, array( '%s' ) ) );
                $redirect_url = add_query_arg( array( 'created' => '1' ), $base_url );
            }

            if ( headers_sent() ) {
                echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
    ?>
    <style>
        .acrb-staff-wrapper {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-staff-wrapper *,
        .acrb-staff-wrapper *::before,
        .acrb-staff-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-staff-topbar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .acrb-staff-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-staff-topbar-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            background: #eef2ff;
            color: #4f46e5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.15);
            flex-shrink: 0;
        }
        .acrb-staff-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-staff-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-staff-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Status Notifications */
        .acrb-staff-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .acrb-staff-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>

    <div class="acrb-staff-wrapper">
        <!-- Topbar Heading Banner -->
        <header class="acrb-staff-topbar-header">
            <div class="acrb-staff-topbar-title-group">
                <div class="acrb-staff-topbar-icon">
                    <span class="dashicons dashicons-id-alt"></span>
                </div>
                <div class="acrb-staff-topbar-meta">
                    <h1><?php esc_html_e( 'Fleet Operations Staff', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Manage front-desk operators, chauffeurs, fleet dispatchers, and maintenance technicians.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php if ( isset( $_GET['created'] ) && '1' === $_GET['created'] ) : ?>
            <div class="acrb-staff-alert-success">
                ✓ <?php esc_html_e( 'New staff member added successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
            <div class="acrb-staff-alert-success">
                ✓ <?php esc_html_e( 'Staff profile updated successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) : ?>
            <div class="acrb-staff-alert-success">
                ✓ <?php esc_html_e( 'Staff record removed successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php endif; ?>

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
                } else {
                    echo '<div class="acrb-staff-alert-error">' . esc_html__( 'Staff add/edit component missing.', 'awesome-car-rental' ) . '</div>';
                }
                break;

            case 'view':
                if ( file_exists( ACRB_PATH . 'admin/staff/staff-view.php' ) ) {
                    require_once ACRB_PATH . 'admin/staff/staff-view.php';
                } else {
                    echo '<div class="acrb-staff-alert-error">' . esc_html__( 'Staff profile view component missing.', 'awesome-car-rental' ) . '</div>';
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
                } else {
                    echo '<div class="acrb-staff-alert-error">' . esc_html__( 'Staff directory component missing.', 'awesome-car-rental' ) . '</div>';
                }
                break;
        }
        ?>
    </div>
    <?php
}