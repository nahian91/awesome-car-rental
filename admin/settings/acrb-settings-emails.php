<?php
/**
 * Emails Tab - Branding & Configuration (SaaS Modernized)
 * Location: admin/settings/acrb-settings-emails.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_emails_view() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // SAVE LOGIC
    if ( isset( $_POST['acrb_save_emails'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_email_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_email_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $acrb_nonce, 'acrb_save_email_settings' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security check failed. Please try again.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            update_option( 'acrb_admin_email', sanitize_email( wp_unslash( $_POST['acrb_admin_email'] ?? '' ) ) );
            update_option( 'acrb_email_logo', esc_url_raw( wp_unslash( $_POST['acrb_email_logo'] ?? '' ) ) );
            update_option( 'acrb_email_accent_color', sanitize_hex_color( wp_unslash( $_POST['acrb_email_accent_color'] ?? '#4f46e5' ) ) );
            update_option( 'acrb_email_footer_text', sanitize_textarea_field( wp_unslash( $_POST['acrb_email_footer_text'] ?? '' ) ) );

            // Clear dashboard transients
            delete_transient( 'acrb_dashboard_revenue' );
        }
    }

    $admin_email  = get_option( 'acrb_admin_email', get_option( 'admin_email' ) );
    $logo_url     = get_option( 'acrb_email_logo', '' );
    $accent_color = get_option( 'acrb_email_accent_color', '#4f46e5' );
    $footer_text  = get_option( 'acrb_email_footer_text', 'Thank you for choosing our car rental service.' );

    wp_enqueue_media();
    ?>

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
                        <label class="acrb-admin-label"><?php esc_html_e( 'Notification Recipient (Admin)', 'awesome-car-rental' ); ?></label>
                        <input type="email" name="acrb_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="acrb-admin-input">
                        <p class="description" style="margin-top:6px; font-size:12px; color:#64748b;"><?php esc_html_e( 'Administrative updates and booking receipts will be routed here.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Brand Logo URL', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-admin-upload-wrap">
                            <input type="text" name="acrb_email_logo" id="acrb_logo_url" value="<?php echo esc_url( $logo_url ); ?>" class="acrb-admin-input" placeholder="https://example.com/logo.png">
                            <button type="button" class="acrb-admin-btn-upload" id="acrb_upload_btn">
                                <span class="dashicons dashicons-admin-media" style="font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
                                <?php esc_html_e( 'Select Logo', 'awesome-car-rental' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Email Accent Color', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-color-picker-wrap">
                            <input type="color" name="acrb_email_accent_color" id="acrb_accent_picker" value="<?php echo esc_attr( $accent_color ); ?>">
                            <span id="acrb_color_code_label" style="font-size: 13px; color: #475569; font-family: monospace; font-weight:600;"><?php echo esc_html( $accent_color ); ?></span>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Footer Signature & Legal Text', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_email_footer_text" id="acrb_footer_input" rows="4" class="acrb-admin-textarea"><?php echo esc_textarea( $footer_text ); ?></textarea>
                    </div>

                    <div style="margin-top:22px;">
                        <button type="submit" name="acrb_save_emails" class="acrb-admin-btn-save">
                            <span class="dashicons dashicons-saved" style="font-size:17px; width:17px; height:17px;"></span>
                            <span><?php esc_html_e( 'Save Email Branding', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Live Preview Card -->
                <div class="acrb-admin-preview-container">
                    <div class="acrb-admin-card">
                        <div class="acrb-admin-card-header">
                            <span class="dashicons dashicons-visibility"></span>
                            <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Live Preview', 'awesome-car-rental' ); ?></h3>
                        </div>

                        <div class="acrb-admin-mock-email">
                            <div class="acrb-admin-mock-header" id="preview-header" style="background-color: <?php echo esc_attr( $accent_color ); ?>;">
                                <div id="preview-logo-container">
                                    <?php if ( ! empty( $logo_url ) ) : ?>
                                        <img src="<?php echo esc_url( $logo_url ); ?>" id="preview-logo" style="max-height: 40px; object-fit: contain;" alt="">
                                    <?php else : ?>
                                        <span id="preview-logo-placeholder" style="color:#ffffff; font-weight:800; font-size:15px; letter-spacing:0.5px; opacity:0.95;"><?php esc_html_e( 'YOUR LOGO', 'awesome-car-rental' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="acrb-admin-mock-body">
                                <p style="font-size:14px; color:#0f172a; margin-top:0;"><strong><?php esc_html_e( 'Hello Customer,', 'awesome-car-rental' ); ?></strong></p>
                                <p style="font-size:13px; color:#475569; line-height:1.5;">
                                    <?php 
                                    echo wp_kses_post( 
                                        sprintf( 
                                            /* translators: %s: The name of the car (e.g. Tesla Model S). */
                                            __( 'Your rental for the %s has been confirmed.', 'awesome-car-rental' ), 
                                            '<strong>Tesla Model S</strong>' 
                                        ) 
                                    ); 
                                    ?>
                                </p>
                                <div class="acrb-admin-mock-btn" id="preview-btn" style="background-color: <?php echo esc_attr( $accent_color ); ?>; color: #ffffff;">
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
            $('#acrb_color_code_label').text(color);
        });

        $('#acrb_footer_input').on('keyup', function() {
            $('#preview-footer').text($(this).val());
        });

        $('#acrb_logo_url').on('change keyup', function() {
            var url = $(this).val();
            if (url) {
                $('#preview-logo-container').html('<img src="' + url + '" id="preview-logo" style="max-height: 40px; object-fit: contain;" alt="">');
            } else {
                $('#preview-logo-container').html('<span style="color:#ffffff; font-weight:800; font-size:15px; letter-spacing:0.5px; opacity:0.95;"><?php echo esc_js( __( 'YOUR LOGO', 'awesome-car-rental' ) ); ?></span>');
            }
        });
    });
    </script>
    <?php
}