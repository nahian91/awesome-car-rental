<?php
/**
 * Single Inspection Detail View (`inspections-view.php`)
 * Location: admin/inspections/inspections-view.php
 *
 * @package Awesome_Car_Rental
 * @since   2.6.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$table_cars        = $wpdb->prefix . 'dev_nahian_cars';

$insp_id  = isset( $_GET['insp_id'] ) ? absint( $_GET['insp_id'] ) : ( isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0 );
$back_url = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_inspections} WHERE id = %d", $insp_id ), ARRAY_A );

if ( ! $record ) {
    ?>
    <div class="acrb-insp-notfound">
        <p><?php esc_html_e( 'Inspection record not found.', 'awesome-car-rental' ); ?></p>
        <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-insp-btn-back">
            &larr; <?php esc_html_e( 'Back to All Inspections', 'awesome-car-rental' ); ?>
        </a>
    </div>
    <?php
    return;
}

// Retrieve vehicle name
$car_name = ! empty( $record['car_name'] ) ? $record['car_name'] : '';
if ( empty( $car_name ) ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $car_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", absint( $record['car_id'] ) ) ) ?: __( 'Vehicle', 'awesome-car-rental' );
}

$i_stage = (string) ( $record['stage'] ?? 'Check-Out' );
$stage_class = 'acrb-stage-checkout';
if ( 'Check-In' === $i_stage ) {
    $stage_class = 'acrb-stage-checkin';
} elseif ( 'Periodic Check' === $i_stage ) {
    $stage_class = 'acrb-stage-periodic';
}

$edit_url = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=edit&insp_id=' . absint( $record['id'] ) );
?>

<style>
    .acrb-insp-view-card {
        max-width: 900px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 28px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        box-sizing: border-box;
    }
    .acrb-insp-view-card *,
    .acrb-insp-view-card *::before,
    .acrb-insp-view-card *::after {
        box-sizing: border-box;
    }

    /* Header Bar */
    .acrb-insp-view-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 16px;
        gap: 16px;
        flex-wrap: wrap;
    }
    .acrb-insp-view-title {
        margin: 0;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .acrb-insp-view-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Action Buttons */
    .acrb-insp-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.15s ease;
        line-height: 1;
    }
    .acrb-insp-btn-secondary {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
    }
    .acrb-insp-btn-secondary:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }
    .acrb-insp-btn-secondary .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
        color: #64748b;
    }
    .acrb-insp-btn-primary {
        background: #4f46e5;
        border: 1px solid #4f46e5;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.2);
    }
    .acrb-insp-btn-primary:hover {
        background: #4338ca;
        color: #ffffff;
    }

    /* Dual Grid Panels */
    .acrb-insp-data-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    .acrb-insp-data-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .acrb-insp-data-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .acrb-insp-data-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }
    .acrb-insp-data-val {
        color: #0f172a;
        font-size: 13.5px;
        font-weight: 700;
        text-align: right;
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

    /* Damage Notes Card */
    .acrb-insp-damage-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .acrb-insp-damage-heading {
        margin: 0 0 10px;
        font-size: 12.5px;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .acrb-insp-damage-text {
        margin: 0;
        color: #475569;
        font-size: 13.5px;
        line-height: 1.6;
        white-space: pre-line;
    }

    /* Not Found Notice */
    .acrb-insp-notfound {
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
    .acrb-insp-btn-back {
        color: #991b1b;
        text-decoration: underline;
        font-weight: 700;
    }

    @media (max-width: 780px) {
        .acrb-insp-data-grid {
            grid-template-columns: 1fr;
            gap: 14px;
        }
    }
</style>

<div class="acrb-insp-view-card">
    <div class="acrb-insp-view-header">
        <h2 class="acrb-insp-view-title">
            <?php printf( esc_html__( 'Inspection Report #%d Details', 'awesome-car-rental' ), absint( $record['id'] ) ); ?>
        </h2>
        <div class="acrb-insp-view-actions">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-insp-btn acrb-insp-btn-secondary">
                <span class="dashicons dashicons-edit"></span>
                <span><?php esc_html_e( 'Edit Report', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-insp-btn acrb-insp-btn-primary">
                <span>&larr; <?php esc_html_e( 'Back to Directory', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
    </div>
    
    <div class="acrb-insp-data-grid">
        <div class="acrb-insp-data-panel">
            <div class="acrb-insp-data-row">
                <span class="acrb-insp-data-label"><?php esc_html_e( 'Vehicle Name', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-insp-data-val"><?php echo esc_html( $car_name ); ?></strong>
            </div>
            <div class="acrb-insp-data-row">
                <span class="acrb-insp-data-label"><?php esc_html_e( 'Inspection Stage', 'awesome-car-rental' ); ?></span>
                <span class="acrb-insp-stage-badge <?php echo esc_attr( $stage_class ); ?>">
                    <?php echo esc_html( $i_stage ); ?>
                </span>
            </div>
            <div class="acrb-insp-data-row">
                <span class="acrb-insp-data-label"><?php esc_html_e( 'Inspection Date & Time', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-insp-data-val"><?php echo esc_html( (string) $record['inspection_date'] ); ?></strong>
            </div>
        </div>

        <div class="acrb-insp-data-panel">
            <div class="acrb-insp-data-row">
                <span class="acrb-insp-data-label"><?php esc_html_e( 'Current Mileage', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-insp-data-val"><?php echo esc_html( ! empty( $record['mileage'] ) ? (string) $record['mileage'] : '—' ); ?></strong>
            </div>
            <div class="acrb-insp-data-row">
                <span class="acrb-insp-data-label"><?php esc_html_e( 'Fuel Level', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-insp-data-val"><?php echo esc_html( (string) $record['fuel_level'] ); ?></strong>
            </div>
            <div class="acrb-insp-data-row">
                <span class="acrb-insp-data-label"><?php esc_html_e( 'Cleanliness Condition', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-insp-data-val"><?php echo esc_html( (string) $record['cleanliness'] ); ?></strong>
            </div>
            <div class="acrb-insp-data-row">
                <span class="acrb-insp-data-label"><?php esc_html_e( 'Staff Inspector', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-insp-data-val"><?php echo esc_html( (string) $record['inspector'] ); ?></strong>
            </div>
        </div>
    </div>

    <div class="acrb-insp-damage-box">
        <h3 class="acrb-insp-damage-heading"><?php esc_html_e( 'Damage Log & Notes', 'awesome-car-rental' ); ?></h3>
        <p class="acrb-insp-damage-text"><?php echo ! empty( $record['damages'] ) ? nl2br( esc_html( (string) $record['damages'] ) ) : esc_html__( 'No damages recorded.', 'awesome-car-rental' ); ?></p>
    </div>
</div>