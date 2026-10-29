<?php
/**
 * Staff List Component (`staff-list.php`)
 * Location: admin/staff/staff-list.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_staff = $wpdb->prefix . 'dev_nahian_staff';
$page_slug   = 'awesome_car_rental';
$base_url    = admin_url( 'admin.php?page=' . $page_slug . '&tab=staff' );
?>

<div class="acrb-table-wrap">
    <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
        <thead>
            <tr>
                <th style="width: 50px;" data-orderable="false"><?php esc_html_e( 'Avatar', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Name & Email', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Designation', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                <th class="all" style="text-align:right; width:110px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $staff_list = $wpdb->get_results( "SELECT * FROM {$table_staff} ORDER BY id DESC", ARRAY_A );

            if ( ! empty( $staff_list ) ) :
                foreach ( $staff_list as $member ) :
                    $m_id     = absint( $member['id'] );
                    $m_name   = esc_html( $member['name'] );
                    $m_email  = esc_html( $member['email'] );
                    $m_phone  = esc_html( $member['phone'] ?: '—' );
                    $m_role   = esc_html( $member['role'] ?: 'Front Desk Agent' );
                    $m_status = sanitize_key( $member['status'] ?? 'active' );
                    $initial  = strtoupper( mb_substr( trim( $m_name ), 0, 1 ) ?: 'S' );

                    $view_url = add_query_arg( array( 'view' => $m_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'staff_id' => $m_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'staff_id' => $m_id ), $base_url ), 'acrb_del_staff_' . $m_id );
            ?>
            <tr>
                <td>
                    <div class="acrb-avatar-circle">
                        <?php echo esc_html( $initial ); ?>
                    </div>
                </td>
                <td>
                    <strong style="color:#0f172a; display:block;"><?php echo esc_html( $m_name ); ?></strong>
                    <span style="font-size:12px; color:#64748b;"><?php echo esc_html( $m_email ); ?></span>
                </td>
                <td>
                    <span style="background:#eff6ff; color:#4f46e5; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; display:inline-block; white-space:nowrap;">
                        <?php echo esc_html( $m_role ); ?>
                    </span>
                </td>
                <td><?php echo esc_html( $m_phone ); ?></td>
                <td>
                    <?php 
                    $is_act = ( 'active' === $m_status );
                    $bg     = $is_act ? '#ecfdf5' : '#f1f5f9';
                    $color  = $is_act ? '#059669' : '#64748b';
                    ?>
                    <span style="background:<?php echo esc_attr( $bg ); ?>; color:<?php echo esc_attr( $color ); ?>; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; display:inline-block; white-space:nowrap;">
                        ● <?php echo esc_html( ucfirst( $m_status ) ); ?>
                    </span>
                </td>
                <td style="text-align:right;">
                    <div class="acrb-actions-group">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Profile', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this staff member?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:40px; color:#64748b;">
                    <?php esc_html_e( 'No staff members found in database.', 'awesome-car-rental' ); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>