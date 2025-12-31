<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payments Tab - Toggle-based Gateway Configuration
 */
function acrb_settings_payments_view() {
	// 1. SAVE LOGIC
	if ( isset( $_POST['acrb_save_payments'] ) ) {

		// Fix: Verify Nonce (Resolves NonceVerification.Missing)
		$acrb_nonce = isset( $_POST['acrb_payments_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_payments_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $acrb_nonce, 'acrb_save_payments_action' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed. Please try again.', 'awesome-car-rental' ) . '</p></div>';
		} else {

			// Fix: Toggle Gateways
			$gateways = [ 'acrb_enable_pay_later', 'acrb_enable_bank', 'acrb_enable_cash' ];
			foreach ( $gateways as $gateway ) {
				update_option( $gateway, isset( $_POST[ $gateway ] ) ? 'yes' : 'no' );
			}

			// Fix: Unslash and Validate Textareas (Resolves MissingUnslash & InputNotValidated)
			$bank_details = isset( $_POST['acrb_bank_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acrb_bank_details'] ) ) : '';
			$cash_instr   = isset( $_POST['acrb_cash_instructions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['acrb_cash_instructions'] ) ) : '';

			update_option( 'acrb_bank_details', $bank_details );
			update_option( 'acrb_cash_instructions', $cash_instr );

			echo '<div class="notice notice-success is-dismissible" style="border-radius:8px;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Payment gateways updated.', 'awesome-car-rental' ) . '</p></div>';
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
			<h2 class="acrb-tab-title"><?php esc_html_e( 'Payment Gateways', 'awesome-car-rental' ); ?></h2>
			<p class="acrb-tab-desc"><?php esc_html_e( 'Enable and configure how your customers can pay for their rentals.', 'awesome-car-rental' ); ?></p>
		</header>

		<form method="post">
			<?php wp_nonce_field( 'acrb_save_payments_action', 'acrb_payments_nonce' ); ?>

			<div class="acrb-payments-stack">
				
				<div class="acrb-method-card <?php echo ( 'yes' === $later_on ) ? 'is-active' : ''; ?>">
					<div class="acrb-method-header">
						<div class="acrb-method-info">
							<div class="acrb-method-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
							<div class="acrb-method-title">
								<h4><?php esc_html_e( 'Book Now, Pay Later', 'awesome-car-rental' ); ?></h4>
								<p><?php esc_html_e( 'Reserve the car immediately; payment is handled offline.', 'awesome-car-rental' ); ?></p>
							</div>
						</div>
						<label class="acrb-toggle">
							<input type="checkbox" name="acrb_enable_pay_later" value="yes" <?php checked( $later_on, 'yes' ); ?> class="acrb-gateway-toggle">
							<span class="acrb-slider"></span>
						</label>
					</div>
				</div>

				<div class="acrb-method-card <?php echo ( 'yes' === $bank_on ) ? 'is-active' : ''; ?>">
					<div class="acrb-method-header">
						<div class="acrb-method-info">
							<div class="acrb-method-icon"><span class="dashicons dashicons-bank"></span></div>
							<div class="acrb-method-title">
								<h4><?php esc_html_e( 'Direct Bank Transfer', 'awesome-car-rental' ); ?></h4>
								<p><?php esc_html_e( 'Show account details for manual wire transfers.', 'awesome-car-rental' ); ?></p>
							</div>
						</div>
						<label class="acrb-toggle">
							<input type="checkbox" name="acrb_enable_bank" value="yes" <?php checked( $bank_on, 'yes' ); ?> class="acrb-gateway-toggle">
							<span class="acrb-slider"></span>
						</label>
					</div>
					<div class="acrb-method-content">
						<label class="acrb-label"><?php esc_html_e( 'Bank Account Details & Payment Instructions', 'awesome-car-rental' ); ?></label>
						<textarea name="acrb_bank_details" rows="5" class="acrb-textarea" placeholder="<?php esc_attr_e( "Account Name:\nIBAN/Account Number:\nBank Name:\nSWIFT Code:", 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $bank_info ); ?></textarea>
					</div>
				</div>

				<div class="acrb-method-card <?php echo ( 'yes' === $cash_on ) ? 'is-active' : ''; ?>">
					<div class="acrb-method-header">
						<div class="acrb-method-info">
							<div class="acrb-method-icon"><span class="dashicons dashicons-money-alt"></span></div>
							<div class="acrb-method-title">
								<h4><?php esc_html_e( 'Cash on Pickup', 'awesome-car-rental' ); ?></h4>
								<p><?php esc_html_e( 'Accept physical payments at your local rental office.', 'awesome-car-rental' ); ?></p>
							</div>
						</div>
						<label class="acrb-toggle">
							<input type="checkbox" name="acrb_enable_cash" value="yes" <?php checked( $cash_on, 'yes' ); ?> class="acrb-gateway-toggle">
							<span class="acrb-slider"></span>
						</label>
					</div>
					<div class="acrb-method-content">
						<label class="acrb-label"><?php esc_html_e( 'Cash Instructions', 'awesome-car-rental' ); ?></label>
						<textarea name="acrb_cash_instructions" rows="4" class="acrb-textarea" placeholder="<?php esc_attr_e( 'e.g. Please visit our main desk at Terminal 1. We accept USD and EUR.', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $cash_info ); ?></textarea>
					</div>
				</div>

			</div>

			<div class="acrb-footer-action">
				<button type="submit" name="acrb_save_payments" class="acrb-btn-save">
					<?php esc_html_e( 'Save Changes', 'awesome-car-rental' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}