<?php
/**
 * [acrb_login] AJAX Login System
 * Optimized for Security, UI, and Internationalization.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. BACKEND LOGIC: AJAX Authenticator
add_action('wp_ajax_nopriv_acrb_ajax_login', 'acrb_handle_ajax_login');

function acrb_handle_ajax_login() {
    // A. Validate & Sanitize Nonce (Check Existence, Unslash, then Sanitize)
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
    
    // We unslash the password but DON'T sanitize to preserve special characters
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $password = wp_unslash( $_POST['password'] ); 

    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true
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
add_shortcode('acrb_login', function() {
    // If logged in, redirect to account page
    if ( is_user_logged_in() ) {
        return '<script>window.location.href="' . esc_url( home_url( '/acrb-account/' ) ) . '";</script>';
    }

    ob_start(); ?>
    
    <style>
        .acrb-auth-page-wrapper { max-width: 900px; margin: 60px auto; display: flex; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .acrb-auth-side-info { flex: 1; background: #1e293b; padding: 50px; color: #fff; display: flex; flex-direction: column; justify-content: center; position: relative; }
        .acrb-auth-card { flex: 1.2; padding: 50px; background: #fff; }
        .acrb-auth-side-info h2 { color: #fff !important; font-size: 32px; margin-bottom: 20px; font-weight: 800; line-height: 1.2; }
        .acrb-auth-side-info p { color: #94a3b8; line-height: 1.6; font-size: 15px; }
        .acrb-auth-title { font-size: 26px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .acrb-auth-subtitle { color: #64748b; font-size: 14px; margin-bottom: 35px; }
        .acrb-login-field { margin-bottom: 20px; }
        .acrb-login-field label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; }
        .acrb-login-field input { width: 100%; padding: 14px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 15px; transition: 0.2s; background: #f8fafc; }
        .acrb-login-field input:focus { background: #fff; border-color: #2563eb; outline: none; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .acrb-login-btn { width: 100%; background: #2563eb; color: #fff; border: none; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 16px; cursor: pointer; margin-top: 10px; transition: 0.3s; }
        .acrb-login-btn:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3); }
        .acrb-login-btn:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }
        .acrb-auth-footer { text-align: center; margin-top: 25px; font-size: 14px; color: #64748b; }
        .acrb-auth-footer a { color: #2563eb; font-weight: 700; text-decoration: none; }
        #acrb-login-status { margin-top: 20px; font-size: 14px; text-align: center; min-height: 24px; font-weight: 500; }
        .status-error { color: #dc2626; padding: 10px; background: #fef2f2; border-radius: 8px; }
        .status-loading { color: #2563eb; }
        .status-success { color: #16a34a; }
        @media (max-width: 768px) {
            .acrb-auth-page-wrapper { flex-direction: column; margin: 20px; }
            .acrb-auth-side-info { padding: 40px 30px; text-align: center; }
            .acrb-auth-card { padding: 40px 30px; }
        }
    </style>

    <div class="acrb-auth-page-wrapper">
        <div class="acrb-auth-side-info">
            <h2><?php esc_html_e( 'Fast. Seamless.', 'awesome-car-rental' ); ?><br><?php esc_html_e( 'Secure.', 'awesome-car-rental' ); ?></h2>
            <p><?php esc_html_e( 'Access your private dashboard to manage your fleet, download receipts, and update your preferences.', 'awesome-car-rental' ); ?></p>
            <div style="margin-top: 40px; display: flex; gap: 15px; align-items: center; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px;">
                <span class="dashicons dashicons-shield" style="color:#60a5fa; font-size: 20px; width: 20px; height: 20px;"></span>
                <span style="font-size: 13px; color: #cbd5e1;"><?php esc_html_e( 'Your data is encrypted and secure.', 'awesome-car-rental' ); ?></span>
            </div>
        </div>

        <div class="acrb-auth-card">
            <h2 class="acrb-auth-title"><?php esc_html_e( 'Welcome Back', 'awesome-car-rental' ); ?></h2>
            <p class="acrb-auth-subtitle"><?php esc_html_e( 'Sign in to continue your journey.', 'awesome-car-rental' ); ?></p>

            <form id="acrb-login-form">
                <input type="hidden" id="acrb_login_nonce" value="<?php echo esc_attr( wp_create_nonce( 'acrb-login-nonce' ) ); ?>">
                
                <div class="acrb-login-field">
                    <label><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
                    <input type="email" id="acrb_login_email" placeholder="name@example.com" required>
                </div>

                <div class="acrb-login-field">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="margin-bottom: 0;"><?php esc_html_e( 'Password', 'awesome-car-rental' ); ?></label>
                        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size: 12px; color: #2563eb; text-decoration: none;">
                            <?php esc_html_e( 'Forgot?', 'awesome-car-rental' ); ?>
                        </a>
                    </div>
                    <input type="password" id="acrb_login_password" placeholder="••••••••" required>
                </div>

                <button type="submit" id="acrb-login-submit" class="acrb-login-btn">
                    <?php esc_html_e( 'Sign In', 'awesome-car-rental' ); ?>
                </button>

                <div id="acrb-login-status"></div>
                
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
            
            statusDiv.innerHTML = '<span class="status-loading">Verifying credentials...</span>';
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
                    statusDiv.innerHTML = '<span class="status-success">Success! Redirecting...</span>';
                    
                    const urlParams = new URLSearchParams(window.location.search);
                    const redirectTo = urlParams.get('redirect_to');
                    
                    // Securely handle redirect via decodeURIComponent
                    window.location.href = redirectTo ? decodeURIComponent(redirectTo) : '<?php echo esc_url( home_url( '/acrb-account/' ) ); ?>';
                } else {
                    statusDiv.innerHTML = '<div class="status-error">' + data.data + '</div>';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="status-error">Connection error. Please try again.</div>';
                submitBtn.disabled = false;
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
});