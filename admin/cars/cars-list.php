<?php
/**
 * Cars List Component (`cars-list.php`)
 * Location: admin/cars/cars-list.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.7
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;

$acrb_table_cars      = $wpdb->prefix . 'dev_nahian_cars';
$acrb_currency_symbol = get_option( 'acrb_currency', '$' );
$acrb_currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$acrb_rental_unit     = get_option( 'acrb_rental_unit', 'day' );
$acrb_page_slug       = 'awesome_car_rental';
$acrb_base_url        = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=cars' );

$acrb_format_price = static function( float $val ) use ( $acrb_currency_symbol, $acrb_currency_pos ): string {
	$formatted = number_format( $val, 2 );
	return ( 'left' === $acrb_currency_pos ) ? $acrb_currency_symbol . $formatted : $formatted . $acrb_currency_symbol;
};

// Retrieve all fleet records directly from custom SQL table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_cars = $wpdb->get_results( "SELECT * FROM {$acrb_table_cars} ORDER BY id DESC", ARRAY_A );
?>

<style>
	.acrb-cars-table-wrap {
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
		box-sizing: border-box;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	}
	.acrb-cars-table-wrap *,
	.acrb-cars-table-wrap *::before,
	.acrb-cars-table-wrap *::after {
		box-sizing: border-box;
	}
	.acrb-cars-datatable {
		width: 100% !important;
		border-collapse: collapse;
		font-family: inherit;
	}
	.acrb-cars-datatable thead th {
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
	.acrb-cars-datatable tbody td {
		vertical-align: middle;
		padding: 14px 16px;
		border-bottom: 1px solid #f1f5f9;
		color: #334155;
		font-size: 13px;
	}
	.acrb-cars-datatable tbody tr:hover {
		background-color: #fcfdfe;
	}

	/* Vehicle Meta */
	.acrb-car-title-text {
		font-size: 14px;
		font-weight: 800;
		color: #0f172a;
		margin-bottom: 3px;
		display: block;
		line-height: 1.3;
	}
	.acrb-car-meta-sub {
		display: flex;
		align-items: center;
		gap: 6px;
		flex-wrap: wrap;
	}
	.acrb-car-vin-badge {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		color: #64748b;
		font-size: 11px;
		background: #f1f5f9;
		padding: 2px 6px;
		border-radius: 4px;
		border: 1px solid #e2e8f0;
		font-weight: 600;
		letter-spacing: 0.02em;
	}
	.acrb-car-plate-badge {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		color: #475569;
		font-size: 11px;
		background: #ffffff;
		padding: 2px 6px;
		border-radius: 4px;
		border: 1px solid #cbd5e1;
		font-weight: 700;
	}

	/* Category Pill */
	.acrb-car-category-pill {
		background: #eff6ff;
		color: #4f46e5;
		border: 1px solid #dbeafe;
		padding: 3px 9px;
		border-radius: 9999px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		display: inline-block;
		white-space: nowrap;
	}

	/* Rate Display */
	.acrb-car-rate-val {
		color: #059669;
		font-size: 14px;
		font-weight: 800;
		letter-spacing: -0.01em;
	}
	.acrb-car-rate-unit {
		font-size: 11.5px;
		color: #64748b;
		font-weight: 500;
	}

	/* Actions Column */
	.acrb-cars-th-actions {
		text-align: right !important;
		width: 120px;
	}
	.acrb-cars-cell-actions {
		text-align: right !important;
	}
	.acrb-cars-actions-group {
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
	.acrb-cars-empty-row {
		text-align: center;
		padding: 48px 20px;
		color: #64748b;
		font-size: 13.5px;
	}
</style>

<div class="acrb-cars-table-wrap">
	<table class="acrb-datatable acrb-cars-datatable widefat striped display nowrap">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Vehicle & Registry', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Base Rate', 'awesome-car-rental' ); ?></th>
				<th class="all acrb-cars-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			if ( ! empty( $acrb_cars ) ) :
				foreach ( $acrb_cars as $acrb_car ) :
					$acrb_c_id    = absint( $acrb_car['id'] );
					$acrb_c_name  = esc_html( $acrb_car['name'] );
					$acrb_c_brand = esc_html( $acrb_car['brand'] ?: '' );
					$acrb_c_year  = absint( $acrb_car['year'] ?: 2026 );
					$acrb_c_vin   = esc_html( $acrb_car['vin'] ?: '—' );
					$acrb_c_plate = esc_html( $acrb_car['plate'] ?: '' );
					$acrb_c_cat   = esc_html( $acrb_car['category'] ?: __( 'General Fleet', 'awesome-car-rental' ) );
					$acrb_c_price = floatval( $acrb_car['price'] ?? 0.00 );

					$acrb_view_url = add_query_arg( array( 'sub' => 'view', 'car_id' => $acrb_c_id ), $acrb_base_url );
					$acrb_edit_url = add_query_arg( array( 'sub' => 'edit', 'car_id' => $acrb_c_id ), $acrb_base_url );
					$acrb_del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'car_id' => $acrb_c_id ), $acrb_base_url ), 'acrb_del_car_' . $acrb_c_id );
			?>
			<tr>
				<td>
					<strong class="acrb-car-title-text">
						<?php echo esc_html( ( $acrb_c_brand ? $acrb_c_brand . ' ' : '' ) . $acrb_c_name ); ?>
						<?php if ( $acrb_c_year ) : ?>
							<span style="font-weight: 500; color: #64748b; font-size: 12px;">(<?php echo esc_html( (string) $acrb_c_year ); ?>)</span>
						<?php endif; ?>
					</strong>
					<div class="acrb-car-meta-sub">
						<span class="acrb-car-vin-badge">VIN: <?php echo esc_html( $acrb_c_vin ); ?></span>
						<?php if ( ! empty( $acrb_c_plate ) ) : ?>
							<span class="acrb-car-plate-badge"><?php echo esc_html( $acrb_c_plate ); ?></span>
						<?php endif; ?>
					</div>
				</td>
				<td>
					<span class="acrb-car-category-pill">
						<?php echo esc_html( $acrb_c_cat ); ?>
					</span>
				</td>
				<td data-order="<?php echo esc_attr( (string) $acrb_c_price ); ?>">
					<strong class="acrb-car-rate-val"><?php echo esc_html( $acrb_format_price( $acrb_c_price ) ); ?></strong>
					<span class="acrb-car-rate-unit">/ <?php echo esc_html( $acrb_rental_unit ); ?></span>
				</td>
				<td class="acrb-cars-cell-actions">
					<div class="acrb-cars-actions-group">
						<a href="<?php echo esc_url( $acrb_view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit Vehicle', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this fleet vehicle?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete Vehicle', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
			<tr>
				<td colspan="4" class="acrb-cars-empty-row">
					<?php esc_html_e( 'No vehicles found in fleet. Click "Add New Vehicle" above to list your first car.', 'awesome-car-rental' ); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>