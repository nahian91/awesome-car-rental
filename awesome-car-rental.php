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
                    'slug'            => 'awesome_car_rental',
                    'account'         => false,
                    'contact'         => false,
                    'support'         => false,
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

    // Load High-End SaaS Typography (Plus Jakarta Sans + Inter)
    wp_enqueue_style( 'acrb-font-jakarta', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap', array(), null );
    ?>
    <style>
        :root {
            --acrb-bg: #f8fafc;
            --acrb-surface: #ffffff;
            --acrb-border: #e2e8f0;
            --acrb-border-subtle: #f1f5f9;
            --acrb-text-main: #0f172a;
            --acrb-text-muted: #64748b;
            --acrb-primary: #4f46e5;
            --acrb-primary-soft: #eef2ff;
            --acrb-radius-lg: 20px;
            --acrb-radius-md: 12px;
            --acrb-radius-sm: 8px;
            --acrb-shadow-card: 0 10px 30px -5px rgba(15, 23, 42, 0.05), 0 1px 3px 0 rgba(15, 23, 42, 0.03);
            --acrb-shadow-active: 0 4px 14px -1px rgba(79, 70, 229, 0.35);
        }

        .awesome-car-rental {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--acrb-text-main);
            max-width: 1560px;
            margin: 24px 20px 40px 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        .awesome-car-rental *,
        .awesome-car-rental *:before,
        .awesome-car-rental *:after {
            box-sizing: border-box;
        }

        .acrb-layout-body {
            display: flex;
            gap: 28px;
            align-items: flex-start;
        }

        /* Sidebar Container */
        .afd-sidebar-container {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
            border: 1px solid var(--acrb-border);
            border-radius: var(--acrb-radius-lg);
            padding: 20px 14px;
            box-shadow: var(--acrb-shadow-card);
            position: sticky;
            top: 48px;
        }

        /* Compact Header Embedded into Sidebar */
        .afd-sidebar-hero {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 4px 8px 18px 8px;
            border-bottom: 1px solid var(--acrb-border-subtle);
            margin-bottom: 16px;
        }

        .afd-sidebar-hero-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 6px 14px -2px rgba(79, 70, 229, 0.4);
            flex-shrink: 0;
        }

        .afd-sidebar-hero-icon .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .afd-sidebar-hero-meta h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14.5px;
            font-weight: 800;
            color: var(--acrb-text-main);
            margin: 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .afd-sidebar-hero-meta span {
            font-size: 11px;
            color: #059669;
            background: #ecfdf5;
            padding: 1px 7px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            margin-top: 3px;
        }

        /* Nav Grouping */
        .afd-sidebar-group {
            margin-bottom: 18px;
        }

        .afd-sidebar-group:last-child {
            margin-bottom: 0;
        }

        .afd-sidebar-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 700;
            padding: 4px 10px 6px 10px;
            margin: 0;
        }

        .afd-left-tabs {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        /* Item Links */
        .afd-left-tabs li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 10px;
            border-radius: var(--acrb-radius-md);
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 13.5px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        /* Icon Wrapper */
        .afd-tab-icon-wrap {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #64748b;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .afd-tab-icon-wrap .dashicons {
            font-size: 15px;
            width: 15px;
            height: 15px;
        }

        /* Interactive Hover */
        .afd-left-tabs li a:hover {
            background: #ffffff;
            color: var(--acrb-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transform: translateX(2px);
        }

        .afd-left-tabs li a:hover .afd-tab-icon-wrap {
            background: var(--acrb-primary-soft);
            color: var(--acrb-primary);
        }

        /* Modern Gradient Active State */
        .afd-left-tabs li a.active {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: #ffffff;
            font-weight: 600;
            box-shadow: var(--acrb-shadow-active);
        }

        .afd-left-tabs li a.active .afd-tab-icon-wrap {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }

        /* Active Left Glow Indicator */
        .afd-left-tabs li a.active::before {
            content: '';
            position: absolute;
            left: -14px;
            top: 50%;
            transform: translateY(-50%);
            height: 18px;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background: var(--acrb-primary);
        }

        /* Right Content Container */
        .afd-right-box {
            flex: 1;
            min-width: 0;
            background: var(--acrb-surface);
            border: 1px solid var(--acrb-border);
            border-radius: var(--acrb-radius-lg);
            padding: 28px;
            box-shadow: var(--acrb-shadow-card);
        }

        /* Alert Notice */
        .acrb-notice-success {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 18px;
            border-radius: var(--acrb-radius-md);
            margin-bottom: 24px;
            font-size: 13.5px;
            font-weight: 500;
        }

        @media(max-width: 1024px) {
            .acrb-layout-body { flex-direction: column; }
            .afd-sidebar-container { width: 100%; position: static; }
            .afd-left-tabs li a.active::before { display: none; }
        }
    </style>

    <div class="awesome-car-rental">
        <div class="acrb-layout-body">
            <?php if ( 'print' !== $action ) : ?>
            <div class="afd-sidebar-container">
                <!-- Brand Hero -->
                <div class="afd-sidebar-hero">
                    <div class="afd-sidebar-hero-icon">
                        <span class="dashicons dashicons-car"></span>
                    </div>
                    <div class="afd-sidebar-hero-meta">
                        <h3>Car Rental</h3>
                        <span>Pro v<?php echo esc_html( ACRB_VERSION ); ?></span>
                    </div>
                </div>

                <?php
                // Easy, Friendly & Clear Navigation Names
                $navigation_sections = array(
                    __( 'Daily Work', 'awesome-car-rental' ) => array(
                        'dashboard'      => array( 'label' => __( 'Overview', 'awesome-car-rental' ),        'icon' => 'dashicons-chart-pie' ),
                        'bookings'       => array( 'label' => __( 'Bookings', 'awesome-car-rental' ),        'icon' => 'dashicons-calendar-alt' ),
                        'inspections'    => array( 'label' => __( 'Vehicle Checks', 'awesome-car-rental' ),  'icon' => 'dashicons-clipboard' ),
                    ),
                    __( 'Cars & Fleet', 'awesome-car-rental' ) => array(
                        'cars'           => array( 'label' => __( 'All Cars', 'awesome-car-rental' ),        'icon' => 'dashicons-car' ),
                        'categories'     => array( 'label' => __( 'Car Types', 'awesome-car-rental' ),       'icon' => 'dashicons-category' ),
                        'extras'         => array( 'label' => __( 'Add-ons', 'awesome-car-rental' ),         'icon' => 'dashicons-star-empty' ),
                        'maintenance'    => array( 'label' => __( 'Repairs', 'awesome-car-rental' ),         'icon' => 'dashicons-admin-tools' ),
                    ),
                    __( 'Business', 'awesome-car-rental' ) => array(
                        'customers'      => array( 'label' => __( 'Customers', 'awesome-car-rental' ),       'icon' => 'dashicons-admin-users' ),
                        'staff'          => array( 'label' => __( 'Team', 'awesome-car-rental' ),            'icon' => 'dashicons-id-alt' ),
                        'promo_codes'    => array( 'label' => __( 'Coupons', 'awesome-car-rental' ),         'icon' => 'dashicons-tag' ),
                        'income_expense' => array( 'label' => __( 'Money & Costs', 'awesome-car-rental' ),   'icon' => 'dashicons-money-alt' ),
                        'reports'        => array( 'label' => __( 'Reports', 'awesome-car-rental' ),         'icon' => 'dashicons-chart-bar' ),
                    ),
                    __( 'Settings', 'awesome-car-rental' ) => array(
                        'shortcode'      => array( 'label' => __( 'Shortcodes', 'awesome-car-rental' ),      'icon' => 'dashicons-editor-code' ),
                        'settings'       => array( 'label' => __( 'Settings', 'awesome-car-rental' ),        'icon' => 'dashicons-admin-generic' ),
                    ),
                );

                foreach ( $navigation_sections as $group_title => $items ) : ?>
                    <div class="afd-sidebar-group">
                        <h4 class="afd-sidebar-title"><?php echo esc_html( $group_title ); ?></h4>
                        <ul class="afd-left-tabs">
                            <?php foreach ( $items as $slug => $item ) : 
                                $is_active = ( $active === $slug ) ? 'active' : '';
                                $url = add_query_arg( array(
                                    'page' => 'awesome_car_rental',
                                    'tab'  => $slug
                                ), admin_url( 'admin.php' ) );
                            ?>
                                <li>
                                    <a class="<?php echo esc_attr( $is_active ); ?>" href="<?php echo esc_url( $url ); ?>">
                                        <div class="afd-tab-icon-wrap">
                                            <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                                        </div>
                                        <span><?php echo esc_html( $item['label'] ); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="afd-right-box">
                <?php
                if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $acrb_bid = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
                    $acrb_msg = sprintf(
                        /* translators: %d: Booking ID */
                        esc_html__( 'Booking #%d was successfully updated.', 'awesome-car-rental' ),
                        absint( $acrb_bid )
                    );
                    echo '<div class="acrb-notice-success"><span class="dashicons dashicons-yes-alt"></span> ' . wp_kses_post( $acrb_msg ) . '</div>';
                }

                // Sub-page router
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
 * Enqueue Admin-Only Styles & Scripts (Including DataTables)
 */
function acrb_plugin_admin_scripts( $hook ) {
    // Check both $hook and the query parameter for reliable loading
    if ( ! isset( $_GET['page'] ) || 'awesome_car_rental' !== $_GET['page'] ) {
        return;
    }

    wp_enqueue_media();

    // 1. DataTables Core CSS
    wp_enqueue_style(
        'acrb-datatables-css',
        'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
        array(),
        '1.13.7'
    );

    // 2. DataTables Core JS
    wp_enqueue_script(
        'acrb-datatables-js',
        'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
        array( 'jquery' ),
        '1.13.7',
        true
    );

    // 3. Plugin Admin Styles & Scripts
    wp_enqueue_style(
        'acrb-admin-layout',
        ACRB_URL . 'assets/css/admin-style.css',
        array( 'acrb-datatables-css' ),
        ACRB_VERSION
    );

    wp_enqueue_script(
        'acrb-admin-script',
        ACRB_URL . 'assets/js/admin-script.js',
        array( 'jquery', 'acrb-datatables-js' ),
        ACRB_VERSION,
        true
    );

    // 4. Auto-initialize DataTables: Disable all alert warnings & handle empty states smoothly
    $inline_dt_init = "
        jQuery(document).ready(function($) {
            // SILENCE ALL DATATABLES POPUP WARNINGS / ALERTS
            if ( $.fn.dataTable ) {
                $.fn.dataTable.ext.errMode = 'none';
            }

            $('.acrb-datatable, .awesome-car-rental table.wp-list-table').each(function() {
                var \$tbl = $(this);
                if ( ! $.fn.DataTable.isDataTable(this) ) {
                    // Only initialize if there are rows or valid headers
                    if ( \$tbl.find('tbody tr').length > 0 ) {
                        \$tbl.DataTable({
                            responsive: false,
                            scrollX: true,
                            pageLength: 10,
                            order: [],
                            language: {
                                search: '',
                                searchPlaceholder: 'Search records...',
                                lengthMenu: 'Show _MENU_ entries',
                                emptyTable: 'No records found',
                                zeroRecords: 'No matching records found'
                            }
                        });
                    }
                }
            });
        });
    ";
    wp_add_inline_script( 'acrb-datatables-js', $inline_dt_init );
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