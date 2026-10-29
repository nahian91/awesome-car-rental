<?php
/**
 * Maintenance CRM View Component (`maintenance-view.php`)
 * Location: admin/maintenance/maintenance-view.php
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
$maint_id          = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
$page_slug         = 'awesome_car_rental';
$base_url          = admin_url( 'admin.php?page=' . $page_slug . '&tab=maintenance' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$maint_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_maintenance} WHERE id = %d", $maint_id ), ARRAY_A );

if ( ! $maint_record ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Maintenance record not found.', 'awesome-car-rental' ) . '</p></div>';
    return;
}

$v_name   = esc_html( $maint_record['car_name'] ?? 'Fleet Vehicle' );
$v_type   = esc_html( $maint_record['service_type'] ?? 'Routine Service' );
$v_cost   = floatval( $maint_record['cost'] ?? 0.00 );
$v_date   = esc_html( $maint_record['date_in'] ?? '—' );
$v_status = sanitize_key( $maint_record['status'] ?? 'pending' );
$v_notes  = esc_html( $maint_record['notes'] ?? '' );
$v_logged = esc_html( $maint_record['created_at'] ?? '—' );
$edit_url = add_query_arg( array( 'sub' => 'edit', 'maint_id' => $maint_id ), $base_url );

$is_done     = ( 'completed' === $v_status );
$is_progress = ( 'in_progress' === $v_status );
$badge_bg    = $is_done ? '#dcfce7' : ( $is_progress ? '#e0f2fe' : '#fef3c7' );
$badge_color = $is_done ? '#15803d' : ( $is_progress ? '#0369a1' : '#d97706' );
?>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start;">
    <!-- Maintenance Record Dossier Card -->
    <div class="acrb-card" style="text-align: center;">
        <div style="width: 72px; height: 72px; background: #eef2ff; color: #4f46e5; font-size: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; border: 2px solid #e0e7ff;">
            <span class="dashicons dashicons-hammer" style="font-size: 32px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></span>
        </div>
        <h3 style="margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $v_name ); ?></h3>
        <span style="background: #eff6ff; color: #4f46e5; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
            <?php echo esc_html( $v_type ); ?>
        </span>

        <div style="margin-top: 20px; text-align: left; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Repair Cost', 'awesome-car-rental' ); ?></span>
                <strong style="color: #059669;"><?php echo esc_html( $currency_symbol . number_format( $v_cost, 2 ) ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Service Date', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $v_date ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Current Status', 'awesome-car-rental' ); ?></span>
                <span style="background: <?php echo esc_attr( $badge_bg ); ?>; color: <?php echo esc_attr( $badge_color ); ?>; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                    ● <?php echo esc_html( str_replace( '_', ' ', $v_status ) ); ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Record Logged', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $v_logged ); ?></strong>
            </div>
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-primary" style="width: 100%;">
                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span><?php esc_html_e( 'Edit Record', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
    </div>

    <!-- Repair Details & Parts Log -->
    <div class="acrb-card">
        <h2><?php esc_html_e( 'Repair Details & Parts Log', 'awesome-car-rental' ); ?></h2>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px;">
            <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 700; color: #334155; text-transform: uppercase;"><?php esc_html_e( 'Technician Notes', 'awesome-car-rental' ); ?></h4>
            <p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6; white-space: pre-line;">
                <?php echo ! empty( $v_notes ) ? esc_html( $v_notes ) : esc_html__( 'No specific repair notes or parts replacement details provided for this service ticket.', 'awesome-car-rental' ); ?>
            </p>
        </div>
    </div>
</div>