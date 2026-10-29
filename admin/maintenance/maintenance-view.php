<?php
/**
 * Maintenance CRM View Component (`maintenance-view.php`)
 * Location: admin/maintenance/maintenance-view.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.8
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
$currency_symbol   = get_option( 'acrb_currency', '$' );
$maint_id          = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
$page_slug         = 'awesome_car_rental';
$base_url          = admin_url( 'admin.php?page=' . $page_slug . '&tab=maintenance' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$maint_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_maintenance} WHERE id = %d", $maint_id ), ARRAY_A );

if ( ! $maint_record ) {
    ?>
    <div class="acrb-maint-notfound">
        <p><?php esc_html_e( 'Maintenance record not found.', 'awesome-car-rental' ); ?></p>
        <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-maint-btn-back">
            &larr; <?php esc_html_e( 'Back to All Maintenance', 'awesome-car-rental' ); ?>
        </a>
    </div>
    <?php
    return;
}

$v_name      = esc_html( $maint_record['car_name'] ?? 'Fleet Vehicle' );
$v_type      = esc_html( $maint_record['service_type'] ?? 'Routine Service' );
$v_cost      = floatval( $maint_record['cost'] ?? 0.00 );
$v_date      = esc_html( $maint_record['date_in'] ?? '—' );
$v_status    = sanitize_key( $maint_record['status'] ?? 'pending' );
$v_notes     = esc_html( $maint_record['notes'] ?? '' );
$v_logged    = esc_html( $maint_record['created_at'] ?? '—' );
$edit_url    = add_query_arg( array( 'sub' => 'edit', 'maint_id' => $maint_id ), $base_url );

$status_class = 'acrb-status-pending';
if ( 'in_progress' === $v_status ) {
    $status_class = 'acrb-status-in-progress';
} elseif ( 'completed' === $v_status ) {
    $status_class = 'acrb-status-completed';
}
?>

<style>
    .acrb-maint-view-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 20px;
        align-items: start;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-maint-view-grid *,
    .acrb-maint-view-grid *::before,
    .acrb-maint-view-grid *::after {
        box-sizing: border-box;
    }

    /* Cards */
    .acrb-maint-view-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
    }
    .acrb-maint-dossier {
        text-align: center;
    }

    /* Avatar Icon Wrap */
    .acrb-maint-avatar-wrap {
        width: 72px;
        height: 72px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px auto;
        border: 2px solid #e0e7ff;
    }
    .acrb-maint-avatar-wrap .dashicons {
        font-size: 32px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Titles & Badges */
    .acrb-maint-view-name {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
    }
    .acrb-maint-view-type {
        background: #eff6ff;
        color: #4f46e5;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
    }

    /* Meta Details List */
    .acrb-maint-meta-list {
        margin-top: 20px;
        text-align: left;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 13px;
    }
    .acrb-maint-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .acrb-maint-meta-label {
        color: #64748b;
        font-weight: 500;
    }
    .acrb-maint-meta-val {
        color: #0f172a;
        font-weight: 700;
    }
    .acrb-maint-meta-cost {
        color: #059669;
        font-weight: 700;
        font-size: 14px;
    }

    /* Status Pills */
    .acrb-status-pill {
        padding: 2px 8px;
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

    /* Action Links */
    .acrb-maint-view-actions {
        margin-top: 20px;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .acrb-maint-btn-edit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        transition: background 0.15s ease;
    }
    .acrb-maint-btn-edit:hover {
        background: #4338ca;
        color: #ffffff;
    }
    .acrb-maint-btn-edit .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .acrb-maint-btn-back {
        color: #64748b;
        text-decoration: none;
        font-size: 12.5px;
        font-weight: 600;
        text-align: center;
        padding: 6px;
        transition: color 0.15s ease;
    }
    .acrb-maint-btn-back:hover {
        color: #0f172a;
    }

    /* Details Panel */
    .acrb-maint-panel-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 16px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }
    .acrb-maint-details-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
    }
    .acrb-maint-details-heading {
        margin: 0 0 10px 0;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .acrb-maint-details-text {
        margin: 0;
        font-size: 13.5px;
        color: #334155;
        line-height: 1.6;
        white-space: pre-line;
    }

    /* Not Found Alert */
    .acrb-maint-notfound {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    @media (max-width: 900px) {
        .acrb-maint-view-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="acrb-maint-view-grid">
    <!-- Dossier Card -->
    <div class="acrb-maint-view-card acrb-maint-dossier">
        <div class="acrb-maint-avatar-wrap">
            <span class="dashicons dashicons-hammer"></span>
        </div>

        <h3 class="acrb-maint-view-name"><?php echo esc_html( $v_name ); ?></h3>
        <span class="acrb-maint-view-type"><?php echo esc_html( $v_type ); ?></span>

        <div class="acrb-maint-meta-list">
            <div class="acrb-maint-meta-row">
                <span class="acrb-maint-meta-label"><?php esc_html_e( 'Repair Cost', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-maint-meta-cost"><?php echo esc_html( $currency_symbol . number_format( $v_cost, 2 ) ); ?></strong>
            </div>
            <div class="acrb-maint-meta-row">
                <span class="acrb-maint-meta-label"><?php esc_html_e( 'Service Date', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-maint-meta-val"><?php echo esc_html( $v_date ); ?></strong>
            </div>
            <div class="acrb-maint-meta-row">
                <span class="acrb-maint-meta-label"><?php esc_html_e( 'Current Status', 'awesome-car-rental' ); ?></span>
                <span class="acrb-status-pill <?php echo esc_attr( $status_class ); ?>">
                    ● <?php echo esc_html( str_replace( '_', ' ', $v_status ) ); ?>
                </span>
            </div>
            <div class="acrb-maint-meta-row">
                <span class="acrb-maint-meta-label"><?php esc_html_e( 'Record Logged', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-maint-meta-val"><?php echo esc_html( $v_logged ); ?></strong>
            </div>
        </div>

        <div class="acrb-maint-view-actions">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-maint-btn-edit">
                <span class="dashicons dashicons-edit"></span>
                <span><?php esc_html_e( 'Edit Record', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-maint-btn-back">
                &larr; <?php esc_html_e( 'Back to All Maintenance', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </div>

    <!-- Details Log -->
    <div class="acrb-maint-view-card">
        <h2 class="acrb-maint-panel-title"><?php esc_html_e( 'Repair Details & Parts Log', 'awesome-car-rental' ); ?></h2>
        <div class="acrb-maint-details-box">
            <h4 class="acrb-maint-details-heading"><?php esc_html_e( 'Technician Notes', 'awesome-car-rental' ); ?></h4>
            <p class="acrb-maint-details-text">
                <?php echo ! empty( $v_notes ) ? esc_html( $v_notes ) : esc_html__( 'No specific repair notes or parts replacement details provided for this service ticket.', 'awesome-car-rental' ); ?>
            </p>
        </div>
    </div>
</div>