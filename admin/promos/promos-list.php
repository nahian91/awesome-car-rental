<?php
/**
 * Promo Codes List Component (`promo-codes-list.php`)
 * Location: admin/promo-codes/promo-codes-list.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_promos    = $wpdb->prefix . 'dev_nahian_promo_codes';
$currency_symbol = get_option( 'acrb_currency', '£' );
$page_slug       = 'awesome_car_rental';
?>

<div class="acrb-table-wrap">
	<table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Promo Code', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Discount', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Min. Spend', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Expiry Date', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
				<th class="all" style="text-align:right; width:100px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$promo_list = $wpdb->get_results( "SELECT * FROM {$table_promos} ORDER BY id DESC", ARRAY_A );

			if ( ! empty( $promo_list ) ) :
				foreach ( $promo_list as $promo ) :
					$promo_id    = absint( $promo['id'] );
					$item_code   = (string) ( $promo['promo_code'] ?? '' );
					$item_type   = (string) ( $promo['discount_type'] ?? 'percentage' );
					$item_val    = floatval( $promo['discount_value'] ?? 0 );
					$item_min    = floatval( $promo['min_spend'] ?? 0 );
					$item_expiry = (string) ( $promo['expiry_date'] ?? 'Never' );
					$item_status = (string) ( $promo['promo_status'] ?? 'active' );
					
					$discount_display = ( 'percentage' === $item_type ) ? $item_val . '%' : $currency_symbol . number_format( $item_val, 2 );

					$edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=promo_codes&sub=edit&promo_id=' . $promo_id );
					$del_url  = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=promo_codes&sub=delete&promo_id=' . $promo_id ), 'acrb_del_promo_' . $promo_id );
			?>
			<tr>
				<td><code style="background:#f1f5f9; padding:4px 8px; font-size:13px; font-weight:bold; color:#0f172a; border-radius:6px; border:1px solid #e2e8f0;"><?php echo esc_html( $item_code ); ?></code></td>
				<td data-order="<?php echo esc_attr( (string) $item_val ); ?>"><strong><?php echo esc_html( $discount_display ); ?></strong></td>
				<td data-order="<?php echo esc_attr( (string) $item_min ); ?>"><?php echo esc_html( $item_min > 0 ? $currency_symbol . number_format( $item_min, 2 ) : '—' ); ?></td>
				<td data-order="<?php echo esc_attr( empty( $item_expiry ) ? '9999-12-31' : $item_expiry ); ?>"><?php echo esc_html( $item_expiry ?: 'Never' ); ?></td>
				<td>
					<?php 
					$badge_bg    = '#dcfce7'; 
					$badge_color = '#15803d';
					if ( 'disabled' === $item_status ) { 
						$badge_bg    = '#fee2e2'; 
						$badge_color = '#b91c1c'; 
					}
					?>
					<span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; text-transform:uppercase;">
						<?php echo esc_html( $item_status ); ?>
					</span>
				</td>
				<td style="text-align:right;">
					<div class="acrb-actions-group">
						<a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this promo code?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
			<tr>
				<td colspan="6" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No promo codes found.', 'awesome-car-rental' ); ?></td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>