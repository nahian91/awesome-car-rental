<?php
/**
 * Maintenance List Component (`maintenance-list.php`)
 * Location: admin/maintenance/maintenance-list.php
 *
 * @package Awesome_Car_Rental
 * @since   1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
$currency_symbol   = get_option( 'acrb_currency', '£' );
$page_slug         = 'awesome_car_rental';
$base_url          = admin_url( 'admin.php?page=' . $page_slug . '&tab=maintenance' );
?>

<div class="acrb-table-wrap">
    <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Vehicle Name', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Service Type', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Cost', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Date In', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                <th class="all" style="text-align:right; width:110px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
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
                    
                    $view_url = add_query_arg( array( 'view' => $m_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'maint_id' => $m_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'maint_id' => $m_id ), $base_url ), 'acrb_del_maint_' . $m_id );
            ?>
            <tr>
                <td><strong style="color:#0f172a;"><?php echo esc_html( $car_name ); ?></strong></td>
                <td>
                    <span style="background:#eff6ff; color:#4f46e5; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; display:inline-block; white-space:nowrap;">
                        <?php echo esc_html( $m_type ); ?>
                    </span>
                </td>
                <td data-order="<?php echo esc_attr( (string) $m_cost ); ?>">
                    <strong style="white-space:nowrap; color:#059669;"><?php echo esc_html( $currency_symbol . number_format( $m_cost, 2 ) ); ?></strong>
                </td>
                <td data-order="<?php echo esc_attr( $m_date ); ?>"><?php echo esc_html( $m_date ); ?></td>
                <td>
                    <?php 
                    $badge_bg    = '#fef3c7'; 
                    $badge_color = '#d97706';
                    if ( 'in_progress' === $m_status ) { 
                        $badge_bg    = '#e0f2fe'; 
                        $badge_color = '#0369a1'; 
                    } elseif ( 'completed' === $m_status ) { 
                        $badge_bg    = '#dcfce7'; 
                        $badge_color = '#15803d'; 
                    }
                    ?>
                    <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; display:inline-block; white-space:nowrap;">
                        ● <?php echo esc_html( str_replace( '_', ' ', $m_status ) ); ?>
                    </span>
                </td>
                <td style="text-align:right;">
                    <div class="acrb-actions-group" style="display:inline-flex; gap:6px;">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this maintenance record?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:40px; color:#64748b;">
                    <?php esc_html_e( 'No maintenance records found in database.', 'awesome-car-rental' ); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>