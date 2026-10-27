<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display Fleet Inventory List Table - Awesome Car Rental (SaaS Modernized)
 */
function acrb_cars_list() {
    // 1. DYNAMIC SETTINGS FETCHING
    $currency     = get_option( 'acrb_currency', '£' );
    $currency_pos = get_option( 'acrb_currency_pos', 'left' );
    $page_slug    = 'awesome_car_rental';

    $cars = get_posts( [
        'post_type'   => 'acrb_cars',
        'numberposts' => -1, 
        'orderby'     => 'ID',
        'order'       => 'DESC',
    ] );

    // Handle Success Messages
    if ( isset( $_GET['msg'] ) && 'deleted' === $_GET['msg'] ) {
        echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Car moved to trash successfully.', 'awesome-car-rental' ) . '</p></div>';
    }
    ?>

    <style>
        /* Modern SaaS Layout Theme */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-dash-wrap *, .acrb-dash-wrap *::before, .acrb-dash-wrap *::after { box-sizing: border-box; }
        
        /* Table Wrap */
        .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-table-wrap table.widefat { border: none; margin: 0; box-shadow: none; }
        .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
        .acrb-table-wrap table.widefat tbody td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
        .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

        .acrb-car-img { width: 56px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; }
        .acrb-no-img { width: 56px; height: 42px; border-radius: 6px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; }
        
        .acrb-car-title { font-size: 14px; font-weight: 600; color: #0f172a; }
        .acrb-vin-label { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; font-family: monospace; }
        
        .acrb-cat-badge { background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Action Buttons Group */
        .acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
        .acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; }
        .acrb-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        .acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
    </style>

    <div class="acrb-dash-wrap">
        <div class="acrb-table-wrap">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width: 70px;"><?php esc_html_e( 'Image', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Car Details', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Class/Category', 'awesome-car-rental' ); ?></th>
                        <th style="text-align:right; width: 140px;"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $cars ) : 
                        foreach ( $cars as $car ) :
                            $car_id   = $car->ID;
                            $cats     = wp_get_post_terms( $car_id, 'acrb_categories' );
                            $vin      = get_post_meta( $car_id, 'vin_number', true );

                            // URLs
                            $edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars&sub=edit&car_id=' . intval( $car_id ) );
                            $view_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars&sub=view&car_id=' . intval( $car_id ) );
                            
                            // Secure Delete URL with Nonce
                            $del_url_raw = admin_url( 'admin-post.php?action=acrb_delete_car&car=' . intval( $car_id ) );
                            $del_url     = wp_nonce_url( $del_url_raw, 'acrb_delete_car_' . $car_id );
                    ?>
                        <tr>
                            <td>
                                <?php if ( has_post_thumbnail( $car_id ) ) : ?>
                                    <?php echo get_the_post_thumbnail( $car_id, [56, 42], ['class' => 'acrb-car-img'] ); ?>
                                <?php else : ?>
                                    <div class="acrb-no-img"><span class="dashicons dashicons-car"></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="acrb-car-title"><?php echo esc_html( $car->post_title ); ?></strong><br>
                                <div class="acrb-vin-label">
                                    <?php echo $vin ? esc_html__( 'VIN:', 'awesome-car-rental' ) . ' ' . esc_html( $vin ) : esc_html__( 'ID:', 'awesome-car-rental' ) . ' #' . esc_html( $car_id ); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ( $cats ) : foreach ( $cats as $c ) : ?>
                                    <span class="acrb-cat-badge"><?php echo esc_html( $c->name ); ?></span>
                                <?php endforeach; else : ?>
                                    <span style="color:#64748b;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <div class="acrb-actions-group">
                                    <a class="acrb-btn-action" href="<?php echo esc_url( $view_url ); ?>" title="<?php esc_attr_e( 'View', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <a class="acrb-btn-action" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a class="acrb-btn-action acrb-btn-delete" href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to move this vehicle to trash?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color: #b91c1c;">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 40px; color:#64748b;">
                                <?php esc_html_e( 'No cars found in inventory.', 'awesome-car-rental' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}