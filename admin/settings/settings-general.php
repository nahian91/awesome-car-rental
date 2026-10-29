<?php
/**
 * General Settings Tab - SaaS Style Localization & Rules (Modernized)
 * Location: admin/settings/acrb-settings-general.php
 *
 * @package Awesome_Car_Rental
 * @since   4.8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_general_view(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $acrb_page_slug = 'awesome_car_rental';
    $acrb_base_url  = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=settings&sub=general' );

    $acrb_currencies = array(
        'USD' => array( 'name' => 'US Dollar',        'symbol' => '$' ),
        'EUR' => array( 'name' => 'Euro',             'symbol' => '€' ),
        'GBP' => array( 'name' => 'British Pound',    'symbol' => '£' ),
        'BDT' => array( 'name' => 'Bangladeshi Taka', 'symbol' => '৳' ),
        'INR' => array( 'name' => 'Indian Rupee',     'symbol' => '₹' ),
        'JPY' => array( 'name' => 'Japanese Yen',     'symbol' => '¥' ),
        'CAD' => array( 'name' => 'Canadian Dollar',  'symbol' => 'CA$' ),
        'AED' => array( 'name' => 'UAE Dirham',       'symbol' => 'د.إ' ),
    );

    // 1. Process Form Submission with Safe Redirect.
    if ( isset( $_POST['acrb_save_general'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_general_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_general_nonce'] ) ) : '';

        if ( wp_verify_nonce( $acrb_nonce, 'acrb_save_general_settings' ) ) {
            // Save Currency Code & Symbol
            $acrb_selected_code = isset( $_POST['acrb_currency_code'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_currency_code'] ) ) : 'USD';
            update_option( 'acrb_currency_code', $acrb_selected_code );
            update_option( 'acrb_currency', $acrb_currencies[ $acrb_selected_code ]['symbol'] ?? '$' );

            // Save text/select fields safely
            $acrb_string_fields = array( 'acrb_rental_unit', 'acrb_currency_pos', 'acrb_pickup_start', 'acrb_pickup_end' );
            foreach ( $acrb_string_fields as $acrb_field ) {
                if ( isset( $_POST[ $acrb_field ] ) ) {
                    update_option( $acrb_field, sanitize_text_field( wp_unslash( $_POST[ $acrb_field ] ) ) );
                }
            }

            // Save numeric and checkbox rules safely
            update_option( 'acrb_min_rent_days', max( 1, absint( $_POST['acrb_min_rent_days'] ?? 1 ) ) );
            update_option( 'acrb_buffer_time', absint( $_POST['acrb_buffer_time'] ?? 2 ) );
            update_option( 'acrb_min_age', max( 18, absint( $_POST['acrb_min_age'] ?? 21 ) ) );
            update_option( 'acrb_weekend_rental', isset( $_POST['acrb_weekend_rental'] ) ? 'yes' : 'no' );

            delete_transient( 'acrb_dashboard_revenue' );

            $acrb_redirect_url = add_query_arg( 'updated', '1', $acrb_base_url );

            if ( headers_sent() ) {
                echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $acrb_redirect_url ) ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $acrb_redirect_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $acrb_redirect_url );
            exit;
        }
    }

    // 2. Fetch Stored Values.
    $acrb_unit      = (string) get_option( 'acrb_rental_unit', 'day' );
    $acrb_curr_code = (string) get_option( 'acrb_currency_code', 'USD' );
    $acrb_curr_pos  = (string) get_option( 'acrb_currency_pos', 'left' );
    $acrb_min_days  = absint( get_option( 'acrb_min_rent_days', 1 ) );
    $acrb_buffer    = absint( get_option( 'acrb_buffer_time', 2 ) );
    $acrb_min_age   = absint( get_option( 'acrb_min_age', 21 ) );
    $acrb_p_start   = (string) get_option( 'acrb_pickup_start', '08:00' );
    $acrb_p_end     = (string) get_option( 'acrb_pickup_end', '20:00' );
    $acrb_weekend   = (string) get_option( 'acrb_weekend_rental', 'yes' );
    $acrb_curr_sym  = $acrb_currencies[ $acrb_curr_code ]['symbol'] ?? '$';
    ?>

    <style>
        .acrb-general-settings {
            width: 100%;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }
        .acrb-general-settings *,
        .acrb-general-settings *::before,
        .acrb-general-settings *::after {
            box-sizing: border-box;
        }

        /* Responsive 2-Column Section Grid */
        .acrb-general-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            align-items: start;
            margin-bottom: 24px;
        }

        /* Section Cards */
        .acrb-general-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            padding: 24px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .acrb-general-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 14px;
        }
        .acrb-general-card-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            background: #eef2ff;
            color: #4f46e5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
        }
        .acrb-general-card-icon .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        .acrb-general-card-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
        }
        .acrb-general-card-subtitle {
            margin: 2px 0 0;
            font-size: 12.5px;
            color: #64748b;
        }

        /* Field Layout */
        .acrb-general-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }
        .acrb-general-field {
            display: flex;
            flex-direction: column;
            margin-bottom: 18px;
        }
        .acrb-general-field:last-child {
            margin-bottom: 0;
        }
        .acrb-general-label {
            display: block;
            font-weight: 700;
            color: #1e293b;
            font-size: 13.5px;
            margin-bottom: 6px;
        }
        .acrb-general-hint {
            margin: 6px 0 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Inputs & Selects */
        .acrb-general-input,
        .acrb-general-select {
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
        .acrb-general-input:hover,
        .acrb-general-select:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-general-input:focus,
        .acrb-general-select:focus {
            background: #ffffff !important;
            border-color: #4f46e5 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        }

        /* Select Chevron Container */
        .acrb-general-select-wrap {
            position: relative;
            width: 100%;
        }
        .acrb-general-select-wrap select {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 36px !important;
            cursor: pointer;
        }
        .acrb-general-select-arrow {
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

        /* Time Range Input Group */
        .acrb-time-range-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .acrb-time-range-sep {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            flex-shrink: 0;
        }

        /* Checkbox Setting Card */
        .acrb-general-checkbox-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
            margin-top: 14px;
        }
        .acrb-general-checkbox-card:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }
        .acrb-general-checkbox-card input[type="checkbox"] {
            margin-top: 2px;
            width: 17px;
            height: 17px;
            accent-color: #4f46e5;
            cursor: pointer;
            flex-shrink: 0;
        }
        .acrb-checkbox-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .acrb-checkbox-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .acrb-checkbox-desc {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Footer Submit Action */
        .acrb-general-actions {
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
        }
        .acrb-general-btn-save {
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
        .acrb-general-btn-save:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }
        .acrb-general-btn-save .dashicons {
            font-size: 17px;
            width: 17px;
            height: 17px;
        }

        @media (max-width: 900px) {
            .acrb-general-grid {
                grid-template-columns: 1fr;
            }
            .acrb-general-row {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }
    </style>

    <div class="acrb-general-settings">
        <form method="post">
            <?php wp_nonce_field( 'acrb_save_general_settings', 'acrb_general_nonce' ); ?>
            
            <div class="acrb-general-grid">
                
                <!-- Localization & Policy Card -->
                <div class="acrb-general-card">
                    <div class="acrb-general-card-header">
                        <div class="acrb-general-card-icon">
                            <span class="dashicons dashicons-admin-site"></span>
                        </div>
                        <div>
                            <h3 class="acrb-general-card-title"><?php esc_html_e( 'Localization & Policy', 'awesome-car-rental' ); ?></h3>
                            <p class="acrb-general-card-subtitle"><?php esc_html_e( 'Currency format, driver requirements, and core pricing intervals.', 'awesome-car-rental' ); ?></p>
                        </div>
                    </div>

                    <div class="acrb-general-row">
                        <!-- Currency Selection -->
                        <div class="acrb-general-field">
                            <label class="acrb-general-label" for="acrb_currency_code"><?php esc_html_e( 'Base Currency', 'awesome-car-rental' ); ?></label>
                            <div class="acrb-general-select-wrap">
                                <select id="acrb_currency_code" name="acrb_currency_code" class="acrb-general-select">
                                    <?php foreach ( $acrb_currencies as $acrb_code => $acrb_data ) : ?>
                                        <option value="<?php echo esc_attr( $acrb_code ); ?>" data-symbol="<?php echo esc_attr( $acrb_data['symbol'] ); ?>" <?php selected( $acrb_curr_code, $acrb_code ); ?>>
                                            <?php echo esc_html( $acrb_data['symbol'] . ' - ' . $acrb_data['name'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="dashicons dashicons-arrow-down-alt2 acrb-general-select-arrow"></span>
                            </div>
                        </div>

                        <!-- Symbol Position -->
                        <div class="acrb-general-field">
                            <label class="acrb-general-label" for="acrb_currency_pos"><?php esc_html_e( 'Symbol Position', 'awesome-car-rental' ); ?></label>
                            <div class="acrb-general-select-wrap">
                                <select id="acrb_currency_pos" name="acrb_currency_pos" class="acrb-general-select">
                                    <option value="left" <?php selected( $acrb_curr_pos, 'left' ); ?>>
                                        <?php 
                                        /* translators: %s: Currency symbol, e.g., $ */
                                        printf( esc_html__( 'Before (%s100)', 'awesome-car-rental' ), esc_html( $acrb_curr_sym ) ); 
                                        ?>
                                    </option>
                                    <option value="right" <?php selected( $acrb_curr_pos, 'right' ); ?>>
                                        <?php 
                                        /* translators: %s: Currency symbol, e.g., $ */
                                        printf( esc_html__( 'After (100%s)', 'awesome-car-rental' ), esc_html( $acrb_curr_sym ) ); 
                                        ?>
                                    </option>
                                </select>
                                <span class="dashicons dashicons-arrow-down-alt2 acrb-general-select-arrow"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Base Pricing Unit -->
                    <div class="acrb-general-field">
                        <label class="acrb-general-label" for="acrb_rental_unit"><?php esc_html_e( 'Base Pricing Interval', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-general-select-wrap">
                            <select id="acrb_rental_unit" name="acrb_rental_unit" class="acrb-general-select">
                                <option value="day" <?php selected( $acrb_unit, 'day' ); ?>><?php esc_html_e( 'Daily (Fixed 24-hour cycles)', 'awesome-car-rental' ); ?></option>
                                <option value="hour" <?php selected( $acrb_unit, 'hour' ); ?>><?php esc_html_e( 'Hourly (Flexible duration)', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="dashicons dashicons-arrow-down-alt2 acrb-general-select-arrow"></span>
                        </div>
                        <p class="acrb-general-hint"><?php esc_html_e( 'Governs rate calculations across all fleet vehicles and booking calculation engines.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <div class="acrb-general-row" style="margin-top: 18px;">
                        <!-- Minimum Rental Cycle -->
                        <div class="acrb-general-field">
                            <label class="acrb-general-label" for="acrb_min_rent_days">
                                <span id="acrb_unit_label">
                                    <?php 
                                    /* translators: %s: Time unit (e.g. Days, Hours). */
                                    printf( esc_html__( 'Min. %ss', 'awesome-car-rental' ), esc_html( ucfirst( $acrb_unit ) ) ); 
                                    ?>
                                </span>
                            </label>
                            <input type="number" id="acrb_min_rent_days" name="acrb_min_rent_days" value="<?php echo esc_attr( (string) $acrb_min_days ); ?>" class="acrb-general-input" min="1">
                        </div>

                        <!-- Minimum Driver Age -->
                        <div class="acrb-general-field">
                            <label class="acrb-general-label" for="acrb_min_age"><?php esc_html_e( 'Min. Driver Age', 'awesome-car-rental' ); ?></label>
                            <input type="number" id="acrb_min_age" name="acrb_min_age" value="<?php echo esc_attr( (string) $acrb_min_age ); ?>" class="acrb-general-input" min="18">
                        </div>
                    </div>
                </div>

                <!-- Service Window & Fleet Rules Card -->
                <div class="acrb-general-card">
                    <div class="acrb-general-card-header">
                        <div class="acrb-general-card-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div>
                            <h3 class="acrb-general-card-title"><?php esc_html_e( 'Service Window & Operations', 'awesome-car-rental' ); ?></h3>
                            <p class="acrb-general-card-subtitle"><?php esc_html_e( 'Operating counter hours, turnaround buffers, and weekend policy.', 'awesome-car-rental' ); ?></p>
                        </div>
                    </div>

                    <!-- Daily Operation Hours -->
                    <div class="acrb-general-field">
                        <label class="acrb-general-label"><?php esc_html_e( 'Daily Counter Operation Hours', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-time-range-group">
                            <input type="time" name="acrb_pickup_start" value="<?php echo esc_attr( $acrb_p_start ); ?>" class="acrb-general-input" required>
                            <span class="acrb-time-range-sep"><?php esc_html_e( 'to', 'awesome-car-rental' ); ?></span>
                            <input type="time" name="acrb_pickup_end" value="<?php echo esc_attr( $acrb_p_end ); ?>" class="acrb-general-input" required>
                        </div>
                        <p class="acrb-general-hint"><?php esc_html_e( 'Customer booking datepickers will restrict pickup and dropoff slots to this operating window.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <!-- Turnaround Buffer -->
                    <div class="acrb-general-field">
                        <label class="acrb-general-label" for="acrb_buffer_time"><?php esc_html_e( 'Turnaround Preparation Buffer (Hours)', 'awesome-car-rental' ); ?></label>
                        <input type="number" id="acrb_buffer_time" name="acrb_buffer_time" value="<?php echo esc_attr( (string) $acrb_buffer ); ?>" class="acrb-general-input" min="0">
                        <p class="acrb-general-hint"><?php esc_html_e( 'Minimum buffer between consecutive bookings reserved for vehicle inspection, cleaning, and maintenance.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <!-- Weekend Operations Toggle -->
                    <label class="acrb-general-checkbox-card">
                        <input type="checkbox" name="acrb_weekend_rental" value="yes" <?php checked( $acrb_weekend, 'yes' ); ?>> 
                        <div class="acrb-checkbox-meta">
                            <span class="acrb-checkbox-title"><?php esc_html_e( 'Enable Weekend Operations', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-checkbox-desc"><?php esc_html_e( 'Allow customer pickups and return operations on Saturday and Sunday.', 'awesome-car-rental' ); ?></span>
                        </div>
                    </label>
                </div>

            </div>

            <!-- Action Controls -->
            <div class="acrb-general-actions">
                <button type="submit" name="acrb_save_general" class="acrb-general-btn-save">
                    <span class="dashicons dashicons-saved"></span>
                    <span><?php esc_html_e( 'Save General Settings', 'awesome-car-rental' ); ?></span>
                </button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const currSelect  = document.getElementById('acrb_currency_code');
        const posSelect   = document.getElementById('acrb_currency_pos');
        const unitSelect  = document.getElementById('acrb_rental_unit');
        const unitLabel   = document.getElementById('acrb_unit_label');

        function updateCurrencyPreview() {
            if (!currSelect || !posSelect) return;
            const selectedOpt = currSelect.options[currSelect.selectedIndex];
            const sym         = selectedOpt ? selectedOpt.getAttribute('data-symbol') : '$';

            const optBefore = posSelect.querySelector('option[value="left"]');
            const optAfter  = posSelect.querySelector('option[value="right"]');

            <?php /* translators: %s: Currency symbol preview, e.g., $ */ ?>
            if (optBefore) optBefore.textContent = <?php echo wp_json_encode( __( 'Before (%s100)', 'awesome-car-rental' ) ); ?>.replace('%s', sym);
            <?php /* translators: %s: Currency symbol preview, e.g., $ */ ?>
            if (optAfter)  optAfter.textContent  = <?php echo wp_json_encode( __( 'After (100%s)', 'awesome-car-rental' ) ); ?>.replace('%s', sym);
        }

        function updateUnitLabel() {
            if (!unitSelect || !unitLabel) return;
            const val = unitSelect.value === 'hour' ? 'Hour' : 'Day';
            <?php /* translators: %s: Time unit (e.g. Days, Hours). */ ?>
            unitLabel.textContent = <?php echo wp_json_encode( __( 'Min. %ss', 'awesome-car-rental' ) ); ?>.replace('%s', val);
        }

        if (currSelect) currSelect.addEventListener('change', updateCurrencyPreview);
        if (unitSelect) unitSelect.addEventListener('change', updateUnitLabel);
    });
    </script>
    <?php
}