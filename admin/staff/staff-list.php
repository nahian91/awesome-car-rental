<?php
/**
 * Staff List Component (`staff-list.php`)
 * Location: admin/staff/staff-list.php
 *
 * @package Awesome_Car_Rental
 * @since   2.9.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$acrb_table_staff = $wpdb->prefix . 'dev_nahian_staff';
$acrb_page_slug   = 'awesome_car_rental';
$acrb_base_url    = admin_url( 'admin.php?page=' . $acrb_page_slug . '&tab=staff' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$acrb_staff_list = $wpdb->get_results( "SELECT * FROM {$acrb_table_staff} ORDER BY id DESC", ARRAY_A );
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
	.acrb-staff-table {
		width: 100% !important;
		border-collapse: collapse;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	}
	.acrb-staff-table th,
	.acrb-staff-table td {
		vertical-align: middle;
		padding: 12px 16px;
	}
	.acrb-staff-th-avatar {
		width: 50px;
	}
	.acrb-staff-th-actions {
		text-align: right !important;
		width: 120px;
	}

	/* Avatar and Member Meta */
	.acrb-staff-avatar-circle {
		width: 36px;
		height: 36px;
		min-width: 36px;
		border-radius: 50%;
		background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
		color: #4338ca;
		font-size: 13px;
		font-weight: 800;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1.5px solid #c7d2fe;
	}
	.acrb-staff-name-text {
		color: #0f172a;
		display: block;
		line-height: 1.25;
		font-size: 13.5px;
		font-weight: 700;
	}
	.acrb-staff-email-text {
		font-size: 12px;
		color: #64748b;
		display: block;
	}
	.acrb-staff-info-text {
		color: #475569;
		font-size: 13px;
		font-weight: 500;
	}

	/* Badges */
	.acrb-staff-role-badge {
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
	.acrb-staff-status-badge {
		padding: 3px 9px;
		border-radius: 9999px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		display: inline-block;
		white-space: nowrap;
	}
	.acrb-status-active {
		background: #ecfdf5;
		color: #059669;
		border: 1px solid #a7f3d0;
	}
	.acrb-status-inactive {
		background: #f1f5f9;
		color: #64748b;
		border: 1px solid #e2e8f0;
	}

	/* Actions */
	.acrb-staff-cell-actions {
		text-align: right !important;
	}
	.acrb-staff-actions-group {
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
	.acrb-staff-empty {
		text-align: center;
		padding: 44px 20px;
		color: #64748b;
		font-size: 13.5px;
	}
</style>

<div class="acrb-table-wrap">
	<table class="acrb-datatable acrb-staff-table widefat striped display nowrap">
		<thead>
			<tr>
				<th class="acrb-staff-th-avatar" data-orderable="false"><?php esc_html_e( 'Avatar', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Name & Email', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Designation', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
				<th class="all acrb-staff-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			if ( ! empty( $acrb_staff_list ) ) :
				foreach ( $acrb_staff_list as $acrb_member ) :
					$acrb_m_id     = absint( $acrb_member['id'] );
					$acrb_m_name   = esc_html( $acrb_member['name'] );
					$acrb_m_email  = esc_html( $acrb_member['email'] );
					$acrb_m_phone  = esc_html( $acrb_member['phone'] ?: '—' );
					$acrb_m_role   = esc_html( $acrb_member['role'] ?: 'Front Desk Agent' );
					$acrb_m_status = sanitize_key( $acrb_member['status'] ?? 'active' );
					$acrb_initial  = strtoupper( mb_substr( trim( $acrb_m_name ), 0, 1 ) ?: 'S' );

					$acrb_view_url = add_query_arg( array( 'view' => $acrb_m_id ), $acrb_base_url );
					$acrb_edit_url = add_query_arg( array( 'sub' => 'edit', 'staff_id' => $acrb_m_id ), $acrb_base_url );
					$acrb_del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'staff_id' => $acrb_m_id ), $acrb_base_url ), 'acrb_del_staff_' . $acrb_m_id );

					$acrb_is_act       = ( 'active' === $acrb_m_status );
					$acrb_status_class = $acrb_is_act ? 'acrb-status-active' : 'acrb-status-inactive';
			?>
			<tr>
				<td>
					<div class="acrb-staff-avatar-circle">
						<?php echo esc_html( $acrb_initial ); ?>
					</div>
				</td>
				<td>
					<strong class="acrb-staff-name-text"><?php echo esc_html( $acrb_m_name ); ?></strong>
					<span class="acrb-staff-email-text"><?php echo esc_html( $acrb_m_email ); ?></span>
				</td>
				<td>
					<span class="acrb-staff-role-badge">
						<?php echo esc_html( $acrb_m_role ); ?>
					</span>
				</td>
				<td class="acrb-staff-info-text"><?php echo esc_html( $acrb_m_phone ); ?></td>
				<td>
					<span class="acrb-staff-status-badge <?php echo esc_attr( $acrb_status_class ); ?>">
						● <?php echo esc_html( ucfirst( $acrb_m_status ) ); ?>
					</span>
				</td>
				<td class="acrb-staff-cell-actions">
					<div class="acrb-staff-actions-group">
						<a href="<?php echo esc_url( $acrb_view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Profile', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this staff member?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
			<tr>
				<td colspan="6" class="acrb-staff-empty">
					<?php esc_html_e( 'No staff members found in database.', 'awesome-car-rental' ); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>