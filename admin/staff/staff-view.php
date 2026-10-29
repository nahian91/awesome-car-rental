<?php
/**
 * Staff CRM View Component (`staff-view.php`)
 * Location: admin/staff/staff-view.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_staff = $wpdb->prefix . 'dev_nahian_staff';
$staff_id    = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
$page_slug   = 'awesome_car_rental';
$base_url    = admin_url( 'admin.php?page=' . $page_slug . '&tab=staff' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ), ARRAY_A );

if ( ! $staff ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Staff member not found.', 'awesome-car-rental' ) . '</p></div>';
    return;
}

$s_name   = esc_html( $staff['name'] ?: __( 'Staff Member', 'awesome-car-rental' ) );
$s_email  = esc_html( $staff['email'] ?: '—' );
$s_phone  = esc_html( $staff['phone'] ?: '—' );
$s_role   = esc_html( $staff['role'] ?: 'Front Desk Agent' );
$s_status = sanitize_key( $staff['status'] ?? 'active' );
$s_joined = ! empty( $staff['created_at'] ) ? date_i18n( 'M j, Y', strtotime( $staff['created_at'] ) ) : '—';
$initial  = strtoupper( mb_substr( trim( $s_name ), 0, 2 ) ?: 'ST' );
$edit_url = add_query_arg( array( 'sub' => 'edit', 'staff_id' => $staff_id ), $base_url );

$is_act = ( 'active' === $s_status );
$bg     = $is_act ? '#ecfdf5' : '#f1f5f9';
$color  = $is_act ? '#059669' : '#64748b';
?>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start;">
    <!-- Staff Profile Dossier Card -->
    <div class="acrb-card" style="text-align: center;">
        <div style="width: 72px; height: 72px; background: #eef2ff; color: #4f46e5; font-size: 24px; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; border: 2px solid #e0e7ff;">
            <?php echo esc_html( $initial ); ?>
        </div>
        <h3 style="margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $s_name ); ?></h3>
        <span style="background: #eff6ff; color: #4f46e5; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
            <?php echo esc_html( $s_role ); ?>
        </span>

        <div style="margin-top: 20px; text-align: left; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></span>
                <span style="background: <?php echo esc_attr( $bg ); ?>; color: <?php echo esc_attr( $color ); ?>; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                    ● <?php echo esc_html( ucfirst( $s_status ) ); ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Email', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a; word-break: break-all;"><?php echo esc_html( $s_email ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $s_phone ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Registered On', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $s_joined ); ?></strong>
            </div>
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-primary" style="width: 100%;">
                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span><?php esc_html_e( 'Edit Profile', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
    </div>

    <!-- Operational Overview & Activity Log -->
    <div class="acrb-card">
        <h2><?php esc_html_e( 'Operational Overview', 'awesome-car-rental' ); ?></h2>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'System Access', 'awesome-car-rental' ); ?></span>
                <div style="font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 4px;"><?php echo esc_html( $s_role ); ?></div>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;"><?php esc_html_e( 'Assigned to rental counter dispatch operations.', 'awesome-car-rental' ); ?></p>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Account Status', 'awesome-car-rental' ); ?></span>
                <div style="font-size: 18px; font-weight: 800; color: <?php echo esc_attr( $color ); ?>; margin-top: 4px;"><?php echo esc_html( ucfirst( $s_status ) ); ?></div>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;"><?php esc_html_e( 'Authorized for booking check-in/out updates.', 'awesome-car-rental' ); ?></p>
            </div>
        </div>

        <h3><?php esc_html_e( 'Staff Activity Log', 'awesome-car-rental' ); ?></h3>
        <p style="font-size: 13px; color: #64748b; margin-top: 0;">
            <?php esc_html_e( 'Direct ledger records and dispatches managed by this team member will automatically appear here.', 'awesome-car-rental' ); ?>
        </p>
    </div>
</div>