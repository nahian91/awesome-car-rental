<?php
/**
 * General Settings Tab - SaaS Style Localization & Rules (Modernized)
 * Location: admin/settings/acrb-settings-general.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_settings_general_view() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $currencies = array(
        'USD' => array( 'name' => 'US Dollar',        'symbol' => '$' ),
        'EUR' => array( 'name' => 'Euro',             'symbol' => '€' ),
        'GBP' => array( 'name' => 'British Pound',    'symbol' => '£' ),
        'BDT' => array( 'name' => 'Bangladeshi Taka', 'symbol' => '৳' ),
        'INR' => array( 'name' => 'Indian Rupee',     'symbol' => '₹' ),
        'JPY' => array( 'name' => 'Japanese Yen',     'symbol' => '¥' ),
        'CAD' => array( 'name' => 'Canadian Dollar',  'symbol' => 'CA$' ),
        'AED' => array( 'name' => 'UAE Dirham',       'symbol' => 'د.إ' ),
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
        }
    }

    // Fetch Current Saved Values
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
                            <div class="acrb-select-wrap">
                                <select name="acrb_currency_code" class="acrb-admin-select">
                                    <?php foreach ( $currencies as $code => $data ) : ?>
                                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $curr_code, $code ); ?>>
                                            <?php echo esc_html( $data['symbol'] . ' - ' . $data['name'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="acrb-select-chevron">
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div class="acrb-admin-field-group">
                            <label class="acrb-admin-label"><?php esc_html_e( 'Symbol Position', 'awesome-car-rental' ); ?></label>
                            <div class="acrb-select-wrap">
                                <select name="acrb_currency_pos" class="acrb-admin-select">
                                    <option value="left" <?php selected( $curr_pos, 'left' ); ?>>
                                        <?php 
                                        /* translators: %s: Currency symbol, e.g., £ */
                                        printf( esc_html__( 'Before (%s100)', 'awesome-car-rental' ), esc_html( $currencies[ $curr_code ]['symbol'] ?? '£' ) ); 
                                        ?>
                                    </option>
                                    <option value="right" <?php selected( $curr_pos, 'right' ); ?>>
                                        <?php 
                                        /* translators: %s: Currency symbol, e.g., £ */
                                        printf( esc_html__( 'After (100%s)', 'awesome-car-rental' ), esc_html( $currencies[ $curr_code ]['symbol'] ?? '£' ) ); 
                                        ?>
                                    </option>
                                </select>
                                <span class="acrb-select-chevron">
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Base Pricing Unit', 'awesome-car-rental' ); ?></label>
                        <div class="acrb-select-wrap">
                            <select name="acrb_rental_unit" class="acrb-admin-select">
                                <option value="day" <?php selected( $unit, 'day' ); ?>><?php esc_html_e( 'Daily (Fixed 24h cycles)', 'awesome-car-rental' ); ?></option>
                                <option value="hour" <?php selected( $unit, 'hour' ); ?>><?php esc_html_e( 'Hourly (Flexible duration)', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="acrb-select-chevron">
                                <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="acrb-admin-row">
                        <div class="acrb-admin-field-group">
                            <label class="acrb-admin-label">
                                <?php 
                                /* translators: %s: Time unit (e.g. Day, Hour). */
                                printf( esc_html__( 'Min. %ss', 'awesome-car-rental' ), esc_html( ucfirst( $unit ) ) ); 
                                ?>
                            </label>
                            <input type="number" name="acrb_min_rent_days" value="<?php echo esc_attr( (string) $min_days ); ?>" class="acrb-admin-input" min="1">
                        </div>
                        <div class="acrb-admin-field-group">
                            <label class="acrb-admin-label"><?php esc_html_e( 'Min. Driver Age', 'awesome-car-rental' ); ?></label>
                            <input type="number" name="acrb_min_age" value="<?php echo esc_attr( (string) $min_age ); ?>" class="acrb-admin-input" min="18">
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
                            <span style="color:#64748b; font-weight:600; font-size:13px;"><?php esc_html_e( 'to', 'awesome-car-rental' ); ?></span>
                            <input type="time" name="acrb_pickup_end" value="<?php echo esc_attr( $p_end ); ?>" class="acrb-admin-input">
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Preparation Buffer (Hours)', 'awesome-car-rental' ); ?></label>
                        <input type="number" name="acrb_buffer_time" value="<?php echo esc_attr( (string) $buffer ); ?>" class="acrb-admin-input" min="0">
                        <p class="description" style="margin-top:6px; font-size:12px; color:#64748b;"><?php esc_html_e( 'Buffer period required between consecutive bookings for cleaning and maintenance.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <label class="acrb-admin-checkbox-wrap">
                        <input type="checkbox" name="acrb_weekend_rental" value="yes" <?php checked( $weekend, 'yes' ); ?>> 
                        <div>
                            <span class="acrb-admin-label" style="margin:0; font-size:13.5px;"><?php esc_html_e( 'Enable Weekend Operations', 'awesome-car-rental' ); ?></span>
                            <span style="display:block; font-size:12px; color:#64748b; margin-top:2px;"><?php esc_html_e( 'Allow customer pickups and return operations on Saturday and Sunday.', 'awesome-car-rental' ); ?></span>
                        </div>
                    </label>
                </div>

            </div>

            <div class="acrb-admin-footer">
                <button type="submit" name="acrb_save_general" class="acrb-admin-btn-save">
                    <span class="dashicons dashicons-saved" style="font-size:17px; width:17px; height:17px;"></span>
                    <span><?php esc_html_e( 'Save Changes', 'awesome-car-rental' ); ?></span>
                </button>
            </div>
        </form>
    </div>
    <?php
}