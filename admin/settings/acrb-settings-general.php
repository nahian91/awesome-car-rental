<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * General Settings Tab - SaaS Style Localization & Rules
 */
function acrb_settings_general_view() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $currencies = [
        'USD' => [ 'name' => 'US Dollar',       'symbol' => '$' ],
        'EUR' => [ 'name' => 'Euro',            'symbol' => '€' ],
        'GBP' => [ 'name' => 'British Pound',   'symbol' => '£' ],
        'BDT' => [ 'name' => 'Bangladeshi Taka', 'symbol' => '৳' ],
        'INR' => [ 'name' => 'Indian Rupee',    'symbol' => '₹' ],
        'JPY' => [ 'name' => 'Japanese Yen',    'symbol' => '¥' ],
        'CAD' => [ 'name' => 'Canadian Dollar', 'symbol' => 'CA$' ],
        'AED' => [ 'name' => 'UAE Dirham',      'symbol' => 'د.إ' ],
    ];

    // Handle Form Submission
    if ( isset( $_POST['acrb_save_general'] ) ) {
        $acrb_nonce = isset( $_POST['acrb_general_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_general_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $acrb_nonce, 'acrb_save_general_settings' ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            $selected_code = isset( $_POST['acrb_currency_code'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_currency_code'] ) ) : 'GBP';
            update_option( 'acrb_currency_code', $selected_code );
            update_option( 'acrb_currency', $currencies[$selected_code]['symbol'] ?? '£' );

            $fields = [ 'acrb_rental_unit', 'acrb_currency_pos', 'acrb_pickup_start', 'acrb_pickup_end' ];
            foreach ( $fields as $field ) {
                if ( isset( $_POST[$field] ) ) {
                    update_option( $field, sanitize_text_field( wp_unslash( $_POST[$field] ) ) );
                }
            }

            update_option( 'acrb_min_rent_days', absint( $_POST['acrb_min_rent_days'] ?? 1 ) );
            update_option( 'acrb_buffer_time', absint( $_POST['acrb_buffer_time'] ?? 2 ) );
            update_option( 'acrb_min_age', absint( $_POST['acrb_min_age'] ?? 21 ) );
            update_option( 'acrb_weekend_rental', isset( $_POST['acrb_weekend_rental'] ) ? 'yes' : 'no' );

            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Global configuration updated.', 'awesome-car-rental' ) . '</p></div>';
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
        .acrb-admin-wrapper { margin-top: 20px; max-width: 1000px; }
        .acrb-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .acrb-admin-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .acrb-admin-card-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; }
        .acrb-admin-card-header .dashicons { font-size: 24px; width: 24px; height: 24px; color: #2271b1; }
        .acrb-admin-card-title { margin: 0; font-size: 16px; font-weight: 600; color: #1d2327; }
        .acrb-admin-card-subtitle { margin: 4px 0 0; font-size: 13px; color: #646970; }
        .acrb-admin-field-group { margin-bottom: 18px; }
        .acrb-admin-label { display: block; font-weight: 600; margin-bottom: 8px; color: #1d2327; font-size: 13px; }
        .acrb-admin-row { display: flex; gap: 15px; }
        .acrb-admin-row .acrb-admin-field-group { flex: 1; }
        .acrb-admin-input, .acrb-admin-select { width: 100%; border: 1px solid #8c8f94; border-radius: 4px; padding: 0 8px; height: 36px; line-height: 2; }
        .acrb-admin-input:focus, .acrb-admin-select:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
        .acrb-admin-checkbox-wrap { background: #f6f7f7; padding: 15px; border-radius: 6px; border: 1px solid #dcdcde; display: flex; gap: 12px; cursor: pointer; }
        .acrb-admin-footer { margin-top: 25px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; display: flex; justify-content: flex-end; }
        .acrb-admin-btn-save { background: #2271b1; color: #fff; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .acrb-admin-btn-save:hover { background: #135e96; }
    </style>

    <div class="acrb-admin-wrapper">
        <form method="post">
            <?php wp_nonce_field( 'acrb_save_general_settings', 'acrb_general_nonce' ); ?>
            
            <div class="acrb-admin-grid">
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
/* translators: %s: Time unit (e.g. Day, Hour, Minute). */
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
                            <span><?php esc_html_e( 'to', 'awesome-car-rental' ); ?></span>
                            <input type="time" name="acrb_pickup_end" value="<?php echo esc_attr( $p_end ); ?>" class="acrb-admin-input">
                        </div>
                    </div>

                    <div class="acrb-admin-field-group">
                        <label class="acrb-admin-label"><?php esc_html_e( 'Preparation Buffer (Hours)', 'awesome-car-rental' ); ?></label>
                        <input type="number" name="acrb_buffer_time" value="<?php echo esc_attr( $buffer ); ?>" class="acrb-admin-input">
                        <p class="description"><?php esc_html_e( 'Rest period between bookings.', 'awesome-car-rental' ); ?></p>
                    </div>

                    <label class="acrb-admin-checkbox-wrap">
                        <input type="checkbox" name="acrb_weekend_rental" value="yes" <?php checked( $weekend, 'yes' ); ?>> 
                        <div>
                            <span class="acrb-admin-label" style="margin:0;"><?php esc_html_e( 'Enable Weekend Operations', 'awesome-car-rental' ); ?></span>
                            <span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Allow pickups on Sat/Sun.', 'awesome-car-rental' ); ?></span>
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