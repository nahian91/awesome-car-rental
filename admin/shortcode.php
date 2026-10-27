<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Full Shortcode Management Tab
 * Location: admin/shortcode.php
 */
function acrb_shortcode_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $sections = array(
        'Header & Navigation' => array(
            array(
                'title' => __( 'Header Auth Button', 'awesome-car-rental' ),
                'code'  => '[acrb_header_auth]',
                'desc'  => __( 'Shows "Hi, Name" + Avatar for logged-in users, or Login/Register buttons for guests.', 'awesome-car-rental' ),
                'page'  => __( 'Site Header / Navbar', 'awesome-car-rental' )
            ),
        ),
        'Booking Flow' => array(
            array(
                'title' => __( 'Search & Booking Form', 'awesome-car-rental' ),
                'code'  => '[acrb_search_form]',
                'desc'  => __( 'The initial search bar for dates and locations. Best for the Hero section.', 'awesome-car-rental' ),
                'page'  => __( 'Home Page', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'All Cars', 'awesome-car-rental' ),
                'code'  => '[acrb_all_cars]',
                'desc'  => __( 'Displays your entire rental cars in a modern grid with filters.', 'awesome-car-rental' ),
                'page'  => __( 'Fleet / Inventory', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'Success / Confirmation', 'awesome-car-rental' ),
                'code'  => '[acrb_thanks]',
                'desc'  => __( 'Displays order summary after a successful booking.', 'awesome-car-rental' ),
                'page'  => __( 'Thank You', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'Car Grid View', 'awesome-car-rental' ),
                'code'  => '[acrb_car_grid limit="6" orderby="rand"]',
                'desc'  => __( 'Shows cars in a 3-column grid. Supports limit and orderby (date, rand, title).', 'awesome-car-rental' ),
                'page'  => __( 'Home / Landing Page', 'awesome-car-rental' )
            ),
        ),
        'User & Auth' => array(
            array(
                'title' => __( 'Login Form', 'awesome-car-rental' ),
                'code'  => '[acrb_login]',
                'desc'  => __( 'A clean, AJAX-powered login form that redirects to the account page.', 'awesome-car-rental' ),
                'page'  => __( 'Login Page', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'User Registration', 'awesome-car-rental' ),
                'code'  => '[acrb_register]',
                'desc'  => __( 'Registration form with Phone and Address fields. Auto-logs user in.', 'awesome-car-rental' ),
                'page'  => __( 'Registration Page', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'Customer Dashboard', 'awesome-car-rental' ),
                'code'  => '[acrb_account]',
                'desc'  => __( 'The central hub where users manage their profile and view rentals.', 'awesome-car-rental' ),
                'page'  => __( 'My Account', 'awesome-car-rental' )
            )
        )
    );
    ?>

    <div class="acrb-sc-wrap">
        <header class="acrb-sc-header">
            <h2><span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Shortcode Library', 'awesome-car-rental' ); ?></h2>
            <p><?php esc_html_e( 'Deploy your car rental SaaS components by placing these shortcodes on your site pages.', 'awesome-car-rental' ); ?></p>
        </header>

        <?php foreach ( $sections as $section_name => $items ) : ?>
            <h3 class="acrb-sc-section-title"><?php echo esc_html( $section_name ); ?></h3>
            <div class="acrb-sc-grid">
                <?php foreach ( $items as $sc ) : 
                    $sc_id = sanitize_title( $sc['title'] ); 
                ?>
                    <div class="acrb-sc-card">
                        <span class="acrb-sc-title"><?php echo esc_html( $sc['title'] ); ?></span>
                        <p class="acrb-sc-desc"><?php echo esc_html( $sc['desc'] ); ?></p>
                        
                        <div class="acrb-sc-copy-box">
                            <code class="acrb-sc-code" id="code-<?php echo esc_attr( $sc_id ); ?>"><?php echo esc_html( $sc['code'] ); ?></code>
                            <button type="button" 
                                    class="acrb-copy-btn js-acrb-copy" 
                                    data-target="code-<?php echo esc_attr( $sc_id ); ?>" 
                                    title="<?php esc_attr_e( 'Copy to Clipboard', 'awesome-car-rental' ); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>

                        <div class="acrb-sc-footer">
                            <strong><?php esc_html_e( 'Placement:', 'awesome-car-rental' ); ?></strong> <span><?php echo esc_html( $sc['page'] ); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="acrb-toast" class="acrb-copy-toast" aria-hidden="true">
        <?php esc_html_e( 'Shortcode copied to clipboard!', 'awesome-car-rental' ); ?>
    </div>
    <?php
}