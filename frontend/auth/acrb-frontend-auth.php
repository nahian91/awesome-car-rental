<?php
/**
 * [acrb_header_auth] 
 * Ultra-Modern Elite SaaS Header Auth Widget
 * Location: templates/shortcodes/header-auth.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.3.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'acrb_header_auth', 'acrb_render_header_auth_widget_shortcode' );

function acrb_render_header_auth_widget_shortcode(): string {
    // 1. Fetch Dynamic Styling & Typography Tokens from Style Customizer
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
            --acrb-auth-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-auth-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-auth-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-auth-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-auth-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-header-auth {
            font-family: var(--acrb-auth-font);
            display: inline-flex;
            align-items: center;
            box-sizing: border-box;
            color: var(--acrb-auth-text);
        }
        .acrb-header-auth *,
        .acrb-header-auth *::before,
        .acrb-header-auth *::after {
            box-sizing: border-box;
        }

        /* Inline SVG Uniform Standards */
        .acrb-auth-svg {
            display: inline-block;
            width: 16px;
            height: 16px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Logged In User Pill */
        .acrb-user-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none !important;
            padding: 4px 14px 4px 5px;
            border-radius: 9999px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }
        .acrb-user-wrapper:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .acrb-avatar-frame {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 50%;
            overflow: hidden;
            background: #f1f5f9;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .acrb-avatar-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .acrb-user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }
        .acrb-user-greeting {
            font-size: 10px;
            color: var(--acrb-auth-secondary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .acrb-user-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--acrb-auth-text);
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .acrb-user-chevron {
            color: var(--acrb-auth-secondary);
            margin-left: 2px;
            transition: transform 0.15s ease;
        }
        .acrb-user-wrapper:hover .acrb-user-chevron {
            transform: translateX(2px);
            color: var(--acrb-auth-primary);
        }

        /* Guest Auth Group */
        .acrb-auth-group {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .acrb-h-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: 700;
            padding: 0 16px;
            border-radius: 8px;
            height: 38px;
            transition: all 0.15s ease;
            box-sizing: border-box;
            white-space: nowrap;
        }

        /* Login Button (Muted Clean Border) */
        .acrb-h-login {
            background: #ffffff;
            color: var(--acrb-auth-text) !important;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .acrb-h-login .acrb-auth-svg {
            color: var(--acrb-auth-secondary);
            transition: color 0.15s ease;
        }
        .acrb-h-login:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: var(--acrb-auth-text) !important;
            transform: translateY(-1px);
        }
        .acrb-h-login:hover .acrb-auth-svg {
            color: var(--acrb-auth-primary);
        }

        /* Register Button (Theme Primary Fill) */
        .acrb-h-reg {
            background: var(--acrb-auth-primary);
            color: #ffffff !important;
            border: 1px solid var(--acrb-auth-primary);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.12);
        }
        .acrb-h-reg .acrb-auth-svg {
            color: #ffffff;
        }
        .acrb-h-reg:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.16);
            color: #ffffff !important;
        }
    </style>

    <div class="acrb-header-auth">
        <?php if ( is_user_logged_in() ) : 
            $user        = wp_get_current_user();
            $first_name  = $user->first_name ?: $user->display_name;
            $avatar_url  = get_avatar_url( $user->ID, array( 'size' => 68 ) );
            $account_url = site_url( '/acrb-account/' );
        ?>
            <a href="<?php echo esc_url( $account_url ); ?>" class="acrb-user-wrapper" title="<?php esc_attr_e( 'View Customer Portal', 'awesome-car-rental' ); ?>">
                <div class="acrb-avatar-frame">
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php esc_attr_e( 'Profile Photo', 'awesome-car-rental' ); ?>">
                </div>
                <div class="acrb-user-info">
                    <span class="acrb-user-greeting"><?php esc_html_e( 'Hi,', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-user-name"><?php echo esc_html( $first_name ); ?></span>
                </div>
                <svg class="acrb-auth-svg acrb-user-chevron" viewBox="0 0 24 24">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>

        <?php else : 
            $login_url = site_url( '/acrb-login/' );
            $reg_url   = site_url( '/acrb-registration/' );
        ?>
            <div class="acrb-auth-group">
                <a href="<?php echo esc_url( $login_url ); ?>" class="acrb-h-btn acrb-h-login">
                    <svg class="acrb-auth-svg" viewBox="0 0 24 24">
                        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span><?php esc_html_e( 'Sign In', 'awesome-car-rental' ); ?></span>
                </a>

                <a href="<?php echo esc_url( $reg_url ); ?>" class="acrb-h-btn acrb-h-reg">
                    <svg class="acrb-auth-svg" viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <span><?php esc_html_e( 'Register', 'awesome-car-rental' ); ?></span>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}