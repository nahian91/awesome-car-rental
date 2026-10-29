<?php
/**
 * Payments Tab - Toggle-based Gateway Configuration (SaaS Modernized)
 * Location: admin/settings/acrb-settings-payments.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.2
 */

declare( strict_types=1 );

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
        }
    }

    // 2. DATA FETCHING
    $later_on  = get_option( 'acrb_enable_pay_later', 'no' );
    $bank_on   = get_option( 'acrb_enable_bank', 'no' );
    $cash_on   = get_option( 'acrb_enable_cash', 'no' );
    $bank_info = get_option( 'acrb_bank_details', '' );
    $cash_info = get_option( 'acrb_cash_instructions', '' );
    ?>

    <div class="acrb-payments-container">
        <header class="acrb-settings-header">
            <h3 class="acrb-tab-title"><?php esc_html_e( 'Payment Gateways', 'awesome-car-rental' ); ?></h3>
            <p class="acrb-tab-desc"><?php esc_html_e( 'Enable and configure settlement options for customer reservations.', 'awesome-car-rental' ); ?></p>
        </header>

        <form method="post">
            <?php wp_nonce_field( 'acrb_save_payments_action', 'acrb_payments_nonce' ); ?>

            <div class="acrb-payments-stack">
                
                <!-- 1. Book Now, Pay Later -->
                <div class="acrb-method-card <?php echo ( 'yes' === $later_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Book Now, Pay Later', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Confirm reservations instantly without upfront online payment.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle">
                            <input type="checkbox" name="acrb_enable_pay_later" value="yes" <?php checked( $later_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- 2. Direct Bank Transfer -->
                <div class="acrb-method-card <?php echo ( 'yes' === $bank_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon"><span class="dashicons dashicons-bank"></span></div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Direct Bank Transfer (BACS)', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Instruct customers to wire payments directly to your bank account.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle">
                            <input type="checkbox" name="acrb_enable_bank" value="yes" <?php checked( $bank_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-slider"></span>
                        </label>
                    </div>
                    <div class="acrb-method-content" style="<?php echo ( 'yes' === $bank_on ) ? 'display:block;' : 'display:none;'; ?>">
                        <label class="acrb-label"><?php esc_html_e( 'Bank Account Details & Payment Instructions', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_bank_details" rows="5" class="acrb-textarea" placeholder="<?php esc_attr_e( "Account Name:\nIBAN / Account Number:\nBank Name:\nBIC / SWIFT Code:", 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $bank_info ); ?></textarea>
                    </div>
                </div>

                <!-- 3. Cash on Pickup -->
                <div class="acrb-method-card <?php echo ( 'yes' === $cash_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon"><span class="dashicons dashicons-money-alt"></span></div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Cash on Pickup', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Collect physical cash or counter card payments at vehicle handover.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle">
                            <input type="checkbox" name="acrb_enable_cash" value="yes" <?php checked( $cash_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-slider"></span>
                        </label>
                    </div>
                    <div class="acrb-method-content" style="<?php echo ( 'yes' === $cash_on ) ? 'display:block;' : 'display:none;'; ?>">
                        <label class="acrb-label"><?php esc_html_e( 'Customer Instructions for Cash Payment', 'awesome-car-rental' ); ?></label>
                        <textarea name="acrb_cash_instructions" rows="4" class="acrb-textarea" placeholder="<?php esc_attr_e( 'e.g. Please bring exact cash or a debit/credit card to our rental counter at Terminal 2.', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $cash_info ); ?></textarea>
                    </div>
                </div>

            </div>

            <div class="acrb-footer-action">
                <button type="submit" name="acrb_save_payments" class="acrb-btn-save">
                    <span class="dashicons dashicons-saved" style="font-size:17px; width:17px; height:17px;"></span>
                    <span><?php esc_html_e( 'Save Gateway Settings', 'awesome-car-rental' ); ?></span>
                </button>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.acrb-gateway-toggle').on('change', function() {
            var $card = $(this).closest('.acrb-method-card');
            var $content = $card.find('.acrb-method-content');

            if ($(this).is(':checked')) {
                $card.addClass('is-active');
                if ($content.length) {
                    $content.slideDown(200);
                }
            } else {
                $card.removeClass('is-active');
                if ($content.length) {
                    $content.slideUp(200);
                }
            }
        });
    });
    </script>
    <?php
}