<?php
/**
 * Add / Edit Promo Code Component (`promo-codes-add-edit.php`)
 * Location: admin/promo-codes/promo-codes-add-edit.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_promos    = $wpdb->prefix . 'dev_nahian_promo_codes';
$currency_symbol = get_option( 'acrb_currency', '£' );

if ( isset( $_POST['acrb_save_promo'] ) ) {
	$nonce = isset( $_POST['acrb_promo_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_promo_nonce'] ) ) : '';

	if ( wp_verify_nonce( $nonce, 'acrb_promo_action' ) ) {
		$promo_id      = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
		$coupon_input  = isset( $_POST['promo_code'] ) ? sanitize_text_field( wp_unslash( $_POST['promo_code'] ) ) : '';
		$discount_type = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'percentage';
		$discount_val  = isset( $_POST['discount_value'] ) ? floatval( wp_unslash( $_POST['discount_value'] ) ) : 0;
		$min_spend     = isset( $_POST['min_spend'] ) ? floatval( wp_unslash( $_POST['min_spend'] ) ) : 0;
		$expiry_date   = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
		$max_uses      = isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 0;
		$status        = isset( $_POST['promo_status'] ) ? sanitize_key( wp_unslash( $_POST['promo_status'] ) ) : 'active';

		if ( ! empty( $coupon_input ) ) {
			$promo_code_string = strtoupper( sanitize_title( $coupon_input ) );

			$data = array(
				'promo_code'     => $promo_code_string,
				'discount_type'  => $discount_type,
				'discount_value' => $discount_val,
				'min_spend'      => $min_spend,
				'expiry_date'    => $expiry_date,
				'max_uses'       => $max_uses,
				'promo_status'   => $status,
			);
			$formats = array( '%s', '%s', '%f', '%f', '%s', '%d', '%s' );

			if ( $promo_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $table_promos, $data, array( 'id' => $promo_id ), $formats, array( '%d' ) );
				echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>Success:</strong> Promo code updated.</p></div>';
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert( $table_promos, $data, $formats );
				echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>Success:</strong> New promo code created.</p></div>';
			}
		}
	}
}

$promo_record = null;
if ( $promo_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$promo_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_promos} WHERE id = %d", $promo_id ), ARRAY_A );
}

$coupon_code = $promo_record ? (string) $promo_record['promo_code'] : '';
$disc_type   = $promo_record ? (string) $promo_record['discount_type'] : 'percentage';
$disc_val    = $promo_record ? (float) $promo_record['discount_value'] : '';
$min_val     = $promo_record ? (float) $promo_record['min_spend'] : '';
$expiry_val  = $promo_record ? (string) $promo_record['expiry_date'] : '';
$uses_val    = $promo_record ? absint( $promo_record['max_uses'] ) : '';
$status_val  = $promo_record ? (string) $promo_record['promo_status'] : 'active';
?>

<div class="acrb-card">
	<h2><?php echo $promo_id > 0 ? esc_html__( 'Edit Promo Code', 'awesome-car-rental' ) : esc_html__( 'Create New Promo Code', 'awesome-car-rental' ); ?></h2>
	
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
				<select name="discount_type" class="acrb-select">
					<option value="percentage" <?php selected( $disc_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount (%)', 'awesome-car-rental' ); ?></option>
					<option value="fixed" <?php selected( $disc_type, 'fixed' ); ?>><?php printf( esc_html__( 'Fixed Amount (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></option>
				</select>
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
			<select name="promo_status" class="acrb-select">
				<option value="active" <?php selected( $status_val, 'active' ); ?>><?php esc_html_e( '🟢 Active', 'awesome-car-rental' ); ?></option>
				<option value="disabled" <?php selected( $status_val, 'disabled' ); ?>><?php esc_html_e( '🔴 Disabled', 'awesome-car-rental' ); ?></option>
			</select>
		</div>

		<button type="submit" name="acrb_save_promo" class="acrb-btn-primary">
			<?php echo $promo_id > 0 ? esc_html__( 'Update Promo Code', 'awesome-car-rental' ) : esc_html__( 'Save Promo Code', 'awesome-car-rental' ); ?>
		</button>
	</form>
</div>