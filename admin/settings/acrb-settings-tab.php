<?php
/**
 * Awesome Car Rental - Settings Tab Controller
 * * Handles the display and logic for the Admin Configuration pages.
 * Version: 2.1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function acrb_settings_tab() {
    // 1. SECURITY: Access Control
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    // 2. SECURITY: Nonce Verification for Saving
    if ( ! empty( $_POST ) ) {
        if ( ! isset( $_POST['acrb_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['acrb_settings_nonce'] ), 'acrb_save_settings' ) ) {
            wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', 'awesome-car-rental' ) );
        }
        
        // Settings saving logic would occur here
        // Example: update_option('acrb_day_rate', sanitize_text_field($_POST['day_rate']));
    }

    // Define Sub-Tabs
    $sub_tabs = [
        'general'  => [ 'label' => __( 'General', 'awesome-car-rental' ),  'icon' => 'dashicons-admin-generic' ],
        'payments' => [ 'label' => __( 'Payments', 'awesome-car-rental' ), 'icon' => 'dashicons-cart' ],
        'emails'   => [ 'label' => __( 'Emails', 'awesome-car-rental' ),   'icon' => 'dashicons-email-alt' ]
    ];

    // 3. SANITIZATION: Fixed FILTER_SANITIZE_KEY error
    // Use sanitize_key() which is the correct WordPress standard
    $requested_sub = isset( $_GET['sub'] ) ? sanitize_key( $_GET['sub'] ) : '';
    $active_sub    = ( $requested_sub && array_key_exists( $requested_sub, $sub_tabs ) ) ? $requested_sub : 'general';

    $requested_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    $current_page   = $requested_page ? $requested_page : 'awesome_car_rental';

    ?>
    <div class="acrb-admin-container">
        
        <header class="acrb-header">
            <div class="acrb-header-text">
                <h1><?php esc_html_e( 'Plugin Configuration', 'awesome-car-rental' ); ?></h1>
                <p>
                    <?php 
/* translators: %s: The default currency symbol, e.g., £ */
printf( esc_html__( 'Configure your rental rules, tax rates, and currency settings (Default: %s).', 'awesome-car-rental' ), '£' ); 
?>
                </p>
            </div>
        </header>

        <?php
        /**
         * 4. INTERNATIONALIZATION: Admin Notices
         * Using requested translator comment format.
         */
        if ( isset( $_GET['updated'] ) && isset( $_GET['booking_id'] ) ) {
            $acrb_bid = absint( $_GET['booking_id'] );
            $acrb_new_status = __( 'Confirmed', 'awesome-car-rental' );

            // translators: 1: Booking ID, 2: New status name.
            $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );
            echo '<div class="updated notice is-dismissible"><p>' . wp_kses_post( $acrb_msg ) . '</p></div>';
        }
        ?>
        
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $sub_tabs as $key => $data ) : 
                $url = add_query_arg( [
                    'page' => $current_page,
                    'tab'  => 'settings',
                    'sub'  => $key
                ], admin_url( 'admin.php' ) );

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
                            // Fallback if no view exists
                            echo '<h3>' . esc_html__( 'General Settings', 'awesome-car-rental' ) . '</h3>';
                            echo '<p>' . esc_html__( 'Please define acrb_settings_general_view() to display fields here.', 'awesome-car-rental' ) . '</p>';
                        }
                        break;
                }
                
                ?>
            </form>
        </main>
        
    </div> 
    <?php
}