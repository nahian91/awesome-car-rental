<?php
/**
 * Add / Edit Inspection Component (`inspections-add-edit.php`)
 * Location: admin/inspections/inspections-add-edit.php
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
$table_staff       = $wpdb->prefix . 'dev_nahian_staff';

$insp_id = isset( $_GET['insp_id'] ) ? absint( $_GET['insp_id'] ) : 0;

if ( isset( $_POST['acrb_save_inspection'] ) ) {
    $nonce = isset( $_POST['acrb_insp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_insp_nonce'] ) ) : '';

    if ( wp_verify_nonce( $nonce, 'acrb_insp_action' ) ) {
        $inspection_id = isset( $_POST['insp_id'] ) ? absint( $_POST['insp_id'] ) : 0;
        $car_id        = isset( $_POST['insp_car_id'] ) ? absint( $_POST['insp_car_id'] ) : 0;
        $stage         = isset( $_POST['insp_stage'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_stage'] ) ) : 'Check-Out';
        $insp_date     = isset( $_POST['insp_date'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_date'] ) ) : current_time( 'Y-m-d H:i:s' );
        $mileage       = isset( $_POST['insp_mileage'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_mileage'] ) ) : '';
        $fuel_level    = isset( $_POST['insp_fuel'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_fuel'] ) ) : 'Full (100%)';
        $cleanliness   = isset( $_POST['insp_cleanliness'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_cleanliness'] ) ) : 'Clean';
        $damages       = isset( $_POST['insp_damages'] ) ? sanitize_textarea_field( wp_unslash( $_POST['insp_damages'] ) ) : '';
        $inspector     = isset( $_POST['insp_inspector'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_inspector'] ) ) : '';

        if ( $car_id > 0 ) {
            // Retrieve vehicle name dynamically for the car_name column
            $car_name = '';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $car_row = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$table_cars} WHERE id = %d", $car_id ), ARRAY_A );
            if ( $car_row && ! empty( $car_row['name'] ) ) {
                $car_name = $car_row['name'];
            } else {
                $post_car = get_post( $car_id );
                if ( $post_car ) {
                    $car_name = $post_car->post_title;
                }
            }

            $data = array(
                'car_id'          => $car_id,
                'car_name'        => $car_name,
                'stage'           => $stage,
                'mileage'         => $mileage,
                'fuel_level'      => $fuel_level,
                'cleanliness'     => $cleanliness,
                'inspector'       => $inspector,
                'damages'         => $damages,
                'inspection_date' => $insp_date,
            );
            $formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

            if ( $inspection_id > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update( $table_inspections, $data, array( 'id' => $inspection_id ), $formats, array( '%d' ) );
                echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Inspection updated successfully.', 'awesome-car-rental' ) . '</p></div>';
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_inspections, $data, $formats );
                echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'New inspection logged.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }
}

$insp_record = null;
if ( $insp_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $insp_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_inspections} WHERE id = %d", $insp_id ), ARRAY_A );
}

$i_car_id    = $insp_record ? absint( $insp_record['car_id'] ) : 0;
$i_stage     = $insp_record ? (string) $insp_record['stage'] : 'Check-Out';
$i_date      = $insp_record ? (string) $insp_record['inspection_date'] : current_time( 'Y-m-d\TH:i' );
$i_mileage   = $insp_record ? (string) $insp_record['mileage'] : '';
$i_fuel      = $insp_record ? (string) $insp_record['fuel_level'] : 'Full (100%)';
$i_clean     = $insp_record ? (string) $insp_record['cleanliness'] : 'Clean';
$i_damages   = $insp_record ? (string) $insp_record['damages'] : '';
$i_inspector = $insp_record ? (string) $insp_record['inspector'] : wp_get_current_user()->display_name;

// Fetch cars
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cars = $wpdb->get_results( "SELECT id, name FROM {$table_cars} ORDER BY name ASC", ARRAY_A );
if ( empty( $cars ) ) {
    $cars_posts = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    $cars = array();
    foreach ( $cars_posts as $cp ) {
        $cars[] = array( 'id' => $cp->ID, 'name' => $cp->post_title );
    }
}

// Fetch staff list
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$staff_members = $wpdb->get_results( "SELECT * FROM {$table_staff} ORDER BY id DESC", ARRAY_A );
if ( empty( $staff_members ) ) {
    $wp_users = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ) ) );
    $staff_members = array();
    foreach ( $wp_users as $u ) {
        $staff_members[] = array( 'name' => $u->display_name );
    }
}
?>

<div class="acrb-card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">
            <?php echo $insp_id > 0 ? esc_html__( 'Edit Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Create New Inspection Report', 'awesome-car-rental' ); ?>
        </h2>
    </div>
    
    <form method="post">
        <?php wp_nonce_field( 'acrb_insp_action', 'acrb_insp_nonce' ); ?>
        <input type="hidden" name="insp_id" value="<?php echo absint( $insp_id ); ?>">

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Select Vehicle', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="insp_car_id" class="acrb-select" required>
                        <option value=""><?php esc_html_e( '-- Choose Vehicle --', 'awesome-car-rental' ); ?></option>
                        <?php foreach ( $cars as $car ) : 
                            $c_id   = absint( $car['id'] ?? 0 );
                            $c_name = sanitize_text_field( $car['name'] ?? 'Unnamed Car' );
                        ?>
                            <option value="<?php echo $c_id; ?>" <?php selected( $i_car_id, $c_id ); ?>>
                                <?php echo esc_html( $c_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Inspection Stage', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="insp_stage" class="acrb-select">
                        <option value="Check-Out" <?php selected( $i_stage, 'Check-Out' ); ?>><?php esc_html_e( '🚗 Check-Out (Before Rental)', 'awesome-car-rental' ); ?></option>
                        <option value="Check-In" <?php selected( $i_stage, 'Check-In' ); ?>><?php esc_html_e( '🏁 Check-In (After Return)', 'awesome-car-rental' ); ?></option>
                        <option value="Periodic Check" <?php selected( $i_stage, 'Periodic Check' ); ?>><?php esc_html_e( '🛠️ Periodic Maintenance Audit', 'awesome-car-rental' ); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="acrb-form-group" style="margin-bottom: 20px;">
            <label class="acrb-label"><?php esc_html_e( 'Inspection Date & Time', 'awesome-car-rental' ); ?></label>
            <input type="datetime-local" name="insp_date" value="<?php echo esc_attr( date( 'Y-m-d\TH:i', strtotime( $i_date ) ) ); ?>" class="acrb-input">
        </div>

        <div class="acrb-form-grid-3">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Current Mileage', 'awesome-car-rental' ); ?></label>
                <input type="text" name="insp_mileage" value="<?php echo esc_attr( $i_mileage ); ?>" class="acrb-input" placeholder="e.g. 45,200 km">
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Fuel Level', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="insp_fuel" class="acrb-select">
                        <option value="Full (100%)" <?php selected( $i_fuel, 'Full (100%)' ); ?>>Full (100%)</option>
                        <option value="3/4 Tank" <?php selected( $i_fuel, '3/4 Tank' ); ?>>3/4 Tank</option>
                        <option value="1/2 Tank" <?php selected( $i_fuel, '1/2 Tank' ); ?>>1/2 Tank</option>
                        <option value="1/4 Tank" <?php selected( $i_fuel, '1/4 Tank' ); ?>>1/4 Tank</option>
                        <option value="Empty" <?php selected( $i_fuel, 'Empty' ); ?>>Empty</option>
                    </select>
                </div>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Cleanliness', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="insp_cleanliness" class="acrb-select">
                        <option value="Pristine" <?php selected( $i_clean, 'Pristine' ); ?>>Pristine</option>
                        <option value="Clean" <?php selected( $i_clean, 'Clean' ); ?>>Clean</option>
                        <option value="Needs Wash" <?php selected( $i_clean, 'Needs Wash' ); ?>>Needs Wash</option>
                        <option value="Dirty / Muddy" <?php selected( $i_clean, 'Dirty / Muddy' ); ?>>Dirty / Muddy</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="acrb-form-group" style="margin-bottom: 20px;">
            <label class="acrb-label"><?php esc_html_e( 'Damage Log / Scratches / Dents', 'awesome-car-rental' ); ?></label>
            <textarea name="insp_damages" rows="3" class="acrb-textarea" placeholder="Describe any existing or new scratches..."><?php echo esc_textarea( $i_damages ); ?></textarea>
        </div>

        <div class="acrb-form-group" style="margin-bottom: 24px; max-width: 400px;">
            <label class="acrb-label"><?php esc_html_e( 'Inspector Name / Staff', 'awesome-car-rental' ); ?></label>
            <div class="acrb-select-wrap">
                <select name="insp_inspector" class="acrb-select" required>
                    <option value=""><?php esc_html_e( '-- Select Staff Member --', 'awesome-car-rental' ); ?></option>
                    <?php foreach ( $staff_members as $staff ) : 
                        $staff_name = sanitize_text_field( $staff['name'] ?? ( $staff['full_name'] ?? '' ) );
                        if ( empty( $staff_name ) ) continue;
                    ?>
                        <option value="<?php echo esc_attr( $staff_name ); ?>" <?php selected( $i_inspector, $staff_name ); ?>>
                            <?php echo esc_html( $staff_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <button type="submit" name="acrb_save_inspection" class="acrb-btn-primary" style="height: 44px; padding: 0 32px; font-size: 14px;">
                <span class="dashicons dashicons-saved" style="font-size:17px; width:17px; height:17px; display:inline-flex; align-items:center; justify-content:center;"></span>
                <span><?php echo $insp_id > 0 ? esc_html__( 'Update Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Save Inspection Report', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>