<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [acrb_header_auth] 
 * Displays "Hi, Name", Avatar, and Login/Register buttons
 */
add_shortcode('acrb_header_auth', function() {
    ob_start(); ?>
    <div class="acrb-header-auth">
        <?php if (is_user_logged_in()) : 
            $user = wp_get_current_user();
            $first_name = $user->first_name ?: $user->display_name;
            $avatar_url = get_avatar_url($user->ID, ['size' => 64]);
            $account_url = site_url('/acrb-account/');
        ?>
            <a href="<?php echo esc_url($account_url); ?>" class="acrb-user-wrapper">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php esc_attr_e('User Avatar', 'awesome-car-rental'); ?>" class="acrb-avatar-img">
                <div class="acrb-user-info">
                    <span class="acrb-hi"><?php esc_html_e('Hi,', 'awesome-car-rental'); ?></span>
                    <span class="acrb-user-name"><?php echo esc_html($first_name); ?></span>
                </div>
            </a>

        <?php else : 
            $login_url = site_url('/acrb-login/');
            $reg_url   = site_url('/acrb-registration/');
        ?>
            <div class="acrb-auth-group">
                <a href="<?php echo esc_url($login_url); ?>" class="acrb-h-btn acrb-h-login">
                    <svg class="acrb-h-icon" viewBox="0 0 24 24"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
                    <?php esc_html_e('Login', 'awesome-car-rental'); ?>
                </a>
                <a href="<?php echo esc_url($reg_url); ?>" class="acrb-h-btn acrb-h-reg">
                    <?php esc_html_e('Register', 'awesome-car-rental'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});