<?php
/**
 * Registration Handler for Awesome Car Rental (Elite SaaS Modernized)
 * Location: templates/shortcodes/register.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. BACKEND LOGIC: AJAX Registration Handler
 */
add_action( 'wp_ajax_nopriv_acrb_ajax_register', 'acrb_handle_ajax_registration' );

function acrb_handle_ajax_registration(): void {
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['security'] ) ), 'acrb-reg-nonce' ) ) {
        wp_send_json_error( esc_html__( 'Security check failed. Please refresh the page.', 'awesome-car-rental' ) );
    }

    $username  = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
    $email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    
    // Passwords must be unslashed but NOT sanitized to preserve special characters
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $password  = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $cpassword = isset( $_POST['cpassword'] ) ? wp_unslash( $_POST['cpassword'] ) : '';
    
    $full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
    $phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

    if ( empty( $username ) || empty( $email ) || empty( $password ) || empty( $cpassword ) ) {
        wp_send_json_error( esc_html__( 'Please fill out all required registration fields.', 'awesome-car-rental' ) );
    }
    if ( username_exists( $username ) ) {
        wp_send_json_error( esc_html__( 'This username is already taken.', 'awesome-car-rental' ) );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( esc_html__( 'Please enter a valid email address.', 'awesome-car-rental' ) );
    }
    if ( email_exists( $email ) ) {
        wp_send_json_error( esc_html__( 'This email address is already registered.', 'awesome-car-rental' ) );
    }
    if ( strlen( $password ) < 6 ) {
        wp_send_json_error( esc_html__( 'Password must be at least 6 characters long.', 'awesome-car-rental' ) );
    }
    if ( $password !== $cpassword ) {
        wp_send_json_error( esc_html__( 'The passwords provided do not match.', 'awesome-car-rental' ) );
    }

    $user_id = wp_create_user( $username, $password, $email );

    if ( ! is_wp_error( $user_id ) ) {
        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $full_name,
            'first_name'   => $full_name,
        ) );
        
        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, 'acrb_user_phone', $phone );
        
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
        
        wp_send_json_success( esc_html__( 'Account created successfully! Redirecting...', 'awesome-car-rental' ) );
    } else {
        wp_send_json_error( $user_id->get_error_message() );
    }
}

/**
 * 2. UI SHORTCODE
 */
add_shortcode( 'acrb_register', 'acrb_render_register_shortcode' );

function acrb_render_register_shortcode(): string {
    if ( is_user_logged_in() ) {
        $account_url = esc_url( site_url( '/acrb-account/' ) );
        return '<script>window.location.href=' . wp_json_encode( $account_url ) . ';</script>';
    }

    // Fetch Dynamic Styling & Typography Tokens from Style Customizer
    $primary_color   = sanitize_hex_color( (string) get_option( 'acrb_style_primary_color', '#4f46e5' ) );
    $secondary_color = sanitize_hex_color( (string) get_option( 'acrb_style_secondary_color', '#64748b' ) );
    $text_color      = sanitize_hex_color( (string) get_option( 'acrb_style_text_color', '#0f172a' ) );
    $accent_color    = sanitize_hex_color( (string) get_option( 'acrb_style_accent_color', '#059669' ) );
    $font_slug       = sanitize_text_field( (string) get_option( 'acrb_style_font_family', 'jakarta' ) );

    $font_stacks = array(
        'jakarta'    => "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif",
        'inter'      => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
        'poppins'    => "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif",
        'montserrat' => "'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif",
        'roboto'     => "'Roboto', -apple-system, BlinkMacSystemFont, sans-serif",
    );
    $selected_font = $font_stacks[ $font_slug ] ?? $font_stacks['jakarta'];

    ob_start();
    ?>
    
    <style>
        :root {
            --acrb-reg-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-reg-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-reg-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-reg-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-reg-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-reg-container {
            max-width: 980px;
            margin: 40px auto;
            display: flex;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            font-family: var(--acrb-reg-font);
            color: var(--acrb-reg-text);
            box-sizing: border-box;
        }
        .acrb-reg-container *,
        .acrb-reg-container *::before,
        .acrb-reg-container *::after {
            box-sizing: border-box;
        }

        .acrb-reg-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Side Info Panel */
        .acrb-reg-info {
            flex: 1;
            background: linear-gradient(135deg, var(--acrb-reg-text) 0%, #1e293b 100%);
            padding: 50px 40px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .acrb-reg-info h2 {
            color: #ffffff !important;
            font-size: 28px;
            margin: 0 0 14px 0;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .acrb-reg-info p {
            color: #94a3b8;
            line-height: 1.55;
            font-size: 14px;
            margin: 0 0 24px 0;
        }

        .acrb-info-list {
            list-style: none;
            padding: 0;
            margin: 0 0 32px 0;
        }
        .acrb-info-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            font-size: 13.5px;
            color: #e2e8f0;
            font-weight: 600;
        }
        .acrb-info-list .acrb-reg-icon {
            color: var(--acrb-reg-primary);
        }

        /* Registration Card */
        .acrb-reg-card {
            flex: 1.4;
            padding: 50px 40px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .acrb-reg-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--acrb-reg-text);
            margin: 0 0 4px 0;
            letter-spacing: -0.02em;
        }
        .acrb-reg-subtitle {
            color: var(--acrb-reg-secondary);
            font-size: 13.5px;
            margin: 0 0 24px 0;
        }
        
        .acrb-reg-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .acrb-reg-full {
            grid-column: span 2;
        }
        
        .acrb-reg-field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--acrb-reg-text);
            margin-bottom: 6px;
        }
        .acrb-reg-field input {
            width: 100% !important;
            height: 42px !important;
            padding: 0 14px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            color: var(--acrb-reg-text) !important;
            background: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
            transition: border-color 0.15s ease, background-color 0.15s ease !important;
            font-family: inherit !important;
            line-height: 40px !important;
        }
        .acrb-reg-field input:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-reg-field input:focus {
            background: #ffffff !important;
            border-color: var(--acrb-reg-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
            outline: none !important;
        }
        
        .acrb-reg-btn {
            width: 100%;
            height: 44px;
            background: var(--acrb-reg-primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
            transition: opacity 0.15s ease, transform 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .acrb-reg-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }
        .acrb-reg-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        
        .acrb-reg-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: var(--acrb-reg-secondary);
        }
        .acrb-reg-footer a {
            color: var(--acrb-reg-primary);
            font-weight: 700;
            text-decoration: none;
        }
        .acrb-reg-footer a:hover {
            text-decoration: underline;
        }
        
        .acrb-reg-alert {
            margin-top: 14px;
            padding: 12px 16px;
            border-radius: 8px;
            display: none;
            font-size: 13px;
            text-align: center;
            font-weight: 600;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            display: block;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            display: block;
        }

        @media (max-width: 768px) {
            .acrb-reg-container {
                flex-direction: column;
                margin: 20px;
            }
            .acrb-reg-info {
                padding: 32px 24px;
            }
            .acrb-reg-card {
                padding: 32px 24px;
            }
            .acrb-reg-grid {
                grid-template-columns: 1fr;
            }
            .acrb-reg-full {
                grid-column: span 1;
            }
        }
    </style>

    <div class="acrb-reg-container">
        <!-- Side Info Panel -->
        <div class="acrb-reg-info">
            <h2><?php esc_html_e( 'Start Your Journey.', 'awesome-car-rental' ); ?></h2>
            <p><?php esc_html_e( 'Create a customer account to unlock faster bookings, manage your active rentals, and access exclusive loyalty discounts.', 'awesome-car-rental' ); ?></p>
            <ul class="acrb-info-list">
                <li>
                    <svg class="acrb-reg-icon" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span><?php esc_html_e( 'Instant Reservation Confirmation', 'awesome-car-rental' ); ?></span>
                </li>
                <li>
                    <svg class="acrb-reg-icon" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span><?php esc_html_e( '24/7 Portal & Receipt Management', 'awesome-car-rental' ); ?></span>
                </li>
                <li>
                    <svg class="acrb-reg-icon" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span><?php esc_html_e( 'Exclusive Member Pricing & Perks', 'awesome-car-rental' ); ?></span>
                </li>
            </ul>
            <div style="font-size: 11.5px; color: rgba(255, 255, 255, 0.6); border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 18px;">
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Awesome Car Rental. <?php esc_html_e( 'All rights reserved.', 'awesome-car-rental' ); ?>
            </div>
        </div>

        <!-- Registration Form Card -->
        <div class="acrb-reg-card">
            <h2 class="acrb-reg-title"><?php esc_html_e( 'Create Account', 'awesome-car-rental' ); ?></h2>
            <p class="acrb-reg-subtitle"><?php esc_html_e( 'Enter your personal details to get started.', 'awesome-car-rental' ); ?></p>

            <form id="acrb-register-form">
                <input type="hidden" id="acrb_reg_nonce" value="<?php echo esc_attr( wp_create_nonce( 'acrb-reg-nonce' ) ); ?>">
                
                <div class="acrb-reg-grid">
                    <div class="acrb-reg-field">
                        <label for="acrb_reg_fullname"><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></label>
                        <input type="text" id="acrb_reg_fullname" placeholder="John Doe" required autocomplete="name">
                    </div>
                    <div class="acrb-reg-field">
                        <label for="acrb_reg_phone"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
                        <input type="tel" id="acrb_reg_phone" placeholder="+44 7..." required autocomplete="tel">
                    </div>
                    <div class="acrb-reg-field">
                        <label for="acrb_reg_user"><?php esc_html_e( 'Username', 'awesome-car-rental' ); ?></label>
                        <input type="text" id="acrb_reg_user" placeholder="johndoe12" required autocomplete="username">
                    </div>
                    <div class="acrb-reg-field">
                        <label for="acrb_reg_email"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
                        <input type="email" id="acrb_reg_email" placeholder="john@example.com" required autocomplete="email">
                    </div>
                    <div class="acrb-reg-field">
                        <label for="acrb_reg_pass"><?php esc_html_e( 'Secure Password (Min. 6 chars)', 'awesome-car-rental' ); ?></label>
                        <input type="password" id="acrb_reg_pass" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="acrb-reg-field">
                        <label for="acrb_reg_cpass"><?php esc_html_e( 'Confirm Password', 'awesome-car-rental' ); ?></label>
                        <input type="password" id="acrb_reg_cpass" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="acrb-reg-btn" id="acrb-reg-submit">
                    <svg class="acrb-reg-icon" viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <span><?php esc_html_e( 'Register Now', 'awesome-car-rental' ); ?></span>
                </button>

                <div id="acrb-reg-msg" class="acrb-reg-alert" aria-live="polite"></div>
                
                <div class="acrb-reg-footer">
                    <?php esc_html_e( 'Already have an account?', 'awesome-car-rental' ); ?> 
                    <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Login here', 'awesome-car-rental' ); ?></a>
                </div>
            </form>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#acrb-register-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#acrb-reg-submit');
            const msg = $('#acrb-reg-msg');
            
            const pass = $('#acrb_reg_pass').val();
            const cpass = $('#acrb_reg_cpass').val();

            if (pass.length < 6) {
                msg.removeClass('alert-success').addClass('alert-error').text('<?php echo esc_js( __( 'Password must be at least 6 characters long.', 'awesome-car-rental' ) ); ?>').fadeIn();
                return;
            }

            if (pass !== cpass) {
                msg.removeClass('alert-success').addClass('alert-error').text('<?php echo esc_js( __( 'The passwords provided do not match.', 'awesome-car-rental' ) ); ?>').fadeIn();
                return;
            }
            
            btn.prop('disabled', true).text('<?php echo esc_js( __( 'Creating Account...', 'awesome-car-rental' ) ); ?>');
            msg.hide().removeClass('alert-error alert-success');

            const data = {
                action: 'acrb_ajax_register',
                security: $('#acrb_reg_nonce').val(),
                username: $('#acrb_reg_user').val(),
                email: $('#acrb_reg_email').val(),
                password: pass,
                cpassword: cpass,
                full_name: $('#acrb_reg_fullname').val(),
                phone: $('#acrb_reg_phone').val()
            };

            $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function(res) {
                if (res.success) {
                    msg.addClass('alert-success').text(res.data).fadeIn();
                    setTimeout(() => window.location.href = '<?php echo esc_url( site_url( '/acrb-account/' ) ); ?>', 1800);
                } else {
                    msg.addClass('alert-error').text(res.data).fadeIn();
                    btn.prop('disabled', false).text('<?php echo esc_js( __( 'Register Now', 'awesome-car-rental' ) ); ?>');
                }
            }).fail(function() {
                msg.addClass('alert-error').text('<?php echo esc_js( __( 'Connection error. Please try again.', 'awesome-car-rental' ) ); ?>').fadeIn();
                btn.prop('disabled', false).text('<?php echo esc_js( __( 'Register Now', 'awesome-car-rental' ) ); ?>');
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}