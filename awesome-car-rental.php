<?php
/**
 * Plugin Name: Awesome Car Rental & Booking
 * Description: Core functionality for the Car Rental System (Custom Database Architecture, Admin UI, and Logic).
 * Version: 2.0
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
    function acr_fs() {
        global $acr_fs;

        if ( ! isset( $acr_fs ) ) {
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

    acr_fs();
    do_action( 'acr_fs_loaded' );
}

/**
 * Define Plugin Constants
 */
define( 'ACRB_VERSION', '2.0' );
define( 'ACRB_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACRB_URL', plugin_dir_url( __FILE__ ) );

/*--------------------------------------------------------------
# 1. Include Modular Component Files
--------------------------------------------------------------*/
if ( file_exists( ACRB_PATH . 'includes/database.php' ) ) {
    require_once ACRB_PATH . 'includes/database.php';
}

if ( file_exists( ACRB_PATH . 'includes/menus.php' ) ) {
    require_once ACRB_PATH . 'includes/menus.php';
}

if ( file_exists( ACRB_PATH . 'includes/assets.php' ) ) {
    require_once ACRB_PATH . 'includes/assets.php';
}

if ( file_exists( ACRB_PATH . 'frontend/frontend.php' ) ) {
    require_once ACRB_PATH . 'frontend/frontend.php';
}

/*--------------------------------------------------------------
# 2. Central Admin Page Router & View Controller
--------------------------------------------------------------*/
function acrb_main_page() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

    wp_enqueue_style( 'acrb-font-jakarta', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap', array(), null );
    ?>
    <style>
        /* Modern SaaS Light Sidebar Theme */
        .afd-sidebar-container {
            background: #ffffff !important; /* Crisp White Base */
            color: #0f172a !important;
            border-right: 1px solid #e2e8f0 !important;
        }
        .afd-sidebar-hero {
            background: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        .afd-sidebar-hero-icon {
            background: #4f46e5 !important; /* Vibrant Indigo */
            color: #ffffff !important;
        }
        .afd-sidebar-hero-meta h3 {
            color: #0f172a !important;
        }
        .afd-sidebar-hero-meta span {
            color: #64748b !important;
        }
        .afd-accordion-group {
            margin-bottom: 6px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
        }
        .afd-accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            color: #64748b; /* Muted slate label */
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            cursor: pointer;
            user-select: none;
            transition: color 0.15s ease;
        }
        .afd-accordion-header:hover {
            color: #0f172a;
        }
        .afd-accordion-content {
            list-style: none;
            margin: 0;
            padding: 0;
            display: none;
        }
        .afd-accordion-group.open .afd-accordion-content {
            display: block;
        }
        .afd-accordion-toggle-icon {
            font-size: 14px;
            transition: transform 0.2s ease;
            color: #94a3b8;
        }
        .afd-accordion-group.open .afd-accordion-toggle-icon {
            transform: rotate(180deg);
            color: #4f46e5;
        }
        /* Navigation Links & Active States for Light Theme */
        .afd-left-tabs a {
            color: #334155 !important;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .afd-left-tabs a:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .afd-left-tabs a.active {
            background: #4f46e5 !important; /* Active Indigo */
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        .afd-tab-icon-wrap .dashicons {
            color: inherit !important;
        }
    </style>
    <script>
        document.addEventListener('click', function(e) {
            const header = e.target.closest('.afd-accordion-header');
            if (header) {
                const group = header.parentElement;
                group.classList.toggle('open');
            }
        });
    </script>

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
                // Grouped Accordion Menu Sections (All set to open by default)
                $menu_sections = array(
                    'overview' => array(
                        'title' => __( 'Core', 'awesome-car-rental' ),
                        'open'  => true,
                        'items' => array(
                            'dashboard' => array( 'label' => __( 'Dashboard', 'awesome-car-rental' ), 'icon' => 'dashicons-chart-pie' ),
                            'bookings'  => array( 'label' => __( 'Bookings', 'awesome-car-rental' ),  'icon' => 'dashicons-calendar-alt' ),
                            'calendar'  => array( 'label' => __( 'Calendar', 'awesome-car-rental' ),  'icon' => 'dashicons-calendar' ),
                        ),
                    ),
                    'fleet' => array(
                        'title' => __( 'Fleet & Operations', 'awesome-car-rental' ),
                        'open'  => true,
                        'items' => array(
                            'cars'        => array( 'label' => __( 'All Cars', 'awesome-car-rental' ),     'icon' => 'dashicons-car' ),
                            'categories'  => array( 'label' => __( 'Categories', 'awesome-car-rental' ),   'icon' => 'dashicons-category' ),
                            'extras'      => array( 'label' => __( 'Add-ons', 'awesome-car-rental' ),      'icon' => 'dashicons-star-empty' ),
                            'maintenance' => array( 'label' => __( 'Maintenance', 'awesome-car-rental' ),  'icon' => 'dashicons-admin-tools' ),
                            'inspections' => array( 'label' => __( 'Vehicle Checks', 'awesome-car-rental' ), 'icon' => 'dashicons-clipboard' ),
                        ),
                    ),
                    'business' => array(
                        'title' => __( 'Business & Finance', 'awesome-car-rental' ),
                        'open'  => true,
                        'items' => array(
                            'customers'      => array( 'label' => __( 'Customers', 'awesome-car-rental' ),     'icon' => 'dashicons-admin-users' ),
                            'staff'          => array( 'label' => __( 'Staff', 'awesome-car-rental' ),          'icon' => 'dashicons-id-alt' ),
                            'promo_codes'    => array( 'label' => __( 'Coupons', 'awesome-car-rental' ),        'icon' => 'dashicons-tag' ),
                            'income_expense' => array( 'label' => __( 'Finance', 'awesome-car-rental' ),        'icon' => 'dashicons-money-alt' ),
                            'reports'        => array( 'label' => __( 'Reports', 'awesome-car-rental' ),        'icon' => 'dashicons-chart-bar' ),
                        ),
                    ),
                    'system' => array(
                        'title' => __( 'System & Customization', 'awesome-car-rental' ),
                        'open'  => true,
                        'items' => array(
                            'shortcode' => array( 'label' => __( 'Shortcodes', 'awesome-car-rental' ), 'icon' => 'dashicons-editor-code' ),
                            'settings'  => array( 'label' => __( 'Settings', 'awesome-car-rental' ),   'icon' => 'dashicons-admin-generic' ),
                            'seo'       => array( 'label' => __( 'SEO', 'awesome-car-rental' ),          'icon' => 'dashicons-search' ),
                            'style'     => array( 'label' => __( 'Style', 'awesome-car-rental' ),        'icon' => 'dashicons-art' ),
                        ),
                    ),
                );
                ?>

                <div class="afd-sidebar-group">
                    <?php foreach ( $menu_sections as $section_key => $section ) : 
                        $open_class = $section['open'] ? 'open' : '';
                    ?>
                        <div class="afd-accordion-group <?php echo esc_attr( $open_class ); ?>">
                            <div class="afd-accordion-header">
                                <span><?php echo esc_html( $section['title'] ); ?></span>
                                <span class="dashicons dashicons-arrow-down-alt2 afd-accordion-toggle-icon"></span>
                            </div>
                            <ul class="afd-left-tabs afd-accordion-content">
                                <?php foreach ( $section['items'] as $slug => $item ) : 
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
            </div>
            <?php endif; ?>

            <div class="afd-right-box">
                <?php
                // Sub-page router
                switch ( $active ) {
                    case 'dashboard':
                        if ( file_exists( ACRB_PATH . 'admin/dashboard.php' ) ) {
                            require_once ACRB_PATH . 'admin/dashboard.php';
                            if ( function_exists( 'acrb_dashboard_tab' ) ) {
                                acrb_dashboard_tab();
                            }
                        }
                        break;
                    case 'cars':
                        if ( file_exists( ACRB_PATH . 'admin/cars.php' ) ) {
                            require_once ACRB_PATH . 'admin/cars.php';
                            if ( function_exists( 'acrb_cars_tab' ) ) {
                                acrb_cars_tab();
                            }
                        }
                        break;
                    case 'bookings':
                        if ( file_exists( ACRB_PATH . 'admin/bookings.php' ) ) {
                            require_once ACRB_PATH . 'admin/bookings.php';
                            if ( function_exists( 'acrb_bookings_tab' ) ) {
                                acrb_bookings_tab();
                            }
                        }
                        break;
                    case 'calendar':
                        if ( file_exists( ACRB_PATH . 'admin/calendar.php' ) ) {
                            require_once ACRB_PATH . 'admin/calendar.php';
                        }
                        break;
                    case 'categories':
                        if ( file_exists( ACRB_PATH . 'admin/categories.php' ) ) {
                            require_once ACRB_PATH . 'admin/categories.php';
                            if ( function_exists( 'acrb_category_tab' ) ) {
                                acrb_category_tab();
                            }
                        }
                        break;
                    case 'extras':
                        if ( file_exists( ACRB_PATH . 'admin/extras.php' ) ) {
                            require_once ACRB_PATH . 'admin/extras.php';
                            if ( function_exists( 'acrb_extras_tab' ) ) {
                                acrb_extras_tab();
                            }
                        }
                        break;
                    case 'customers':
                        if ( file_exists( ACRB_PATH . 'admin/customers.php' ) ) {
                            require_once ACRB_PATH . 'admin/customers.php';
                            if ( function_exists( 'acrb_customers_tab' ) ) {
                                acrb_customers_tab();
                            }
                        }
                        break;
                    case 'staff':
                        if ( file_exists( ACRB_PATH . 'admin/staff.php' ) ) {
                            require_once ACRB_PATH . 'admin/staff.php';
                            if ( function_exists( 'acrb_staff_tab' ) ) {
                                acrb_staff_tab();
                            }
                        }
                        break;
                    case 'maintenance':
                        if ( file_exists( ACRB_PATH . 'admin/maintenance.php' ) ) {
                            require_once ACRB_PATH . 'admin/maintenance.php';
                            if ( function_exists( 'acrb_maintenance_tab' ) ) {
                                acrb_maintenance_tab();
                            }
                        }
                        break;
                    case 'inspections':
                        if ( file_exists( ACRB_PATH . 'admin/inspections.php' ) ) {
                            require_once ACRB_PATH . 'admin/inspections.php';
                            if ( function_exists( 'acrb_inspections_tab' ) ) {
                                acrb_inspections_tab();
                            }
                        }
                        break;
                    case 'promo_codes':
                        if ( file_exists( ACRB_PATH . 'admin/promos.php' ) ) {
                            require_once ACRB_PATH . 'admin/promos.php';
                            if ( function_exists( 'acrb_promo_codes_tab' ) ) {
                                acrb_promo_codes_tab();
                            }
                        }
                        break;
                    case 'income_expense':
                        if ( file_exists( ACRB_PATH . 'admin/income-expense.php' ) ) {
                            require_once ACRB_PATH . 'admin/income-expense.php';
                            if ( function_exists( 'acrb_income_expense_tab' ) ) {
                                acrb_income_expense_tab();
                            }
                        }
                        break;
                    case 'reports':
                        if ( file_exists( ACRB_PATH . 'admin/report.php' ) ) {
                            require_once ACRB_PATH . 'admin/report.php';
                            if ( function_exists( 'acrb_reports_tab' ) ) {
                                acrb_reports_tab();
                            }
                        }
                        break;
                    case 'settings':
                        if ( file_exists( ACRB_PATH . 'admin/settings.php' ) ) {
                            require_once ACRB_PATH . 'admin/settings.php';
                            if ( function_exists( 'acrb_settings_tab' ) ) {
                                acrb_settings_tab();
                            }
                        }
                        break;
                    case 'shortcode':
                        if ( file_exists( ACRB_PATH . 'admin/shortcode.php' ) ) {
                            require_once ACRB_PATH . 'admin/shortcode.php';
                            if ( function_exists( 'acrb_shortcode_tab' ) ) {
                                acrb_shortcode_tab();
                            }
                        }
                        break;
                    case 'seo':
                        if ( file_exists( ACRB_PATH . 'admin/seo.php' ) ) {
                            require_once ACRB_PATH . 'admin/seo.php';
                            if ( function_exists( 'acrb_seo_tab' ) ) {
                                acrb_seo_tab();
                            }
                        } else {
                            echo '<div class="wrap"><h2>SEO Settings</h2><p>SEO module coming soon.</p></div>';
                        }
                        break;
                    case 'style':
                        if ( file_exists( ACRB_PATH . 'admin/style.php' ) ) {
                            require_once ACRB_PATH . 'admin/style.php';
                            if ( function_exists( 'acrb_style_tab' ) ) {
                                acrb_style_tab();
                            }
                        } else {
                            echo '<div class="wrap"><h2>Style Customization</h2><p>Style module coming soon.</p></div>';
                        }
                        break;
                    default:
                        if ( file_exists( ACRB_PATH . 'admin/dashboard.php' ) ) {
                            require_once ACRB_PATH . 'admin/dashboard.php';
                            if ( function_exists( 'acrb_dashboard_tab' ) ) {
                                acrb_dashboard_tab();
                            }
                        }
                        break;
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/*--------------------------------------------------------------
# 3. Activation Hooks & Setup Handlers
--------------------------------------------------------------*/
register_activation_hook( __FILE__, 'acrb_run_on_activation' );

function acrb_run_on_activation() {
    if ( function_exists( 'acrb_create_custom_tables' ) ) {
        acrb_create_custom_tables();
    }
    acrb_setup_pages();
}

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

add_filter( 'display_post_states', 'acrb_add_display_post_states', 10, 2 );

function acrb_add_display_post_states( $post_states, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return $post_states;
    }

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