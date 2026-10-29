<?php
/**
 * Add / Edit Customer Component (`customers-add-edit.php`)
 * Location: admin/customers/customers-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_customers = $wpdb->prefix . 'dev_nahian_customers';
$cust_id         = isset( $_GET['cust_id'] ) ? absint( $_GET['cust_id'] ) : 0;

if ( isset( $_POST['acrb_save_customer'] ) && check_admin_referer( 'acrb_cust_action', 'acrb_cust_nonce' ) ) {
    $name    = sanitize_text_field( wp_unslash( $_POST['cust_name'] ?? '' ) );
    $email   = sanitize_email( wp_unslash( $_POST['cust_email'] ?? '' ) );
    $phone   = sanitize_text_field( wp_unslash( $_POST['cust_phone'] ?? '' ) );
    $address = sanitize_textarea_field( wp_unslash( $_POST['cust_address'] ?? '' ) );
    $post_id = absint( $_POST['cust_id'] ?? 0 );

    if ( ! empty( $name ) && ! empty( $email ) ) {
        $data = array(
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'address' => $address,
        );
        $formats = array( '%s', '%s', '%s', '%s' );

        if ( $post_id > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update( $table_customers, $data, array( 'id' => $post_id ), $formats, array( '%d' ) );
            echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Customer profile updated successfully.', 'awesome-car-rental' ) . '</p></div>';
            $cust_id = $post_id;
        } else {
            $data['created_at'] = current_time( 'mysql' );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert( $table_customers, $data, array_merge( $formats, array( '%s' ) ) );
            $new_id = (int) $wpdb->insert_id;
            echo '<div class="notice notice-success is-dismissible" style="margin:0 0 20px;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'New customer registered successfully.', 'awesome-car-rental' ) . '</p></div>';
            $cust_id = $new_id;
        }
    } else {
        echo '<div class="notice notice-error is-dismissible" style="margin:0 0 20px;"><p>' . esc_html__( 'Please provide both customer name and a valid email address.', 'awesome-car-rental' ) . '</p></div>';
    }
}

$customer = null;
if ( $cust_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $cust_id ), ARRAY_A );
}

$c_name    = $customer['name'] ?? '';
$c_email   = $customer['email'] ?? '';
$c_phone   = $customer['phone'] ?? '';
$c_address = $customer['address'] ?? '';
?>

<div class="acrb-card">
    <h2><?php echo $cust_id > 0 ? sprintf( esc_html__( 'Edit Customer Profile: %s', 'awesome-car-rental' ), esc_html( $c_name ) ) : esc_html__( 'Register New Client Profile', 'awesome-car-rental' ); ?></h2>
    
    <form method="post">
        <?php wp_nonce_field( 'acrb_cust_action', 'acrb_cust_nonce' ); ?>
        <input type="hidden" name="cust_id" value="<?php echo absint( $cust_id ); ?>">

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></label>
                <input type="text" name="cust_name" value="<?php echo esc_attr( $c_name ); ?>" class="acrb-input" placeholder="e.g. Eleanor Vance" required>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></label>
                <input type="email" name="cust_email" value="<?php echo esc_attr( $c_email ); ?>" class="acrb-input" placeholder="e.g. eleanor@example.com" required>
            </div>
        </div>

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></label>
                <input type="text" name="cust_phone" value="<?php echo esc_attr( $c_phone ); ?>" class="acrb-input" placeholder="+1 (555) 000-0000">
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Billing / Residential Address', 'awesome-car-rental' ); ?></label>
                <textarea name="cust_address" rows="3" class="acrb-textarea" placeholder="<?php esc_attr_e( 'Street address, city, postcode, country', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $c_address ); ?></textarea>
            </div>
        </div>

        <div style="margin-top: 14px;">
            <button type="submit" name="acrb_save_customer" class="acrb-btn-primary">
                <span class="dashicons <?php echo $cust_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>" style="font-size:17px; width:17px; height:17px;"></span>
                <span><?php echo $cust_id > 0 ? esc_html__( 'Update Customer', 'awesome-car-rental' ) : esc_html__( 'Save Customer', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>