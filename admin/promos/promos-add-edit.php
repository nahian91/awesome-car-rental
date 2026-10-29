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
$acrb_table_promos    = $wpdb->prefix . 'dev_nahian_promo_codes';
$acrb_currency_symbol = get_option( 'acrb_currency', '$' );

// Read-only GET parameters used to resolve editing record.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$acrb_promo_id     = isset( $_GET['promo_id'] ) ? absint( wp_unslash( $_GET['promo_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$acrb_list_url     = admin_url( 'admin.php?page=awesome_car_rental&tab=promo_codes' );
$acrb_error_notice = '';

// 1. Process Form Submission.
if ( isset( $_POST['acrb_save_promo'] ) && check_admin_referer( 'acrb_promo_action', 'acrb_promo_nonce' ) ) {
	$acrb_record_id     = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
	$acrb_coupon_input  = sanitize_text_field( wp_unslash( $_POST['promo_code'] ?? '' ) );
	$acrb_discount_type = sanitize_key( wp_unslash( $_POST['discount_type'] ?? 'percentage' ) );
	$acrb_discount_val  = floatval( wp_unslash( $_POST['discount_value'] ?? 0 ) );
	$acrb_min_spend     = floatval( wp_unslash( $_POST['min_spend'] ?? 0 ) );
	$acrb_expiry_date   = sanitize_text_field( wp_unslash( $_POST['expiry_date'] ?? '' ) );
	$acrb_max_uses      = absint( wp_unslash( $_POST['max_uses'] ?? 0 ) );
	$acrb_status        = sanitize_key( wp_unslash( $_POST['promo_status'] ?? 'active' ) );

	if ( ! empty( $acrb_coupon_input ) && $acrb_discount_val > 0 ) {
		$acrb_promo_code_string = strtoupper( sanitize_title( $acrb_coupon_input ) );

		$acrb_data = array(
			'code'           => $acrb_promo_code_string,
			'discount_type'  => $acrb_discount_type,
			'discount_value' => $acrb_discount_val,
			'min_spend'      => $acrb_min_spend,
			'expiry_date'    => ! empty( $acrb_expiry_date ) ? $acrb_expiry_date : null,
			'max_uses'       => $acrb_max_uses,
			'status'         => $acrb_status,
		);
		$acrb_formats = array( '%s', '%s', '%f', '%f', '%s', '%d', '%s' );

		if ( $acrb_record_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $acrb_table_promos, $acrb_data, array( 'id' => $acrb_record_id ), $acrb_formats, array( '%d' ) );
			$acrb_redirect_url = add_query_arg( array( 'updated' => '1' ), $acrb_list_url );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $acrb_table_promos, $acrb_data, $acrb_formats );
			$acrb_redirect_url = add_query_arg( array( 'created' => '1' ), $acrb_list_url );
		}

		if ( headers_sent() ) {
			echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $acrb_redirect_url ) ) . ');</script>';
			echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $acrb_redirect_url ) . '"></noscript>';
			exit;
		}

		wp_safe_redirect( $acrb_redirect_url );
		exit;
	} else {
		$acrb_error_notice = __( 'Please specify a valid promo code string and a discount value greater than zero.', 'awesome-car-rental' );
	}
}

// 2. Fetch Existing Promo Code Record.
$acrb_promo_record = null;
if ( $acrb_promo_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$acrb_promo_record = $wpdb->get_row(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$acrb_table_promos} WHERE id = %d",
			$acrb_promo_id
		),
		ARRAY_A
	);
}

$acrb_coupon_code = $acrb_promo_record ? (string) $acrb_promo_record['code'] : '';
$acrb_disc_type   = $acrb_promo_record ? (string) $acrb_promo_record['discount_type'] : 'percentage';
$acrb_disc_val    = $acrb_promo_record ? (float) $acrb_promo_record['discount_value'] : '';
$acrb_min_val     = $acrb_promo_record ? (float) $acrb_promo_record['min_spend'] : '';
$acrb_expiry_val  = $acrb_promo_record ? (string) $acrb_promo_record['expiry_date'] : '';
$acrb_uses_val    = $acrb_promo_record ? absint( $acrb_promo_record['max_uses'] ) : '';
$acrb_status_val  = $acrb_promo_record ? (string) $acrb_promo_record['status'] : 'active';
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
		<?php
		if ( $acrb_promo_id > 0 ) {
			/* translators: %s: Promo code string */
			printf( esc_html__( 'Edit Promo Code: %s', 'awesome-car-rental' ), esc_html( $acrb_coupon_code ) );
		} else {
			esc_html_e( 'Create New Promo Code', 'awesome-car-rental' );
		}
		?>
	</h2>

	<?php if ( ! empty( $acrb_error_notice ) ) : ?>
		<div class="acrb-promos-alert-error">
			&#10005; <?php echo esc_html( $acrb_error_notice ); ?>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'acrb_promo_action', 'acrb_promo_nonce' ); ?>
		<input type="hidden" name="promo_id" value="<?php echo absint( $acrb_promo_id ); ?>">

		<!-- Coupon Code String -->
		<div class="acrb-promos-form-group acrb-promos-form-group-single">
			<label class="acrb-promos-label" for="promo_code"><?php esc_html_e( 'Coupon Code String', 'awesome-car-rental' ); ?></label>
			<input type="text" id="promo_code" name="promo_code" value="<?php echo esc_attr( $acrb_coupon_code ); ?>" class="acrb-promos-input acrb-promos-input-code" placeholder="<?php esc_attr_e( 'e.g. SUMMER2026', 'awesome-car-rental' ); ?>" required>
		</div>

		<div class="acrb-promos-grid-2">
			<!-- Discount Type -->
			<div class="acrb-promos-form-group">
				<label class="acrb-promos-label" for="discount_type"><?php esc_html_e( 'Discount Type', 'awesome-car-rental' ); ?></label>
				<div class="acrb-promos-select-wrap">
					<select id="discount_type" name="discount_type" class="acrb-promos-select">
						<option value="percentage" <?php selected( $acrb_disc_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount (%)', 'awesome-car-rental' ); ?></option>
						<option value="fixed" <?php selected( $acrb_disc_type, 'fixed' ); ?>>
							<?php
							/* translators: %s: Currency symbol */
							printf( esc_html__( 'Fixed Amount (%s)', 'awesome-car-rental' ), esc_html( $acrb_currency_symbol ) );
							?>
						</option>
					</select>
					<span class="dashicons dashicons-arrow-down-alt2 acrb-promos-select-arrow"></span>
				</div>
			</div>

			<!-- Discount Value -->
			<div class="acrb-promos-form-group">
				<label class="acrb-promos-label" for="discount_value"><?php esc_html_e( 'Discount Value', 'awesome-car-rental' ); ?></label>
				<input type="number" id="discount_value" step="0.01" min="0.01" name="discount_value" value="<?php echo esc_attr( (string) $acrb_disc_val ); ?>" class="acrb-promos-input" placeholder="<?php esc_attr_e( 'e.g. 15 for 15%', 'awesome-car-rental' ); ?>" required>
			</div>
		</div>

		<div class="acrb-promos-grid-3">
			<!-- Minimum Spend -->
			<div class="acrb-promos-form-group">
				<label class="acrb-promos-label" for="min_spend">
					<?php
					/* translators: %s: Currency symbol */
					printf( esc_html__( 'Min. Spend (%s)', 'awesome-car-rental' ), esc_html( $acrb_currency_symbol ) );
					?>
				</label>
				<input type="number" id="min_spend" step="0.01" min="0" name="min_spend" value="<?php echo esc_attr( (string) $acrb_min_val ); ?>" class="acrb-promos-input" placeholder="0.00">
			</div>

			<!-- Expiration Date -->
			<div class="acrb-promos-form-group">
				<label class="acrb-promos-label" for="expiry_date"><?php esc_html_e( 'Expiration Date', 'awesome-car-rental' ); ?></label>
				<input type="date" id="expiry_date" name="expiry_date" value="<?php echo esc_attr( $acrb_expiry_val ); ?>" class="acrb-promos-input">
			</div>

			<!-- Max Uses -->
			<div class="acrb-promos-form-group">
				<label class="acrb-promos-label" for="max_uses"><?php esc_html_e( 'Max Uses (0 = Unlimited)', 'awesome-car-rental' ); ?></label>
				<input type="number" id="max_uses" min="0" name="max_uses" value="<?php echo esc_attr( (string) $acrb_uses_val ); ?>" class="acrb-promos-input" placeholder="0">
			</div>
		</div>

		<!-- Promo Status -->
		<div class="acrb-promos-form-group acrb-promos-form-group-single">
			<label class="acrb-promos-label" for="promo_status"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
			<div class="acrb-promos-select-wrap">
				<select id="promo_status" name="promo_status" class="acrb-promos-select">
					<option value="active" <?php selected( $acrb_status_val, 'active' ); ?>><?php esc_html_e( 'Active', 'awesome-car-rental' ); ?></option>
					<option value="disabled" <?php selected( $acrb_status_val, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'awesome-car-rental' ); ?></option>
				</select>
				<span class="dashicons dashicons-arrow-down-alt2 acrb-promos-select-arrow"></span>
			</div>
		</div>

		<!-- Submit & Cancel Actions -->
		<div class="acrb-promos-btn-actions">
			<button type="submit" name="acrb_save_promo" class="acrb-promos-btn-submit">
				<span class="dashicons <?php echo $acrb_promo_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>"></span>
				<span><?php echo $acrb_promo_id > 0 ? esc_html__( 'Update Promo Code', 'awesome-car-rental' ) : esc_html__( 'Save Promo Code', 'awesome-car-rental' ); ?></span>
			</button>
			<a href="<?php echo esc_url( $acrb_list_url ); ?>" class="acrb-promos-btn-cancel">
				<?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
			</a>
		</div>
	</form>
</div>