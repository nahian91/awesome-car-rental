<?php
/**
 * Promo Codes List Component (`promos-list.php`)
 * Location: admin/promos/promos-list.php
 *
 * @package Awesome_Car_Rental
 * @since   3.0.0
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
$acrb_page_slug       = 'awesome_car_rental';
$acrb_base_url        = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=promo_codes' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_promo_list = $wpdb->get_results( "SELECT * FROM {$acrb_table_promos} ORDER BY id DESC", ARRAY_A );
?>

<style>
	.acrb-table-wrap {
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
		margin-top: 16px;
		box-sizing: border-box;
	}
	.acrb-table-wrap *,
	.acrb-table-wrap *::before,
	.acrb-table-wrap *::after {
		box-sizing: border-box;
	}
	.acrb-promos-table {
		width: 100% !important;
		border-collapse: collapse;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	}
	.acrb-promos-table thead th {
		background: #f8fafc;
		color: #475569;
		font-weight: 700;
		font-size: 12px;
		text-transform: uppercase;
		letter-spacing: 0.03em;
		padding: 13px 16px;
		border-bottom: 1px solid #e2e8f0;
		vertical-align: middle;
	}
	.acrb-promos-table tbody td {
		vertical-align: middle;
		padding: 14px 16px;
		border-bottom: 1px solid #f1f5f9;
		color: #334155;
		font-size: 13px;
	}
	.acrb-promos-table tbody tr:hover {
		background-color: #fcfdfe;
	}

	/* Coupon Tag */
	.acrb-promo-tag-code {
		background: #f1f5f9;
		padding: 4px 10px;
		font-size: 13px;
		font-weight: 800;
		color: #0f172a;
		border-radius: 6px;
		border: 1px solid #e2e8f0;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		letter-spacing: 0.04em;
		display: inline-block;
	}

	/* Values & Metrics */
	.acrb-promo-discount-text {
		font-size: 14px;
		font-weight: 800;
		color: #059669;
	}
	.acrb-promo-subtext {
		color: #64748b;
		font-size: 12.5px;
	}
	.acrb-promo-date-text {
		color: #475569;
		font-weight: 500;
	}
	.acrb-promo-expired-text {
		color: #dc2626;
		font-weight: 700;
	}

	/* Status Pills */
	.acrb-promo-status-badge {
		padding: 3px 10px;
		border-radius: 9999px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		display: inline-block;
		white-space: nowrap;
	}
	.acrb-promo-status-active {
		background: #ecfdf5;
		color: #059669;
		border: 1px solid #a7f3d0;
	}
	.acrb-promo-status-disabled {
		background: #fef2f2;
		color: #991b1b;
		border: 1px solid #fecaca;
	}

	/* Action Buttons */
	.acrb-promos-th-actions {
		text-align: right !important;
		width: 110px;
	}
	.acrb-promos-cell-actions {
		text-align: right !important;
	}
	.acrb-promos-actions-group {
		display: inline-flex;
		align-items: center;
		justify-content: flex-end;
		gap: 6px;
	}
	.acrb-btn-action {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		border-radius: 7px;
		color: #475569;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		text-decoration: none;
		transition: all 0.15s ease;
		box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
	}
	.acrb-btn-action:hover {
		background: #f8fafc;
		color: #0f172a;
		border-color: #cbd5e1;
		transform: translateY(-1px);
	}
	.acrb-btn-action .dashicons {
		font-size: 15px;
		width: 15px;
		height: 15px;
	}
	.acrb-btn-delete {
		color: #dc2626;
		border-color: #fee2e2;
		background: #fff5f5;
	}
	.acrb-btn-delete:hover {
		background: #fee2e2;
		color: #991b1b;
		border-color: #fca5a5;
	}

	/* Empty State */
	.acrb-promos-empty {
		text-align: center;
		padding: 48px 20px;
		color: #64748b;
		font-size: 13.5px;
	}
</style>

<div class="acrb-table-wrap">
	<table class="acrb-datatable acrb-promos-table widefat striped display nowrap">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Promo Code', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Discount', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Min. Spend', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Max Uses', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Expiry Date', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
				<th class="all acrb-promos-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			if ( ! empty( $acrb_promo_list ) ) :
				$acrb_current_time = current_time( 'Y-m-d' );

				foreach ( $acrb_promo_list as $acrb_promo ) :
					$acrb_promo_id    = absint( $acrb_promo['id'] );
					$acrb_item_code   = (string) ( $acrb_promo['code'] ?? '' );
					$acrb_item_type   = (string) ( $acrb_promo['discount_type'] ?? 'percentage' );
					$acrb_item_val    = floatval( $acrb_promo['discount_value'] ?? 0 );
					$acrb_item_min    = floatval( $acrb_promo['min_spend'] ?? 0 );
					$acrb_item_expiry = trim( (string) ( $acrb_promo['expiry_date'] ?? '' ) );
					$acrb_item_uses   = absint( $acrb_promo['max_uses'] ?? 0 );
					$acrb_item_status = sanitize_key( $acrb_promo['status'] ?? 'active' );
					
					$acrb_discount_display = ( 'percentage' === $acrb_item_type ) ? $acrb_item_val . '%' : $acrb_currency_symbol . number_format( $acrb_item_val, 2 );

					// Check expiration.
					$acrb_is_expired = ( ! empty( $acrb_item_expiry ) && $acrb_item_expiry < $acrb_current_time );

					$acrb_edit_url = add_query_arg( array( 'sub' => 'edit', 'promo_id' => $acrb_promo_id ), $acrb_base_url );
					$acrb_del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'promo_id' => $acrb_promo_id ), $acrb_base_url ), 'acrb_del_promo_' . $acrb_promo_id );

					$acrb_status_class = ( 'active' === $acrb_item_status && ! $acrb_is_expired ) ? 'acrb-promo-status-active' : 'acrb-promo-status-disabled';
					$acrb_status_label = ( $acrb_is_expired && 'active' === $acrb_item_status ) ? __( 'Expired', 'awesome-car-rental' ) : ucfirst( $acrb_item_status );
			?>
			<tr>
				<td>
					<code class="acrb-promo-tag-code"><?php echo esc_html( $acrb_item_code ); ?></code>
				</td>
				<td data-order="<?php echo esc_attr( (string) $acrb_item_val ); ?>">
					<strong class="acrb-promo-discount-text"><?php echo esc_html( $acrb_discount_display ); ?></strong>
				</td>
				<td data-order="<?php echo esc_attr( (string) $acrb_item_min ); ?>">
					<?php echo esc_html( $acrb_item_min > 0 ? $acrb_currency_symbol . number_format( $acrb_item_min, 2 ) : '—' ); ?>
				</td>
				<td data-order="<?php echo esc_attr( (string) $acrb_item_uses ); ?>">
					<span class="acrb-promo-subtext">
						<?php
						if ( $acrb_item_uses > 0 ) {
							/* translators: %d: Maximum number of promo code redemptions */
							printf( esc_html__( '%d redemptions', 'awesome-car-rental' ), esc_html( (string) $acrb_item_uses ) );
						} else {
							esc_html_e( 'Unlimited', 'awesome-car-rental' );
						}
						?>
					</span>
				</td>
				<td data-order="<?php echo esc_attr( empty( $acrb_item_expiry ) ? '9999-12-31' : $acrb_item_expiry ); ?>">
					<?php if ( empty( $acrb_item_expiry ) ) : ?>
						<span class="acrb-promo-subtext"><?php esc_html_e( 'No Expiry', 'awesome-car-rental' ); ?></span>
					<?php elseif ( $acrb_is_expired ) : ?>
						<span class="acrb-promo-expired-text"><?php echo esc_html( $acrb_item_expiry ); ?> (<?php esc_html_e( 'Expired', 'awesome-car-rental' ); ?>)</span>
					<?php else : ?>
						<span class="acrb-promo-date-text"><?php echo esc_html( $acrb_item_expiry ); ?></span>
					<?php endif; ?>
				</td>
				<td>
					<span class="acrb-promo-status-badge <?php echo esc_attr( $acrb_status_class ); ?>">
						● <?php echo esc_html( $acrb_status_label ); ?>
					</span>
				</td>
				<td class="acrb-promos-cell-actions">
					<div class="acrb-promos-actions-group">
						<a href="<?php echo esc_url( $acrb_edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit Promo Code', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this promo code?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
			<tr>
				<td colspan="7" class="acrb-promos-empty">
					<?php esc_html_e( 'No promo codes found in the database.', 'awesome-car-rental' ); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>