<?php
/**
 * Fleet Maintenance & Servicing Module Dispatcher (`maintenance.php`)
 * Location: admin/maintenance.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_maintenance_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    global $wpdb;
    $table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';

    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $maint_id  = isset( $_GET['maint_id'] ) ? absint( wp_unslash( $_GET['maint_id'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
    $edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

    if ( $view_id > 0 ) {
        $sub_state = 'view';
        $maint_id  = $view_id;
    } elseif ( $edit_id > 0 ) {
        $sub_state = 'edit';
        $maint_id  = $edit_id;
    }

    $error_notice = '';

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
                $redirect_url = admin_url( 'admin.php?page=awesome_car_rental&tab=maintenance&updated=1' );
            } else {
                $data['created_at'] = current_time( 'mysql' );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_maintenance, $data, array_merge( $formats, array( '%s' ) ) );
                $redirect_url = admin_url( 'admin.php?page=awesome_car_rental&tab=maintenance&created=1' );
            }

            // Safe Redirect with JS fallback to prevent "headers already sent"
            if ( headers_sent() ) {
                echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            $error_notice = __( 'Please enter the vehicle name/model.', 'awesome-car-rental' );
        }
    }
    ?>
    <style>
        .acrb-maint-wrapper {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-maint-wrapper *,
        .acrb-maint-wrapper *::before,
        .acrb-maint-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-maint-topbar-header {
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
        .acrb-maint-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-maint-topbar-icon {
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
        .acrb-maint-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-maint-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-maint-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Notifications */
        .acrb-maint-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .acrb-maint-alert-error {
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

    <div class="acrb-maint-wrapper">
        <!-- Topbar Heading Banner -->
        <header class="acrb-maint-topbar-header">
            <div class="acrb-maint-topbar-title-group">
                <div class="acrb-maint-topbar-icon">
                    <span class="dashicons dashicons-hammer"></span>
                </div>
                <div class="acrb-maint-topbar-meta">
                    <h1><?php esc_html_e( 'Fleet Maintenance & Servicing', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Monitor vehicle health, schedule repairs, and track operational upkeep costs.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php if ( isset( $_GET['created'] ) && '1' === $_GET['created'] ) : ?>
            <div class="acrb-maint-alert-success">
                ✓ <?php esc_html_e( 'New maintenance record logged successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
            <div class="acrb-maint-alert-success">
                ✓ <?php esc_html_e( 'Maintenance record updated successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) : ?>
            <div class="acrb-maint-alert-success">
                ✓ <?php esc_html_e( 'Maintenance record deleted successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $error_notice ) ) : ?>
            <div class="acrb-maint-alert-error">
                ✕ <?php echo esc_html( $error_notice ); ?>
            </div>
        <?php endif; ?>

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