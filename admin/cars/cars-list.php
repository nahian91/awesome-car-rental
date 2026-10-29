<?php
/**
 * Cars List Component (`cars-list.php`)
 * Location: admin/cars/cars-list.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$currency_symbol = get_option( 'acrb_currency', '£' );
$page_slug       = 'awesome_car_rental';
$base_url        = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars' );
?>

<div class="acrb-table-wrap">
    <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
        <thead>
            <tr>
                <th style="width: 60px;" data-orderable="false"><?php esc_html_e( 'Photo', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Vehicle & VIN', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Daily Rate', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                <th class="all" style="text-align:right; width:130px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $cars = get_posts( array(
                'post_type'   => 'acrb_cars',
                'numberposts' => -1,
                'post_status' => 'publish',
                'orderby'     => 'date',
                'order'       => 'DESC',
            ) );

            if ( ! empty( $cars ) ) :
                foreach ( $cars as $car ) :
                    $c_id      = absint( $car->ID );
                    $c_title   = esc_html( $car->post_title );
                    $c_vin     = esc_html( get_post_meta( $c_id, 'vin', true ) ?: '—' );
                    $c_price   = floatval( get_post_meta( $c_id, 'price_per_day', true ) );
                    $c_status  = sanitize_key( get_post_meta( $c_id, 'acrb_vehicle_status', true ) ?: 'active' );
                    $thumb_url = get_the_post_thumbnail_url( $c_id, 'thumbnail' );

                    // Get category terms
                    $terms = wp_get_post_terms( $c_id, 'acrb_categories', array( 'fields' => 'names' ) );
                    $category_name = ! empty( $terms ) && ! is_wp_error( $terms ) ? esc_html( $terms[0] ) : __( 'Uncategorized', 'awesome-car-rental' );

                    $view_url = add_query_arg( array( 'sub' => 'view', 'car_id' => $c_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'car_id' => $c_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'car_id' => $c_id ), $base_url ), 'acrb_del_car_' . $c_id );
            ?>
            <tr>
                <td>
                    <?php if ( $thumb_url ) : ?>
                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="" style="width:50px; height:38px; border-radius:6px; object-fit:cover; border:1px solid #e2e8f0;">
                    <?php else : ?>
                        <div style="width:50px; height:38px; border-radius:6px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#64748b;">
                            <span class="dashicons dashicons-car" style="font-size:16px; width:16px; height:16px;"></span>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="color:#0f172a; font-size:14px; display:block;"><?php echo esc_html( $c_title ); ?></strong>
                    <span style="font-family:monospace; color:#64748b; font-size:11px; background:#f1f5f9; padding:1px 5px; border-radius:4px;">VIN: <?php echo esc_html( $c_vin ); ?></span>
                </td>
                <td>
                    <span style="background:#eff6ff; color:#3b82f6; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase;">
                        <?php echo esc_html( $category_name ); ?>
                    </span>
                </td>
                <td data-order="<?php echo esc_attr( (string) $c_price ); ?>">
                    <strong style="color:#059669;"><?php echo esc_html( $currency_symbol . number_format( $c_price, 2 ) ); ?></strong> <span style="font-size:11px; color:#64748b;"><?php esc_html_e( '/day', 'awesome-car-rental' ); ?></span>
                </td>
                <td>
                    <?php 
                    $is_act = ( 'active' === $c_status );
                    $bg     = $is_act ? '#ecfdf5' : '#fef3c7';
                    $color  = $is_act ? '#059669' : '#d97706';
                    ?>
                    <span style="background:<?php echo esc_attr( $bg ); ?>; color:<?php echo esc_attr( $color ); ?>; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase;">
                        ● <?php echo esc_html( ucfirst( $c_status ) ); ?>
                    </span>
                </td>
                <td style="text-align:right;">
                    <div class="acrb-actions-group">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this vehicle?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:40px; color:#64748b;">
                    <?php esc_html_e( 'No vehicles found in fleet. Click "Add New Car" above to list your first vehicle.', 'awesome-car-rental' ); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>