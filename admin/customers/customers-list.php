<?php
/**
 * Customers List Component (`customers-list.php`)
 * Location: admin/customers/customers-list.php
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
$page_slug       = 'awesome_car_rental';
$base_url        = admin_url( 'admin.php?page=' . $page_slug . '&tab=customers' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$customers_list = $wpdb->get_results(
    "SELECT id, name, email, phone FROM {$table_customers} ORDER BY id DESC",
    ARRAY_A
);
?>

<div class="acrb-table-wrap">
    <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Customer', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></th>
                <th class="all" width="120" style="text-align:right;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ( ! empty( $customers_list ) ) :
                foreach ( $customers_list as $cust ) :
                    $c_id    = absint( $cust['id'] );
                    $c_name  = esc_html( $cust['name'] ?: __( 'Unnamed Customer', 'awesome-car-rental' ) );
                    $c_email = esc_html( $cust['email'] ?: '—' );
                    $c_phone = esc_html( $cust['phone'] ?: '—' );

                    $initials = strtoupper( mb_substr( trim( $c_name ), 0, 2 ) ?: 'CU' );

                    $view_url = add_query_arg( array( 'view' => $c_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'cust_id' => $c_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'cust_id' => $c_id ), $base_url ), 'acrb_del_cust_' . $c_id );
            ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="acrb-avatar-circle" style="width:36px; height:36px; min-width:36px; border-radius:50%; background:#eef2ff; color:#4f46e5; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; border:1px solid #e0e7ff;">
                            <?php echo esc_html( $initials ); ?>
                        </div>
                        <div>
                            <strong style="color:#0f172a; display:block; line-height:1.2;"><?php echo esc_html( $c_name ); ?></strong>
                            <span style="font-size:11.5px; color:#64748b; font-family:monospace;">ID #<?php echo absint( $c_id ); ?></span>
                        </div>
                    </div>
                </td>
                <td style="color:#475569;"><?php echo esc_html( $c_email ); ?></td>
                <td style="color:#475569;"><?php echo esc_html( $c_phone ); ?></td>
                <td style="text-align:right;">
                    <div class="acrb-actions-group">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Customer History', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit Profile', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this customer record?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:40px; color:#64748b;">
                        <?php esc_html_e( 'No registered customers found in database.', 'awesome-car-rental' ); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>