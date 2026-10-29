<?php
/**
 * Full Shortcode Management Tab
 * Location: admin/shortcode.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
                'title' => __( 'All Cars Directory', 'awesome-car-rental' ),
                'code'  => '[acrb_all_cars]',
                'desc'  => __( 'Displays your entire rental fleet in a modern grid with interactive filtering.', 'awesome-car-rental' ),
                'page'  => __( 'Fleet / Inventory', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'Success / Confirmation', 'awesome-car-rental' ),
                'code'  => '[acrb_thanks]',
                'desc'  => __( 'Displays customer order summary and payment confirmation after a successful booking.', 'awesome-car-rental' ),
                'page'  => __( 'Thank You Page', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'Car Grid View', 'awesome-car-rental' ),
                'code'  => '[acrb_car_grid limit="6" orderby="rand"]',
                'desc'  => __( 'Shows cars in a flexible grid. Supports limit and orderby parameters (date, rand, title).', 'awesome-car-rental' ),
                'page'  => __( 'Landing Pages', 'awesome-car-rental' )
            ),
        ),
        'User & Auth' => array(
            array(
                'title' => __( 'Client Login Form', 'awesome-car-rental' ),
                'code'  => '[acrb_login]',
                'desc'  => __( 'A clean, AJAX-powered login form that redirects directly to the customer dashboard.', 'awesome-car-rental' ),
                'page'  => __( 'Login Page', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'User Registration', 'awesome-car-rental' ),
                'code'  => '[acrb_register]',
                'desc'  => __( 'Registration form with Phone and Address collection. Automatically signs customer in.', 'awesome-car-rental' ),
                'page'  => __( 'Registration Page', 'awesome-car-rental' )
            ),
            array(
                'title' => __( 'Customer Dashboard', 'awesome-car-rental' ),
                'code'  => '[acrb_account]',
                'desc'  => __( 'Central hub where registered clients manage their profile and track reservations.', 'awesome-car-rental' ),
                'page'  => __( 'My Account', 'awesome-car-rental' )
            )
        )
    );
    ?>

    <div class="acrb-sc-wrap">
        
        <!-- Header -->
        <header class="acrb-sc-header">
            <div class="acrb-sc-header-icon">
                <span class="dashicons dashicons-editor-code"></span>
            </div>
            <div class="acrb-sc-header-text">
                <h2><?php esc_html_e( 'Shortcode Directory', 'awesome-car-rental' ); ?></h2>
                <p><?php esc_html_e( 'Deploy your car rental SaaS components by placing these shortcodes into pages, posts, or page builders.', 'awesome-car-rental' ); ?></p>
            </div>
        </header>

        <!-- Loop Sections -->
        <?php foreach ( $sections as $section_name => $items ) : ?>
            <h3 class="acrb-sc-section-title"><?php echo esc_html( $section_name ); ?></h3>
            <div class="acrb-sc-grid">
                <?php foreach ( $items as $sc ) : 
                    $sc_id = sanitize_title( $sc['title'] ); 
                ?>
                    <div class="acrb-sc-card">
                        <div class="acrb-sc-card-top">
                            <span class="acrb-sc-title"><?php echo esc_html( $sc['title'] ); ?></span>
                            <p class="acrb-sc-desc"><?php echo esc_html( $sc['desc'] ); ?></p>
                        </div>
                        
                        <div>
                            <div class="acrb-sc-copy-box">
                                <code class="acrb-sc-code" id="code-<?php echo esc_attr( $sc_id ); ?>"><?php echo esc_html( $sc['code'] ); ?></code>
                                <button type="button" 
                                        class="acrb-copy-btn js-acrb-copy" 
                                        data-target="code-<?php echo esc_attr( $sc_id ); ?>" 
                                        title="<?php esc_attr_e( 'Copy to Clipboard', 'awesome-car-rental' ); ?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>

                            <div class="acrb-sc-footer">
                                <strong><?php esc_html_e( 'Target Page:', 'awesome-car-rental' ); ?></strong> 
                                <span class="acrb-sc-placement-badge"><?php echo esc_html( $sc['page'] ); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

    </div>

    <!-- Feedback Toast -->
    <div id="acrb-toast" class="acrb-copy-toast" aria-hidden="true">
        <span class="dashicons dashicons-yes-alt" style="color:#10b981;"></span>
        <span><?php esc_html_e( 'Shortcode copied to clipboard!', 'awesome-car-rental' ); ?></span>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var toastTimer;
        
        $('.js-acrb-copy').on('click', function(e) {
            e.preventDefault();
            var targetId = $(this).data('target');
            var textToCopy = $('#' + targetId).text().trim();
            var $btn = $(this);

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(showSuccess, fallbackCopy);
            } else {
                fallbackCopy();
            }

            function fallbackCopy() {
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(textToCopy).select();
                try {
                    document.execCommand('copy');
                    showSuccess();
                } catch (err) {
                    // Fail silently
                }
                $temp.remove();
            }

            function showSuccess() {
                $btn.addClass('is-copied');
                $btn.find('.dashicons').removeClass('dashicons-admin-page').addClass('dashicons-yes');

                var $toast = $('#acrb-toast');
                $toast.addClass('is-visible');

                clearTimeout(toastTimer);
                toastTimer = setTimeout(function() {
                    $toast.removeClass('is-visible');
                    $btn.removeClass('is-copied');
                    $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-admin-page');
                }, 2200);
            }
        });
    });
    </script>
    <?php
}