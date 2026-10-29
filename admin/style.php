<?php
/**
 * Style & Branding Customizer Module (`style.php`)
 * Essential 5 styling controls with 5 premium Google Fonts and automated frontend injection.
 *
 * @package Awesome_Car_Rental
 * @since   2.4.0
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

    $saved_notice = false;

    // Handle Form Save
    if ( isset( $_POST['acrb_style_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acrb_seo_nonce'] ?? $_POST['acrb_style_nonce'] ?? '' ) ), 'acrb_save_style_settings' ) ) {
        update_option( 'acrb_style_primary_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_primary_color'] ?? '#4f46e5' ) ) );
        update_option( 'acrb_style_secondary_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_secondary_color'] ?? '#64748b' ) ) );
        update_option( 'acrb_style_text_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_text_color'] ?? '#0f172a' ) ) );
        update_option( 'acrb_style_accent_color', sanitize_hex_color( wp_unslash( $_POST['acrb_style_accent_color'] ?? '#059669' ) ) );
        update_option( 'acrb_style_font_family', sanitize_text_field( wp_unslash( $_POST['acrb_style_font_family'] ?? 'jakarta' ) ) );
        $saved_notice = true;
    }

    // Retrieve Options
    $primary_color   = (string) get_option( 'acrb_style_primary_color', '#4f46e5' );
    $secondary_color = (string) get_option( 'acrb_style_secondary_color', '#64748b' );
    $text_color      = (string) get_option( 'acrb_style_text_color', '#0f172a' );
    $accent_color    = (string) get_option( 'acrb_style_accent_color', '#059669' );
    $font_family     = (string) get_option( 'acrb_style_font_family', 'jakarta' );
    ?>

    <style>
        .acrb-style-container {
            max-width: 780px;
            margin: 20px 0 40px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
            box-sizing: border-box;
        }
        .acrb-style-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        }
        .acrb-style-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }
        .acrb-style-header p {
            margin: 4px 0 0;
            font-size: 13.5px;
            color: #64748b;
        }
        .acrb-style-alert {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .acrb-style-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            padding: 26px;
            margin-bottom: 24px;
        }
        .acrb-style-card-title {
            margin: 0 0 18px;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }
        .acrb-style-form-group {
            margin-bottom: 20px;
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
        .acrb-style-color-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .acrb-style-color-picker {
            width: 44px;
            height: 42px;
            padding: 2px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            cursor: pointer;
            box-sizing: border-box;
        }
        .acrb-style-input,
        .acrb-style-select {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13.5px;
            color: #1e293b;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .acrb-style-input:focus,
        .acrb-style-select:focus {
            background: #ffffff;
            border-color: #4f46e5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .acrb-style-hint {
            margin: 6px 0 0;
            font-size: 12px;
            color: #64748b;
        }
        .acrb-style-submit-btn {
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transition: background 0.15s ease;
        }
        .acrb-style-submit-btn:hover {
            background: #4338ca;
        }
    </style>

    <div class="wrap acrb-style-container">

        <div class="acrb-style-header">
            <h2><?php esc_html_e( 'Style & Theme Settings', 'awesome-car-rental' ); ?></h2>
            <p><?php esc_html_e( 'Customize your brand colors and font family across all car rental templates.', 'awesome-car-rental' ); ?></p>
        </div>

        <?php if ( $saved_notice ) : ?>
            <div class="acrb-style-alert">
                ✓ <?php esc_html_e( 'Theme styles saved and updated successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'acrb_save_style_settings', 'acrb_style_nonce' ); ?>

            <div class="acrb-style-card">
                <h3 class="acrb-style-card-title"><?php esc_html_e( 'Brand Color Palette', 'awesome-car-rental' ); ?></h3>

                <!-- 1. Primary Color -->
                <div class="acrb-style-form-group">
                    <label class="acrb-style-label" for="acrb_primary_input"><?php esc_html_e( 'Primary Color', 'awesome-car-rental' ); ?></label>
                    <div class="acrb-style-color-row">
                        <input type="color" class="acrb-style-color-picker" id="acrb_primary_picker" value="<?php echo esc_attr( $primary_color ); ?>" />
                        <input type="text" class="acrb-style-input" id="acrb_primary_input" name="acrb_style_primary_color" value="<?php echo esc_attr( $primary_color ); ?>" />
                    </div>
                    <p class="acrb-style-hint"><?php esc_html_e( 'Primary CTAs, active reservation days, and highlight borders.', 'awesome-car-rental' ); ?></p>
                </div>

                <!-- 2. Secondary Color -->
                <div class="acrb-style-form-group">
                    <label class="acrb-style-label" for="acrb_secondary_input"><?php esc_html_e( 'Secondary Color', 'awesome-car-rental' ); ?></label>
                    <div class="acrb-style-color-row">
                        <input type="color" class="acrb-style-color-picker" id="acrb_secondary_picker" value="<?php echo esc_attr( $secondary_color ); ?>" />
                        <input type="text" class="acrb-style-input" id="acrb_secondary_input" name="acrb_style_secondary_color" value="<?php echo esc_attr( $secondary_color ); ?>" />
                    </div>
                    <p class="acrb-style-hint"><?php esc_html_e( 'Secondary buttons, vehicle subtitle tags, and inactive indicators.', 'awesome-car-rental' ); ?></p>
                </div>

                <!-- 3. Text Color -->
                <div class="acrb-style-form-group">
                    <label class="acrb-style-label" for="acrb_text_input"><?php esc_html_e( 'Text Color', 'awesome-car-rental' ); ?></label>
                    <div class="acrb-style-color-row">
                        <input type="color" class="acrb-style-color-picker" id="acrb_text_picker" value="<?php echo esc_attr( $text_color ); ?>" />
                        <input type="text" class="acrb-style-input" id="acrb_text_input" name="acrb_style_text_color" value="<?php echo esc_attr( $text_color ); ?>" />
                    </div>
                    <p class="acrb-style-hint"><?php esc_html_e( 'Vehicle names, pricing text, and headings.', 'awesome-car-rental' ); ?></p>
                </div>

                <!-- 4. Accent Color -->
                <div class="acrb-style-form-group">
                    <label class="acrb-style-label" for="acrb_accent_input"><?php esc_html_e( 'Accent Color', 'awesome-car-rental' ); ?></label>
                    <div class="acrb-style-color-row">
                        <input type="color" class="acrb-style-color-picker" id="acrb_accent_picker" value="<?php echo esc_attr( $accent_color ); ?>" />
                        <input type="text" class="acrb-style-input" id="acrb_accent_input" name="acrb_style_accent_color" value="<?php echo esc_attr( $accent_color ); ?>" />
                    </div>
                    <p class="acrb-style-hint"><?php esc_html_e( 'Available slot indicators, checkmarks, and active status tags.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>

            <!-- 5. Font Family -->
            <div class="acrb-style-card">
                <h3 class="acrb-style-card-title"><?php esc_html_e( 'Typography', 'awesome-car-rental' ); ?></h3>
                <div class="acrb-style-form-group">
                    <label class="acrb-style-label" for="acrb_font_family_select"><?php esc_html_e( 'Google Font Family', 'awesome-car-rental' ); ?></label>
                    <select name="acrb_style_font_family" id="acrb_font_family_select" class="acrb-style-select">
                        <option value="jakarta" <?php selected( $font_family, 'jakarta' ); ?>><?php esc_html_e( 'Plus Jakarta Sans (Modern SaaS & Tech)', 'awesome-car-rental' ); ?></option>
                        <option value="inter" <?php selected( $font_family, 'inter' ); ?>><?php esc_html_e( 'Inter (Clean & Highly Legible UI)', 'awesome-car-rental' ); ?></option>
                        <option value="poppins" <?php selected( $font_family, 'poppins' ); ?>><?php esc_html_e( 'Poppins (Geometric & Friendly)', 'awesome-car-rental' ); ?></option>
                        <option value="montserrat" <?php selected( $font_family, 'montserrat' ); ?>><?php esc_html_e( 'Montserrat (Classic Automotive Bold)', 'awesome-car-rental' ); ?></option>
                        <option value="roboto" <?php selected( $font_family, 'roboto' ); ?>><?php esc_html_e( 'Roboto (Universal Modern Grotesk)', 'awesome-car-rental' ); ?></option>
                    </select>
                    <p class="acrb-style-hint"><?php esc_html_e( 'Automatically enqueued from Google Fonts across your car rental pages.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>

            <button type="submit" class="acrb-style-submit-btn">
                <?php esc_html_e( 'Save Style Settings', 'awesome-car-rental' ); ?>
            </button>
        </form>
    </div>

    <!-- Color Synchronizer Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pairs = [
                { picker: 'acrb_primary_picker', input: 'acrb_primary_input' },
                { picker: 'acrb_secondary_picker', input: 'acrb_secondary_input' },
                { picker: 'acrb_text_picker', input: 'acrb_text_input' },
                { picker: 'acrb_accent_picker', input: 'acrb_accent_input' }
            ];

            pairs.forEach(function(item) {
                const p = document.getElementById(item.picker);
                const i = document.getElementById(item.input);
                if (p && i) {
                    p.addEventListener('input', function() { i.value = p.value; });
                    i.addEventListener('input', function() { p.value = i.value; });
                }
            });
        });
    </script>
    <?php
}