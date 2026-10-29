<?php
/**
 * Cars List Component (`cars-list.php`)
 * Location: admin/cars/cars-list.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

$currency_symbol = get_option( 'acrb_currency', '$' );
$currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$rental_unit     = get_option( 'acrb_rental_unit', 'day' );
$page_slug       = 'awesome_car_rental';
$base_url        = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars' );

$format_price = static function( float $val ) use ( $currency_symbol, $currency_pos ): string {
    $formatted = number_format( $val, 2 );
    return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
};

$cars = get_posts( array(
    'post_type'   => 'acrb_cars',
    'numberposts' => -1,
    'post_status' => 'publish',
    'orderby'     => 'date',
    'order'       => 'DESC',
) );
?>

<style>
    .acrb-cars-table-wrap {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-cars-table-wrap *,
    .acrb-cars-table-wrap *::before,
    .acrb-cars-table-wrap *::after {
        box-sizing: border-box;
    }
    .acrb-cars-datatable {
        width: 100% !important;
        border-collapse: collapse;
        font-family: inherit;
    }
    .acrb-cars-datatable thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 13px 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .acrb-cars-datatable tbody td {
        vertical-align: middle;
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 13px;
    }
    .acrb-cars-datatable tbody tr:hover {
        background-color: #fcfdfe;
    }

    /* Thumbnail Box */
    .acrb-car-thumb-wrap {
        width: 54px;
        height: 40px;
        border-radius: 8px;
        overflow: hidden;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .acrb-car-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .acrb-car-thumb-placeholder {
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .acrb-car-thumb-placeholder .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    /* Vehicle Meta */
    .acrb-car-title-text {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 3px;
        display: block;
        line-height: 1.3;
    }
    .acrb-car-vin-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #64748b;
        font-size: 11px;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        letter-spacing: 0.02em;
    }

    /* Category Pill */
    .acrb-car-category-pill {
        background: #eff6ff;
        color: #4f46e5;
        border: 1px solid #dbeafe;
        padding: 3px 9px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
        white-space: nowrap;
    }

    /* Rate Display */
    .acrb-car-rate-val {
        color: #059669;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.01em;
    }
    .acrb-car-rate-unit {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
    }

    /* Status Badges */
    .acrb-car-status-pill {
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
        white-space: nowrap;
    }
    .acrb-car-status-active {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    .acrb-car-status-maintenance {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .acrb-car-status-rented {
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }

    /* Actions Column */
    .acrb-cars-th-actions {
        text-align: right !important;
        width: 120px;
    }
    .acrb-cars-cell-actions {
        text-align: right !important;
    }
    .acrb-cars-actions-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }
    .acrb-btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 7px;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .acrb-btn-action:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .acrb-btn-action .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
    }
    .acrb-btn-delete {
        color: #dc2626;
        border-color: #fee2e2;
        background: #fff5f5;
    }
    .acrb-btn-delete:hover {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }

    /* Empty State */
    .acrb-cars-empty-row {
        text-align: center;
        padding: 48px 20px;
        color: #64748b;
        font-size: 13.5px;
    }
</style>

<div class="acrb-cars-table-wrap">
    <table class="acrb-datatable acrb-cars-datatable widefat striped display nowrap">
        <thead>
            <tr>
                <th style="width: 70px;" data-orderable="false"><?php esc_html_e( 'Photo', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Vehicle & VIN', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Base Rate', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                <th class="all acrb-cars-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ( ! empty( $cars ) ) :
                foreach ( $cars as $car ) :
                    $c_id      = absint( $car->ID );
                    $c_title   = esc_html( $car->post_title );
                    $c_vin     = esc_html( (string) ( get_post_meta( $c_id, 'vin', true ) ?: '—' ) );
                    $c_price   = floatval( get_post_meta( $c_id, 'price_per_day', true ) );
                    $c_status  = sanitize_key( (string) ( get_post_meta( $c_id, 'acrb_vehicle_status', true ) ?: 'active' ) );
                    $thumb_url = get_the_post_thumbnail_url( $c_id, 'thumbnail' );

                    // Fetch taxonomy category or custom post meta fallback
                    $terms         = wp_get_post_terms( $c_id, 'acrb_categories', array( 'fields' => 'names' ) );
                    $meta_cat      = get_post_meta( $c_id, 'category', true );
                    $category_name = ! empty( $terms ) && ! is_wp_error( $terms ) ? esc_html( $terms[0] ) : ( ! empty( $meta_cat ) ? esc_html( $meta_cat ) : __( 'General Fleet', 'awesome-car-rental' ) );

                    $view_url = add_query_arg( array( 'sub' => 'view', 'car_id' => $c_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'car_id' => $c_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'car_id' => $c_id ), $base_url ), 'acrb_del_car_' . $c_id );

                    $status_class = 'acrb-car-status-active';
                    $status_label = __( 'Available', 'awesome-car-rental' );

                    if ( 'maintenance' === $c_status ) {
                        $status_class = 'acrb-car-status-maintenance';
                        $status_label = __( 'Maintenance', 'awesome-car-rental' );
                    } elseif ( 'rented' === $c_status ) {
                        $status_class = 'acrb-car-status-rented';
                        $status_label = __( 'In Service', 'awesome-car-rental' );
                    }
            ?>
            <tr>
                <td>
                    <div class="acrb-car-thumb-wrap">
                        <?php if ( $thumb_url ) : ?>
                            <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $c_title ); ?>" class="acrb-car-thumb-img">
                        <?php else : ?>
                            <div class="acrb-car-thumb-placeholder">
                                <span class="dashicons dashicons-car"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <strong class="acrb-car-title-text"><?php echo esc_html( $c_title ); ?></strong>
                    <span class="acrb-car-vin-badge">VIN: <?php echo esc_html( $c_vin ); ?></span>
                </td>
                <td>
                    <span class="acrb-car-category-pill">
                        <?php echo esc_html( $category_name ); ?>
                    </span>
                </td>
                <td data-order="<?php echo esc_attr( (string) $c_price ); ?>">
                    <strong class="acrb-car-rate-val"><?php echo esc_html( $format_price( $c_price ) ); ?></strong>
                    <span class="acrb-car-rate-unit">/ <?php echo esc_html( $rental_unit ); ?></span>
                </td>
                <td>
                    <span class="acrb-car-status-pill <?php echo esc_attr( $status_class ); ?>">
                        ● <?php echo esc_html( $status_label ); ?>
                    </span>
                </td>
                <td class="acrb-cars-cell-actions">
                    <div class="acrb-cars-actions-group">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit Vehicle', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this fleet vehicle?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete Vehicle', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr>
                <td colspan="6" class="acrb-cars-empty-row">
                    <?php esc_html_e( 'No vehicles found in fleet. Click "Add New Vehicle" above to list your first car.', 'awesome-car-rental' ); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>