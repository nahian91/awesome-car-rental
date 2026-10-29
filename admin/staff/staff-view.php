<?php
/**
 * Staff CRM View Component (`staff-view.php`)
 * Location: admin/staff/staff-view.php
 *
 * @package Awesome_Car_Rental
 * @since   2.9.6
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$table_staff = $wpdb->prefix . 'dev_nahian_staff';
$staff_id    = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : ( isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0 );
$page_slug   = 'awesome_car_rental';
$base_url    = admin_url( 'admin.php?page=' . $page_slug . '&tab=staff' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ), ARRAY_A );

if ( ! $staff ) {
    ?>
    <div class="acrb-staff-notfound">
        <p><?php esc_html_e( 'Staff member profile not found.', 'awesome-car-rental' ); ?></p>
        <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-staff-btn-back">
            &larr; <?php esc_html_e( 'Back to Staff Directory', 'awesome-car-rental' ); ?>
        </a>
    </div>
    <?php
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

$is_act       = ( 'active' === $s_status );
$status_class = $is_act ? 'acrb-status-active' : 'acrb-status-inactive';
?>

<style>
    .acrb-staff-view-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 20px;
        align-items: start;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-staff-view-grid *,
    .acrb-staff-view-grid *::before,
    .acrb-staff-view-grid *::after {
        box-sizing: border-box;
    }

    /* Cards */
    .acrb-staff-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
    }
    .acrb-staff-dossier {
        text-align: center;
    }

    /* Avatar Badge */
    .acrb-staff-avatar-wrap {
        width: 72px;
        height: 72px;
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        color: #4338ca;
        font-size: 24px;
        font-weight: 800;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px auto;
        border: 2px solid #c7d2fe;
    }

    /* Headings & Badges */
    .acrb-staff-view-name {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
    }
    .acrb-staff-role-pill {
        background: #eff6ff;
        color: #4f46e5;
        border: 1px solid #dbeafe;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
    }

    /* Meta Details List */
    .acrb-staff-meta-list {
        margin-top: 20px;
        text-align: left;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 13px;
    }
    .acrb-staff-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .acrb-staff-meta-label {
        color: #64748b;
        font-weight: 500;
        flex-shrink: 0;
    }
    .acrb-staff-meta-val {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
    }
    .acrb-staff-meta-val-break {
        word-break: break-all;
    }

    /* Status Pills */
    .acrb-staff-status-badge {
        padding: 2px 8px;
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

    /* Action Links */
    .acrb-staff-view-actions {
        margin-top: 20px;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .acrb-staff-btn-edit {
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
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .acrb-staff-btn-edit:hover {
        background: #4338ca;
        color: #ffffff;
        transform: translateY(-1px);
    }
    .acrb-staff-btn-edit .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .acrb-staff-btn-back {
        color: #64748b;
        text-decoration: none;
        font-size: 12.5px;
        font-weight: 600;
        text-align: center;
        padding: 6px;
        transition: color 0.15s ease;
    }
    .acrb-staff-btn-back:hover {
        color: #0f172a;
    }

    /* Operational Overview Section */
    .acrb-staff-section-title {
        margin: 0 0 16px;
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }
    .acrb-staff-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .acrb-staff-stat-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
    }
    .acrb-staff-stat-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: block;
    }
    .acrb-staff-stat-value {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        margin-top: 4px;
    }
    .acrb-staff-stat-desc {
        font-size: 12px;
        color: #64748b;
        margin: 4px 0 0;
    }

    /* Activity Log Block */
    .acrb-staff-log-title {
        margin: 0 0 8px;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }
    .acrb-staff-log-desc {
        font-size: 13px;
        color: #64748b;
        margin: 0;
        line-height: 1.5;
    }

    /* Not Found Alert */
    .acrb-staff-notfound {
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
        .acrb-staff-view-grid {
            grid-template-columns: 1fr;
        }
        .acrb-staff-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="acrb-staff-view-grid">
    <!-- Staff Profile Dossier Card -->
    <div class="acrb-staff-card acrb-staff-dossier">
        <div class="acrb-staff-avatar-wrap">
            <?php echo esc_html( $initial ); ?>
        </div>
        <h3 class="acrb-staff-view-name"><?php echo esc_html( $s_name ); ?></h3>
        <span class="acrb-staff-role-pill">
            <?php echo esc_html( $s_role ); ?>
        </span>

        <div class="acrb-staff-meta-list">
            <div class="acrb-staff-meta-row">
                <span class="acrb-staff-meta-label"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></span>
                <span class="acrb-staff-status-badge <?php echo esc_attr( $status_class ); ?>">
                    ● <?php echo esc_html( ucfirst( $s_status ) ); ?>
                </span>
            </div>
            <div class="acrb-staff-meta-row">
                <span class="acrb-staff-meta-label"><?php esc_html_e( 'Email', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-staff-meta-val acrb-staff-meta-val-break"><?php echo esc_html( $s_email ); ?></strong>
            </div>
            <div class="acrb-staff-meta-row">
                <span class="acrb-staff-meta-label"><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-staff-meta-val"><?php echo esc_html( $s_phone ); ?></strong>
            </div>
            <div class="acrb-staff-meta-row">
                <span class="acrb-staff-meta-label"><?php esc_html_e( 'Registered On', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-staff-meta-val"><?php echo esc_html( $s_joined ); ?></strong>
            </div>
        </div>

        <div class="acrb-staff-view-actions">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-staff-btn-edit">
                <span class="dashicons dashicons-edit"></span>
                <span><?php esc_html_e( 'Edit Profile', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-staff-btn-back">
                &larr; <?php esc_html_e( 'Back to Staff Directory', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </div>

    <!-- Operational Overview & Activity Log Card -->
    <div class="acrb-staff-card">
        <h2 class="acrb-staff-section-title"><?php esc_html_e( 'Operational Overview', 'awesome-car-rental' ); ?></h2>

        <div class="acrb-staff-stats-grid">
            <div class="acrb-staff-stat-card">
                <span class="acrb-staff-stat-label"><?php esc_html_e( 'System Access', 'awesome-car-rental' ); ?></span>
                <div class="acrb-staff-stat-value"><?php echo esc_html( $s_role ); ?></div>
                <p class="acrb-staff-stat-desc"><?php esc_html_e( 'Assigned to rental counter dispatch operations.', 'awesome-car-rental' ); ?></p>
            </div>
            <div class="acrb-staff-stat-card">
                <span class="acrb-staff-stat-label"><?php esc_html_e( 'Account Status', 'awesome-car-rental' ); ?></span>
                <div class="acrb-staff-stat-value">
                    <span class="acrb-staff-status-badge <?php echo esc_attr( $status_class ); ?>">
                        ● <?php echo esc_html( ucfirst( $s_status ) ); ?>
                    </span>
                </div>
                <p class="acrb-staff-stat-desc"><?php esc_html_e( 'Authorized for booking check-in/out updates.', 'awesome-car-rental' ); ?></p>
            </div>
        </div>

        <h3 class="acrb-staff-log-title"><?php esc_html_e( 'Staff Activity Log', 'awesome-car-rental' ); ?></h3>
        <p class="acrb-staff-log-desc">
            <?php esc_html_e( 'Direct ledger records and dispatches managed by this team member will automatically appear here.', 'awesome-car-rental' ); ?>
        </p>
    </div>
</div>