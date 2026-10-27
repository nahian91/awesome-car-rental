<?php
/**
 * Emails Tab - Branding & Configuration (SaaS Modernized)
 * Location: admin/settings-emails.php
 */
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
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security check failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            update_option( 'acrb_admin_email', sanitize_email( wp_unslash( $_POST['acrb_admin_email'] ?? '' ) ) );
            update_option( 'acrb_email_logo', esc_url_raw( wp_unslash( $_POST['acrb_email_logo'] ?? '' ) ) );
            update_option( 'acrb_email_accent_color', sanitize_hex_color( wp_unslash( $_POST['acrb_email_accent_color'] ?? '#4f46e5' ) ) );
            update_option( 'acrb_email_footer_text', sanitize_textarea_field( wp_unslash( $_POST['acrb_email_footer_text'] ?? '' ) ) );

            // Clear dashboard transients
            delete_transient( 'acrb_dashboard_revenue' );

            echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Email configurations updated.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    $admin_email  = get_option( 'acrb_admin_email', get_option( 'admin_email' ) );
    $logo_url     = get_option( 'acrb_email_logo', '' );
    $accent_color = get_option( 'acrb_email_accent_color', '#3b82f6' );
    $footer_text  = get_option( 'acrb_email_footer_text', 'Thank you for choosing our car rental service.' );

    wp_enqueue_media();
    ?>

    <style>
        /* Modern SaaS Layout & Email Theme */
        .acrb-admin-wrapper { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-admin-wrapper *, .acrb-admin-wrapper *::before, .acrb-admin-wrapper *::after { box-sizing: border-box; }

        .acrb-admin-grid { display: grid; grid-template-columns: 1fr 420px; gap: 24px; align-items: start; }
        
        .acrb-admin-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-admin-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }
        .acrb-admin-card-header .dashicons { font-size: 22px; width: 22px; height: 22px; color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-admin-card-title { margin: 0; font-size: 15px; font-weight: 600; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .acrb-admin-field-group { margin-bottom: 20px; }
        .acrb-admin-label { display: block; font-weight: 600; margin-bottom: 8px; color: #334155; font-size: 13px; }
        .acrb-admin-input, .acrb-admin-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-admin-input { height: 42px; }
        .acrb-admin-textarea { padding: 12px 14px; height: auto; }
        .acrb-admin-input:hover, .acrb-admin-textarea:hover { border-color: #94a3b8; }
        .acrb-admin-input:focus, .acrb-admin-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }
        
        /* Media Upload Styling */
        .acrb-admin-upload-wrap { display: flex; gap: 12px; }
        .acrb-admin-btn-upload { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 18px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 42px; cursor: pointer; transition: all 0.2s ease; white-space: nowrap; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        .acrb-admin-btn-upload:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

        /* Color Picker Customization */
        .acrb-color-picker-wrap { display: flex; align-items: center; gap: 12px; }
        input[type="color"] { -webkit-appearance: none; border: 1px solid #cbd5e1; width: 50px; height: 42px; border-radius: 8px; cursor: pointer; background: #fff; padding: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
        input[type="color"]::-webkit-color-swatch { border: none; border-radius: 6px; }

        /* Mock Email Preview */
        .acrb-admin-preview-container { position: sticky; top: 20px; }
        .acrb-admin-mock-email { background: #f8fafc; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .acrb-admin-mock-header { padding: 32px 20px; text-align: center; transition: background 0.3s ease; }
        .acrb-admin-mock-body { background: #fff; margin: -20px 20px 20px; padding: 24px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .acrb-admin-mock-btn { display: block; padding: 12px 20px; text-align: center; border-radius: 8px; font-weight: 600; margin: 20px 0 16px 0; transition: background 0.3s ease; font-size: 13px; text-decoration: none; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .acrb-admin-mock-footer { font-size: 11px; color: #64748b; text-align: center; line-height: 1.5; margin-top: 16px; border-top: 1px solid #f1f5f9; padding-top: 12px; }

        .acrb-admin-footer { margin-top: 24px; padding: 20px 28px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; display: flex; justify-content: flex-end; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-admin-btn-save { background: #3b82f6; color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; height: 42px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-admin-btn-save:hover { background: #2563eb; }
        
        @media (max-width: 900px) { .acrb-admin-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="acrb-admin-wrapper">
        <form method="post" id="acrb-email-form">
            <?php wp_nonce_field( 'acrb_save_email_settings', 'acrb_email_nonce' ); ?>

            <div class="acrb-admin-grid">
                
                <!-- Settings Form Card -->
                <div class="acrb-admin-card">
                    <div class="acrb-admin-card-header">
                        <span class="dashicons dashicons-email-alt"></span>
                        <div>
                            <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Sender & Branding', 'awesome-car-rental' ); ?></h3>
                        </div>
                    </div>
                    
                    <div class="acrb-admin-field-group">
                        <label class="acrb-label"><?php esc_html_e( 'Notification Recipient (Admin)', 'awesome-car-rental' ); ?></label>
                        <input type="email" name="acrb_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="acrb-admin-input">
                        <p class="description" style="margin-top:6px; font-size:12px; color:#64748b;"><?php esc_html_e( 'New booking alerts will be sent here.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-label"><?php esc_html_e( 'Brand Logo URL', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-admin-upload-wrap">
                            <input type="text" name="acrb_email_logo" id="acrb_logo_url" value="<?php echo esc_url( $logo_url ); ?>" class="acrb-admin-input">
                            <button type="button" class="acrb-admin-btn-upload" id="acrb_upload_btn"><?php esc_html_e( 'Select Logo', 'awesome-car-rental' ); ?></button>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-label"><?php esc_html_e( 'Email Accent Color', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-color-picker-wrap">
                            <input type="color" name="acrb_email_accent_color" id="acrb_accent_picker" value="<?php echo esc_attr( $accent_color ); ?>">
                            <span style="font-size: 13px; color: #64748b; font-family: monospace;"><?php echo esc_html( $accent_color ); ?></span>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-label"><?php esc_html_e( 'Footer Signature Text', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_email_footer_text" id="acrb_footer_input" rows="4" class="acrb-admin-textarea"><?php echo esc_textarea( $footer_text ); ?></textarea>
                    </div>

                    <div class="acrb-admin-footer" style="margin-top:0; padding:0; border:none; box-shadow:none; background:transparent; justify-content:flex-start;">
                        <button type="submit" name="acrb_save_emails" class="acrb-admin-btn-save">
                            <?php esc_html_e( 'Save Email Branding', 'awesome-car-rental' ); ?>
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
                                    <?php if ( $logo_url ) : ?>
                                        <img src="<?php echo esc_url( $logo_url ); ?>" id="preview-logo" style="max-height: 40px; object-fit: contain;" alt="">
                                    <?php else : ?>
                                        <span id="preview-logo-placeholder" style="color:#fff; font-weight:700; font-size:15px; letter-spacing:0.5px; opacity:0.9;"><?php esc_html_e( 'YOUR LOGO', 'awesome-car-rental' ); ?></span>
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
                                            __( 'Your rental for the %s is confirmed.', 'awesome-car-rental' ), 
                                            '<strong>Tesla Model S</strong>' 
                                        ) 
                                    ); 
                                    ?>
                                </p>
                                <div class="acrb-admin-mock-btn" id="preview-btn" style="background-color: <?php echo esc_attr( $accent_color ); ?>; color: #fff;">
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
    jQuery(document).ready(function($){
        var frame;
        $('#acrb_upload_btn').on('click', function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: '<?php echo esc_js( __( 'Select Brand Logo', 'awesome-car-rental' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Use Logo', 'awesome-car-rental' ) ); ?>' },
                multiple: false
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#acrb_logo_url').val(attachment.url).trigger('change');
            });
            frame.open();
        });

        $('#acrb_accent_picker').on('input', function(){
            var color = $(this).val();
            $('#preview-header, #preview-btn').css('background-color', color);
            $(this).siblings('span').text(color);
        });

        $('#acrb_footer_input').on('keyup', function(){
            $('#preview-footer').text($(this).val());
        });

        $('#acrb_logo_url').on('change keyup', function(){
            var url = $(this).val();
            if(url) {
                $('#preview-logo-container').html('<img src="'+url+'" id="preview-logo" style="max-height: 40px; object-fit: contain;" alt="">');
            } else {
                $('#preview-logo-container').html('<span style="color:#fff; font-weight:700; font-size:15px; letter-spacing:0.5px; opacity:0.9;"><?php echo esc_js( __( 'YOUR LOGO', 'awesome-car-rental' ) ); ?></span>');
            }
        });
    });
    </script>
    <?php
}