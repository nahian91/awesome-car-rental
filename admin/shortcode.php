<?php
if (!defined('ABSPATH')) exit;

/**
 * Full Shortcode Management Tab
*/

function acrb_shortcode_tab() {

    $sections = [
        'Header & Navigation' => [
            [
                'title' => 'Header Auth Button',
                'code'  => '[acrb_header_auth]',
                'desc'  => 'Shows "Hi, Name" + Avatar for logged-in users, or Login/Register buttons for guests.',
                'page'  => 'Site Header / Navbar'
            ],
        ],
        'Booking Flow' => [
            [
                'title' => 'Search & Booking Form',
                'code'  => '[acrb_search_form]',
                'desc'  => 'The initial search bar for dates and locations. Best for the Hero section.',
                'page'  => 'Home Page'
            ],
            [
                'title' => 'All Cars',
                'code'  => '[acrb_all_cars]',
                'desc'  => 'Displays your entire rental cars in a modern grid with filters.',
                'page'  => 'Fleet / Inventory'
            ],
            [
                'title' => 'Success / Confirmation',
                'code'  => '[acrb_thanks]',
                'desc'  => 'Displays order summary after a successful booking.',
                'page'  => 'Thank You'
            ],
            [
                'title' => 'Car Grid View',
                'code'  => '[acrb_car_grid limit="6" orderby="rand"]',
                'desc'  => 'Shows cars in a 3-column grid. Supports limit and orderby (date, rand, title).',
                'page'  => 'Home / Landing Page'
            ],
        ],
        'User & Auth' => [
            [
                'title' => 'Login Form',
                'code'  => '[acrb_login]',
                'desc'  => 'A clean, AJAX-powered login form that redirects to the account page.',
                'page'  => 'Login Page'
            ],
            [
                'title' => 'User Registration',
                'code'  => '[acrb_register]',
                'desc'  => 'Registration form with Phone and Address fields. Auto-logs user in.',
                'page'  => 'Registration Page'
            ],
            [
                'title' => 'Customer Dashboard',
                'code'  => '[acrb_account]',
                'desc'  => 'The central hub where users manage their profile and view rentals.',
                'page'  => 'My Account'
            ]
        ]
    ];
    ?>

    <div class="acrb-sc-wrap">
        <header class="acrb-sc-header">
            <h2><span class="dashicons dashicons-editor-code"></span> <?php esc_html_e('Shortcode Library', 'awesome-car-rental'); ?></h2>
            <p><?php esc_html_e('Deploy your car rental SaaS components by placing these shortcodes on your site pages.', 'awesome-car-rental'); ?></p>
        </header>

        <?php foreach ($sections as $section_name => $items) : ?>
            <h3 class="acrb-sc-section-title"><?php echo esc_html($section_name); ?></h3>
            <div class="acrb-sc-grid">
                <?php foreach ($items as $sc) : 
                    $sc_id = sanitize_title($sc['title']); 
                ?>
                    <div class="acrb-sc-card">
                        <span class="acrb-sc-title"><?php echo esc_html($sc['title']); ?></span>
                        <p class="acrb-sc-desc"><?php echo esc_html($sc['desc']); ?></p>
                        
                        <div class="acrb-sc-copy-box">
                            <code class="acrb-sc-code" id="code-<?php echo esc_attr($sc_id); ?>"><?php echo esc_html($sc['code']); ?></code>
                            <button type="button" 
                                    class="acrb-copy-btn js-acrb-copy" 
                                    data-target="code-<?php echo esc_attr($sc_id); ?>" 
                                    title="<?php esc_attr_e('Copy to Clipboard', 'awesome-car-rental'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>

                        <div class="acrb-sc-footer">
                            <strong><?php esc_html_e('Placement:', 'awesome-car-rental'); ?></strong> <span><?php echo esc_html($sc['page']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="acrb-toast" class="acrb-copy-toast" aria-hidden="true">
        <?php esc_html_e('Shortcode copied to clipboard!', 'awesome-car-rental'); ?>
    </div>
    <?php
}