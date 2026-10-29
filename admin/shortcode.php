<?php
/**
 * Full Shortcode Management Tab
 * Location: admin/shortcode.php
 *
 * @package Awesome_Car_Rental
 * @since   3.1.8
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_shortcode_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    $sections = array(
        __( 'Header & Navigation', 'awesome-car-rental' ) => array(
            array(
                'title' => __( 'Header Auth Button', 'awesome-car-rental' ),
                'code'  => '[acrb_header_auth]',
                'desc'  => __( 'Shows "Hi, Name" + Avatar for logged-in users, or Login/Register buttons for guests.', 'awesome-car-rental' ),
                'page'  => __( 'Site Header / Navbar', 'awesome-car-rental' ),
            ),
        ),
        __( 'Booking Flow', 'awesome-car-rental' )        => array(
            array(
                'title' => __( 'Search & Booking Form', 'awesome-car-rental' ),
                'code'  => '[acrb_search_form]',
                'desc'  => __( 'The initial search bar for dates and locations. Best for the Hero section.', 'awesome-car-rental' ),
                'page'  => __( 'Home Page', 'awesome-car-rental' ),
            ),
            array(
                'title' => __( 'All Cars Directory', 'awesome-car-rental' ),
                'code'  => '[acrb_all_cars]',
                'desc'  => __( 'Displays your entire rental fleet in a modern grid with interactive filtering.', 'awesome-car-rental' ),
                'page'  => __( 'Fleet / Inventory', 'awesome-car-rental' ),
            ),
            array(
                'title' => __( 'Success / Confirmation', 'awesome-car-rental' ),
                'code'  => '[acrb_thanks]',
                'desc'  => __( 'Displays customer order summary and payment confirmation after a successful booking.', 'awesome-car-rental' ),
                'page'  => __( 'Thank You Page', 'awesome-car-rental' ),
            ),
            array(
                'title' => __( 'Car Grid View', 'awesome-car-rental' ),
                'code'  => '[acrb_car_grid limit="6" orderby="rand"]',
                'desc'  => __( 'Shows cars in a flexible grid. Supports limit and orderby parameters (date, rand, title).', 'awesome-car-rental' ),
                'page'  => __( 'Landing Pages', 'awesome-car-rental' ),
            ),
        ),
        __( 'User & Auth', 'awesome-car-rental' )        => array(
            array(
                'title' => __( 'Client Login Form', 'awesome-car-rental' ),
                'code'  => '[acrb_login]',
                'desc'  => __( 'A clean, AJAX-powered login form that redirects directly to the customer dashboard.', 'awesome-car-rental' ),
                'page'  => __( 'Login Page', 'awesome-car-rental' ),
            ),
            array(
                'title' => __( 'User Registration', 'awesome-car-rental' ),
                'code'  => '[acrb_register]',
                'desc'  => __( 'Registration form with Phone and Address collection. Automatically signs customer in.', 'awesome-car-rental' ),
                'page'  => __( 'Registration Page', 'awesome-car-rental' ),
            ),
            array(
                'title' => __( 'Customer Dashboard', 'awesome-car-rental' ),
                'code'  => '[acrb_account]',
                'desc'  => __( 'Central hub where registered clients manage their profile and track reservations.', 'awesome-car-rental' ),
                'page'  => __( 'My Account', 'awesome-car-rental' ),
            ),
        ),
    );
    ?>

    <style>
        .acrb-sc-wrapper {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
            position: relative;
        }
        .acrb-sc-wrapper *,
        .acrb-sc-wrapper *::before,
        .acrb-sc-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-sc-topbar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .acrb-sc-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-sc-topbar-icon {
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
        .acrb-sc-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-sc-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-sc-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Section Title */
        .acrb-sc-section-title {
            margin: 28px 0 16px 0;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .acrb-sc-section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e2e8f0;
            margin-left: 8px;
        }

        /* Shortcode Grid */
        .acrb-sc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        /* Card Container */
        .acrb-sc-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .acrb-sc-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 20px -4px rgba(15, 23, 42, 0.07);
            transform: translateY(-2px);
        }

        /* Card Top */
        .acrb-sc-card-top {
            margin-bottom: 16px;
        }
        .acrb-sc-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            display: block;
        }
        .acrb-sc-desc {
            margin: 0;
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.45;
        }

        /* Copy Block */
        .acrb-sc-copy-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 8px 6px 12px;
            gap: 8px;
            margin-bottom: 12px;
            transition: border-color 0.15s ease;
        }
        .acrb-sc-copy-box:hover {
            border-color: #cbd5e1;
        }
        .acrb-sc-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12.5px;
            font-weight: 700;
            color: #4f46e5;
            background: transparent;
            padding: 0;
            border: none;
            word-break: break-all;
        }
        .acrb-copy-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #475569;
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .acrb-copy-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .acrb-copy-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .acrb-copy-btn.is-copied {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        /* Card Footer */
        .acrb-sc-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 12px;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }
        .acrb-sc-footer strong {
            color: #64748b;
            font-weight: 600;
        }
        .acrb-sc-placement-badge {
            background: #eff6ff;
            color: #4f46e5;
            border: 1px solid #dbeafe;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        /* Floating Toast Notification */
        .acrb-copy-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 999999;
        }
        .acrb-copy-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .acrb-toast-icon {
            color: #10b981;
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
    </style>

    <div class="acrb-sc-wrapper">

        <!-- Topbar Banner -->
        <header class="acrb-sc-topbar-header">
            <div class="acrb-sc-topbar-title-group">
                <div class="acrb-sc-topbar-icon">
                    <span class="dashicons dashicons-editor-code"></span>
                </div>
                <div class="acrb-sc-topbar-meta">
                    <h1><?php esc_html_e( 'Shortcode Directory', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Deploy your car rental SaaS components by placing these shortcodes into pages, posts, or page builders.', 'awesome-car-rental' ); ?></p>
                </div>
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

        <!-- Feedback Toast -->
        <div id="acrb-toast" class="acrb-copy-toast" aria-hidden="true">
            <span class="dashicons dashicons-yes-alt acrb-toast-icon"></span>
            <span><?php esc_html_e( 'Shortcode copied to clipboard!', 'awesome-car-rental' ); ?></span>
        </div>

    </div>

    <script>
    jQuery(document).ready(function($) {
        var toastTimer;
        
        $('.js-acrb-copy').on('click', function(e) {
            e.preventDefault();
            var targetId   = $(this).data('target');
            var textToCopy = $('#' + targetId).text().trim();
            var $btn       = $(this);

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