<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Bookings Tab Logic & Router
 * Routes actions to dedicated booking modules with a properly spaced elite SaaS Topbar.
 */
function acrb_bookings_tab() {
    $action     = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
    $booking_id = isset( $_GET['booking_id'] ) ? intval( $_GET['booking_id'] ) : 0;

    // 1. Verify Nonce ONLY if an action is being performed
    if ( ! empty( $action ) ) {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'acrb_car_action' ) ) {
            wp_die( esc_html__( 'Security check failed. Please try again.', 'awesome-car-rental' ) );
        }
    }

    // 2. Handle Deletion Action
    if ( 'delete' === $action && $booking_id > 0 ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to delete bookings.', 'awesome-car-rental' ) );
        }

        if ( 'acrb_bookings' !== get_post_type( $booking_id ) ) {
            wp_die( esc_html__( 'Invalid booking record.', 'awesome-car-rental' ) );
        }

        $trashed = wp_trash_post( $booking_id );

        if ( false === $trashed ) {
            wp_die( esc_html__( 'Failed to move the booking to the trash.', 'awesome-car-rental' ) );
        }

        delete_transient( 'acrb_dashboard_revenue' );

        $redirect_url = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&msg=deleted&booking_id=' . $booking_id );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    $page_slug = 'awesome_car_rental';
    $base_url  = admin_url( 'admin.php?page=' . $page_slug . '&tab=bookings' );
    ?>

    <style>
        /* Modern SaaS Layout Theme with Proper Spacing */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 20px 20px 0 0; box-sizing: border-box; }
        .acrb-dash-wrap *, .acrb-dash-wrap *::before, .acrb-dash-wrap *::after { box-sizing: border-box; }

        /* Ultra-Premium Top Header Bar */
        .acrb-header-flex { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); 
            border: 1px solid #e2e8f0; 
            border-top: 3px solid #3b82f6;
            border-radius: 14px; 
            padding: 24px 32px; 
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); 
            margin-bottom: 24px; 
            position: relative;
        }
        .acrb-title-wrapper { display: flex; align-items: center; gap: 18px; width: 100%; }
        .acrb-title-icon { width: 48px; height: 48px; min-width: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
        .acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-title-content { display: flex; flex-direction: column; gap: 4px; }
        .acrb-main-title { font-size: 22px; font-weight: 700; margin: 0; color: #0f172a; letter-spacing: -0.5px; line-height: 1.2; }
        .acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; line-height: 1.4; }
        
        /* Header Actions & Buttons */
        .acrb-header-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .acrb-header-flex .button { 
            background: #ffffff; 
            color: #334155; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 10px 18px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.25s ease; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            height: 38px;
            font-size: 13px;
        }
        .acrb-header-flex .button .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-header-flex .button:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-header-actions { width: 100%; justify-content: flex-end; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <!-- Elite SaaS Topbar Header -->
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title">
                        <?php 
                        if ( 'edit' === $action ) {
                            esc_html_e( 'Manage & Edit Reservation', 'awesome-car-rental' );
                        } elseif ( 'view' === $action ) {
                            esc_html_e( 'Reservation Details', 'awesome-car-rental' );
                        } elseif ( 'print' === $action ) {
                            esc_html_e( 'Print Dispatch Manifest', 'awesome-car-rental' );
                        } else {
                            esc_html_e( 'Fleet Booking Directory', 'awesome-car-rental' );
                        }
                        ?>
                    </h1>
                    <p class="acrb-sub-title">
                        <?php 
                        if ( ! empty( $action ) && $booking_id > 0 ) {
                            printf( esc_html__( 'Viewing parameters for record ID #%d', 'awesome-car-rental' ), absint( $booking_id ) );
                        } else {
                            esc_html_e( 'Manage and monitor all incoming vehicle reservations and rental statuses.', 'awesome-car-rental' );
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="acrb-header-actions">
                <?php if ( ! empty( $action ) ) : ?>
                    <a href="<?php echo esc_url( $base_url ); ?>" class="button">
                        <span class="dashicons dashicons-arrow-left-alt2"></span> 
                        <span><?php esc_html_e( 'Return to Bookings', 'awesome-car-rental' ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Router Content Switcher -->
        <main>
            <?php
            switch ( $action ) {
                case 'edit':
                    if ( $booking_id > 0 ) {
                        require_once ACRB_PATH . 'admin/bookings/acrb-bookings-edit.php';
                    } else {
                        require_once ACRB_PATH . 'admin/bookings/acrb-bookings-list.php';
                    }
                    break;

                case 'view':
                    if ( $booking_id > 0 ) {
                        require_once ACRB_PATH . 'admin/bookings/acrb-bookings-view.php';
                    } else {
                        require_once ACRB_PATH . 'admin/bookings/acrb-bookings-list.php';
                    }
                    break;

                case 'print':
                    if ( $booking_id > 0 ) {
                        require_once ACRB_PATH . 'admin/bookings/acrb-bookings-print.php';
                    } else {
                        require_once ACRB_PATH . 'admin/bookings/acrb-bookings-list.php';
                    }
                    break;

                default:
                    require_once ACRB_PATH . 'admin/bookings/acrb-bookings-list.php';
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}