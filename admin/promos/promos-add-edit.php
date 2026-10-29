<?php
/**
 * Add / Edit Promo Code Component (`promo-codes-add-edit.php`)
 * Location: admin/promo-codes/promo-codes-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_promos    = $wpdb->prefix . 'dev_nahian_promo_codes';
$currency_symbol = get_option( 'acrb_currency', '£' );
$promo_id        = isset( $_GET['promo_id'] ) ? absint( $_GET['promo_id'] ) : 0;
$redirect_script = '';

if ( isset( $_POST['acrb_save_promo'] ) ) {
    $nonce = isset( $_POST['acrb_promo_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_promo_nonce'] ) ) : '';

    if ( wp_verify_nonce( $nonce, 'acrb_promo_action' ) ) {
        $record_id     = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
        $coupon_input  = isset( $_POST['promo_code'] ) ? sanitize_text_field( wp_unslash( $_POST['promo_code'] ) ) : '';
        $discount_type = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'percentage';
        $discount_val  = isset( $_POST['discount_value'] ) ? floatval( wp_unslash( $_POST['discount_value'] ) ) : 0;
        $min_spend     = isset( $_POST['min_spend'] ) ? floatval( wp_unslash( $_POST['min_spend'] ) ) : 0;
        $expiry_date   = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
        $max_uses      = isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 0;
        $status        = isset( $_POST['promo_status'] ) ? sanitize_key( wp_unslash( $_POST['promo_status'] ) ) : 'active';

        if ( ! empty( $coupon_input ) ) {
            $promo_code_string = strtoupper( sanitize_title( $coupon_input ) );

            // Synchronized with SQL schema columns: code, discount_type, discount_value, min_spend, expiry_date, max_uses, status
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
                $msg_code = 'updated';
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_promos, $data, $formats );
                $record_id = $wpdb->insert_id;
                $msg_code  = 'created';
            }

            // Safe JavaScript redirect to bypass header conflicts and avoid double notices
            $target_url      = add_query_arg( array( 'promo_id' => $record_id, 'updated' => $msg_code ), admin_url( 'admin.php?page=awesome_car_rental&tab=promo_codes&sub=edit' ) );
            $redirect_script = '<script>window.location.href = ' . wp_json_encode( $target_url ) . ';</script>';
        }
    }
}

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

echo $redirect_script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>

<div class="acrb-card" style="max-width: 900px; margin: 0 auto;">
    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;">
            <p><strong><?php esc_html_e( 'Success:', 'awesome-car-rental' ); ?></strong> 
                <?php 
                if ( 'updated' === $_GET['updated'] ) {
                    esc_html_e( 'Promo code updated successfully.', 'awesome-car-rental' );
                } else {
                    esc_html_e( 'New promo code created successfully.', 'awesome-car-rental' );
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">
            <?php echo $promo_id > 0 ? esc_html__( 'Edit Promo Code', 'awesome-car-rental' ) : esc_html__( 'Create New Promo Code', 'awesome-car-rental' ); ?>
        </h2>
    </div>
    
    <form method="post">
        <?php wp_nonce_field( 'acrb_promo_action', 'acrb_promo_nonce' ); ?>
        <input type="hidden" name="promo_id" value="<?php echo absint( $promo_id ); ?>">

        <div class="acrb-form-group" style="margin-bottom:20px; max-width:500px;">
            <label class="acrb-label"><?php esc_html_e( 'Coupon Code String', 'awesome-car-rental' ); ?></label>
            <input type="text" name="promo_code" value="<?php echo esc_attr( $coupon_code ); ?>" class="acrb-input" style="text-transform:uppercase; font-weight:bold;" placeholder="e.g. SUMMER2026" required>
        </div>

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Discount Type', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="discount_type" class="acrb-select">
                        <option value="percentage" <?php selected( $disc_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount (%)', 'awesome-car-rental' ); ?></option>
                        <option value="fixed" <?php selected( $disc_type, 'fixed' ); ?>><?php printf( esc_html__( 'Fixed Amount (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></option>
                    </select>
                </div>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Discount Value', 'awesome-car-rental' ); ?></label>
                <input type="number" step="0.01" name="discount_value" value="<?php echo esc_attr( (string) $disc_val ); ?>" class="acrb-input" placeholder="e.g. 15 for 15%" required>
            </div>
        </div>

        <div class="acrb-form-grid-3">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php printf( esc_html__( 'Min. Spend (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></label>
                <input type="number" step="0.01" name="min_spend" value="<?php echo esc_attr( (string) $min_val ); ?>" class="acrb-input" placeholder="0.00">
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Expiration Date', 'awesome-car-rental' ); ?></label>
                <input type="date" name="expiry_date" value="<?php echo esc_attr( $expiry_val ); ?>" class="acrb-input">
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Max Uses (0 = Unlimited)', 'awesome-car-rental' ); ?></label>
                <input type="number" name="max_uses" value="<?php echo esc_attr( (string) $uses_val ); ?>" class="acrb-input" placeholder="0">
            </div>
        </div>

        <div class="acrb-form-group" style="margin-bottom:24px; max-width:400px;">
            <label class="acrb-label"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
            <div class="acrb-select-wrap">
                <select name="promo_status" class="acrb-select">
                    <option value="active" <?php selected( $status_val, 'active' ); ?>><?php esc_html_e( '🟢 Active', 'awesome-car-rental' ); ?></option>
                    <option value="disabled" <?php selected( $status_val, 'disabled' ); ?>><?php esc_html_e( '🔴 Disabled', 'awesome-car-rental' ); ?></option>
                </select>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <button type="submit" name="acrb_save_promo" class="acrb-btn-primary" style="height: 44px; padding: 0 32px; font-size: 14px;">
                <span class="dashicons <?php echo $promo_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>" style="font-size:17px; width:17px; height:17px; display:inline-flex; align-items:center; justify-content:center;"></span>
                <span><?php echo $promo_id > 0 ? esc_html__( 'Update Promo Code', 'awesome-car-rental' ) : esc_html__( 'Save Promo Code', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>