<?php
/**
 * Add / Edit Promo Code Component (`promos-add-edit.php`)
 * Location: admin/promos/promos-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   2.9.9
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$table_promos    = $wpdb->prefix . 'dev_nahian_promo_codes';
$currency_symbol = get_option( 'acrb_currency', '$' );
$promo_id        = isset( $_GET['promo_id'] ) ? absint( wp_unslash( $_GET['promo_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$list_url        = admin_url( 'admin.php?page=awesome_car_rental&tab=promo_codes' );
$error_notice    = '';

// 1. Process Form Submission
if ( isset( $_POST['acrb_save_promo'] ) && check_admin_referer( 'acrb_promo_action', 'acrb_promo_nonce' ) ) {
    $record_id     = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
    $coupon_input  = sanitize_text_field( wp_unslash( $_POST['promo_code'] ?? '' ) );
    $discount_type = sanitize_key( wp_unslash( $_POST['discount_type'] ?? 'percentage' ) );
    $discount_val  = floatval( wp_unslash( $_POST['discount_value'] ?? 0 ) );
    $min_spend     = floatval( wp_unslash( $_POST['min_spend'] ?? 0 ) );
    $expiry_date   = sanitize_text_field( wp_unslash( $_POST['expiry_date'] ?? '' ) );
    $max_uses      = absint( wp_unslash( $_POST['max_uses'] ?? 0 ) );
    $status        = sanitize_key( wp_unslash( $_POST['promo_status'] ?? 'active' ) );

    if ( ! empty( $coupon_input ) && $discount_val > 0 ) {
        $promo_code_string = strtoupper( sanitize_title( $coupon_input ) );

        $data = array(
            'code'           => $promo_code_string,
            'discount_type'  => $discount_type,
            'discount_value' => $discount_val,
            'min_spend'      => $min_spend,
            'expiry_date'    => ! empty( $expiry_date ) ? $expiry_date : null,
            'max_uses'       => $max_uses,
            'status'         => $status,
        );
        $formats = array( '%s', '%s', '%f', '%f', '%s', '%d', '%s' );

        if ( $record_id > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update( $table_promos, $data, array( 'id' => $record_id ), $formats, array( '%d' ) );
            $redirect_url = add_query_arg( array( 'updated' => '1' ), $list_url );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert( $table_promos, $data, $formats );
            $redirect_url = add_query_arg( array( 'created' => '1' ), $list_url );
        }

        if ( headers_sent() ) {
            echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
            exit;
        }

        wp_safe_redirect( $redirect_url );
        exit;
    } else {
        $error_notice = __( 'Please specify a valid promo code string and a discount value greater than zero.', 'awesome-car-rental' );
    }
}

// 2. Fetch Existing Promo Code Record
$promo_record = null;
if ( $promo_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $promo_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_promos} WHERE id = %d", $promo_id ), ARRAY_A );
}

$coupon_code = $promo_record ? (string) $promo_record['code'] : '';
$disc_type   = $promo_record ? (string) $promo_record['discount_type'] : 'percentage';
$disc_val    = $promo_record ? (float) $promo_record['discount_value'] : '';
$min_val     = $promo_record ? (float) $promo_record['min_spend'] : '';
$expiry_val  = $promo_record ? (string) $promo_record['expiry_date'] : '';
$uses_val    = $promo_record ? absint( $promo_record['max_uses'] ) : '';
$status_val  = $promo_record ? (string) $promo_record['status'] : 'active';
?>

<style>
    .acrb-promos-card {
        max-width: 860px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 28px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        box-sizing: border-box;
    }
    .acrb-promos-card *,
    .acrb-promos-card *::before,
    .acrb-promos-card *::after {
        box-sizing: border-box;
    }
    .acrb-promos-header-title {
        margin: 0 0 24px;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 14px;
    }

    /* Grids */
    .acrb-promos-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }
    .acrb-promos-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }
    .acrb-promos-form-group {
        display: flex;
        flex-direction: column;
    }
    .acrb-promos-form-group-single {
        max-width: 420px;
        margin-bottom: 18px;
    }
    .acrb-promos-label {
        display: block;
        font-weight: 700;
        color: #1e293b;
        font-size: 13.5px;
        margin-bottom: 6px;
    }

    /* Form Controls */
    .acrb-promos-input,
    .acrb-promos-select {
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
    .acrb-promos-input-code {
        text-transform: uppercase !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    }
    .acrb-promos-input:hover,
    .acrb-promos-select:hover {
        border-color: #94a3b8 !important;
        background: #ffffff !important;
    }
    .acrb-promos-input:focus,
    .acrb-promos-select:focus {
        background: #ffffff !important;
        border-color: #4f46e5 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    }

    /* Select Chevron Container */
    .acrb-promos-select-wrap {
        position: relative;
        width: 100%;
    }
    .acrb-promos-select-wrap select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 36px !important;
        cursor: pointer;
    }
    .acrb-promos-select-arrow {
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

    /* Action Buttons */
    .acrb-promos-btn-actions {
        margin-top: 28px;
        display: flex;
        gap: 12px;
        align-items: center;
        border-top: 1px solid #f1f5f9;
        padding-top: 20px;
    }
    .acrb-promos-btn-submit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 11px 24px;
        font-size: 13.5px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .acrb-promos-btn-submit:hover {
        background: #4338ca;
        color: #ffffff;
        transform: translateY(-1px);
    }
    .acrb-promos-btn-submit .dashicons {
        font-size: 17px;
        width: 17px;
        height: 17px;
    }
    .acrb-promos-btn-cancel {
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 14px;
        border-radius: 8px;
        transition: color 0.15s ease;
    }
    .acrb-promos-btn-cancel:hover {
        color: #0f172a;
    }

    /* Alerts */
    .acrb-promos-alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 12px 18px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    @media (max-width: 780px) {
        .acrb-promos-grid-2,
        .acrb-promos-grid-3 {
            grid-template-columns: 1fr;
            gap: 14px;
        }
        .acrb-promos-form-group-single {
            max-width: 100%;
        }
    }
</style>

<div class="acrb-promos-card">
    <h2 class="acrb-promos-header-title">
        <?php echo $promo_id > 0 ? sprintf( esc_html__( 'Edit Promo Code: %s', 'awesome-car-rental' ), esc_html( $coupon_code ) ) : esc_html__( 'Create New Promo Code', 'awesome-car-rental' ); ?>
    </h2>

    <?php if ( ! empty( $error_notice ) ) : ?>
        <div class="acrb-promos-alert-error">
            ✕ <?php echo esc_html( $error_notice ); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field( 'acrb_promo_action', 'acrb_promo_nonce' ); ?>
        <input type="hidden" name="promo_id" value="<?php echo absint( $promo_id ); ?>">

        <!-- Coupon Code String -->
        <div class="acrb-promos-form-group acrb-promos-form-group-single">
            <label class="acrb-promos-label" for="promo_code"><?php esc_html_e( 'Coupon Code String', 'awesome-car-rental' ); ?></label>
            <input type="text" id="promo_code" name="promo_code" value="<?php echo esc_attr( $coupon_code ); ?>" class="acrb-promos-input acrb-promos-input-code" placeholder="<?php esc_attr_e( 'e.g. SUMMER2026', 'awesome-car-rental' ); ?>" required>
        </div>

        <div class="acrb-promos-grid-2">
            <!-- Discount Type -->
            <div class="acrb-promos-form-group">
                <label class="acrb-promos-label" for="discount_type"><?php esc_html_e( 'Discount Type', 'awesome-car-rental' ); ?></label>
                <div class="acrb-promos-select-wrap">
                    <select id="discount_type" name="discount_type" class="acrb-promos-select">
                        <option value="percentage" <?php selected( $disc_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount (%)', 'awesome-car-rental' ); ?></option>
                        <option value="fixed" <?php selected( $disc_type, 'fixed' ); ?>><?php printf( esc_html__( 'Fixed Amount (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></option>
                    </select>
                    <span class="dashicons dashicons-arrow-down-alt2 acrb-promos-select-arrow"></span>
                </div>
            </div>

            <!-- Discount Value -->
            <div class="acrb-promos-form-group">
                <label class="acrb-promos-label" for="discount_value"><?php esc_html_e( 'Discount Value', 'awesome-car-rental' ); ?></label>
                <input type="number" id="discount_value" step="0.01" min="0.01" name="discount_value" value="<?php echo esc_attr( (string) $disc_val ); ?>" class="acrb-promos-input" placeholder="<?php esc_attr_e( 'e.g. 15 for 15%', 'awesome-car-rental' ); ?>" required>
            </div>
        </div>

        <div class="acrb-promos-grid-3">
            <!-- Minimum Spend -->
            <div class="acrb-promos-form-group">
                <label class="acrb-promos-label" for="min_spend"><?php printf( esc_html__( 'Min. Spend (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></label>
                <input type="number" id="min_spend" step="0.01" min="0" name="min_spend" value="<?php echo esc_attr( (string) $min_val ); ?>" class="acrb-promos-input" placeholder="0.00">
            </div>

            <!-- Expiration Date -->
            <div class="acrb-promos-form-group">
                <label class="acrb-promos-label" for="expiry_date"><?php esc_html_e( 'Expiration Date', 'awesome-car-rental' ); ?></label>
                <input type="date" id="expiry_date" name="expiry_date" value="<?php echo esc_attr( $expiry_val ); ?>" class="acrb-promos-input">
            </div>

            <!-- Max Uses -->
            <div class="acrb-promos-form-group">
                <label class="acrb-promos-label" for="max_uses"><?php esc_html_e( 'Max Uses (0 = Unlimited)', 'awesome-car-rental' ); ?></label>
                <input type="number" id="max_uses" min="0" name="max_uses" value="<?php echo esc_attr( (string) $uses_val ); ?>" class="acrb-promos-input" placeholder="0">
            </div>
        </div>

        <!-- Promo Status -->
        <div class="acrb-promos-form-group acrb-promos-form-group-single">
            <label class="acrb-promos-label" for="promo_status"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
            <div class="acrb-promos-select-wrap">
                <select id="promo_status" name="promo_status" class="acrb-promos-select">
                    <option value="active" <?php selected( $status_val, 'active' ); ?>><?php esc_html_e( 'Active', 'awesome-car-rental' ); ?></option>
                    <option value="disabled" <?php selected( $status_val, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'awesome-car-rental' ); ?></option>
                </select>
                <span class="dashicons dashicons-arrow-down-alt2 acrb-promos-select-arrow"></span>
            </div>
        </div>

        <!-- Submit & Cancel Actions -->
        <div class="acrb-promos-btn-actions">
            <button type="submit" name="acrb_save_promo" class="acrb-promos-btn-submit">
                <span class="dashicons <?php echo $promo_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>"></span>
                <span><?php echo $promo_id > 0 ? esc_html__( 'Update Promo Code', 'awesome-car-rental' ) : esc_html__( 'Save Promo Code', 'awesome-car-rental' ); ?></span>
            </button>
            <a href="<?php echo esc_url( $list_url ); ?>" class="acrb-promos-btn-cancel">
                <?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </form>
</div>