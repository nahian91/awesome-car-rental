<?php
/**
 * Add / Edit Maintenance Component (`maintenance-add-edit.php`)
 * Location: admin/maintenance/maintenance-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
$table_cars        = $wpdb->prefix . 'dev_nahian_cars';
$currency_symbol   = get_option( 'acrb_currency', '£' );
$maint_id          = isset( $_GET['maint_id'] ) ? absint( $_GET['maint_id'] ) : 0;
$redirect_script   = '';

// 1. FORM PROCESSING & SAVING LOGIC
if ( isset( $_POST['acrb_save_maintenance'] ) ) {
    $nonce = isset( $_POST['acrb_maint_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_maint_nonce'] ) ) : '';

    if ( wp_verify_nonce( $nonce, 'acrb_maint_action' ) ) {
        $record_id    = isset( $_POST['maint_id'] ) ? absint( $_POST['maint_id'] ) : 0;
        $car_name     = isset( $_POST['maint_car_name'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_car_name'] ) ) : '';
        $service_type = isset( $_POST['maint_type'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_type'] ) ) : 'Routine Service';
        $cost         = isset( $_POST['maint_cost'] ) ? floatval( wp_unslash( $_POST['maint_cost'] ) ) : 0.00;
        $date_in      = isset( $_POST['maint_date'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_date'] ) ) : current_time( 'Y-m-d' );
        $status       = isset( $_POST['maint_status'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_status'] ) ) : 'pending';
        $notes        = isset( $_POST['maint_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['maint_notes'] ) ) : '';

        if ( ! empty( $car_name ) ) {
            $data = array(
                'car_name'     => $car_name,
                'service_type' => $service_type,
                'cost'         => $cost,
                'date_in'      => $date_in,
                'status'       => $status,
                'notes'        => $notes,
            );
            $formats = array( '%s', '%s', '%f', '%s', '%s', '%s' );

            if ( $record_id > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update( $table_maintenance, $data, array( 'id' => $record_id ), $formats, array( '%d' ) );
                $msg_code = 'updated';
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_maintenance, $data, $formats );
                $record_id = $wpdb->insert_id;
                $msg_code = 'created';
            }

            // Safe JavaScript redirect to bypass HTTP header sent conflicts
            $target_url      = add_query_arg( array( 'maint_id' => $record_id, 'updated' => $msg_code ), admin_url( 'admin.php?page=awesome_car_rental&tab=maintenance&sub=edit' ) );
            $redirect_script = '<script>window.location.href = ' . wp_json_encode( $target_url ) . ';</script>';
        }
    }
}

// 2. DATA FETCHING FOR EDIT STATE
$maint_record = null;
if ( $maint_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $maint_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_maintenance} WHERE id = %d", $maint_id ), ARRAY_A );
}

$m_car_name = $maint_record['car_name'] ?? '';
$m_type     = $maint_record['service_type'] ?? 'Routine Service';
$m_cost     = $maint_record['cost'] ?? '';
$m_date     = $maint_record['date_in'] ?? current_time( 'Y-m-d' );
$m_status   = $maint_record['status'] ?? 'pending';
$m_notes    = $maint_record['notes'] ?? '';

// Fetch vehicles from cars inventory table or custom posts as fallback
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cars_list = $wpdb->get_results( "SELECT id, name FROM {$table_cars} ORDER BY name ASC", ARRAY_A );
if ( empty( $cars_list ) ) {
    $cars_posts = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    $cars_list = array();
    foreach ( $cars_posts as $cp ) {
        $cars_list[] = array( 'name' => $cp->post_title );
    }
}

// Output redirect script if triggered
echo $redirect_script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>

<div class="acrb-card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">
            <?php echo $maint_id > 0 ? esc_html__( 'Edit Maintenance Record', 'awesome-car-rental' ) : esc_html__( 'Log New Maintenance Service', 'awesome-car-rental' ); ?>
        </h2>
    </div>

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;">
            <p><strong><?php esc_html_e( 'Success:', 'awesome-car-rental' ); ?></strong> 
                <?php 
                if ( 'updated' === $_GET['updated'] ) {
                    esc_html_e( 'Maintenance record updated successfully.', 'awesome-car-rental' );
                } else {
                    esc_html_e( 'New maintenance service logged successfully.', 'awesome-car-rental' );
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <?php wp_nonce_field( 'acrb_maint_action', 'acrb_maint_nonce' ); ?>
        <input type="hidden" name="maint_id" value="<?php echo absint( $maint_id ); ?>">

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Select Vehicle', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="maint_car_name" class="acrb-select" required>
                        <option value=""><?php esc_html_e( '-- Choose Vehicle --', 'awesome-car-rental' ); ?></option>
                        <?php foreach ( $cars_list as $car ) : 
                            $c_name = sanitize_text_field( $car['name'] ?? '' );
                            if ( empty( $c_name ) ) continue;
                        ?>
                            <option value="<?php echo esc_attr( $c_name ); ?>" <?php selected( $m_car_name, $c_name ); ?>>
                                <?php echo esc_html( $c_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="acrb-select-chevron">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Service Type', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="maint_type" class="acrb-select">
                        <option value="Routine Service" <?php selected( $m_type, 'Routine Service' ); ?>><?php esc_html_e( 'Routine Service / Oil Change', 'awesome-car-rental' ); ?></option>
                        <option value="Mechanical Repair" <?php selected( $m_type, 'Mechanical Repair' ); ?>><?php esc_html_e( 'Mechanical Repair', 'awesome-car-rental' ); ?></option>
                        <option value="Tire Replacement" <?php selected( $m_type, 'Tire Replacement' ); ?>><?php esc_html_e( 'Tire Replacement / Alignment', 'awesome-car-rental' ); ?></option>
                        <option value="Bodywork & Detailing" <?php selected( $m_type, 'Bodywork & Detailing' ); ?>><?php esc_html_e( 'Bodywork & Detailing', 'awesome-car-rental' ); ?></option>
                        <option value="Inspection & Safety" <?php selected( $m_type, 'Inspection & Safety' ); ?>><?php esc_html_e( 'Inspection & Safety', 'awesome-car-rental' ); ?></option>
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
                <label class="acrb-label"><?php printf( esc_html__( 'Repair Cost (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></label>
                <input type="number" step="0.01" name="maint_cost" value="<?php echo esc_attr( (string) $m_cost ); ?>" class="acrb-input" placeholder="0.00">
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Service Date (Date In)', 'awesome-car-rental' ); ?></label>
                <input type="date" name="maint_date" value="<?php echo esc_attr( $m_date ); ?>" class="acrb-input">
            </div>
        </div>

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="maint_status" class="acrb-select">
                        <option value="pending" <?php selected( $m_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'awesome-car-rental' ); ?></option>
                        <option value="in_progress" <?php selected( $m_status, 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'awesome-car-rental' ); ?></option>
                        <option value="completed" <?php selected( $m_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'awesome-car-rental' ); ?></option>
                    </select>
                    <span class="acrb-select-chevron">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <div class="acrb-form-group" style="margin-bottom:20px;">
            <label class="acrb-label"><?php esc_html_e( 'Repair Notes & Parts Replaced', 'awesome-car-rental' ); ?></label>
            <textarea name="maint_notes" rows="4" class="acrb-textarea" placeholder="<?php esc_attr_e( 'Enter service details, notes, or replacement parts...', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $m_notes ); ?></textarea>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <button type="submit" name="acrb_save_maintenance" class="acrb-btn-primary" style="height: 44px; padding: 0 32px; font-size: 14px;">
                <span class="dashicons <?php echo $maint_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>" style="font-size:17px; width:17px; height:17px; display:inline-flex; align-items:center; justify-content:center;"></span>
                <span><?php echo $maint_id > 0 ? esc_html__( 'Update Maintenance Record', 'awesome-car-rental' ) : esc_html__( 'Save Maintenance Record', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>