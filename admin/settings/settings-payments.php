<?php
/**
 * Payments Tab - Toggle-based Gateway Configuration (SaaS Modernized)
 * Location: admin/settings/acrb-settings-payments.php
 *
 * @package Awesome_Car_Rental
 * @since   3.1.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_payments_view(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $page_slug = 'awesome_car_rental';
    $base_url  = admin_url( 'admin.php?page=' . $page_slug . '&tab=settings&sub=payments' );

    // 1. Process Form Save with Safe Redirect
    if ( isset( $_POST['acrb_save_payments'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_payments_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_payments_nonce'] ) ) : '';

        if ( wp_verify_nonce( $acrb_nonce, 'acrb_save_payments_action' ) ) {
            $gateways = array( 'acrb_enable_pay_later', 'acrb_enable_bank', 'acrb_enable_cash' );
            foreach ( $gateways as $gateway ) {
                update_option( $gateway, isset( $_POST[ $gateway ] ) ? 'yes' : 'no' );
            }

            $bank_details = isset( $_POST['acrb_bank_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acrb_bank_details'] ) ) : '';
            $cash_instr   = isset( $_POST['acrb_cash_instructions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acrb_cash_instructions'] ) ) : '';

            update_option( 'acrb_bank_details', $bank_details );
            update_option( 'acrb_cash_instructions', $cash_instr );

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

    // 2. Data Fetching
    $later_on  = (string) get_option( 'acrb_enable_pay_later', 'no' );
    $bank_on   = (string) get_option( 'acrb_enable_bank', 'no' );
    $cash_on   = (string) get_option( 'acrb_enable_cash', 'no' );
    $bank_info = (string) get_option( 'acrb_bank_details', '' );
    $cash_info = (string) get_option( 'acrb_cash_instructions', '' );
    ?>

    <style>
        .acrb-payments-container {
            width: 100%;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }
        .acrb-payments-container *,
        .acrb-payments-container *::before,
        .acrb-payments-container *::after {
            box-sizing: border-box;
        }

        /* Section Header */
        .acrb-payments-header {
            margin-bottom: 22px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 14px;
        }
        .acrb-payments-title {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .acrb-payments-desc {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Gateway Stack */
        .acrb-payments-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }

        /* Method Card */
        .acrb-method-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 22px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
        }
        .acrb-method-card:hover {
            border-color: #cbd5e1;
        }
        .acrb-method-card.is-active {
            border-color: #4f46e5;
            box-shadow: 0 4px 16px -2px rgba(79, 70, 229, 0.08);
            background: #fafbff;
        }

        /* Method Header */
        .acrb-method-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .acrb-method-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-method-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgba(79, 70, 229, 0.12);
        }
        .acrb-method-icon .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        .acrb-method-title h4 {
            margin: 0 0 3px 0;
            font-size: 14.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .acrb-method-title p {
            margin: 0;
            font-size: 12.5px;
            color: #64748b;
        }

        /* Toggle Switch */
        .acrb-toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .acrb-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .acrb-toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #cbd5e1;
            transition: .25s ease;
            border-radius: 24px;
        }
        .acrb-toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .25s ease;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        input:checked + .acrb-toggle-slider {
            background-color: #4f46e5;
        }
        input:checked + .acrb-toggle-slider:before {
            transform: translateX(20px);
        }

        /* Expandable Content */
        .acrb-method-content {
            margin-top: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }
        .acrb-method-content.is-hidden {
            display: none;
        }
        .acrb-method-label {
            display: block;
            font-weight: 700;
            color: #1e293b;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .acrb-method-textarea {
            display: block;
            width: 100% !important;
            max-width: 100% !important;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            font-size: 13px !important;
            font-family: inherit !important;
            color: #1e293b !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
            line-height: 1.5 !important;
            resize: vertical;
        }
        .acrb-method-textarea:hover {
            border-color: #94a3b8 !important;
        }
        .acrb-method-textarea:focus {
            border-color: #4f46e5 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        }

        /* Submit Action Footer */
        .acrb-payments-footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
        }
        .acrb-payments-btn-save {
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
        .acrb-payments-btn-save:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }
        .acrb-payments-btn-save .dashicons {
            font-size: 17px;
            width: 17px;
            height: 17px;
        }

        @media (max-width: 680px) {
            .acrb-method-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .acrb-toggle-switch {
                align-self: flex-end;
            }
        }
    </style>

    <div class="acrb-payments-container">
        <header class="acrb-payments-header">
            <h3 class="acrb-payments-title"><?php esc_html_e( 'Payment Gateways & Settlement', 'awesome-car-rental' ); ?></h3>
            <p class="acrb-payments-desc"><?php esc_html_e( 'Enable and configure settlement options for customer reservations.', 'awesome-car-rental' ); ?></p>
        </header>

        <form method="post">
            <?php wp_nonce_field( 'acrb_save_payments_action', 'acrb_payments_nonce' ); ?>

            <div class="acrb-payments-stack">
                
                <!-- 1. Book Now, Pay Later -->
                <div class="acrb-method-card <?php echo ( 'yes' === $later_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Book Now, Pay Later', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Confirm reservations instantly without upfront online payment.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle-switch">
                            <input type="checkbox" name="acrb_enable_pay_later" value="yes" <?php checked( $later_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- 2. Direct Bank Transfer -->
                <div class="acrb-method-card <?php echo ( 'yes' === $bank_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon">
                                <span class="dashicons dashicons-bank"></span>
                            </div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Direct Bank Transfer (BACS)', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Instruct customers to wire payments directly to your bank account.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle-switch">
                            <input type="checkbox" name="acrb_enable_bank" value="yes" <?php checked( $bank_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="acrb-method-content <?php echo ( 'yes' === $bank_on ) ? '' : 'is-hidden'; ?>">
                        <label class="acrb-method-label" for="acrb_bank_details"><?php esc_html_e( 'Bank Account Details & Payment Instructions', 'awesome-car-rental' ); ?></label>
                        <textarea id="acrb_bank_details" name="acrb_bank_details" rows="5" class="acrb-method-textarea" placeholder="<?php esc_attr_e( "Account Name:\nIBAN / Account Number:\nBank Name:\nBIC / SWIFT Code:", 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $bank_info ); ?></textarea>
                    </div>
                </div>

                <!-- 3. Cash on Pickup -->
                <div class="acrb-method-card <?php echo ( 'yes' === $cash_on ) ? 'is-active' : ''; ?>">
                    <div class="acrb-method-header">
                        <div class="acrb-method-info">
                            <div class="acrb-method-icon">
                                <span class="dashicons dashicons-money-alt"></span>
                            </div>
                            <div class="acrb-method-title">
                                <h4><?php esc_html_e( 'Cash on Pickup', 'awesome-car-rental' ); ?></h4>
                                <p><?php esc_html_e( 'Collect physical cash or counter card payments at vehicle handover.', 'awesome-car-rental' ); ?></p>
                            </div>
                        </div>
                        <label class="acrb-toggle-switch">
                            <input type="checkbox" name="acrb_enable_cash" value="yes" <?php checked( $cash_on, 'yes' ); ?> class="acrb-gateway-toggle">
                            <span class="acrb-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="acrb-method-content <?php echo ( 'yes' === $cash_on ) ? '' : 'is-hidden'; ?>">
                        <label class="acrb-method-label" for="acrb_cash_instructions"><?php esc_html_e( 'Customer Instructions for Cash Payment', 'awesome-car-rental' ); ?></label>
                        <textarea id="acrb_cash_instructions" name="acrb_cash_instructions" rows="4" class="acrb-method-textarea" placeholder="<?php esc_attr_e( 'e.g. Please bring exact cash or a debit/credit card to our rental counter at Terminal 2.', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $cash_info ); ?></textarea>
                    </div>
                </div>

            </div>

            <div class="acrb-payments-footer">
                <button type="submit" name="acrb_save_payments" class="acrb-payments-btn-save">
                    <span class="dashicons dashicons-saved"></span>
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
                    $content.removeClass('is-hidden').hide().slideDown(200);
                }
            } else {
                $card.removeClass('is-active');
                if ($content.length) {
                    $content.slideUp(200, function() {
                        $(this).addClass('is-hidden');
                    });
                }
            }
        });
    });
    </script>
    <?php
}