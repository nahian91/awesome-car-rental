<?php
/**
 * [acrb_header_auth] 
 * Ultra-Modern Elite SaaS Header Auth Widget
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode('acrb_header_auth', function() {
    ob_start(); ?>
    
    <style>
        /* Modern SaaS Header Auth Theme */
        .acrb-header-auth { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: inline-flex; align-items: center; box-sizing: border-box; }
        .acrb-header-auth *, .acrb-header-auth *::before, .acrb-header-auth *::after { box-sizing: border-box; }

        /* Logged In User State */
        .acrb-user-wrapper { display: flex; align-items: center; gap: 12px; text-decoration: none; padding: 6px 14px 6px 6px; border-radius: 9999px; background: #f8fafc; border: 1px solid #e2e8f0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 2px 5px rgba(15, 23, 42, 0.02); }
        .acrb-user-wrapper:hover { background: #fff; border-color: #cbd5e1; box-shadow: 0 8px 20px -4px rgba(59, 130, 246, 0.12); text-decoration: none; transform: translateY(-1px); }
        
        .acrb-avatar-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        
        .acrb-user-info { display: flex; flex-direction: column; line-height: 1.25; text-align: left; }
        .acrb-hi { font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
        .acrb-user-name { font-size: 13.5px; font-weight: 800; color: #0f172a; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; letter-spacing: -0.2px; }

        /* Guest Auth Group */
        .acrb-auth-group { display: flex; align-items: center; gap: 10px; }
        
        .acrb-h-btn { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 13px; font-weight: 700; padding: 0 20px; border-radius: 10px; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); height: 42px; box-sizing: border-box; }
        
        .acrb-h-login { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; box-shadow: 0 2px 5px rgba(15, 23, 42, 0.02); }
        .acrb-h-login:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; text-decoration: none; transform: translateY(-1px); }
        
        .acrb-h-reg { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; border: 1px solid transparent; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); }
        .acrb-h-reg:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; text-decoration: none; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); }

        .acrb-h-icon { width: 16px; height: 16px; fill: currentColor; opacity: 0.8; }
    </style>

    <div class="acrb-header-auth">
        <?php if (is_user_logged_in()) : 
            $user = wp_get_current_user();
            $first_name = $user->first_name ?: $user->display_name;
            $avatar_url = get_avatar_url($user->ID, ['size' => 80]);
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
                    <span><?php esc_html_e('Login', 'awesome-car-rental'); ?></span>
                </a>
                <a href="<?php echo esc_url($reg_url); ?>" class="acrb-h-btn acrb-h-reg">
                    <span><?php esc_html_e('Register', 'awesome-car-rental'); ?></span>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});