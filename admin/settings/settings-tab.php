<?php
/**
 * Awesome Car Rental - Settings Tab Controller (SaaS Modernized)
 * Handles the display and logic for the Admin Configuration pages.
 * Location: admin/settings.php
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_tab(): void {
    // 1. Access Control.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    $acrb_page_slug = 'awesome_car_rental';
    $acrb_base_url  = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=settings' );

    // Define Sub-Tabs.
    $acrb_sub_tabs = array(
        'general'  => array(
            'label' => __( 'General', 'awesome-car-rental' ),
            'icon'  => 'dashicons-admin-generic',
        ),
        'payments' => array(
            'label' => __( 'Payments', 'awesome-car-rental' ),
            'icon'  => 'dashicons-cart',
        ),
        'emails'   => array(
            'label' => __( 'Emails', 'awesome-car-rental' ),
            'icon'  => 'dashicons-email-alt',
        ),
    );

    // 2. Sub-tab Routing Sanitization.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_requested_sub = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';
    $acrb_active_sub    = ( $acrb_requested_sub && array_key_exists( $acrb_requested_sub, $acrb_sub_tabs ) ) ? $acrb_requested_sub : 'general';
    
    ?>

    <style>
        .acrb-settings-wrapper {
            width: 100%;
            max-width: 1100px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-settings-wrapper *,
        .acrb-settings-wrapper *::before,
        .acrb-settings-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-settings-topbar-header {
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
        .acrb-settings-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-settings-topbar-icon {
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
        .acrb-settings-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-settings-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-settings-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Sub-Navigation Pill Strip */
        .acrb-settings-nav-wrapper {
            display: flex;
            gap: 8px;
            margin-bottom: 22px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
            flex-wrap: wrap;
        }
        .acrb-settings-nav-item {
            background: #ffffff;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 9px 16px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        }
        .acrb-settings-nav-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: #64748b;
            transition: color 0.15s ease;
        }
        .acrb-settings-nav-item:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .acrb-settings-nav-item:hover .dashicons {
            color: #0f172a;
        }
        .acrb-settings-nav-item.is-active {
            background: #eff6ff;
            color: #4f46e5;
            border-color: #c7d2fe;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
        }
        .acrb-settings-nav-item.is-active .dashicons {
            color: #4f46e5;
        }

        /* Success & Info Feedback Notices */
        .acrb-settings-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Main Content Elevation Card */
        .acrb-settings-content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            padding: 26px;
        }

        /* View Fallbacks */
        .acrb-settings-empty-view {
            padding: 24px;
            text-align: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
        }
        .acrb-settings-empty-title {
            margin: 0 0 6px 0;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        .acrb-settings-empty-desc {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }
    </style>

    <div class="acrb-settings-wrapper">
        <!-- Topbar Banner -->
        <header class="acrb-settings-topbar-header">
            <div class="acrb-settings-topbar-title-group">
                <div class="acrb-settings-topbar-icon">
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <div class="acrb-settings-topbar-meta">
                    <h1><?php esc_html_e( 'System Settings & Preferences', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Configure business rules, currency formats, payment gateway integrations, and transactional emails.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Feedback Notices -->
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) :
        ?>
            <div class="acrb-settings-alert-success">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php esc_html_e( 'Settings saved and updated successfully.', 'awesome-car-rental' ); ?></span>
            </div>
        <?php endif; ?>

        <!-- Sub-Navigation Pill Strip -->
        <nav class="acrb-settings-nav-wrapper">
            <?php
            foreach ( $acrb_sub_tabs as $acrb_key => $acrb_data ) : 
                $acrb_url          = add_query_arg( array( 'page' => $acrb_page_slug, 'tab' => 'settings', 'sub' => $acrb_key ), admin_url( 'admin.php' ) );
                $acrb_active_class = ( $acrb_active_sub === $acrb_key ) ? ' is-active' : '';
            ?>
                <a href="<?php echo esc_url( $acrb_url ); ?>" class="acrb-settings-nav-item<?php echo esc_attr( $acrb_active_class ); ?>">
                    <span class="dashicons <?php echo esc_attr( $acrb_data['icon'] ); ?>"></span>
                    <span><?php echo esc_html( $acrb_data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Content Card View -->
        <main class="acrb-settings-content-card">
            <?php
            switch ( $acrb_active_sub ) {
                case 'payments':
                    if ( function_exists( 'acrb_settings_payments_view' ) ) {
                        acrb_settings_payments_view();
                    } elseif ( file_exists( ACRB_PATH . 'admin/settings/settings-payments.php' ) ) {
                        require_once ACRB_PATH . 'admin/settings/settings-payments.php';
                    } else {
                        ?>
                        <div class="acrb-settings-empty-view">
                            <h3 class="acrb-settings-empty-title"><?php esc_html_e( 'Payment Gateway Settings', 'awesome-car-rental' ); ?></h3>
                            <p class="acrb-settings-empty-desc"><?php esc_html_e( 'The payment settings component view was not found.', 'awesome-car-rental' ); ?></p>
                        </div>
                        <?php
                    }
                    break;

                case 'emails':
                    if ( function_exists( 'acrb_settings_emails_view' ) ) {
                        acrb_settings_emails_view();
                    } elseif ( file_exists( ACRB_PATH . 'admin/settings/settings-emails.php' ) ) {
                        require_once ACRB_PATH . 'admin/settings/settings-emails.php';
                    } else {
                        ?>
                        <div class="acrb-settings-empty-view">
                            <h3 class="acrb-settings-empty-title"><?php esc_html_e( 'Notification & Email Settings', 'awesome-car-rental' ); ?></h3>
                            <p class="acrb-settings-empty-desc"><?php esc_html_e( 'The email templates and SMTP settings component view was not found.', 'awesome-car-rental' ); ?></p>
                        </div>
                        <?php
                    }
                    break;

                case 'general':
                default:
                    if ( function_exists( 'acrb_settings_general_view' ) ) {
                        acrb_settings_general_view();
                    } elseif ( file_exists( ACRB_PATH . 'admin/settings/acrb-settings-general.php' ) ) {
                        require_once ACRB_PATH . 'admin/settings/acrb-settings-general.php';
                    } else {
                        ?>
                        <div class="acrb-settings-empty-view">
                            <h3 class="acrb-settings-empty-title"><?php esc_html_e( 'General Settings', 'awesome-car-rental' ); ?></h3>
                            <p class="acrb-settings-empty-desc"><?php esc_html_e( 'Define acrb_settings_general_view() or place acrb-settings-general.php to configure company currency, business hours, and operational defaults.', 'awesome-car-rental' ); ?></p>
                        </div>
                        <?php
                    }
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}