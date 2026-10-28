<?php
/**
 * Customers List Component (`customers-list.php`)
 * Location: admin/customers/customers-list.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_customers = $wpdb->prefix . 'dev_nahian_customers';
$table_bookings  = $wpdb->prefix . 'dev_nahian_bookings';
$currency_symbol = get_option( 'acrb_currency', '£' );
$currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$page_slug       = 'awesome_car_rental';
?>

<div class="acrb-table-wrap">
	<table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Customer Name', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Contact Email', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'LTV (Total Spent)', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Activity', 'awesome-car-rental' ); ?></th>
				<th class="all" width="140" style="text-align:right;" data-orderable="false"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$customers_list = $wpdb->get_results( "SELECT * FROM {$table_customers} ORDER BY id DESC", ARRAY_A );

			if ( ! empty( $customers_list ) ) :
				foreach ( $customers_list as $cust ) :
					$c_id    = absint( $cust['id'] );
					$c_name  = $cust['name'] ?? '';
					$c_email = $cust['email'] ?? '';
					$c_phone = $cust['phone'] ?? '—';

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$client_orders = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT total_amount FROM {$table_bookings} WHERE customer_email = %s OR customer_name = %s",
							$c_email,
							$c_name
						),
						ARRAY_A
					);

					$order_count = count( $client_orders );
					$user_ltv    = 0.00;
					foreach ( $client_orders as $co ) {
						$user_ltv += floatval( $co['total_amount'] ?? 0 );
					}

					$formatted_ltv = number_format_i18n( $user_ltv, 2 );
					$display_ltv   = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted_ltv : $formatted_ltv . $currency_symbol;

					$view_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=customers&view=' . $c_id );
					$edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=customers&sub=edit&cust_id=' . $c_id );
					$del_url  = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=customers&sub=delete&cust_id=' . $c_id ), 'acrb_del_cust_' . $c_id );
			?>
			<tr>
				<td>
					<div style="display:flex; align-items:center; gap:10px;">
						<div style="width:32px; height:32px; background:#eef2ff; color:#3b82f6; border-radius:50%; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center;">
							<?php echo esc_html( strtoupper( substr( $c_name, 0, 2 ) ) ); ?>
						</div>
						<strong><?php echo esc_html( $c_name ); ?></strong>
					</div>
				</td>
				<td style="color:#64748b;"><?php echo esc_html( $c_email ); ?></td>
				<td style="color:#64748b;"><?php echo esc_html( $c_phone ); ?></td>
				<td><strong style="color:#3b82f6;"><?php echo esc_html( $display_ltv ); ?></strong></td>
				<td><span style="background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600;"><?php echo absint( $order_count ); ?> Bookings</span></td>
				<td style="text-align:right;">
					<div class="acrb-actions-group">
						<a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View CRM', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</a>
						<a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this customer record?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
				<tr><td colspan="6" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No customers found.', 'awesome-car-rental' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>