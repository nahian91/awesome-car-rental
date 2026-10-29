<?php
/**
 * Style & Branding Customizer Module (`style.php`)
 * Essential 5 styling controls with 5 premium Google Fonts and automated frontend injection.
 *
 * @package Awesome_Car_Rental
 * @since   3.1.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*--------------------------------------------------------------
# 1. Frontend Google Font Enqueue & CSS Variable Injection
--------------------------------------------------------------*/
add_action( 'wp_enqueue_scripts', 'acrb_enqueue_frontend_custom_fonts', 20 );

function acrb_enqueue_frontend_custom_fonts(): void {
    if ( is_admin() ) {
        return;
    }

    $font_family = sanitize_text_field( (string) get_option( 'acrb_style_font_family', 'jakarta' ) );

    $font_urls = array(
        'jakarta'    => 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
        'inter'      => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        'poppins'    => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap',
        'montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap',
        'roboto'     => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap',
    );

    if ( isset( $font_urls[ $font_family ] ) ) {
        wp_enqueue_style( 'acrb-custom-google-font', $font_urls[ $font_family ], array(), null );
    }
}

add_action( 'wp_head', 'acrb_inject_frontend_custom_styles', 99 );

function acrb_inject_frontend_custom_styles(): void {
    if ( is_admin() ) {
        return;
    }

    $primary_color   = sanitize_hex_color( (string) get_option( 'acrb_style_primary_color', '#4f46e5' ) );
    $secondary_color = sanitize_hex_color( (string) get_option( 'acrb_style_secondary_color', '#64748b' ) );
    $text_color      = sanitize_hex_color( (string) get_option( 'acrb_style_text_color', '#0f172a' ) );
    $accent_color    = sanitize_hex_color( (string) get_option( 'acrb_style_accent_color', '#059669' ) );
    $font_family     = sanitize_text_field( (string) get_option( 'acrb_style_font_family', 'jakarta' ) );

    $font_stacks = array(
        'jakarta'    => "'Plus Jakarta Sans', sans-serif",
        'inter'      => "'Inter', sans-serif",
        'poppins'    => "'Poppins', sans-serif",
        'montserrat' => "'Montserrat', sans-serif",
        'roboto'     => "'Roboto', sans-serif",
    );

    $selected_font = $font_stacks[ $font_family ] ?? $font_stacks['jakarta'];
    ?>
    <style id="acrb-dynamic-custom-styles">
        :root {
            --acrb-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-font-family: <?php echo esc_html( $selected_font ); ?>;
        }

        .awesome-car-rental,
        .acrb-car-card,
        .acrb-booking-widget,
        .acrb-calendar-wrap {
            font-family: var(--acrb-font-family) !important;
            color: var(--acrb-text) !important;
        }

        .acrb-btn-primary,
        .acrb-btn-book {
            background-color: var(--acrb-primary) !important;
            border-color: var(--acrb-primary) !important;
            color: #ffffff !important;
        }

        .acrb-btn-secondary {
            background-color: var(--acrb-secondary) !important;
            border-color: var(--acrb-secondary) !important;
            color: #ffffff !important;
        }

        .acrb-badge-accent,
        .acrb-available-indicator {
            color: var(--acrb-accent) !important;
        }
    </style>
    <?php
}

/*--------------------------------------------------------------
# 2. Admin Interface Controller & UI
--------------------------------------------------------------*/
function acrb_style_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    $page_slug = 'awesome_car_rental';
    $base_url  = admin_url( 'admin.php?page=' . $page_slug . '&tab=style' );

    // Handle Form Save & Safe Redirect
    if ( isset( $_POST['acrb_style_submit'] ) && check_admin_referer( 'acrb_save_style_settings', 'acrb_style_nonce' ) ) {
        update_option( 'acrb_style_primary_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_primary_color'] ?? '#4f46e5' ) ) );
        update_option( 'acrb_style_secondary_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_secondary_color'] ?? '#64748b' ) ) );
        update_option( 'acrb_style_text_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_text_color'] ?? '#0f172a' ) ) );
        update_option( 'acrb_style_accent_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_accent_color'] ?? '#059669' ) ) );
        update_option( 'acrb_style_font_family', sanitize_text_field( wp_unslash( $_POST['acrb_style_font_family'] ?? 'jakarta' ) ) );

        $redirect_url = add_query_arg( 'updated', '1', $base_url );

        if ( headers_sent() ) {
            echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
            exit;
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    // Retrieve Stored Values
    $primary_color   = (string) get_option( 'acrb_style_primary_color', '#4f46e5' );
    $secondary_color = (string) get_option( 'acrb_style_secondary_color', '#64748b' );
    $text_color      = (string) get_option( 'acrb_style_text_color', '#0f172a' );
    $accent_color    = (string) get_option( 'acrb_style_accent_color', '#059669' );
    $font_family     = (string) get_option( 'acrb_style_font_family', 'jakarta' );
    ?>

    <style>
        .acrb-style-container {
            width: 100%;
            max-width: 1100px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-style-container *,
        .acrb-style-container *::before,
        .acrb-style-container *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-style-topbar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .acrb-style-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-style-topbar-icon {
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
        .acrb-style-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-style-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-style-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Success Alert */
        .acrb-style-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Two-Column Grid */
        .acrb-style-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }

        /* Cards */
        .acrb-style-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            padding: 24px;
            margin-bottom: 22px;
        }
        .acrb-style-card-title {
            margin: 0 0 18px;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }
        .acrb-style-card-title .dashicons {
            color: #4f46e5;
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .acrb-style-color-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .acrb-style-form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 18px;
        }
        .acrb-style-form-group:last-child {
            margin-bottom: 0;
        }
        .acrb-style-label {
            display: block;
            font-weight: 700;
            color: #1e293b;
            font-size: 13.5px;
            margin-bottom: 6px;
        }

        /* Color Controls */
        .acrb-style-color-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .acrb-style-color-picker {
            width: 44px;
            height: 42px;
            min-width: 44px;
            padding: 2px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            cursor: pointer;
            box-sizing: border-box;
            transition: border-color 0.15s ease;
        }
        .acrb-style-color-picker:hover {
            border-color: #94a3b8;
        }
        .acrb-style-input,
        .acrb-style-select {
            display: block;
            width: 100% !important;
            max-width: 100% !important;
            height: 42px !important;
            background: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
            font-size: 13.5px !important;
            font-family: inherit !important;
            color: #1e293b !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
            line-height: 40px !important;
        }
        .acrb-style-input-hex {
            text-transform: uppercase !important;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-weight: 700 !important;
            letter-spacing: 0.03em !important;
        }
        .acrb-style-input:hover,
        .acrb-style-select:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-style-input:focus,
        .acrb-style-select:focus {
            background: #ffffff !important;
            border-color: #4f46e5 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        }

        /* Select Dropdown Wrap */
        .acrb-style-select-wrap {
            position: relative;
            width: 100%;
        }
        .acrb-style-select-wrap select {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 36px !important;
            cursor: pointer;
        }
        .acrb-style-select-arrow {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #64748b;
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        .acrb-style-hint {
            margin: 6px 0 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Submit Button */
        .acrb-style-submit-btn {
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
            transition: background 0.15s ease, transform 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .acrb-style-submit-btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }

        /* Live Preview Card */
        .acrb-preview-sidebar {
            position: sticky;
            top: 40px;
        }
        .acrb-preview-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        }
        .acrb-preview-header {
            padding: 14px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .acrb-preview-body {
            padding: 20px;
        }
        .acrb-preview-car-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .acrb-preview-car-name {
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 4px 0;
            line-height: 1.2;
        }
        .acrb-preview-car-sub {
            font-size: 13px;
            margin: 0 0 16px 0;
        }
        .acrb-preview-price-box {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 18px;
        }
        .acrb-preview-price {
            font-size: 22px;
            font-weight: 800;
        }
        .acrb-preview-actions {
            display: flex;
            gap: 8px;
        }
        .acrb-preview-btn-main {
            flex: 1;
            padding: 10px 14px;
            border-radius: 8px;
            text-align: center;
            color: #ffffff;
            font-size: 12.5px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: default;
        }
        .acrb-preview-btn-sec {
            padding: 10px 14px;
            border-radius: 8px;
            text-align: center;
            color: #ffffff;
            font-size: 12.5px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: default;
        }

        @media (max-width: 900px) {
            .acrb-style-grid {
                grid-template-columns: 1fr;
            }
            .acrb-style-color-grid {
                grid-template-columns: 1fr;
            }
            .acrb-preview-sidebar {
                position: static;
            }
        }
    </style>

    <div class="wrap acrb-style-container">

        <!-- Topbar Banner -->
        <header class="acrb-style-topbar-header">
            <div class="acrb-style-topbar-title-group">
                <div class="acrb-style-topbar-icon">
                    <span class="dashicons dashicons-admin-appearance"></span>
                </div>
                <div class="acrb-style-topbar-meta">
                    <h1><?php esc_html_e( 'Style & Theme Settings', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Customize your brand palette, button accents, and typography across all customer-facing templates.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
            <div class="acrb-style-alert-success">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php esc_html_e( 'Theme styles and branding customizer saved successfully.', 'awesome-car-rental' ); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'acrb_save_style_settings', 'acrb_style_nonce' ); ?>

            <div class="acrb-style-grid">

                <div class="acrb-style-main-col">

                    <!-- Brand Color Palette Card -->
                    <div class="acrb-style-card">
                        <h3 class="acrb-style-card-title">
                            <span class="dashicons dashicons-art"></span>
                            <?php esc_html_e( 'Brand Color Palette', 'awesome-car-rental' ); ?>
                        </h3>

                        <div class="acrb-style-color-grid">
                            <!-- 1. Primary Color -->
                            <div class="acrb-style-form-group">
                                <label class="acrb-style-label" for="acrb_primary_input"><?php esc_html_e( 'Primary Brand Color', 'awesome-car-rental' ); ?></label>
                                <div class="acrb-style-color-row">
                                    <input type="color" class="acrb-style-color-picker" id="acrb_primary_picker" value="<?php echo esc_attr( $primary_color ); ?>" />
                                    <input type="text" class="acrb-style-input acrb-style-input-hex" id="acrb_primary_input" name="acrb_style_primary_color" value="<?php echo esc_attr( $primary_color ); ?>" required />
                                </div>
                                <p class="acrb-style-hint"><?php esc_html_e( 'Main booking buttons, active date selections, and primary accents.', 'awesome-car-rental' ); ?></p>
                            </div>

                            <!-- 2. Secondary Color -->
                            <div class="acrb-style-form-group">
                                <label class="acrb-style-label" for="acrb_secondary_input"><?php esc_html_e( 'Secondary / Muted Color', 'awesome-car-rental' ); ?></label>
                                <div class="acrb-style-color-row">
                                    <input type="color" class="acrb-style-color-picker" id="acrb_secondary_picker" value="<?php echo esc_attr( $secondary_color ); ?>" />
                                    <input type="text" class="acrb-style-input acrb-style-input-hex" id="acrb_secondary_input" name="acrb_style_secondary_color" value="<?php echo esc_attr( $secondary_color ); ?>" required />
                                </div>
                                <p class="acrb-style-hint"><?php esc_html_e( 'Secondary action buttons, metadata icons, and border highlights.', 'awesome-car-rental' ); ?></p>
                            </div>

                            <!-- 3. Text Color -->
                            <div class="acrb-style-form-group">
                                <label class="acrb-style-label" for="acrb_text_input"><?php esc_html_e( 'Main Body & Heading Color', 'awesome-car-rental' ); ?></label>
                                <div class="acrb-style-color-row">
                                    <input type="color" class="acrb-style-color-picker" id="acrb_text_picker" value="<?php echo esc_attr( $text_color ); ?>" />
                                    <input type="text" class="acrb-style-input acrb-style-input-hex" id="acrb_text_input" name="acrb_style_text_color" value="<?php echo esc_attr( $text_color ); ?>" required />
                                </div>
                                <p class="acrb-style-hint"><?php esc_html_e( 'Vehicle names, pricing text, and primary typography.', 'awesome-car-rental' ); ?></p>
                            </div>

                            <!-- 4. Accent Color -->
                            <div class="acrb-style-form-group">
                                <label class="acrb-style-label" for="acrb_accent_input"><?php esc_html_e( 'Accent / Success Color', 'awesome-car-rental' ); ?></label>
                                <div class="acrb-style-color-row">
                                    <input type="color" class="acrb-style-color-picker" id="acrb_accent_picker" value="<?php echo esc_attr( $accent_color ); ?>" />
                                    <input type="text" class="acrb-style-input acrb-style-input-hex" id="acrb_accent_input" name="acrb_style_accent_color" value="<?php echo esc_attr( $accent_color ); ?>" required />
                                </div>
                                <p class="acrb-style-hint"><?php esc_html_e( 'Live vehicle availability badges, discount tags, and confirmations.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Typography Card -->
                    <div class="acrb-style-card">
                        <h3 class="acrb-style-card-title">
                            <span class="dashicons dashicons-editor-textcolor"></span>
                            <?php esc_html_e( 'Typography & Web Fonts', 'awesome-car-rental' ); ?>
                        </h3>
                        <div class="acrb-style-form-group">
                            <label class="acrb-style-label" for="acrb_font_family_select"><?php esc_html_e( 'Google Font Family', 'awesome-car-rental' ); ?></label>
                            <div class="acrb-style-select-wrap">
                                <select name="acrb_style_font_family" id="acrb_font_family_select" class="acrb-style-select">
                                    <option value="jakarta" <?php selected( $font_family, 'jakarta' ); ?>><?php esc_html_e( 'Plus Jakarta Sans (Modern SaaS & Clean Tech)', 'awesome-car-rental' ); ?></option>
                                    <option value="inter" <?php selected( $font_family, 'inter' ); ?>><?php esc_html_e( 'Inter (High-Legibility Modern UI)', 'awesome-car-rental' ); ?></option>
                                    <option value="poppins" <?php selected( $font_family, 'poppins' ); ?>><?php esc_html_e( 'Poppins (Geometric & Contemporary Friendly)', 'awesome-car-rental' ); ?></option>
                                    <option value="montserrat" <?php selected( $font_family, 'montserrat' ); ?>><?php esc_html_e( 'Montserrat (Classic Automotive Bold)', 'awesome-car-rental' ); ?></option>
                                    <option value="roboto" <?php selected( $font_family, 'roboto' ); ?>><?php esc_html_e( 'Roboto (Universal Clean Standard)', 'awesome-car-rental' ); ?></option>
                                </select>
                                <span class="dashicons dashicons-arrow-down-alt2 acrb-style-select-arrow"></span>
                            </div>
                            <p class="acrb-style-hint"><?php esc_html_e( 'Automatically enqueued from Google Fonts across your car rental catalogue and widgets.', 'awesome-car-rental' ); ?></p>
                        </div>
                    </div>

                    <div>
                        <button type="submit" name="acrb_style_submit" class="acrb-style-submit-btn">
                            <span class="dashicons dashicons-saved"></span>
                            <span><?php esc_html_e( 'Save Style Settings', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>

                </div>

                <!-- Live Client Card Preview Sidebar -->
                <div class="acrb-preview-sidebar">
                    <div class="acrb-preview-card" id="acrb_preview_card">
                        <div class="acrb-preview-header">
                            <span class="dashicons dashicons-visibility"></span>
                            <span><?php esc_html_e( 'Live Component Preview', 'awesome-car-rental' ); ?></span>
                        </div>
                        <div class="acrb-preview-body">
                            <span class="acrb-preview-car-badge" id="acrb_prev_badge">● <?php esc_html_e( 'Available Now', 'awesome-car-rental' ); ?></span>
                            <h4 class="acrb-preview-car-name" id="acrb_prev_title">Tesla Model 3 Long Range</h4>
                            <p class="acrb-preview-car-sub" id="acrb_prev_sub"><?php esc_html_e( 'Electric • 5 Seats • Autopilot', 'awesome-car-rental' ); ?></p>
                            <div class="acrb-preview-price-box">
                                <span class="acrb-preview-price" id="acrb_prev_price">$120.00</span>
                                <span id="acrb_prev_unit" style="font-size:12px;">/ day</span>
                            </div>
                            <div class="acrb-preview-actions">
                                <div class="acrb-preview-btn-main" id="acrb_prev_btn_main"><?php esc_html_e( 'Book Now', 'awesome-car-rental' ); ?></div>
                                <div class="acrb-preview-btn-sec" id="acrb_prev_btn_sec"><?php esc_html_e( 'Details', 'awesome-car-rental' ); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Live Color & Font Synchronizer Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pairs = [
                { picker: 'acrb_primary_picker', input: 'acrb_primary_input' },
                { picker: 'acrb_secondary_picker', input: 'acrb_secondary_input' },
                { picker: 'acrb_text_picker', input: 'acrb_text_input' },
                { picker: 'acrb_accent_picker', input: 'acrb_accent_input' }
            ];

            const previewCard   = document.getElementById('acrb_preview_card');
            const prevBadge     = document.getElementById('acrb_prev_badge');
            const prevTitle     = document.getElementById('acrb_prev_title');
            const prevSub       = document.getElementById('acrb_prev_sub');
            const prevPrice     = document.getElementById('acrb_prev_price');
            const prevUnit      = document.getElementById('acrb_prev_unit');
            const prevBtnMain   = document.getElementById('acrb_prev_btn_main');
            const prevBtnSec    = document.getElementById('acrb_prev_btn_sec');
            const fontSelect    = document.getElementById('acrb_font_family_select');

            const fontMap = {
                'jakarta': "'Plus Jakarta Sans', sans-serif",
                'inter': "'Inter', sans-serif",
                'poppins': "'Poppins', sans-serif",
                'montserrat': "'Montserrat', sans-serif",
                'roboto': "'Roboto', sans-serif"
            };

            function updatePreview() {
                const primary   = document.getElementById('acrb_primary_input').value;
                const secondary = document.getElementById('acrb_secondary_input').value;
                const text      = document.getElementById('acrb_text_input').value;
                const accent    = document.getElementById('acrb_accent_input').value;
                const fontKey   = fontSelect ? fontSelect.value : 'jakarta';

                if (previewCard && fontMap[fontKey]) {
                    previewCard.style.fontFamily = fontMap[fontKey];
                }

                if (prevBadge)   prevBadge.style.color = accent;
                if (prevTitle)   prevTitle.style.color = text;
                if (prevSub)     prevSub.style.color   = secondary;
                if (prevPrice)   prevPrice.style.color = text;
                if (prevUnit)    prevUnit.style.color  = secondary;

                if (prevBtnMain) {
                    prevBtnMain.style.backgroundColor = primary;
                    prevBtnMain.style.borderColor     = primary;
                }
                if (prevBtnSec) {
                    prevBtnSec.style.backgroundColor  = secondary;
                    prevBtnSec.style.borderColor      = secondary;
                }
            }

            pairs.forEach(function(item) {
                const p = document.getElementById(item.picker);
                const i = document.getElementById(item.input);
                if (p && i) {
                    p.addEventListener('input', function() {
                        i.value = p.value.toUpperCase();
                        updatePreview();
                    });
                    i.addEventListener('input', function() {
                        p.value = i.value;
                        updatePreview();
                    });
                }
            });

            if (fontSelect) {
                fontSelect.addEventListener('change', updatePreview);
            }

            updatePreview();
        });
    </script>
    <?php
}