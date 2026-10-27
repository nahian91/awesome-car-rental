<?php
/**
 * Registration Handler for Awesome Car Rental (Elite SaaS Modernized)
 * Location: shortcodes/register.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. BACKEND LOGIC: AJAX Registration Handler
 */
add_action( 'wp_ajax_nopriv_acrb_ajax_register', 'acrb_handle_ajax_registration' );

function acrb_handle_ajax_registration() {
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['security'] ) ), 'acrb-reg-nonce' ) ) {
        wp_send_json_error( esc_html__( 'Security check failed.', 'awesome-car-rental' ) );
    }

    $username  = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
    $email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    
    // Password must be unslashed but NOT sanitized to preserve special characters
    $password  = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    
    $full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
    $phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $address   = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

    if ( username_exists( $username ) ) {
        wp_send_json_error( esc_html__( 'Username already taken.', 'awesome-car-rental' ) );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( esc_html__( 'Please enter a valid email.', 'awesome-car-rental' ) );
    }
    if ( email_exists( $email ) ) {
        wp_send_json_error( esc_html__( 'This email is already registered.', 'awesome-car-rental' ) );
    }
    if ( strlen( $password ) < 6 ) {
        wp_send_json_error( esc_html__( 'Password must be at least 6 characters.', 'awesome-car-rental' ) );
    }

    $user_id = wp_create_user( $username, $password, $email );

    if ( ! is_wp_error( $user_id ) ) {
        wp_update_user( [
            'ID'           => $user_id,
            'display_name' => $full_name,
            'first_name'   => $full_name
        ] );
        
        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, 'billing_address_1', $address );
        update_user_meta( $user_id, 'acrb_user_phone', $phone );
        update_user_meta( $user_id, 'acrb_user_address', $address );
        
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
        
        wp_send_json_success( esc_html__( 'Account created! Redirecting...', 'awesome-car-rental' ) );
    } else {
        wp_send_json_error( $user_id->get_error_message() );
    }
}

/**
 * 2. UI SHORTCODE
 */
add_shortcode( 'acrb_register', function() {
    if ( is_user_logged_in() ) {
        return '<script>window.location.href="' . esc_url( site_url( '/acrb-account/' ) ) . '";</script>';
    }

    ob_start(); ?>
    
    <style>
        .acrb-reg-container { max-width: 960px; margin: 40px auto; display: flex; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; box-sizing: border-box; }
        .acrb-reg-container *, .acrb-reg-container *::before, .acrb-reg-container *::after { box-sizing: border-box; }

        .acrb-reg-info { flex: 1; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 50px; color: #fff; display: flex; flex-direction: column; justify-content: center; }
        .acrb-reg-card { flex: 1.5; padding: 50px; background: #fff; display: flex; flex-direction: column; justify-content: center; }
        
        .acrb-reg-info h2 { color: #fff !important; font-size: 28px; margin: 0 0 16px 0; font-weight: 800; line-height: 1.25; letter-spacing: -0.5px; }
        .acrb-reg-info p { color: #94a3b8; line-height: 1.6; font-size: 14px; margin: 0 0 24px 0; }
        
        .acrb-info-list { list-style: none; padding: 0; margin: 0 0 32px 0; }
        .acrb-info-list li { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; font-size: 14px; color: #e2e8f0; font-weight: 500; }
        .acrb-info-list .dashicons { color: #3b82f6; font-size: 18px; width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-reg-title { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; letter-spacing: -0.5px; }
        .acrb-reg-subtitle { color: #64748b; font-size: 14px; margin: 0 0 28px 0; }
        
        .acrb-reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .acrb-reg-full { grid-column: span 2; }
        
        .acrb-reg-field label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .acrb-reg-field input, .acrb-reg-field textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-reg-field input { height: 44px; padding: 0 14px; }
        .acrb-reg-field textarea { padding: 12px 14px; height: auto; resize: vertical; }
        .acrb-reg-field input:hover, .acrb-reg-field textarea:hover { border-color: #94a3b8; }
        .acrb-reg-field input:focus, .acrb-reg-field textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }
        
        .acrb-reg-btn { width: 100%; height: 46px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; margin-top: 24px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-reg-btn:hover { background: #2563eb; }
        .acrb-reg-btn:disabled { background: #94a3b8; cursor: not-allowed; box-shadow: none; }
        
        .acrb-reg-footer { text-align: center; margin-top: 24px; font-size: 13px; color: #64748b; }
        .acrb-reg-footer a { color: #3b82f6; font-weight: 700; text-decoration: none; }
        .acrb-reg-footer a:hover { text-decoration: underline; }
        
        .acrb-reg-alert { margin-top: 16px; padding: 12px 14px; border-radius: 8px; display: none; font-size: 13px; text-align: center; font-weight: 500; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; display: block; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; display: block; }

        @media (max-width: 768px) {
            .acrb-reg-container { flex-direction: column; margin: 20px; }
            .acrb-reg-info { padding: 40px 24px; }
            .acrb-reg-card { padding: 40px 24px; }
            .acrb-reg-grid { grid-template-columns: 1fr; }
            .acrb-reg-full { grid-column: span 1; }
        }
    </style>

    <div class="acrb-reg-container">
        <div class="acrb-reg-info">
            <h2><?php esc_html_e( 'Start Your Journey.', 'awesome-car-rental' ); ?></h2>
            <p><?php esc_html_e( 'Create an account to unlock faster bookings, manage your rentals, and access exclusive member pricing.', 'awesome-car-rental' ); ?></p>
            <ul class="acrb-info-list">
                <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Instant Booking Confirmation', 'awesome-car-rental' ); ?></li>
                <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Manage Reservations 24/7', 'awesome-car-rental' ); ?></li>
                <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Exclusive Loyalty Discounts', 'awesome-car-rental' ); ?></li>
            </ul>
            <div style="font-size: 12px; color: rgba(255,255,255,0.6); border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px;">
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Awesome Car Rental. <?php esc_html_e( 'All rights reserved.', 'awesome-car-rental' ); ?>
            </div>
        </div>

        <div class="acrb-reg-card">
            <h2 class="acrb-reg-title"><?php esc_html_e( 'Create Account', 'awesome-car-rental' ); ?></h2>
            <p class="acrb-reg-subtitle"><?php esc_html_e( 'Enter your details to get started.', 'awesome-car-rental' ); ?></p>

            <form id="acrb-register-form">
                <input type="hidden" id="acrb_reg_nonce" value="<?php echo esc_attr( wp_create_nonce( 'acrb-reg-nonce' ) ); ?>">
                
                <div class="acrb-reg-grid">
                    <div class="acrb-reg-field">
                        <label><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></label>
                        <input type="text" id="acrb_reg_fullname" placeholder="John Doe" required>
                    </div>
                    <div class="acrb-reg-field">
                        <label><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
                        <input type="tel" id="acrb_reg_phone" placeholder="+44 7..." required>
                    </div>
                    <div class="acrb-reg-field">
                        <label><?php esc_html_e( 'Username', 'awesome-car-rental' ); ?></label>
                        <input type="text" id="acrb_reg_user" placeholder="johndoe12" required>
                    </div>
                    <div class="acrb-reg-field">
                        <label><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
                        <input type="email" id="acrb_reg_email" placeholder="john@example.com" required>
                    </div>
                    <div class="acrb-reg-field acrb-reg-full">
                        <label><?php esc_html_e( 'Delivery Address', 'awesome-car-rental' ); ?></label>
                        <textarea id="acrb_reg_address" rows="2" placeholder="Street, City, Postcode" required></textarea>
                    </div>
                    <div class="acrb-reg-field acrb-reg-full">
                        <label><?php esc_html_e( 'Secure Password', 'awesome-car-rental' ); ?></label>
                        <input type="password" id="acrb_reg_pass" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="acrb-reg-btn" id="acrb-reg-submit"><?php esc_html_e( 'Register Now', 'awesome-car-rental' ); ?></button>
                <div id="acrb-reg-msg" class="acrb-reg-alert"></div>
                
                <div class="acrb-reg-footer">
                    <?php esc_html_e( 'Already have an account?', 'awesome-car-rental' ); ?> <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Login here', 'awesome-car-rental' ); ?></a>
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
            
            btn.prop('disabled', true).text('<?php echo esc_js( __('Creating Account...', 'awesome-car-rental') ); ?>');
            msg.hide().removeClass('alert-error alert-success');

            const data = {
                action: 'acrb_ajax_register',
                security: $('#acrb_reg_nonce').val(),
                username: $('#acrb_reg_user').val(),
                email: $('#acrb_reg_email').val(),
                password: $('#acrb_reg_pass').val(),
                full_name: $('#acrb_reg_fullname').val(),
                phone: $('#acrb_reg_phone').val(),
                address: $('#acrb_reg_address').val()
            };

            $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function(res) {
                if (res.success) {
                    msg.addClass('alert-success').text(res.data).fadeIn();
                    setTimeout(() => window.location.href = '<?php echo esc_url( site_url( '/acrb-account/' ) ); ?>', 2000);
                } else {
                    msg.addClass('alert-error').text(res.data).fadeIn();
                    btn.prop('disabled', false).text('<?php echo esc_js( __('Register Now', 'awesome-car-rental') ); ?>');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
} );