<?php
/**
 * Awesome Car Rental - Settings Tab Controller (SaaS Modernized Header)
 * Handles the display and logic for the Admin Configuration pages.
 * Version: 2.1.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_tab() {
    // 1. SECURITY: Access Control
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    // 2. SECURITY: Nonce Verification for Saving
    if ( ! empty( $_POST ) ) {
        if ( ! isset( $_POST['acrb_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['acrb_settings_nonce'] ) ), 'acrb_save_settings' ) ) {
            wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', 'awesome-car-rental' ) );
        }
        
        // Example settings saving routine or hook execution point
        do_action( 'acrb_save_settings_submitted' );

        // Clear dashboard transient caches (e.g. if currency changed)
        delete_transient( 'acrb_dashboard_revenue' );

        echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Settings saved successfully.', 'awesome-car-rental' ) . '</p></div>';
    }

    // Define Sub-Tabs
    $sub_tabs = array(
        'general'  => array( 'label' => __( 'General', 'awesome-car-rental' ),  'icon' => 'dashicons-admin-generic' ),
        'payments' => array( 'label' => __( 'Payments', 'awesome-car-rental' ), 'icon' => 'dashicons-cart' ),
        'emails'   => array( 'label' => __( 'Emails', 'awesome-car-rental' ),   'icon' => 'dashicons-email-alt' )
    );

    // 3. SANITIZATION
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $requested_sub = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';
    $active_sub    = ( $requested_sub && array_key_exists( $requested_sub, $sub_tabs ) ) ? $requested_sub : 'general';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $requested_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    $current_page   = $requested_page ? $requested_page : 'awesome_car_rental';

    $currency_symbol = get_option( 'acrb_currency', '£' );
    ?>
    <style>
        /* Modern SaaS Layout Theme */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
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
        
        /* Modern Sub-Navigation Tabs */
        .acrb-nav-wrapper { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
        .acrb-nav-item { 
            background: #fff; 
            color: #475569; 
            border: 1px solid #e2e8f0; 
            border-radius: 8px; 
            padding: 10px 18px; 
            font-weight: 600; 
            font-size: 13px; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.2s ease; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.01);
        }
        .acrb-nav-item .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; color: #64748b; }
        .acrb-nav-item:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
        .acrb-nav-item.is-active { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.1); }
        .acrb-nav-item.is-active .dashicons { color: #3b82f6; }

        /* Content Card Container */
        .acrb-content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-nav-wrapper { flex-direction: column; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Plugin Configuration', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title">
                        <?php 
                        /* translators: %s: The default currency symbol, e.g., £ */
                        printf( esc_html__( 'Configure your rental rules, tax rates, and currency settings (Default: %s).', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); 
                        ?>
                    </p>
                </div>
            </div>
        </header>

        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['updated'] ) && isset( $_GET['booking_id'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $acrb_bid        = absint( wp_unslash( $_GET['booking_id'] ) );
            $acrb_new_status = __( 'Confirmed', 'awesome-car-rental' );

            // translators: 1: Booking ID, 2: New status name.
            $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );
            echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . wp_kses_post( $acrb_msg ) . '</p></div>';
        }
        ?>
        
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $sub_tabs as $key => $data ) : 
                $url = add_query_arg( array(
                    'page' => $current_page,
                    'tab'  => 'settings',
                    'sub'  => $key
                ), admin_url( 'admin.php' ) );

                $active_class = ( $active_sub === $key ) ? ' is-active' : '';
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="acrb-nav-item<?php echo esc_attr( $active_class ); ?>">
                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
                    <span class="acrb-nav-label"><?php echo esc_html( $data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <main class="acrb-content-card">
            <form method="post" action="">
                <?php 
                // Nonce for security
                wp_nonce_field( 'acrb_save_settings', 'acrb_settings_nonce' );

                /**
                 * 5. VIEW ROUTING
                 * Loads the specific view based on the active sub-tab.
                 */
                switch ( $active_sub ) {
                    case 'payments':
                        if ( function_exists( 'acrb_settings_payments_view' ) ) {
                            acrb_settings_payments_view();
                        } else {
                            echo '<p>' . esc_html__( 'Payment settings view is missing.', 'awesome-car-rental' ) . '</p>';
                        }
                        break;
                    case 'emails':
                        if ( function_exists( 'acrb_settings_emails_view' ) ) {
                            acrb_settings_emails_view();
                        } else {
                            echo '<p>' . esc_html__( 'Email settings view is missing.', 'awesome-car-rental' ) . '</p>';
                        }
                        break;
                    default:
                        if ( function_exists( 'acrb_settings_general_view' ) ) {
                            acrb_settings_general_view();
                        } else {
                            echo '<h3 style="margin-top:0; font-size:15px; font-weight:600; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;">' . esc_html__( 'General Settings', 'awesome-car-rental' ) . '</h3>';
                            echo '<p style="color:#64748b;">' . esc_html__( 'Please define acrb_settings_general_view() to display fields here.', 'awesome-car-rental' ) . '</p>';
                        }
                        break;
                }
                ?>
            </form>
        </main>
    </div> 
    <?php
}