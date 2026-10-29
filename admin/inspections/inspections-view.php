<?php
/**
 * Single Inspection Detail View (`inspections-view.php`)
 * Location: admin/inspections/inspections-view.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
$table_cars        = $wpdb->prefix . 'dev_nahian_cars';

$insp_id = isset( $_GET['insp_id'] ) ? absint( $_GET['insp_id'] ) : 0;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_inspections} WHERE id = %d", $insp_id ), ARRAY_A );

if ( ! $record ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Inspection record not found.', 'awesome-car-rental' ) . '</p></div>';
    return;
}

// Retrieve vehicle name
$car_name = ! empty( $record['car_name'] ) ? $record['car_name'] : '';
if ( empty( $car_name ) ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $car_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", absint( $record['car_id'] ) ) ) ?: __( 'Vehicle', 'awesome-car-rental' );
}

$i_stage = (string) ( $record['stage'] ?? 'Check-Out' );
$badge_bg = '#e0f2fe'; $badge_color = '#0369a1';
if ( 'Check-In' === $i_stage ) { $badge_bg = '#dcfce7'; $badge_color = '#15803d'; } 
elseif ( 'Periodic Check' === $i_stage ) { $badge_bg = '#fef3c7'; $badge_color = '#d97706'; }

$back_url = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections' );
$edit_url = admin_url( 'admin.php?page=awesome_car_rental&tab=inspections&sub=edit&insp_id=' . absint( $record['id'] ) );
?>

<div class="acrb-card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">
            <?php printf( esc_html__( 'Inspection Report #%d Details', 'awesome-car-rental' ), absint( $record['id'] ) ); ?>
        </h2>
        <div>
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-secondary" style="text-decoration: none; margin-right: 8px;">
                <span class="dashicons dashicons-edit" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                <?php esc_html_e( 'Edit Report', 'awesome-car-rental' ); ?>
            </a>
            <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-btn-primary" style="text-decoration: none;">
                ← <?php esc_html_e( 'Back to Directory', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;">
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-size: 13px;"><?php esc_html_e( 'Vehicle Name', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $car_name ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-size: 13px;"><?php esc_html_e( 'Inspection Stage', 'awesome-car-rental' ); ?></span>
                <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                    <?php echo esc_html( $i_stage ); ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-size: 13px;"><?php esc_html_e( 'Inspection Date & Time', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $record['inspection_date'] ); ?></strong>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-size: 13px;"><?php esc_html_e( 'Current Mileage', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $record['mileage'] ?: '—' ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-size: 13px;"><?php esc_html_e( 'Fuel Level', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $record['fuel_level'] ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-size: 13px;"><?php esc_html_e( 'Cleanliness Condition', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $record['cleanliness'] ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-size: 13px;"><?php esc_html_e( 'Staff Inspector', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $record['inspector'] ); ?></strong>
            </div>
        </div>
    </div>

    <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 10px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);">
        <h3 style="margin: 0 0 10px; font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.03em;"><?php esc_html_e( 'Damage Log & Notes', 'awesome-car-rental' ); ?></h3>
        <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.6;"><?php echo nl2br( esc_html( $record['damages'] ?: 'No damages recorded.' ) ); ?></p>
    </div>
</div>