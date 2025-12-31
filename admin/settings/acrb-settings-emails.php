<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Emails Tab - Branding & Configuration
 */
function acrb_settings_emails_view() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // SAVE LOGIC
    if ( isset( $_POST['acrb_save_emails'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_email_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_email_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $acrb_nonce, 'acrb_save_email_settings' ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            update_option( 'acrb_admin_email', sanitize_email( wp_unslash( $_POST['acrb_admin_email'] ?? '' ) ) );
            update_option( 'acrb_email_logo', esc_url_raw( wp_unslash( $_POST['acrb_email_logo'] ?? '' ) ) );
            update_option( 'acrb_email_accent_color', sanitize_hex_color( wp_unslash( $_POST['acrb_email_accent_color'] ?? '#4f46e5' ) ) );
            update_option( 'acrb_email_footer_text', sanitize_textarea_field( wp_unslash( $_POST['acrb_email_footer_text'] ?? '' ) ) );

            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Email configurations updated.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    $admin_email  = get_option( 'acrb_admin_email', get_option( 'admin_email' ) );
    $logo_url     = get_option( 'acrb_email_logo', '' );
    $accent_color = get_option( 'acrb_email_accent_color', '#4f46e5' );
    $footer_text  = get_option( 'acrb_email_footer_text', 'Thank you for choosing our car rental service.' );

    wp_enqueue_media();
    ?>

    <style>
        .acrb-admin-wrapper { margin-top: 20px; max-width: 1100px; }
        .acrb-admin-grid { display: grid; grid-template-columns: 1fr 400px; gap: 24px; align-items: start; }
        .acrb-admin-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .acrb-admin-card-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; }
        .acrb-admin-card-title { margin: 0; font-size: 16px; font-weight: 600; color: #1d2327; }
        .acrb-admin-field-group { margin-bottom: 20px; }
        .acrb-admin-label { display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327; font-size: 13px; }
        .acrb-admin-input, .acrb-admin-textarea { width: 100%; border: 1px solid #8c8f94; border-radius: 4px; padding: 8px; }
        
        /* Media Upload Styling */
        .acrb-admin-upload-wrap { display: flex; gap: 10px; }
        .acrb-admin-btn-upload { white-space: nowrap; }

        /* Mock Email Preview */
        .acrb-admin-preview-container { position: sticky; top: 50px; }
        .acrb-admin-mock-email { background: #f0f0f1; border-radius: 8px; overflow: hidden; border: 1px solid #dcdcde; font-family: sans-serif; }
        .acrb-admin-mock-header { padding: 30px 20px; text-align: center; transition: background 0.3s ease; }
        .acrb-admin-mock-body { background: #fff; margin: -20px 15px 15px; padding: 20px; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .acrb-admin-mock-btn { display: block; padding: 12px; text-align: center; border-radius: 4px; font-weight: 600; margin: 20px 0; transition: background 0.3s ease; }
        .acrb-admin-mock-footer { font-size: 11px; color: #646970; text-align: center; line-height: 1.5; margin-top: 15px; }

        .acrb-admin-footer { margin-top: 25px; display: flex; justify-content: flex-end; }
        .acrb-admin-btn-save { background: #2271b1; color: #fff; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; cursor: pointer; }
        
        @media (max-width: 900px) { .acrb-admin-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="acrb-admin-wrapper">
        <form method="post" id="acrb-email-form">
            <?php wp_nonce_field( 'acrb_save_email_settings', 'acrb_email_nonce' ); ?>

            <div class="acrb-admin-grid">
                
                <div class="acrb-admin-card">
                    <div class="acrb-admin-card-header">
                        <span class="dashicons dashicons-email-alt" style="color:#2271b1;"></span>
                        <div>
                            <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Sender & Branding', 'awesome-car-rental' ); ?></h3>
                        </div>
                    </div>
                    
                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Notification Recipient (Admin)', 'awesome-car-rental' ); ?></label>
                        <input type="email" name="acrb_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="acrb-admin-input">
                        <p class="description"><?php esc_html_e( 'New booking alerts will be sent here.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Brand Logo URL', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-admin-upload-wrap">
                            <input type="text" name="acrb_email_logo" id="acrb_logo_url" value="<?php echo esc_url( $logo_url ); ?>" class="acrb-admin-input">
                            <button type="button" class="button acrb-admin-btn-upload" id="acrb_upload_btn"><?php esc_html_e( 'Select Logo', 'awesome-car-rental' ); ?></button>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Email Accent Color', 'awesome-car-rental' ); ?></label>
                        <input type="color" name="acrb_email_accent_color" id="acrb_accent_picker" value="<?php echo esc_attr( $accent_color ); ?>" style="width:100px; height:40px; cursor:pointer;">
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Footer Signature Text', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_email_footer_text" id="acrb_footer_input" rows="4" class="acrb-admin-textarea"><?php echo esc_textarea( $footer_text ); ?></textarea>
                    </div>

                    <div class="acrb-admin-footer">
                        <button type="submit" name="acrb_save_emails" class="acrb-admin-btn-save">
                            <?php esc_html_e( 'Save Email Branding', 'awesome-car-rental' ); ?>
                        </button>
                    </div>
                </div>

                <div class="acrb-admin-preview-container">
                    <div class="acrb-admin-card">
                        <div class="acrb-admin-card-header">
                            <span class="dashicons dashicons-visibility" style="color:#2271b1;"></span>
                            <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Live Preview', 'awesome-car-rental' ); ?></h3>
                        </div>

                        <div class="acrb-admin-mock-email">
                            <div class="acrb-admin-mock-header" id="preview-header" style="background-color: <?php echo esc_attr( $accent_color ); ?>;">
                                <div id="preview-logo-container">
                                    <?php if ( $logo_url ) : ?>
                                        <img src="<?php echo esc_url( $logo_url ); ?>" id="preview-logo" style="max-height: 45px;">
                                    <?php else : ?>
                                        <span id="preview-logo-placeholder" style="color:#fff; font-weight:700; opacity:0.8;"><?php esc_html_e( 'YOUR LOGO', 'awesome-car-rental' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="acrb-admin-mock-body">
                                <p style="font-size:14px; color:#1d2327;"><strong><?php esc_html_e( 'Hello Customer,', 'awesome-car-rental' ); ?></strong></p>
                                <p style="font-size:13px; color:#646970; line-height:1.4;">
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
        });

        $('#acrb_footer_input').on('keyup', function(){
            $('#preview-footer').text($(this).val());
        });

        $('#acrb_logo_url').on('change keyup', function(){
            var url = $(this).val();
            if(url) {
                $('#preview-logo-container').html('<img src="'+url+'" id="preview-logo" style="max-height: 45px;">');
            } else {
                $('#preview-logo-container').html('<span style="color:#fff; font-weight:700; opacity:0.8;">YOUR LOGO</span>');
            }
        });
    });
    </script>
    <?php
}