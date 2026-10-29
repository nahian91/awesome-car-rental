<?php
/**
 * Add / Edit Staff Component (`staff-add-edit.php`)
 * Location: admin/staff/staff-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_staff = $wpdb->prefix . 'dev_nahian_staff';
$staff_id    = isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0;

$staff = null;
if ( $staff_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ), ARRAY_A );
}

$s_name   = $staff['name'] ?? '';
$s_email  = $staff['email'] ?? '';
$s_phone  = $staff['phone'] ?? '';
$s_role   = $staff['role'] ?? 'Front Desk Agent';
$s_status = $staff['status'] ?? 'active';
?>

<div class="acrb-card">
    <h2><?php echo $staff_id > 0 ? sprintf( esc_html__( 'Edit Staff Profile: %s', 'awesome-car-rental' ), esc_html( $s_name ) ) : esc_html__( 'Add New Staff Member', 'awesome-car-rental' ); ?></h2>
    
    <form method="post">
        <?php wp_nonce_field( 'acrb_staff_action', 'acrb_staff_nonce' ); ?>
        <input type="hidden" name="staff_record_id" value="<?php echo absint( $staff_id ); ?>">

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></label>
                <input type="text" name="staff_name" value="<?php echo esc_attr( $s_name ); ?>" class="acrb-input" placeholder="e.g. Liam Wilson" required>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
                <input type="email" name="staff_email" value="<?php echo esc_attr( $s_email ); ?>" class="acrb-input" placeholder="e.g. liam@example.com" required>
            </div>
        </div>

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
                <input type="text" name="staff_phone" value="<?php echo esc_attr( $s_phone ); ?>" class="acrb-input" placeholder="+1 (555) 000-0000">
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Designation / Role', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="staff_designation" class="acrb-select">
                        <option value="Front Desk Agent" <?php selected( $s_role, 'Front Desk Agent' ); ?>><?php esc_html_e( 'Front Desk Agent', 'awesome-car-rental' ); ?></option>
                        <option value="Chauffeur / Driver" <?php selected( $s_role, 'Chauffeur / Driver' ); ?>><?php esc_html_e( 'Chauffeur / Driver', 'awesome-car-rental' ); ?></option>
                        <option value="Fleet Manager" <?php selected( $s_role, 'Fleet Manager' ); ?>><?php esc_html_e( 'Fleet Manager', 'awesome-car-rental' ); ?></option>
                        <option value="Maintenance Technician" <?php selected( $s_role, 'Maintenance Technician' ); ?>><?php esc_html_e( 'Maintenance Technician', 'awesome-car-rental' ); ?></option>
                    </select>
                    <span class="acrb-select-chevron">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Staff Status', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="staff_status" class="acrb-select">
                        <option value="active" <?php selected( $s_status, 'active' ); ?>><?php esc_html_e( 'Active', 'awesome-car-rental' ); ?></option>
                        <option value="inactive" <?php selected( $s_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive / On Leave', 'awesome-car-rental' ); ?></option>
                    </select>
                    <span class="acrb-select-chevron">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <div style="margin-top: 14px;">
            <button type="submit" name="acrb_save_staff" class="acrb-btn-primary">
                <span class="dashicons <?php echo $staff_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>" style="font-size:17px; width:17px; height:17px;"></span>
                <span><?php echo $staff_id > 0 ? esc_html__( 'Update Staff Member', 'awesome-car-rental' ) : esc_html__( 'Save Staff Member', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>