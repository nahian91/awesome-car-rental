<?php
/**
 * Add / Edit Customer Component (`customers-add-edit.php`)
 * Location: admin/customers/customers-add-edit.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_customers = $wpdb->prefix . 'dev_nahian_customers';
$cust_id = isset( $_GET['cust_id'] ) ? absint( $_GET['cust_id'] ) : 0;

if ( isset( $_POST['acrb_save_customer'] ) && check_admin_referer( 'acrb_cust_action', 'acrb_cust_nonce' ) ) {
	$name  = sanitize_text_field( wp_unslash( $_POST['cust_name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['cust_email'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['cust_phone'] ?? '' ) );
	$post_id = absint( $_POST['cust_id'] ?? 0 );

	if ( ! empty( $name ) && ! empty( $email ) ) {
		$data = array( 'name' => $name, 'email' => $email, 'phone' => $phone );
		$formats = array( '%s', '%s', '%s' );

		if ( $post_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table_customers, $data, array( 'id' => $post_id ), $formats, array( '%d' ) );
			echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p>Customer updated successfully.</p></div>';
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $table_customers, $data, $formats );
			echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p>New customer registered successfully.</p></div>';
		}
	}
}

$customer = null;
if ( $cust_id > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $cust_id ), ARRAY_A );
}

$c_name  = $customer['name'] ?? '';
$c_email = $customer['email'] ?? '';
$c_phone = $customer['phone'] ?? '';
?>

<div class="acrb-card">
	<h2><?php echo $cust_id > 0 ? esc_html__( 'Edit Customer Profile', 'awesome-car-rental' ) : esc_html__( 'Register New Client Profile', 'awesome-car-rental' ); ?></h2>
	
	<form method="post">
		<?php wp_nonce_field( 'acrb_cust_action', 'acrb_cust_nonce' ); ?>
		<input type="hidden" name="cust_id" value="<?php echo absint( $cust_id ); ?>">

		<div class="acrb-form-grid-2">
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></label>
				<input type="text" name="cust_name" value="<?php echo esc_attr( $c_name ); ?>" class="acrb-input" required>
			</div>
			<div class="acrb-form-group">
				<label class="acrb-label"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
				<input type="email" name="cust_email" value="<?php echo esc_attr( $c_email ); ?>" class="acrb-input" required>
			</div>
		</div>

		<div class="acrb-form-group" style="margin-bottom:24px; max-width:400px;">
			<label class="acrb-label"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
			<input type="text" name="cust_phone" value="<?php echo esc_attr( $c_phone ); ?>" class="acrb-input">
		</div>

		<button type="submit" name="acrb_save_customer" class="acrb-btn-primary">
			<?php echo $cust_id > 0 ? esc_html__( 'Update Customer', 'awesome-car-rental' ); ?> : esc_html__( 'Save Customer', 'awesome-car-rental' ); ?>
		</button>
	</form>
</div>