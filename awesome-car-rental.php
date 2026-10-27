<?php
/**
 * Plugin Name: Awesome Car Rental & Booking
 * Description: Core functionality for the Car Rental System (CPTs, Admin UI, and Logic).
 * Version: 1.3.1
 * Author: Abdullah Nahian
 * Author URI: https://devnahian.com
 * Text Domain: awesome-car-rental
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! function_exists( 'acr_fs' ) ) {
    // Create a helper function for easy SDK access.
    function acr_fs() {
        global $acr_fs;

        if ( ! isset( $acr_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';

            $acr_fs = fs_dynamic_init( array(
                'id'                  => '22722',
                'slug'                => 'awesome-car-rental',
                'type'                => 'plugin',
                'public_key'          => 'pk_5a6eaaad70dbae007650ce2670b2f',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'             => 'awesome_car_rental',
                    'account'          => false,
                    'contact'          => false,
                    'support'          => false,
                ),
            ) );
        }

        return $acr_fs;
    }

    // Init Freemius.
    acr_fs();
    // Signal that SDK was initiated.
    do_action( 'acr_fs_loaded' );
}

/**
 * Define Plugin Constants
 */
define( 'ACRB_VERSION', '1.3.1' );
define( 'ACRB_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACRB_URL', plugin_dir_url( __FILE__ ) );

/*--------------------------------------------------------------
# 1. Register CPTs and Taxonomy
--------------------------------------------------------------*/
add_action( 'init', function() {
    register_post_type( 'acrb_cars', array(
        'labels'   => array( 'name' => __( 'Cars', 'awesome-car-rental' ), 'singular_name' => __( 'Car', 'awesome-car-rental' ) ),
        'public'   => false, 
        'show_ui'  => false, 
        'supports' => array( 'title', 'editor', 'thumbnail' ),
    ) );

    register_taxonomy( 'acrb_categories', 'acrb_cars', array(
        'labels'       => array( 'name' => __( 'Categories', 'awesome-car-rental' ), 'singular_name' => __( 'Category', 'awesome-car-rental' ) ),
        'hierarchical' => true,
        'show_ui'      => false,
    ) );

    register_post_type( 'acrb_bookings', array(
        'labels'   => array( 'name' => __( 'Bookings', 'awesome-car-rental' ), 'singular_name' => __( 'Booking', 'awesome-car-rental' ) ),
        'public'   => false, 
        'show_ui'  => false, 
        'supports' => array( 'title', 'editor' ),
    ) );
});

/*--------------------------------------------------------------
# 2. Admin Menu & Main Page
--------------------------------------------------------------*/
add_action( 'admin_menu', function() {
    add_menu_page(
        __( 'Car Rental', 'awesome-car-rental' ),
        __( 'Car Rental', 'awesome-car-rental' ),
        'manage_options',
        'awesome_car_rental', 
        'acrb_main_page',
        'dashicons-car', 
        20
    );
});

function acrb_main_page() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

    // Load Google Fonts Inter
    wp_enqueue_style( 'acrb-google-font-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), null );
    ?>
    <style>
        /* Enterprise High-End SaaS Dashboard Wrapper */
        .awesome-car-rental { 
            display: flex; 
            gap: 28px; 
            margin: 24px 24px 0 0; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            color: #1e293b; 
            max-width: 1600px; 
            -webkit-font-smoothing: antialiased;
        }
        
        /* Modern White SaaS Sidebar Container */
        .afd-sidebar-container { 
            width: 280px; 
            flex-shrink: 0; 
            background: #ffffff; 
            border: 1px solid #e2e8f0;
            border-radius: 16px; 
            padding: 20px 16px; 
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); 
            height: fit-content; 
            position: sticky; 
            top: 24px; 
        }

        /* Sidebar Title Header */
        .afd-sidebar-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            font-weight: 700;
            padding: 6px 12px 14px 12px;
            margin: 0 0 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Sleek Light Theme Navigation List */
        .afd-left-tabs { 
            list-style: none; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
        }
        .afd-left-tabs li a { 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            padding: 12px 14px; 
            border-radius: 10px; 
            color: #334155; 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 14px;
            transition: all 0.2s ease; 
        }
        .afd-left-tabs li a .dashicons { 
            font-size: 18px; 
            width: 18px; 
            height: 18px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            color: #64748b; 
            transition: 0.2s ease; 
        }
        .afd-left-tabs li a:hover { 
            background: #f8fafc; 
            color: #0f172a; 
        }
        .afd-left-tabs li a:hover .dashicons { 
            color: #0f172a; 
        }
        .afd-left-tabs li a.active { 
            background: #0f172a; 
            color: #fff; 
            font-weight: 600; 
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2); 
        }
        .afd-left-tabs li a.active .dashicons { 
            color: #fff; 
        }

        /* Right Workspace Box */
        .afd-right-box { 
            flex: 1; 
            min-width: 0; 
        }

        @media(max-width: 1024px) {
            .awesome-car-rental { flex-direction: column; }
            .afd-sidebar-container { width: 100%; position: static; }
        }
    </style>

    <div class="awesome-car-rental">
        <?php if ( 'print' !== $action ) : ?>
        <div class="afd-sidebar-container">
            <h3 class="afd-sidebar-title"><?php esc_html_e( 'Fleet Management', 'awesome-car-rental' ); ?></h3>
            <ul class="afd-left-tabs">
                <?php
                // Comprehensive Enterprise SaaS Menu Items with Shortcode Tab Restored
                $menu_items = array(
                    'dashboard'      => array( 'label' => __( 'Dashboard', 'awesome-car-rental' ),      'icon' => 'dashicons-performance' ),
                    'bookings'       => array( 'label' => __( 'Booking', 'awesome-car-rental' ),        'icon' => 'dashicons-calendar-alt' ),
                    'cars'           => array( 'label' => __( 'Cars', 'awesome-car-rental' ),            'icon' => 'dashicons-car' ),
                    'categories'     => array( 'label' => __( 'Categories', 'awesome-car-rental' ),      'icon' => 'dashicons-category' ),
                    'extras'         => array( 'label' => __( 'Extras', 'awesome-car-rental' ),          'icon' => 'dashicons-star-empty' ),
                    'customers'      => array( 'label' => __( 'Customers', 'awesome-car-rental' ),      'icon' => 'dashicons-admin-users' ),
                    'staff'          => array( 'label' => __( 'Staff', 'awesome-car-rental' ),           'icon' => 'dashicons-id-alt' ),
                    'maintenance'    => array( 'label' => __( 'Maintenance', 'awesome-car-rental' ),    'icon' => 'dashicons-admin-tools' ),
                    'inspections'    => array( 'label' => __( 'Inspections', 'awesome-car-rental' ),    'icon' => 'dashicons-visibility' ),
                    'promo_codes'    => array( 'label' => __( 'Promo Codes', 'awesome-car-rental' ),    'icon' => 'dashicons-tag' ),
                    'income_expense' => array( 'label' => __( 'Income & Expense', 'awesome-car-rental' ),'icon' => 'dashicons-money-alt' ),
                    'reports'        => array( 'label' => __( 'Reports', 'awesome-car-rental' ),         'icon' => 'dashicons-chart-bar' ),
                    'settings'       => array( 'label' => __( 'Settings', 'awesome-car-rental' ),        'icon' => 'dashicons-admin-generic' ),
                    'shortcode'      => array( 'label' => __( 'Shortcode', 'awesome-car-rental' ),       'icon' => 'dashicons-editor-code' ),
                );

                foreach ( $menu_items as $slug => $item ) :
                    $is_active = ( $active === $slug ) ? 'active' : '';
                    $url = add_query_arg( array(
                        'page' => 'awesome_car_rental',
                        'tab'  => $slug
                    ), admin_url( 'admin.php' ) );
                    ?>
                    <li>
                        <a class="<?php echo esc_attr( $is_active ); ?>" href="<?php echo esc_url( $url ); ?>">
                            <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                            <span class="afd-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="afd-right-box">
            <?php
            if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $acrb_bid        = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
                $acrb_new_status = __( 'Updated', 'awesome-car-rental' );

                // translators: 1: Booking ID, 2: New status name.
                $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );
                echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px 0;"><p>' . wp_kses_post( $acrb_msg ) . '</p></div>';
            }

            // Extended Switch Router with 'shortcode' tab handled
            switch ( $active ) {
                case 'dashboard':      acrb_dashboard_tab(); break;
                case 'cars':           acrb_cars_tab(); break;
                case 'bookings':       acrb_bookings_tab(); break;
                case 'categories':     acrb_category_tab(); break;
                case 'extras':         acrb_extras_tab(); break;
                case 'customers':      acrb_customers_tab(); break;
                case 'staff':          acrb_staff_tab(); break;
                case 'maintenance':    acrb_maintenance_tab(); break;
                case 'inspections':    acrb_inspections_tab(); break;
                case 'promo_codes':    acrb_promo_codes_tab(); break;
                case 'income_expense': acrb_income_expense_tab(); break;
                case 'reports':        acrb_reports_tab(); break;
                case 'settings':       acrb_settings_tab(); break;
                case 'shortcode':      acrb_shortcode_tab(); break;
                default:               acrb_dashboard_tab(); break;
            }
            ?>
        </div>
    </div>
    <?php
}

/*--------------------------------------------------------------
# 3. Load Include Files
--------------------------------------------------------------*/
require_once ACRB_PATH . 'admin/dashboard.php';
require_once ACRB_PATH . 'admin/cars.php';
require_once ACRB_PATH . 'admin/bookings.php';
require_once ACRB_PATH . 'admin/categories.php';
require_once ACRB_PATH . 'admin/extras.php';
require_once ACRB_PATH . 'admin/customers.php';
require_once ACRB_PATH . 'admin/staff.php';
require_once ACRB_PATH . 'admin/maintenance.php';
require_once ACRB_PATH . 'admin/inspections.php';
require_once ACRB_PATH . 'admin/promo-codes.php';
require_once ACRB_PATH . 'admin/income-expense.php';
require_once ACRB_PATH . 'admin/report.php';
require_once ACRB_PATH . 'admin/settings.php';
require_once ACRB_PATH . 'admin/shortcode.php';
require_once ACRB_PATH . 'frontend/frontend.php';

/**
 * Enqueue Admin-Only Styles & Scripts
 */
function acrb_plugin_admin_scripts( $hook ) {
    if ( 'toplevel_page_awesome_car_rental' !== $hook ) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
        'acrb-admin-layout',
        ACRB_URL . 'assets/css/admin-style.css',
        array(),
        ACRB_VERSION
    );

    wp_enqueue_script(
        'acrb-admin-script',
        ACRB_URL . 'assets/js/admin-script.js',
        array( 'jquery' ),
        ACRB_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'acrb_plugin_admin_scripts' );

/**
 * Helper: Check if current post contains plugin shortcodes
 */
function acrb_has_shortcodes() {
    if ( ! is_singular() ) {
        return false;
    }

    global $post;
    if ( ! is_a( $post, 'WP_Post' ) ) {
        return false;
    }

    $shortcodes = array(
        'acrb_all_cars', 
        'acrb_single_car', 
        'acrb_account', 
        'acrb_thanks', 
        'acrb_login', 
        'acrb_register'
    );

    foreach ( $shortcodes as $shortcode ) {
        if ( has_shortcode( $post->post_content, $shortcode ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Enqueue Frontend Styles & Scripts (Conditionally Optimized)
 */
function acrb_plugin_frontend_scripts() {
    if ( ! acrb_has_shortcodes() ) {
        return;
    }

    wp_enqueue_style( 'acrb-frontend-style', ACRB_URL . 'assets/css/frontend-style.css', array(), ACRB_VERSION );
    wp_enqueue_script( 'acrb-frontend-script', ACRB_URL . 'assets/js/frontend-script.js', array( 'jquery' ), ACRB_VERSION, true );
    
    wp_localize_script( 'acrb-frontend-script', 'acrb_ajax_obj', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'acrb_frontend_nonce' ),
        'currency' => get_option( 'acrb_currency', '£' )
    ));
}
add_action( 'wp_enqueue_scripts', 'acrb_plugin_frontend_scripts' );

/*--------------------------------------------------------------
# 4. Activation Hooks & Setup
--------------------------------------------------------------*/
register_activation_hook( __FILE__, 'acrb_run_on_activation' );

function acrb_run_on_activation() {
    acrb_setup_pages();
}

/**
 * Create plugin pages on activation.
 */
function acrb_setup_pages() {
    $pages = array(
        'acrb_all_cars_page'     => array( 'title' => __( 'All Cars', 'awesome-car-rental' ), 'slug' => 'acrb-cars', 'short' => '[acrb_all_cars]' ),
        'acrb_single_car_page'   => array( 'title' => __( 'Car Details', 'awesome-car-rental' ), 'slug' => 'acrb-car-details', 'short' => '[acrb_single_car]' ),
        'acrb_account_page'      => array( 'title' => __( 'Account', 'awesome-car-rental' ), 'slug' => 'acrb-account', 'short' => '[acrb_account]' ),
        'acrb_thanks_page'       => array( 'title' => __( 'Thank You', 'awesome-car-rental' ), 'slug' => 'acrb-thanks', 'short' => '[acrb_thanks]' ),
        'acrb_login_page'        => array( 'title' => __( 'Login', 'awesome-car-rental' ), 'slug' => 'acrb-login', 'short' => '[acrb_login]' ),
        'acrb_registration_page' => array( 'title' => __( 'Registration', 'awesome-car-rental' ), 'slug' => 'acrb-registration', 'short' => '[acrb_register]' ),
    );

    foreach ( $pages as $option_key => $data ) {
        $existing_page_id = get_option( $option_key );
        $page_object      = $existing_page_id ? get_post( $existing_page_id ) : null;

        if ( ! $page_object || 'trash' === $page_object->post_status ) {
            $trashed_page = get_page_by_path( $data['slug'], OBJECT, 'page' );
            if ( $trashed_page && 'trash' === $trashed_page->post_status ) {
                wp_delete_post( $trashed_page->ID, true );
            }

            $new_page_id = wp_insert_post( array(
                'post_title'   => $data['title'],
                'post_name'    => $data['slug'],
                'post_content' => $data['short'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
            ) );

            if ( ! is_wp_error( $new_page_id ) ) {
                update_option( $option_key, $new_page_id );
            }
        }
    }
}

/**
 * Add custom labels to the 'Pages' list in the WordPress admin.
 */
add_filter( 'display_post_states', 'acrb_add_display_post_states', 10, 2 );

function acrb_add_display_post_states( $post_states, $post ) {
    $acrb_pages = array(
        'acrb_all_cars_page'     => __( 'All Cars', 'awesome-car-rental' ),
        'acrb_single_car_page'   => __( 'Car Details', 'awesome-car-rental' ),
        'acrb_account_page'      => __( 'Account', 'awesome-car-rental' ),
        'acrb_thanks_page'       => __( 'Thanks', 'awesome-car-rental' ),
        'acrb_login_page'        => __( 'Login', 'awesome-car-rental' ),
        'acrb_registration_page' => __( 'Registration', 'awesome-car-rental' ),
    );

    foreach ( $acrb_pages as $option_key => $label ) {
        if ( intval( get_option( $option_key ) ) === $post->ID ) {
            $post_states[] = $label;
        }
    }

    return $post_states;
}