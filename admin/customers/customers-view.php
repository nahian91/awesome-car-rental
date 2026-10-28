<?php
/**
 * Customer CRM View Component (`customers-view.php`)
 * Location: admin/customers/customers-view.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_customers = $wpdb->prefix . 'dev_nahian_customers';
$table_bookings  = $wpdb->prefix . 'dev_nahian_bookings';
$currency_symbol = get_option( 'acrb_currency', '£' );
$currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$view_id         = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $view_id ), ARRAY_A );

if ( ! $customer ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer not found.', 'awesome-car-rental' ) . '</p></div>';
	return;
}

$cust_name  = $customer['name'] ?? '';
$cust_email = $customer['email'] ?? '';
$cust_phone = $customer['phone'] ?? 'N/A';
$cust_date  = $customer['created_at'] ?? '';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$orders = $wpdb->get_results(
	$wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE customer_email = %s OR customer_name = %s ORDER BY id DESC", $cust_email, $cust_name ),
	ARRAY_A
);
?>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px;">
	<div class="acrb-card" style="text-align: center; height: fit-content;">
		<div style="width: 80px; height: 80px; background: #eef2ff; color: #3b82f6; font-size: 28px; font-weight: 700; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
			<?php echo esc_html( strtoupper( substr( $cust_name, 0, 2 ) ) ); ?>
		</div>
		<h3 style="margin: 0 0 4px; font-size: 18px; color: #0f172a;"><?php echo esc_html( $cust_name ); ?></h3>
		<span style="background: #eff6ff; color: #3b82f6; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase;">Verified Client</span>

		<div style="margin-top: 24px; text-align: left; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
			<div style="display:flex; justify-content:space-between;"><span style="color:#64748b;">Email</span><strong><?php echo esc_html( $cust_email ); ?></strong></div>
			<div style="display:flex; justify-content:space-between;"><span style="color:#64748b;">Phone</span><strong><?php echo esc_html( $cust_phone ); ?></strong></div>
			<div style="display:flex; justify-content:space-between;"><span style="color:#64748b;">Joined</span><strong><?php echo esc_html( date_i18n( 'M Y', strtotime( $cust_date ) ) ); ?></strong></div>
		</div>
	</div>

	<div class="acrb-table-wrap">
		<div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #0f172a;"><?php esc_html_e( 'Customer Booking History', 'awesome-car-rental' ); ?></div>
		<table class="widefat striped" style="width:100%;">
			<thead>
				<tr>
					<th>Order ID</th>
					<th>Vehicle</th>
					<th>Total Price</th>
					<th>Status</th>
					<th style="text-align:right;">Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $orders ) ) : foreach ( $orders as $order ) : 
					$o_id        = absint( $order['id'] );
					$status      = sanitize_key( $order['booking_status'] ?? 'pending' );
					$raw_price   = floatval( $order['total_amount'] ?? 0 );
					$car_name    = esc_html( $order['car_name'] ?? 'Fleet Vehicle' );
					$edit_url    = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&action=edit&booking_id=' . $o_id );
				?>
				<tr>
					<td><strong>#<?php echo $o_id; ?></strong></td>
					<td><?php echo $car_name; ?></td>
					<td><strong style="color:#3b82f6;"><?php echo esc_html( $currency_symbol ) . number_format( $raw_price, 2 ); ?></strong></td>
					<td><span style="background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:700; text-transform:uppercase;"><?php echo esc_html( $status ); ?></span></td>
					<td style="text-align:right;">
						<a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action"><span class="dashicons dashicons-edit"></span></a>
					</td>
				</tr>
				<?php endforeach; else : ?>
					<tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">No bookings found for this client.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>