<?php
/**
 * Inspections List Component (`inspections-list.php`)
 * Location: admin/inspections/inspections-list.php
 *
 * @package Awesome_Car_Rental
 * @since   2.6.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$acrb_table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$acrb_table_cars        = $wpdb->prefix . 'dev_nahian_cars';
$acrb_page_slug         = 'awesome_car_rental';
$acrb_base_url          = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=inspections' );
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
	.acrb-insp-table {
		width: 100% !important;
		border-collapse: collapse;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	}
	.acrb-insp-table th,
	.acrb-insp-table td {
		vertical-align: middle;
	}
	.acrb-insp-th-actions {
		text-align: right !important;
		width: 120px;
	}
	.acrb-insp-car-title {
		color: #0f172a;
		font-size: 13.5px;
		font-weight: 700;
	}
	.acrb-insp-meta-text {
		color: #475569;
		font-size: 13px;
		font-weight: 500;
	}
	.acrb-insp-date-val {
		color: #64748b;
		font-size: 12.5px;
		font-weight: 500;
		white-space: nowrap;
	}

	/* Stage Badges */
	.acrb-insp-stage-badge {
		padding: 3px 10px;
		border-radius: 9999px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		display: inline-block;
		white-space: nowrap;
	}
	.acrb-stage-checkout {
		background: #e0f2fe;
		color: #0369a1;
	}
	.acrb-stage-checkin {
		background: #dcfce7;
		color: #15803d;
	}
	.acrb-stage-periodic {
		background: #fef3c7;
		color: #d97706;
	}

	/* Action Buttons */
	.acrb-insp-cell-actions {
		text-align: right !important;
	}
	.acrb-insp-actions-group {
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
		border-radius: 6px;
		color: #475569;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		text-decoration: none;
		transition: all 0.15s ease;
	}
	.acrb-btn-action:hover {
		background: #f1f5f9;
		color: #0f172a;
		border-color: #cbd5e1;
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
	.acrb-insp-empty {
		text-align: center;
		padding: 44px 20px;
		color: #64748b;
		font-size: 13.5px;
	}
</style>

<div class="acrb-table-wrap">
	<table class="acrb-datatable acrb-insp-table widefat striped display nowrap">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Stage', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Mileage / Fuel', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Inspector', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
				<th class="all acrb-insp-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$acrb_reports = $wpdb->get_results( "SELECT * FROM {$acrb_table_inspections} ORDER BY id DESC", ARRAY_A );

			if ( ! empty( $acrb_reports ) ) :
				foreach ( $acrb_reports as $acrb_rep ) :
					$acrb_rep_id = absint( $acrb_rep['id'] );
					$acrb_car_id = absint( $acrb_rep['car_id'] );
					
					// Fallback to table lookup if car_name column is empty.
					$acrb_car_title = ! empty( $acrb_rep['car_name'] ) ? $acrb_rep['car_name'] : '';
					if ( empty( $acrb_car_title ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$acrb_car_title = $wpdb->get_var(
							$wpdb->prepare(
								// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								"SELECT name FROM {$acrb_table_cars} WHERE id = %d",
								$acrb_car_id
							)
						) ?: __( 'Unknown Vehicle', 'awesome-car-rental' );
					}
					
					$acrb_i_stage   = (string) ( $acrb_rep['stage'] ?? 'Check-Out' );
					$acrb_i_mileage = (string) ( $acrb_rep['mileage'] ?? '—' );
					$acrb_i_fuel    = (string) ( $acrb_rep['fuel_level'] ?? '—' );
					$acrb_inspector = (string) ( $acrb_rep['inspector'] ?? 'Admin' );
					$acrb_i_date    = (string) ( $acrb_rep['inspection_date'] ?? '' );
					
					$acrb_stage_class = 'acrb-stage-checkout';
					if ( 'Check-In' === $acrb_i_stage ) {
						$acrb_stage_class = 'acrb-stage-checkin';
					} elseif ( 'Periodic Check' === $acrb_i_stage ) {
						$acrb_stage_class = 'acrb-stage-periodic';
					}

					$acrb_view_url = add_query_arg( array( 'sub' => 'view', 'insp_id' => $acrb_rep_id ), $acrb_base_url );
					$acrb_edit_url = add_query_arg( array( 'sub' => 'edit', 'insp_id' => $acrb_rep_id ), $acrb_base_url );
					$acrb_del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'insp_id' => $acrb_rep_id ), $acrb_base_url ), 'acrb_del_insp_' . $acrb_rep_id );
			?>
			<tr>
				<td><strong class="acrb-insp-car-title"><?php echo esc_html( $acrb_car_title ); ?></strong></td>
				<td>
					<span class="acrb-insp-stage-badge <?php echo esc_attr( $acrb_stage_class ); ?>">
						<?php echo esc_html( $acrb_i_stage ); ?>
					</span>
				</td>
				<td>
					<span class="acrb-insp-meta-text"><?php echo esc_html( $acrb_i_mileage . ' / ' . $acrb_i_fuel ); ?></span>
				</td>
				<td>
					<span class="acrb-insp-meta-text"><?php echo esc_html( $acrb_inspector ); ?></span>
				</td>
				<td data-order="<?php echo esc_attr( $acrb_i_date ); ?>">
					<span class="acrb-insp-date-val"><?php echo esc_html( $acrb_i_date ); ?></span>
				</td>
				<td class="acrb-insp-cell-actions">
					<div class="acrb-insp-actions-group">
						<a href="<?php echo esc_url( $acrb_view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this inspection report?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
			<tr>
				<td colspan="6" class="acrb-insp-empty">
					<?php esc_html_e( 'No inspection reports found in database.', 'awesome-car-rental' ); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>