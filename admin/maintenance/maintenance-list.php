<?php
/**
 * Maintenance List Component (`maintenance-list.php`)
 * Location: admin/maintenance/maintenance-list.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.7
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
$currency_symbol   = get_option( 'acrb_currency', '$' );
$page_slug         = 'awesome_car_rental';
$base_url          = admin_url( 'admin.php?page=' . $page_slug . '&tab=maintenance' );
?>

<style>
    .acrb-table-wrap {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        margin-top: 16px;
    }
    .acrb-maint-table {
        width: 100% !important;
        border-collapse: collapse;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-maint-table th,
    .acrb-maint-table td {
        vertical-align: middle;
    }
    .acrb-maint-th-actions {
        text-align: right !important;
        width: 110px;
    }
    .acrb-maint-car-name {
        color: #0f172a;
        font-size: 13.5px;
        font-weight: 700;
    }
    .acrb-maint-type-badge {
        background: #eff6ff;
        color: #4f46e5;
        padding: 3px 9px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
        white-space: nowrap;
    }
    .acrb-maint-cost-val {
        white-space: nowrap;
        color: #059669;
        font-weight: 700;
        font-size: 13.5px;
    }
    .acrb-maint-date-val {
        color: #475569;
        font-size: 13px;
        font-weight: 500;
    }

    /* Status Badges */
    .acrb-status-pill {
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
        white-space: nowrap;
    }
    .acrb-status-pending {
        background: #fef3c7;
        color: #d97706;
    }
    .acrb-status-in-progress {
        background: #e0f2fe;
        color: #0369a1;
    }
    .acrb-status-completed {
        background: #dcfce7;
        color: #15803d;
    }

    /* Action Buttons */
    .acrb-maint-cell-actions {
        text-align: right !important;
    }
    .acrb-maint-actions-group {
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
    .acrb-maint-empty {
        text-align: center;
        padding: 44px 20px;
        color: #64748b;
        font-size: 13.5px;
    }
</style>

<div class="acrb-table-wrap">
    <table class="acrb-datatable acrb-maint-table widefat striped display nowrap">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Vehicle Name', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Service Type', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Cost', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Date In', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                <th class="all acrb-maint-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $records = $wpdb->get_results( "SELECT * FROM {$table_maintenance} ORDER BY id DESC", ARRAY_A );

            if ( ! empty( $records ) ) :
                foreach ( $records as $rec ) :
                    $m_id     = absint( $rec['id'] );
                    $car_name = esc_html( $rec['car_name'] ?? 'Fleet Vehicle' );
                    $m_type   = esc_html( $rec['service_type'] ?? 'Routine Service' );
                    $m_cost   = floatval( $rec['cost'] ?? 0.00 );
                    $m_date   = esc_html( $rec['date_in'] ?? '—' );
                    $m_status = sanitize_key( $rec['status'] ?? 'pending' );

                    $status_class = 'acrb-status-pending';
                    if ( 'in_progress' === $m_status ) {
                        $status_class = 'acrb-status-in-progress';
                    } elseif ( 'completed' === $m_status ) {
                        $status_class = 'acrb-status-completed';
                    }
                    
                    $view_url = add_query_arg( array( 'view' => $m_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'maint_id' => $m_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'maint_id' => $m_id ), $base_url ), 'acrb_del_maint_' . $m_id );
            ?>
            <tr>
                <td><strong class="acrb-maint-car-name"><?php echo esc_html( $car_name ); ?></strong></td>
                <td>
                    <span class="acrb-maint-type-badge">
                        <?php echo esc_html( $m_type ); ?>
                    </span>
                </td>
                <td data-order="<?php echo esc_attr( (string) $m_cost ); ?>">
                    <strong class="acrb-maint-cost-val"><?php echo esc_html( $currency_symbol . number_format( $m_cost, 2 ) ); ?></strong>
                </td>
                <td data-order="<?php echo esc_attr( $m_date ); ?>">
                    <span class="acrb-maint-date-val"><?php echo esc_html( $m_date ); ?></span>
                </td>
                <td>
                    <span class="acrb-status-pill <?php echo esc_attr( $status_class ); ?>">
                        ● <?php echo esc_html( str_replace( '_', ' ', $m_status ) ); ?>
                    </span>
                </td>
                <td class="acrb-maint-cell-actions">
                    <div class="acrb-maint-actions-group">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this maintenance record?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr>
                <td colspan="6" class="acrb-maint-empty">
                    <?php esc_html_e( 'No maintenance records found in database.', 'awesome-car-rental' ); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>