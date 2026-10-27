<?php
/**
 * General Settings Tab - SaaS Style Localization & Rules (Modernized)
 * Location: admin/settings-general.php (or included file)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_general_view() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $currencies = array(
        'USD' => array( 'name' => 'US Dollar',       'symbol' => '$' ),
        'EUR' => array( 'name' => 'Euro',            'symbol' => '€' ),
        'GBP' => array( 'name' => 'British Pound',   'symbol' => '£' ),
        'BDT' => array( 'name' => 'Bangladeshi Taka', 'symbol' => '৳' ),
        'INR' => array( 'name' => 'Indian Rupee',    'symbol' => '₹' ),
        'JPY' => array( 'name' => 'Japanese Yen',    'symbol' => '¥' ),
        'CAD' => array( 'name' => 'Canadian Dollar', 'symbol' => 'CA$' ),
        'AED' => array( 'name' => 'UAE Dirham',      'symbol' => 'د.إ' ),
    );

    // Handle Form Submission
    if ( isset( $_POST['acrb_save_general'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_general_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_general_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $acrb_nonce, 'acrb_save_general_settings' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security check failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            $selected_code = isset( $_POST['acrb_currency_code'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_currency_code'] ) ) : 'GBP';
            update_option( 'acrb_currency_code', $selected_code );
            update_option( 'acrb_currency', $currencies[ $selected_code ]['symbol'] ?? '£' );

            $fields = array( 'acrb_rental_unit', 'acrb_currency_pos', 'acrb_pickup_start', 'acrb_pickup_end' );
            foreach ( $fields as $field ) {
                if ( isset( $_POST[ $field ] ) ) {
                    update_option( $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
                }
            }

            update_option( 'acrb_min_rent_days', absint( $_POST['acrb_min_rent_days'] ?? 1 ) );
            update_option( 'acrb_buffer_time', absint( $_POST['acrb_buffer_time'] ?? 2 ) );
            update_option( 'acrb_min_age', absint( $_POST['acrb_min_age'] ?? 21 ) );
            update_option( 'acrb_weekend_rental', isset( $_POST['acrb_weekend_rental'] ) ? 'yes' : 'no' );

            // Clear dashboard transient caches
            delete_transient( 'acrb_dashboard_revenue' );

            echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Global configuration updated.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    // Fetch Values
    $unit      = get_option( 'acrb_rental_unit', 'day' );
    $curr_code = get_option( 'acrb_currency_code', 'GBP' );
    $curr_pos  = get_option( 'acrb_currency_pos', 'left' );
    $min_days  = get_option( 'acrb_min_rent_days', 1 );
    $buffer    = get_option( 'acrb_buffer_time', 2 );
    $min_age   = get_option( 'acrb_min_age', 21 );
    $p_start   = get_option( 'acrb_pickup_start', '08:00' );
    $p_end     = get_option( 'acrb_pickup_end', '20:00' );
    $weekend   = get_option( 'acrb_weekend_rental', 'yes' );
    ?>

    <style>
        /* Modern SaaS Layout & Form Theme */
        .acrb-admin-wrapper { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-admin-wrapper *, .acrb-admin-wrapper *::before, .acrb-admin-wrapper *::after { box-sizing: border-box; }

        .acrb-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; }
        
        .acrb-admin-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-admin-card-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }
        .acrb-admin-card-header .dashicons { font-size: 22px; width: 22px; height: 22px; color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-admin-card-title { margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .acrb-admin-card-subtitle { margin: 0; font-size: 13px; color: #64748b; }
        
        .acrb-admin-field-group { margin-bottom: 20px; }
        .acrb-admin-label { display: block; font-weight: 600; margin-bottom: 8px; color: #334155; font-size: 13px; }
        .acrb-admin-row { display: flex; gap: 16px; }
        .acrb-admin-row .acrb-admin-field-group { flex: 1; }
        
        .acrb-admin-input, .acrb-admin-select { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; height: 42px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-admin-input:hover, .acrb-admin-select:hover { border-color: #94a3b8; }
        .acrb-admin-input:focus, .acrb-admin-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }
        
        .acrb-admin-checkbox-wrap { background: #f8fafc; padding: 16px 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; gap: 14px; align-items: center; cursor: pointer; transition: all 0.2s ease; }
        .acrb-admin-checkbox-wrap:hover { border-color: #cbd5e1; background: #fff; }
        .acrb-admin-checkbox-wrap input[type="checkbox"] { width: 18px; height: 18px; accent-color: #3b82f6; cursor: pointer; }
        
        .acrb-admin-footer { margin-top: 24px; padding: 20px 28px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; display: flex; justify-content: flex-end; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-admin-btn-save { background: #3b82f6; color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; height: 42px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-admin-btn-save:hover { background: #2563eb; }

        @media(max-width: 900px) {
            .acrb-admin-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-admin-wrapper">
        <form method="post">
            <?php wp_nonce_field( 'acrb_save_general_settings', 'acrb_general_nonce' ); ?>
            
            <div class="acrb-admin-grid">
                <!-- Localization & Policy Card -->
                <div class="acrb-admin-card">
                    <div class="acrb-admin-card-header">
                        <span class="dashicons dashicons-admin-site"></span>
                        <div>
                            <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Localization & Policy', 'awesome-car-rental' ); ?></h3>
                            <p class="acrb-admin-card-subtitle"><?php esc_html_e( 'Currency and core pricing logic.', 'awesome-car-rental' ); ?></p>
                        </div>
                    </div>

                    <div class="acrb-admin-row">
                        <div class="acrb-admin-field-group">
                            <label class="acrb-admin-label"><?php esc_html_e( 'Currency', 'awesome-car-rental' ); ?></label>
                            <select name="acrb_currency_code" class="acrb-admin-select">
                                <?php foreach ( $currencies as $code => $data ) : ?>
                                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $curr_code, $code ); ?>>
                                        <?php echo esc_html( $data['symbol'] . ' - ' . $data['name'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="acrb-admin-field-group">
                            <label class="acrb-admin-label"><?php esc_html_e( 'Symbol Position', 'awesome-car-rental' ); ?></label>
                            <select name="acrb_currency_pos" class="acrb-admin-select">
                                <option value="left" <?php selected( $curr_pos, 'left' ); ?>>
                                    <?php 
                                    /* translators: %s: Currency symbol, e.g., £ */
                                    printf( esc_html__( 'Before (%s100)', 'awesome-car-rental' ), '£' ); 
                                    ?>
                                </option>
                                <option value="right" <?php selected( $curr_pos, 'right' ); ?>>
                                    <?php 
                                    /* translators: %s: Currency symbol, e.g., £ */
                                    printf( esc_html__( 'After (100%s)', 'awesome-car-rental' ), '£' ); 
                                    ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Base Pricing Unit', 'awesome-car-rental' ); ?></label>
                        <select name="acrb_rental_unit" class="acrb-admin-select">
                            <option value="day" <?php selected( $unit, 'day' ); ?>><?php esc_html_e( 'Daily (Fixed 24h cycles)', 'awesome-car-rental' ); ?></option>
                            <option value="hour" <?php selected( $unit, 'hour' ); ?>><?php esc_html_e( 'Hourly (Flexible duration)', 'awesome-car-rental' ); ?></option>
                        </select>
                    </div>

                    <div class="acrb-admin-row">
                        <div class="acrb-admin-field-group">
                            <label class="acrb-admin-label">
                            <?php 
                            /* translators: %s: Time unit (e.g. Day, Hour). */
                            printf( esc_html__( 'Min. %ss', 'awesome-car-rental' ), esc_html( ucfirst( $unit ) ) ); 
                            ?>
                            </label>
                            <input type="number" name="acrb_min_rent_days" value="<?php echo esc_attr( $min_days ); ?>" class="acrb-admin-input">
                        </div>
                        <div class="acrb-admin-field-group">
                            <label class="acrb-admin-label"><?php esc_html_e( 'Min. Driver Age', 'awesome-car-rental' ); ?></label>
                            <input type="number" name="acrb_min_age" value="<?php echo esc_attr( $min_age ); ?>" class="acrb-admin-input">
                        </div>
                    </div>
                </div>

                <!-- Service Window Card -->
                <div class="acrb-admin-card">
                    <div class="acrb-admin-card-header">
                        <span class="dashicons dashicons-clock"></span>
                        <div>
                            <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Service Window', 'awesome-car-rental' ); ?></h3>
                            <p class="acrb-admin-card-subtitle"><?php esc_html_e( 'Pickup times and preparation rules.', 'awesome-car-rental' ); ?></p>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Daily Operation Hours', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-admin-row" style="align-items:center;">
                            <input type="time" name="acrb_pickup_start" value="<?php echo esc_attr( $p_start ); ?>" class="acrb-admin-input">
                            <span style="color:#64748b; font-weight:500;"><?php esc_html_e( 'to', 'awesome-car-rental' ); ?></span>
                            <input type="time" name="acrb_pickup_end" value="<?php echo esc_attr( $p_end ); ?>" class="acrb-admin-input">
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Preparation Buffer (Hours)', 'awesome-car-rental' ); ?></label>
                        <input type="number" name="acrb_buffer_time" value="<?php echo esc_attr( $buffer ); ?>" class="acrb-admin-input">
                        <p class="description" style="margin-top:6px; font-size:12px; color:#64748b;"><?php esc_html_e( 'Rest period between bookings.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <label class="acrb-admin-checkbox-wrap">
                        <input type="checkbox" name="acrb_weekend_rental" value="yes" <?php checked( $weekend, 'yes' ); ?>> 
                        <div>
                            <span class="acrb-admin-label" style="margin:0;"><?php esc_html_e( 'Enable Weekend Operations', 'awesome-car-rental' ); ?></span>
                            <span style="display:block; font-size:12px; color:#64748b; margin-top:2px;"><?php esc_html_e( 'Allow pickups on Sat/Sun.', 'awesome-car-rental' ); ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="acrb-admin-footer">
                <button type="submit" name="acrb_save_general" class="acrb-admin-btn-save">
                    <?php esc_html_e( 'Save Changes', 'awesome-car-rental' ); ?>
                </button>
            </div>
        </form>
    </div>
    <?php
}