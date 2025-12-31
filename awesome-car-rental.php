<?php
/**
 * Plugin Name: Awesome Car Rental & Booking
 * Description: Core functionality for the Car Rental System (CPTs, Admin UI, and Logic).
 * Version: 1.1.1
 * Author: Abdullah Nahian
 * Author URI: https://devnahian.com
 * Text Domain: awesome-car-rental
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Define Plugin Constants
 */
define( 'ACRB_VERSION', '1.0.0' );
define( 'ACRB_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACRB_URL', plugin_dir_url( __FILE__ ) );

/*--------------------------------------------------------------
# 1. Register CPTs and Taxonomy
--------------------------------------------------------------*/
add_action('init', function(){

    register_post_type('acrb_cars', [
        'labels' => ['name' => 'Cars', 'singular_name' => 'Car'],
        'public' => false, 'show_ui' => false, 'supports' => ['title','editor','thumbnail'],
    ]);

    register_taxonomy('acrb_categories','acrb_cars',[
        'labels' => ['name' => 'Categories', 'singular_name' => 'Category'],
        'hierarchical' => true,
        'show_ui' => false
    ]);

    register_post_type('acrb_bookings', [
        'labels' => ['name' => 'Bookings', 'singular_name' => 'Booking'],
        'public' => false, 'show_ui' => false, 'supports' => ['title','editor'],
    ]);

});

/*--------------------------------------------------------------
# 2. Admin Menu & Main Page
--------------------------------------------------------------*/
add_action('admin_menu', function(){
    add_menu_page(
        'Car Rental',
        'Car Rental',
        'manage_options',
        'awesome_car_rental', 
        'acrb_main_page',
        'dashicons-car', 
        20
    );
});

function acrb_main_page(){
    // FIX: Using sanitize_key( wp_unslash() ) to avoid Undefined Constant fatal error 
    // and satisfy WordPress security requirements.
    // FIX: Combined sanitization, unslashing, and nonce suppression for the sniffer
    $active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    ?>
    <div class="awesome-car-rental">
        <?php if ( 'print' !== $action ) : ?>
        <div class="afd-sidebar-container">
            
            <div class="afd-brand-header">
                <h2>
                    <span class="dashicons dashicons-car"></span>
                    Car Rental
                    <span class="version-tag">v1.0</span>
                </h2>
            </div>

            <ul class="afd-left-tabs">
                <?php
                $menu_items = [
                    'dashboard'  => ['label' => 'Dashboard',  'icon' => 'dashicons-performance'],
                    'cars'       => ['label' => 'Cars',       'icon' => 'dashicons-car'],
                    'bookings'   => ['label' => 'Bookings',   'icon' => 'dashicons-calendar-alt'],
                    'categories' => ['label' => 'Categories', 'icon' => 'dashicons-category'],
                    'extras'     => ['label' => 'Extras',     'icon' => 'dashicons-plus-alt'],
                    'reports'    => ['label' => 'Reports',    'icon' => 'dashicons-chart-bar'],
                    'customers'  => ['label' => 'Customers',  'icon' => 'dashicons-groups'],
                    'settings'   => ['label' => 'Settings',   'icon' => 'dashicons-admin-generic'],
                    'shortcode'  => ['label' => 'Shortcodes', 'icon' => 'dashicons-editor-code'],
                ];

                foreach ($menu_items as $slug => $item) :
                    $is_active = ($active === $slug) ? 'active' : '';
                    $url = add_query_arg( [
                        'page' => 'awesome_car_rental',
                        'tab'  => $slug
                    ], admin_url( 'admin.php' ) );
                    ?>
                    <li>
                        <a class="<?php echo esc_attr($is_active); ?>" href="<?php echo esc_url($url); ?>">
                            <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                            <span class="afd-nav-label"><?php echo esc_html($item['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="afd-right-box">
            <?php
            // Handling status updates with the required translators comment format
           if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_bid = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_new_status = __( 'Updated', 'awesome-car-rental' );

                // translators: 1: Booking ID, 2: New status name.
                $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_bid ), esc_html( $acrb_new_status ) );
                echo '<div class="updated notice is-dismissible"><p>' . wp_kses_post( $acrb_msg ) . '</p></div>';
            }

            switch($active){
                case 'dashboard':  acrb_dashboard_tab(); break;
                case 'cars':       acrb_cars_tab(); break;
                case 'bookings':   acrb_bookings_tab(); break;
                case 'categories': acrb_category_tab(); break;
                case 'extras':     acrb_extras_tab(); break;
                case 'reports':    acrb_reports_tab(); break;
                case 'customers':  acrb_customers_tab(); break;
                case 'settings':   acrb_settings_tab(); break;
                case 'shortcode':  acrb_shortcode_tab(); break;
                default:           acrb_dashboard_tab(); break;
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
require_once ACRB_PATH . 'admin/report.php';
require_once ACRB_PATH . 'admin/customers.php';
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
 * Enqueue Frontend Styles & Scripts
 */
function acrb_plugin_frontend_scripts() {
    wp_enqueue_style( 'acrb-frontend-style', ACRB_URL . 'assets/css/frontend-style.css', array(), ACRB_VERSION );
    wp_enqueue_script( 'acrb-frontend-script', ACRB_URL . 'assets/js/frontend-script.js', array( 'jquery' ), ACRB_VERSION, true );
    wp_localize_script( 'acrb-frontend-script', 'acrb_ajax_obj', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'acrb_frontend_nonce' ),
        'currency' => '£'
    ));
}
add_action( 'wp_enqueue_scripts', 'acrb_plugin_frontend_scripts' );

/*--------------------------------------------------------------
# 4. Activation Hooks
--------------------------------------------------------------*/
register_activation_hook(__FILE__, 'acrb_run_on_activation');

function acrb_run_on_activation() {
    delete_option('acrb_pages_installed_v2');
    acrb_setup_pages();
}

add_action('admin_init', function() {
    if (get_option('acrb_pages_installed_v2')) {
        return;
    }
    acrb_setup_pages();
    update_option('acrb_pages_installed_v2', time());
});

/**
 * Create plugin pages on activation.
 */
function acrb_setup_pages() {
    $pages = [
        'acrb_all_cars_page'     => ['title' => 'All Cars', 'slug' => 'acrb-cars', 'short' => '[acrb_all_cars]'],
        'acrb_single_car_page'   => ['title' => 'Car Details', 'slug' => 'acrb-car-details', 'short' => '[acrb_single_car]'],
        'acrb_account_page'      => ['title' => 'Account', 'slug' => 'acrb-account', 'short' => '[acrb_account]'],
        'acrb_thanks_page'       => ['title' => 'Thank You', 'slug' => 'acrb-thanks', 'short' => '[acrb_thanks]'],
        'acrb_login_page'        => ['title' => 'Login', 'slug' => 'acrb-login', 'short' => '[acrb_login]'],
        'acrb_registration_page' => ['title' => 'Registration', 'slug' => 'acrb-registration', 'short' => '[acrb_register]'],
    ];

    foreach ($pages as $option_key => $data) {
        // Check if the page ID is already stored in options
        $existing_page_id = get_option($option_key);
        $page_object = $existing_page_id ? get_post($existing_page_id) : null;

        // If page doesn't exist or is trashed, create it
        if (!$page_object || $page_object->post_status === 'trash') {
            
            // 1. Clean up trash if a page with this slug exists there
            $trashed_page = get_page_by_path($data['slug'], OBJECT, 'page');
            if ($trashed_page && $trashed_page->post_status === 'trash') {
                wp_delete_post($trashed_page->ID, true);
            }

            // 2. Insert the new page
            $new_page_id = wp_insert_post([
                'post_title'   => $data['title'],
                'post_name'    => $data['slug'],
                'post_content' => $data['short'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
            ]);

            if (!is_wp_error($new_page_id)) {
                update_option($option_key, $new_page_id);
            }
        }
    }

    // Only flush rewrite rules once after all pages are created
    flush_rewrite_rules();
}

// Register this function to run ONLY when the plugin is activated
register_activation_hook(__FILE__, 'acrb_setup_pages');

/**
 * Add custom labels to the 'Pages' list in the WordPress admin.
 */
add_filter('display_post_states', 'acrb_add_display_post_states', 10, 2);

function acrb_add_display_post_states($post_states, $post) {
    // Map your option keys to the labels you want to show
    $acrb_pages = [
        'acrb_all_cars_page'     => 'All Cars',
        'acrb_single_car_page'   => 'Car Details',
        'acrb_account_page'      => 'Account',
        'acrb_thanks_page'       => 'Thanks',
        'acrb_login_page'        => 'Login',
        'acrb_registration_page' => 'Registration',
    ];

    foreach ($acrb_pages as $option_key => $label) {
        if (get_option($option_key) == $post->ID) {
            $post_states[] = $label;
        }
    }

    return $post_states;
}