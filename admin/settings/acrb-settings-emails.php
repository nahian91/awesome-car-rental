<?php
/**
 * Emails Tab - Branding & Configuration (SaaS Modernized)
 * Location: admin/settings/acrb-settings-emails.php
 *
 * @package Awesome_Car_Rental
 * @since   3.1.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_emails_view(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $page_slug = 'awesome_car_rental';
    $base_url  = admin_url( 'admin.php?page=' . $page_slug . '&tab=settings&sub=emails' );

    // 1. Process Form Submission with Safe Redirect
    if ( isset( $_POST['acrb_save_emails'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_email_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_email_nonce'] ) ) : '';

        if ( wp_verify_nonce( $acrb_nonce, 'acrb_save_email_settings' ) ) {
            update_option( 'acrb_admin_email', sanitize_email( wp_unslash( $_POST['acrb_admin_email'] ?? '' ) ) );
            update_option( 'acrb_email_logo', esc_url_raw( wp_unslash( $_POST['acrb_email_logo'] ?? '' ) ) );
            update_option( 'acrb_email_accent_color', sanitize_hex_color( wp_unslash( $_POST['acrb_email_accent_color'] ?? '#4f46e5' ) ) );
            update_option( 'acrb_email_footer_text', sanitize_textarea_field( wp_unslash( $_POST['acrb_email_footer_text'] ?? '' ) ) );

            delete_transient( 'acrb_dashboard_revenue' );

            $redirect_url = add_query_arg( 'updated', '1', $base_url );

            if ( headers_sent() ) {
                echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }
    }

    $admin_email  = (string) get_option( 'acrb_admin_email', get_option( 'admin_email' ) );
    $logo_url     = (string) get_option( 'acrb_email_logo', '' );
    $accent_color = (string) get_option( 'acrb_email_accent_color', '#4f46e5' );
    $footer_text  = (string) get_option( 'acrb_email_footer_text', __( 'Thank you for choosing our car rental service.', 'awesome-car-rental' ) );

    wp_enqueue_media();
    ?>

    <style>
        .acrb-emails-wrapper {
            width: 100%;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }
        .acrb-emails-wrapper *,
        .acrb-emails-wrapper *::before,
        .acrb-emails-wrapper *::after {
            box-sizing: border-box;
        }

        /* Two-Column Responsive Layout */
        .acrb-emails-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }

        /* Card Container */
        .acrb-admin-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            padding: 24px;
            margin-bottom: 22px;
        }
        .acrb-admin-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }
        .acrb-admin-card-header .dashicons {
            color: #4f46e5;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        .acrb-admin-card-title {
            margin: 0;
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Field Groups */
        .acrb-admin-field-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 18px;
        }
        .acrb-admin-field-group:last-child {
            margin-bottom: 0;
        }
        .acrb-admin-label {
            display: block;
            font-weight: 700;
            color: #1e293b;
            font-size: 13.5px;
            margin-bottom: 6px;
        }
        .acrb-admin-field-hint {
            margin: 6px 0 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Inputs & Textareas */
        .acrb-admin-input,
        .acrb-admin-textarea {
            display: block;
            width: 100% !important;
            max-width: 100% !important;
            background: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
            font-size: 13.5px !important;
            font-family: inherit !important;
            color: #1e293b !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
        }
        .acrb-admin-input {
            height: 42px !important;
            line-height: 40px !important;
        }
        .acrb-admin-textarea {
            height: auto !important;
            padding: 10px 14px !important;
            line-height: 1.5 !important;
            resize: vertical;
        }
        .acrb-admin-input:hover,
        .acrb-admin-textarea:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-admin-input:focus,
        .acrb-admin-textarea:focus {
            background: #ffffff !important;
            border-color: #4f46e5 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        }

        /* Upload Wrap */
        .acrb-admin-upload-wrap {
            display: flex;
            gap: 8px;
        }
        .acrb-admin-btn-upload {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-weight: 700;
            font-size: 13px;
            border-radius: 8px;
            padding: 0 16px;
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            height: 42px;
        }
        .acrb-admin-btn-upload:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }

        /* Color Picker Wrap */
        .acrb-color-picker-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .acrb-color-picker-field {
            width: 44px;
            height: 42px;
            min-width: 44px;
            padding: 2px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            cursor: pointer;
            transition: border-color 0.15s ease;
        }
        .acrb-color-picker-field:hover {
            border-color: #94a3b8;
        }
        .acrb-color-code-label {
            font-size: 13px;
            color: #475569;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        /* Save Button */
        .acrb-admin-btn-save {
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
            margin-top: 6px;
        }
        .acrb-admin-btn-save:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }

        /* Live Preview Container */
        .acrb-admin-preview-container {
            position: sticky;
            top: 40px;
        }
        .acrb-admin-mock-email {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .acrb-admin-mock-header {
            padding: 22px 20px;
            text-align: center;
            transition: background-color 0.2s ease;
        }
        .acrb-admin-mock-logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
        }
        .acrb-admin-mock-logo-img {
            max-height: 40px;
            object-fit: contain;
        }
        .acrb-admin-mock-logo-placeholder {
            color: #ffffff;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: 0.05em;
            opacity: 0.95;
        }
        .acrb-admin-mock-body {
            padding: 20px;
        }
        .acrb-admin-mock-greeting {
            font-size: 14px;
            color: #0f172a;
            margin: 0 0 10px 0;
            font-weight: 700;
        }
        .acrb-admin-mock-text {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
            margin: 0 0 16px 0;
        }
        .acrb-admin-mock-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px 14px;
            border-radius: 8px;
            color: #ffffff;
            font-size: 12.5px;
            font-weight: 700;
            margin-bottom: 20px;
            cursor: default;
            transition: background-color 0.2s ease;
        }
        .acrb-admin-mock-footer {
            font-size: 11.5px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            line-height: 1.45;
            word-break: break-word;
        }

        @media (max-width: 900px) {
            .acrb-emails-grid {
                grid-template-columns: 1fr;
            }
            .acrb-admin-preview-container {
                position: static;
            }
        }
    </style>

    <div class="acrb-emails-wrapper">
        <form method="post" id="acrb-email-form">
            <?php wp_nonce_field( 'acrb_save_email_settings', 'acrb_email_nonce' ); ?>

            <div class="acrb-emails-grid">
                
                <!-- Settings Form Card -->
                <div class="acrb-admin-card">
                    <div class="acrb-admin-card-header">
                        <span class="dashicons dashicons-email-alt"></span>
                        <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Sender & Email Branding', 'awesome-car-rental' ); ?></h3>
                    </div>
                    
                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label" for="acrb_admin_email"><?php esc_html_e( 'Notification Recipient (Admin Email)', 'awesome-car-rental' ); ?></label>
                        <input type="email" id="acrb_admin_email" name="acrb_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="acrb-admin-input" required>
                        <p class="acrb-admin-field-hint"><?php esc_html_e( 'Administrative updates and booking reservation receipts will be dispatched here.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label" for="acrb_logo_url"><?php esc_html_e( 'Brand Logo URL', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-admin-upload-wrap">
                            <input type="text" name="acrb_email_logo" id="acrb_logo_url" value="<?php echo esc_url( $logo_url ); ?>" class="acrb-admin-input" placeholder="<?php esc_attr_e( 'https://example.com/logo.png', 'awesome-car-rental' ); ?>">
                            <button type="button" class="acrb-admin-btn-upload" id="acrb_upload_btn">
                                <span class="dashicons dashicons-admin-media"></span>
                                <span><?php esc_html_e( 'Select Logo', 'awesome-car-rental' ); ?></span>
                            </button>
                        </div>
                        <p class="acrb-admin-field-hint"><?php esc_html_e( 'High-resolution logo with transparent background for email headers.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label" for="acrb_accent_picker"><?php esc_html_e( 'Email Header Accent Color', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-color-picker-wrap">
                            <input type="color" name="acrb_email_accent_color" id="acrb_accent_picker" class="acrb-color-picker-field" value="<?php echo esc_attr( $accent_color ); ?>">
                            <span id="acrb_color_code_label" class="acrb-color-code-label"><?php echo esc_html( strtoupper( $accent_color ) ); ?></span>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label" for="acrb_footer_input"><?php esc_html_e( 'Footer Signature & Legal Text', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_email_footer_text" id="acrb_footer_input" rows="4" class="acrb-admin-textarea"><?php echo esc_textarea( $footer_text ); ?></textarea>
                    </div>

                    <div>
                        <button type="submit" name="acrb_save_emails" class="acrb-admin-btn-save">
                            <span class="dashicons dashicons-saved"></span>
                            <span><?php esc_html_e( 'Save Email Branding', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Live Mockup Preview Card -->
                <div class="acrb-admin-preview-container">
                    <div class="acrb-admin-card">
                        <div class="acrb-admin-card-header">
                            <span class="dashicons dashicons-visibility"></span>
                            <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Email Mockup Preview', 'awesome-car-rental' ); ?></h3>
                        </div>

                        <div class="acrb-admin-mock-email">
                            <div class="acrb-admin-mock-header" id="preview-header" style="background-color: <?php echo esc_attr( $accent_color ); ?>;">
                                <div id="preview-logo-container" class="acrb-admin-mock-logo-wrap">
                                    <?php if ( ! empty( $logo_url ) ) : ?>
                                        <img src="<?php echo esc_url( $logo_url ); ?>" id="preview-logo" class="acrb-admin-mock-logo-img" alt="">
                                    <?php else : ?>
                                        <span id="preview-logo-placeholder" class="acrb-admin-mock-logo-placeholder"><?php esc_html_e( 'YOUR LOGO', 'awesome-car-rental' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="acrb-admin-mock-body">
                                <p class="acrb-admin-mock-greeting"><?php esc_html_e( 'Hello Customer,', 'awesome-car-rental' ); ?></p>
                                <p class="acrb-admin-mock-text">
                                    <?php 
                                    echo wp_kses_post( 
                                        sprintf( 
                                            /* translators: %s: The name of the car (e.g. Tesla Model S). */
                                            __( 'Your rental reservation for the %s has been confirmed.', 'awesome-car-rental' ), 
                                            '<strong>Tesla Model S</strong>' 
                                        ) 
                                    ); 
                                    ?>
                                </p>
                                <div class="acrb-admin-mock-btn" id="preview-btn" style="background-color: <?php echo esc_attr( $accent_color ); ?>;">
                                    <?php esc_html_e( 'View Booking Details', 'awesome-car-rental' ); ?>
                                </div>
                                <div id="preview-footer" class="acrb-admin-mock-footer"><?php echo esc_html( $footer_text ); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Live Preview Script -->
    <script>
    jQuery(document).ready(function($) {
        var frame;
        $('#acrb_upload_btn').on('click', function(e) {
            e.preventDefault();
            if (frame) {
                frame.open();
                return;
            }
            frame = wp.media({
                title: '<?php echo esc_js( __( 'Select Brand Logo', 'awesome-car-rental' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Use Logo', 'awesome-car-rental' ) ); ?>' },
                multiple: false
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#acrb_logo_url').val(attachment.url).trigger('change');
            });
            frame.open();
        });

        $('#acrb_accent_picker').on('input', function() {
            var color = $(this).val();
            $('#preview-header, #preview-btn').css('background-color', color);
            $('#acrb_color_code_label').text(color.toUpperCase());
        });

        $('#acrb_footer_input').on('input keyup', function() {
            $('#preview-footer').text($(this).val());
        });

        $('#acrb_logo_url').on('change input keyup', function() {
            var url = $(this).val().trim();
            if (url) {
                $('#preview-logo-container').html('<img src="' + encodeURI(url) + '" id="preview-logo" class="acrb-admin-mock-logo-img" alt="">');
            } else {
                $('#preview-logo-container').html('<span id="preview-logo-placeholder" class="acrb-admin-mock-logo-placeholder"><?php echo esc_js( __( 'YOUR LOGO', 'awesome-car-rental' ) ); ?></span>');
            }
        });
    });
    </script>
    <?php
}