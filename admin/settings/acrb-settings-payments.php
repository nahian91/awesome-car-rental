<?php
/**
 * Payments Tab - Toggle-based Gateway Configuration (SaaS Modernized)
 * Location: admin/settings-payments.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_payments_view() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // 1. SAVE LOGIC
    if ( isset( $_POST['acrb_save_payments'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_payments_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_payments_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $acrb_nonce, 'acrb_save_payments_action' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security check failed. Please try again.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            // Toggle Gateways
            $gateways = array( 'acrb_enable_pay_later', 'acrb_enable_bank', 'acrb_enable_cash' );
            foreach ( $gateways as $gateway ) {
                update_option( $gateway, isset( $_POST[ $gateway ] ) ? 'yes' : 'no' );
            }

            // Unslash and Validate Textareas
            $bank_details = isset( $_POST['acrb_bank_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acrb_bank_details'] ) ) : '';
            $cash_instr   = isset( $_POST['acrb_cash_instructions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acrb_cash_instructions'] ) ) : '';

            update_option( 'acrb_bank_details', $bank_details );
            update_option( 'acrb_cash_instructions', $cash_instr );

            // Clear dashboard transient caches
            delete_transient( 'acrb_dashboard_revenue' );

            echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Payment gateways updated.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    // 2. DATA FETCHING
    $later_on  = get_option( 'acrb_enable_pay_later', 'no' );
    $bank_on   = get_option( 'acrb_enable_bank', 'no' );
    $cash_on   = get_option( 'acrb_enable_cash', 'no' );
    $bank_info = get_option( 'acrb_bank_details', '' );
    $cash_info = get_option( 'acrb_cash_instructions', '' );
    ?>

    <style>
        /* Modern SaaS Layout & Payments Theme */
        .acrb-payments-container { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-payments-container *, .acrb-payments-container *::before, .acrb-payments-container *::after { box-sizing: border-box; }

        .acrb-settings-header { margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }
        .acrb-tab-title { font-size: 15px; font-weight: 600; margin: 0 0 4px 0; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .acrb-tab-desc { margin: 0; font-size: 13px; color: #64748b; }

        .acrb-payments-stack { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
        
        .acrb-method-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-method-card.is-active { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08); background: #f8fafc; }
        
        .acrb-method-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .acrb-method-info { display: flex; align-items: center; gap: 16px; }
        
        .acrb-method-icon { width: 44px; height: 44px; min-width: 44px; background: #eff6ff; color: #3b82f6; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .acrb-method-icon .dashicons { font-size: 22px; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-method-title h4 { margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: #0f172a; }
        .acrb-method-title p { margin: 0; font-size: 13px; color: #64748b; }

        /* Modern Toggle Switch */
        .acrb-toggle { position: relative; display: inline-block; width: 50px; height: 26px; flex-shrink: 0; }
        .acrb-toggle input { opacity: 0; width: 0; height: 0; }
        .acrb-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 26px; }
        .acrb-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .acrb-toggle input:checked + .acrb-slider { background-color: #3b82f6; }
        .acrb-toggle input:focus + .acrb-slider { box-shadow: 0 0 1px #3b82f6; }
        .acrb-toggle input:checked + .acrb-slider:before { transform: translateX(24px); }

        .acrb-method-content { margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: none; }
        .acrb-method-card.is-active .acrb-method-content { display: block; }

        .acrb-label { display: block; font-weight: 600; margin-bottom: 8px; color: #334155; font-size: 13px; }
        .acrb-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 14px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; resize: vertical; }
        .acrb-textarea:hover { border-color: #94a3b8; }
        .acrb-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }

        .acrb-footer-action { margin-top: 24px; padding: 20px 28px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; display: flex; justify-content: flex-end; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-btn-save { background: #3b82f6; color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; height: 42px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-btn-save:hover { background: #2563eb; }

        @media(max-width: 768px) {
            .acrb-method-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .acrb-toggle { align-self: flex-end; margin-top: -36px; }
        }
    </style>

    <div class="acrb-payments-container">
        <header class="acrb-settings-header">
            <h2 class="acrb-tab-title"><?php esc_html_e( 'Payment Gateways', 'awesome-car-rental' ); ?></h2>
            <p class="acrb-tab-desc"><?php esc_html_e( 'Enable and configure how your customers can pay for their rentals.', 'awesome-car-rental' ); ?></p>
        </header>

        <form method="post">
            <?php wp_nonce_field( 'acrb_save_payments_action', 'acrb_payments_nonce' ); ?>

            <div class="acrb-payments-stack">
                
                <!-- Book Now, Pay Later -->
                <div class="acrb-method-card <?php echo ( 'yes' === $later_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Book Now, Pay Later', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Reserve the car immediately; payment is handled offline.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle">
                            <input type="checkbox" name="acrb_enable_pay_later" value="yes" <?php checked( $later_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Direct Bank Transfer -->
                <div class="acrb-method-card <?php echo ( 'yes' === $bank_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon"><span class="dashicons dashicons-bank"></span></div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Direct Bank Transfer', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Show account details for manual wire transfers.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle">
                            <input type="checkbox" name="acrb_enable_bank" value="yes" <?php checked( $bank_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-slider"></span>
                        </label>
                    </div>
                    <div class="acrb-method-content">
                        <label class="acrb-label"><?php esc_html_e( 'Bank Account Details & Payment Instructions', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_bank_details" rows="5" class="acrb-textarea" placeholder="<?php esc_attr_e( "Account Name:\nIBAN/Account Number:\nBank Name:\nSWIFT Code:", 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $bank_info ); ?></textarea>
                    </div>
                </div>

                <!-- Cash on Pickup -->
                <div class="acrb-method-card <?php echo ( 'yes' === $cash_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon"><span class="dashicons dashicons-money-alt"></span></div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Cash on Pickup', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Accept physical payments at your local rental office.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle">
                            <input type="checkbox" name="acrb_enable_cash" value="yes" <?php checked( $cash_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-slider"></span>
                        </label>
                    </div>
                    <div class="acrb-method-content">
                        <label class="acrb-label"><?php esc_html_e( 'Cash Instructions', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_cash_instructions" rows="4" class="acrb-textarea" placeholder="<?php esc_attr_e( 'e.g. Please visit our main desk at Terminal 1. We accept USD and EUR.', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $cash_info ); ?></textarea>
                    </div>
                </div>

            </div>

            <div class="acrb-footer-action">
                <button type="submit" name="acrb_save_payments" class="acrb-btn-save">
                    <?php esc_html_e( 'Save Changes', 'awesome-car-rental' ); ?>
                </button>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.acrb-gateway-toggle').on('change', function() {
            var $card = $(this).closest('.acrb-method-card');
            if ($(this).is(':checked')) {
                $card.addClass('is-active');
            } else {
                $card.removeClass('is-active');
            }
        });
    });
    </script>
    <?php
}