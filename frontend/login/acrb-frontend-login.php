<?php
/**
 * [acrb_login] AJAX Login System (Elite SaaS Modernized)
 * Optimized for Security, UI, and Internationalization with Style Customizer Integration.
 * Location: templates/shortcodes/login.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.3.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. BACKEND LOGIC: AJAX Authenticator
add_action( 'wp_ajax_nopriv_acrb_ajax_login', 'acrb_handle_ajax_login' );

function acrb_handle_ajax_login(): void {
    // A. Validate & Sanitize Nonce
    $nonce = isset( $_POST['security'] ) ? sanitize_key( wp_unslash( $_POST['security'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'acrb-login-nonce' ) ) {
        wp_send_json_error( esc_html__( 'Security check failed. Please refresh the page.', 'awesome-car-rental' ) );
    }

    // B. Validate existence of username and password
    if ( ! isset( $_POST['username'] ) || ! isset( $_POST['password'] ) ) {
        wp_send_json_error( esc_html__( 'Required fields are missing.', 'awesome-car-rental' ) );
    }

    // C. Unslash and Sanitize Inputs
    $username = sanitize_user( wp_unslash( $_POST['username'] ) );
    
    // Unslash password without sanitization to preserve special characters
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $password = wp_unslash( $_POST['password'] ); 

    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true,
    );

    // D. Attempt Sign-on
    $user = wp_signon( $creds, is_ssl() );

    if ( is_wp_error( $user ) ) {
        wp_send_json_error( esc_html__( 'Invalid email or password.', 'awesome-car-rental' ) );
    } else {
        wp_send_json_success();
    }
}

// 2. UI SHORTCODE
add_shortcode( 'acrb_login', 'acrb_render_login_shortcode' );

function acrb_render_login_shortcode(): string {
    // If logged in, redirect to account page
    if ( is_user_logged_in() ) {
        $account_url = esc_url( home_url( '/acrb-account/' ) );
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
            --acrb-login-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-login-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-login-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-login-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-login-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-auth-page-wrapper {
            max-width: 980px;
            margin: 40px auto;
            display: flex;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            font-family: var(--acrb-login-font);
            color: var(--acrb-login-text);
            box-sizing: border-box;
        }
        .acrb-auth-page-wrapper *,
        .acrb-auth-page-wrapper *::before,
        .acrb-auth-page-wrapper *::after {
            box-sizing: border-box;
        }

        /* SVG Icon Standards */
        .acrb-login-icon {
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

        /* Side Info Banner */
        .acrb-auth-side-info {
            flex: 1;
            background: linear-gradient(135deg, var(--acrb-login-text) 0%, #1e293b 100%);
            padding: 50px 40px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        .acrb-auth-side-info h2 {
            color: #ffffff !important;
            font-size: 28px;
            margin: 0 0 14px 0;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .acrb-auth-side-info p {
            color: #94a3b8;
            line-height: 1.55;
            font-size: 14px;
            margin: 0;
        }

        /* Form Card */
        .acrb-auth-card {
            flex: 1.2;
            padding: 50px 40px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .acrb-auth-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--acrb-login-text);
            margin: 0 0 4px 0;
            letter-spacing: -0.02em;
        }
        .acrb-auth-subtitle {
            color: var(--acrb-login-secondary);
            font-size: 13.5px;
            margin: 0 0 28px 0;
        }
        
        .acrb-login-field {
            margin-bottom: 20px;
        }
        .acrb-login-field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--acrb-login-text);
            margin-bottom: 6px;
        }
        .acrb-login-field input {
            width: 100% !important;
            height: 42px !important;
            padding: 0 14px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            color: var(--acrb-login-text) !important;
            background: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
            transition: border-color 0.15s ease, background-color 0.15s ease !important;
            font-family: inherit !important;
        }
        .acrb-login-field input:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-login-field input:focus {
            background: #ffffff !important;
            border-color: var(--acrb-login-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
            outline: none !important;
        }
        
        .acrb-login-btn {
            width: 100%;
            height: 44px;
            background: var(--acrb-login-primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            margin-top: 6px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
            transition: opacity 0.15s ease, transform 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .acrb-login-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }
        .acrb-login-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        
        .acrb-auth-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--acrb-login-secondary);
        }
        .acrb-auth-footer a {
            color: var(--acrb-login-primary);
            font-weight: 700;
            text-decoration: none;
        }
        .acrb-auth-footer a:hover {
            text-decoration: underline;
        }

        #acrb-login-status {
            margin-top: 14px;
            font-size: 13px;
            text-align: center;
            min-height: 22px;
            font-weight: 600;
        }
        .status-error {
            color: #991b1b;
            padding: 10px 14px;
            background: #fef2f2;
            border-radius: 8px;
            border: 1px solid #fecaca;
        }
        .status-loading {
            color: var(--acrb-login-primary);
            font-weight: 600;
        }
        .status-success {
            color: var(--acrb-login-accent);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .acrb-auth-page-wrapper {
                flex-direction: column;
                margin: 20px;
            }
            .acrb-auth-side-info {
                padding: 32px 24px;
            }
            .acrb-auth-card {
                padding: 32px 24px;
            }
        }
    </style>

    <div class="acrb-auth-page-wrapper">
        <!-- Side Info Panel -->
        <div class="acrb-auth-side-info">
            <h2><?php esc_html_e( 'Fast. Seamless.', 'awesome-car-rental' ); ?><br><?php esc_html_e( 'Secure.', 'awesome-car-rental' ); ?></h2>
            <p><?php esc_html_e( 'Access your private dashboard to manage your fleet reservations, download settled receipts, and update your account preferences.', 'awesome-car-rental' ); ?></p>
            <div style="margin-top: 28px; display: flex; gap: 12px; align-items: center; background: rgba(255, 255, 255, 0.06); padding: 14px 16px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.08);">
                <svg class="acrb-login-icon" style="color: #60a5fa; width: 22px; height: 22px;" viewBox="0 0 24 24">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span style="font-size: 12.5px; color: #cbd5e1; font-weight: 500;"><?php esc_html_e( 'Your reservation data is encrypted and secure.', 'awesome-car-rental' ); ?></span>
            </div>
        </div>

        <!-- Auth Form Card -->
        <div class="acrb-auth-card">
            <h2 class="acrb-auth-title"><?php esc_html_e( 'Welcome Back', 'awesome-car-rental' ); ?></h2>
            <p class="acrb-auth-subtitle"><?php esc_html_e( 'Sign in to continue your journey.', 'awesome-car-rental' ); ?></p>

            <form id="acrb-login-form">
                <input type="hidden" id="acrb_login_nonce" value="<?php echo esc_attr( wp_create_nonce( 'acrb-login-nonce' ) ); ?>">
                
                <div class="acrb-login-field">
                    <label for="acrb_login_email"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
                    <input type="email" id="acrb_login_email" placeholder="name@example.com" required autocomplete="email">
                </div>

                <div class="acrb-login-field" style="margin-bottom: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label for="acrb_login_password" style="margin-bottom: 0;"><?php esc_html_e( 'Password', 'awesome-car-rental' ); ?></label>
                        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size: 12px; color: var(--acrb-login-primary); font-weight: 700; text-decoration: none;">
                            <?php esc_html_e( 'Forgot password?', 'awesome-car-rental' ); ?>
                        </a>
                    </div>
                    <input type="password" id="acrb_login_password" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" id="acrb-login-submit" class="acrb-login-btn">
                    <svg class="acrb-login-icon" viewBox="0 0 24 24">
                        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span><?php esc_html_e( 'Sign In', 'awesome-car-rental' ); ?></span>
                </button>

                <div id="acrb-login-status" aria-live="polite"></div>
                
                <div class="acrb-auth-footer">
                    <?php esc_html_e( "Don't have an account?", 'awesome-car-rental' ); ?> 
                    <a href="<?php echo esc_url( home_url( '/acrb-registration/' ) ); ?>"><?php esc_html_e( 'Join Now', 'awesome-car-rental' ); ?></a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('acrb-login-form');
        const statusDiv = document.getElementById('acrb-login-status');
        const submitBtn = document.getElementById('acrb-login-submit');

        if (!loginForm) return;

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            statusDiv.innerHTML = '<span class="status-loading"><?php echo esc_js( __( 'Verifying credentials...', 'awesome-car-rental' ) ); ?></span>';
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'acrb_ajax_login');
            formData.append('username', document.getElementById('acrb_login_email').value);
            formData.append('password', document.getElementById('acrb_login_password').value);
            formData.append('security', document.getElementById('acrb_login_nonce').value);

            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = '<span class="status-success"><?php echo esc_js( __( 'Success! Redirecting...', 'awesome-car-rental' ) ); ?></span>';
                    
                    const urlParams = new URLSearchParams(window.location.search);
                    const redirectTo = urlParams.get('redirect_to');
                    
                    window.location.href = redirectTo ? decodeURIComponent(redirectTo) : '<?php echo esc_url( home_url( '/acrb-account/' ) ); ?>';
                } else {
                    statusDiv.innerHTML = '<div class="status-error">' + (data.data || '<?php echo esc_js( __( 'Login failed.', 'awesome-car-rental' ) ); ?>') + '</div>';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="status-error"><?php echo esc_js( __( 'Connection error. Please try again.', 'awesome-car-rental' ) ); ?></div>';
                submitBtn.disabled = false;
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}