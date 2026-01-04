<?php
/**
 * Registration Handler for Awesome Car Rental
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. BACKEND LOGIC: AJAX Registration Handler
 */
add_action( 'wp_ajax_nopriv_acrb_ajax_register', 'acrb_handle_ajax_registration' );

function acrb_handle_ajax_registration() {
    // FIX: MissingUnslash and InputNotSanitized for security nonce
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['security'] ) ), 'acrb-reg-nonce' ) ) {
        wp_send_json_error( esc_html__( 'Security check failed.', 'awesome-car-rental' ) );
    }

    // FIX: InputNotValidated, MissingUnslash, and Sanitization for all fields
    $username  = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
    $email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    
    // FIX: Password must be unslashed but NOT sanitized to preserve special characters
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
        .acrb-reg-container { max-width: 900px; margin: 40px auto; display: flex; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; }
        .acrb-reg-info { flex: 1; background: #4f46e5; padding: 50px; color: #fff; display: flex; flex-direction: column; justify-content: center; }
        .acrb-reg-card { flex: 1.5; padding: 50px; background: #fff; }
        .acrb-reg-info h2 { color: #fff; font-size: 32px; margin-bottom: 20px; font-weight: 800; }
        .acrb-reg-info p { color: rgba(255,255,255,0.8); line-height: 1.6; }
        .acrb-info-list { list-style: none; padding: 0; margin: 30px 0; }
        .acrb-info-list li { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-size: 14px; }
        .acrb-info-list .dashicons { color: #60a5fa; }
        .acrb-reg-title { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 5px; }
        .acrb-reg-subtitle { color: #64748b; font-size: 14px; margin-bottom: 30px; }
        .acrb-reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .acrb-reg-full { grid-column: span 2; }
        .acrb-reg-field label { display: block; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 8px; }
        .acrb-reg-field input, .acrb-reg-field textarea { width: 100%; padding: 12px 15px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: 0.2s; }
        .acrb-reg-field input:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .acrb-reg-btn { width: 100%; background: #4f46e5; color: #fff; border: none; padding: 15px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; margin-top: 25px; transition: 0.3s; }
        .acrb-reg-btn:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(37,99,235,0.2); }
        .acrb-reg-footer { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
        .acrb-reg-footer a { color: #4f46e5; font-weight: 700; text-decoration: none; }
        .acrb-reg-alert { margin-top: 15px; padding: 12px; border-radius: 8px; display: none; font-size: 14px; text-align: center; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; display: block; }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; display: block; }
        @media (max-width: 768px) {
            .acrb-reg-container { flex-direction: column; margin: 20px; }
            .acrb-reg-info { padding: 30px; }
            .acrb-reg-card { padding: 30px; }
            .acrb-reg-grid { grid-template-columns: 1fr; }
            .acrb-reg-full { grid-column: span 1; }
        }
    </style>

    <div class="acrb-reg-container">
        <div class="acrb-reg-info">
            <h2>Start Your Journey.</h2>
            <p>Create an account to unlock faster bookings, manage your rentals, and access exclusive member pricing.</p>
            <ul class="acrb-info-list">
                <li><span class="dashicons dashicons-yes-alt"></span> Instant Booking Confirmation</li>
                <li><span class="dashicons dashicons-yes-alt"></span> Manage Reservations 24/7</li>
                <li><span class="dashicons dashicons-yes-alt"></span> Exclusive Loyalty Discounts</li>
            </ul>
            <div style="margin-top: auto; font-size: 12px; color: rgba(255,255,255,0.6);">
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Awesome Car Rental. All rights reserved.
            </div>
        </div>

        <div class="acrb-reg-card">
            <h2 class="acrb-reg-title">Create Account</h2>
            <p class="acrb-reg-subtitle">Enter your details to get started.</p>

            <form id="acrb-register-form">
                <input type="hidden" id="acrb_reg_nonce" value="<?php echo esc_attr( wp_create_nonce( 'acrb-reg-nonce' ) ); ?>">
                
                <div class="acrb-reg-grid">
                    <div class="acrb-reg-field">
                        <label>Full Name</label>
                        <input type="text" id="acrb_reg_fullname" placeholder="John Doe" required>
                    </div>
                    <div class="acrb-reg-field">
                        <label>Phone Number</label>
                        <input type="tel" id="acrb_reg_phone" placeholder="+44 7..." required>
                    </div>
                    <div class="acrb-reg-field">
                        <label>Username</label>
                        <input type="text" id="acrb_reg_user" placeholder="johndoe12" required>
                    </div>
                    <div class="acrb-reg-field">
                        <label>Email Address</label>
                        <input type="email" id="acrb_reg_email" placeholder="john@example.com" required>
                    </div>
                    <div class="acrb-reg-field acrb-reg-full">
                        <label>Delivery Address</label>
                        <textarea id="acrb_reg_address" rows="2" placeholder="Street, City, Postcode" required></textarea>
                    </div>
                    <div class="acrb-reg-field acrb-reg-full">
                        <label>Secure Password</label>
                        <input type="password" id="acrb_reg_pass" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="acrb-reg-btn" id="acrb-reg-submit">Register Now</button>
                <div id="acrb-reg-msg" class="acrb-reg-alert"></div>
                
                <div class="acrb-reg-footer">
                    Already have an account? <a href="<?php echo esc_url( wp_login_url() ); ?>">Login here</a>
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
            
            btn.prop('disabled', true).text('Creating Account...');
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
                    btn.prop('disabled', false).text('Register Now');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
} );