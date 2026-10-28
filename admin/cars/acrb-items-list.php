<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display Fleet Inventory List Table - Awesome Car Rental (SaaS Modernized)
 */
function acrb_cars_list() {
    // 1. Dynamic Settings Fetching
    $currency     = get_option( 'acrb_currency', '£' );
    $currency_pos = get_option( 'acrb_currency_pos', 'left' );
    $page_slug    = 'awesome_car_rental';

    $cars = get_posts( array(
        'post_type'   => 'acrb_cars',
        'numberposts' => -1, 
        'orderby'     => 'ID',
        'order'       => 'DESC',
    ) );

    // Handle Success Messages
    if ( isset( $_GET['msg'] ) && 'deleted' === $_GET['msg'] ) {
        echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Car moved to trash successfully.', 'awesome-car-rental' ) . '</p></div>';
    }
    ?>

    <style>
        .acrb-dash-wrap { width: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-dash-wrap *, .acrb-dash-wrap *::before, .acrb-dash-wrap *::after { box-sizing: border-box; }
        
        /* Table Wrap Container */
        .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .acrb-table-wrap .dataTables_wrapper { padding: 16px 20px; font-family: inherit; }
        .acrb-table-wrap .dataTables_wrapper::before,
        .acrb-table-wrap .dataTables_wrapper::after { content: ''; display: table; clear: both; }

        /* Top Controls: Length & Search */
        .acrb-table-wrap .dataTables_length { float: left; margin-bottom: 16px; font-size: 13px; font-weight: 500; color: #64748b; }
        .acrb-table-wrap .dataTables_length select { border: 1px solid #cbd5e1 !important; border-radius: 8px !important; padding: 5px 10px !important; font-size: 13px !important; color: #0f172a !important; background: #ffffff !important; outline: none !important; }
        
        .acrb-table-wrap .dataTables_filter { float: right; margin-bottom: 16px; }
        .acrb-table-wrap .dataTables_filter input { width: 240px; height: 36px; padding: 0 14px !important; border-radius: 8px !important; border: 1px solid #cbd5e1 !important; background: #ffffff !important; font-size: 13px !important; color: #0f172a !important; outline: none !important; transition: all 0.15s ease-in-out; }
        .acrb-table-wrap .dataTables_filter input:focus { border-color: #4f46e5 !important; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important; }

        /* Table Styling */
        .acrb-table-wrap table.widefat { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin: 0; box-shadow: none; width: 100% !important; }
        .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        .acrb-table-wrap table.widefat tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
        .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

        /* Bottom Controls */
        .acrb-table-wrap .dataTables_info { float: left; padding-top: 14px !important; font-size: 12.5px !important; color: #64748b !important; font-weight: 500; }
        .acrb-table-wrap .dataTables_paginate { float: right; padding-top: 10px !important; }
        .acrb-table-wrap .dataTables_paginate .paginate_button { border-radius: 6px !important; border: 1px solid transparent !important; padding: 4px 10px !important; margin: 0 2px !important; font-size: 12.5px !important; color: #64748b !important; }
        .acrb-table-wrap .dataTables_paginate .paginate_button:hover { background: #f1f5f9 !important; color: #0f172a !important; border-color: #e2e8f0 !important; }
        .acrb-table-wrap .dataTables_paginate .paginate_button.current,
        .acrb-table-wrap .dataTables_paginate .paginate_button.current:hover { background: #4f46e5 !important; color: #ffffff !important; border-color: #4f46e5 !important; }

        /* Item Visuals & Pricing */
        .acrb-car-img { width: 56px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; display: block; }
        .acrb-no-img { width: 56px; height: 42px; border-radius: 6px; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; }
        
        .acrb-car-title { font-size: 14px; font-weight: 600; color: #0f172a; }
        .acrb-vin-label { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; font-family: monospace; white-space: nowrap; }
        
        .acrb-cat-badge { background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; white-space: nowrap; margin-right: 4px; }

        .acrb-price-val { font-size: 15px; font-weight: 700; color: #15803d; white-space: nowrap; }
        .acrb-price-unit { font-size: 11.5px; font-weight: 500; color: #64748b; margin-left: 2px; }

        /* Actions Group */
        .acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
        .acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; flex-shrink: 0; }
        .acrb-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        .acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
    </style>

    <div class="acrb-dash-wrap">
        <div class="acrb-table-wrap">
            <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width: 70px;" data-orderable="false"><?php esc_html_e( 'Image', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Car Details', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Class/Category', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Base Price', 'awesome-car-rental' ); ?></th>
                        <th class="all" style="text-align:right; width: 120px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $cars ) ) : 
                        foreach ( $cars as $car ) :
                            $car_id       = $car->ID;
                            $cats         = wp_get_post_terms( $car_id, 'acrb_categories' );
                            $vin          = get_post_meta( $car_id, 'vin_number', true );
                            
                            // Base Price retrieval & formatting
                            $raw_price    = get_post_meta( $car_id, 'daily_rate', true );
                            if ( '' === $raw_price ) {
                                $raw_price = get_post_meta( $car_id, 'base_price', true );
                            }
                            $price_num    = is_numeric( $raw_price ) ? floatval( $raw_price ) : 0;
                            $formatted_p  = number_format( $price_num, 2 );
                            $price_output = ( 'right' === $currency_pos ) ? $formatted_p . $currency : $currency . $formatted_p;

                            // URLs
                            $edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars&sub=edit&car_id=' . intval( $car_id ) );
                            $view_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars&sub=view&car_id=' . intval( $car_id ) );
                            
                            // Secure Delete URL with Nonce
                            $del_url_raw = admin_url( 'admin-post.php?action=acrb_delete_car&car=' . intval( $car_id ) );
                            $del_url     = wp_nonce_url( $del_url_raw, 'acrb_delete_car_' . $car_id );
                    ?>
                        <tr>
                            <!-- 1. Featured Image -->
                            <td>
                                <?php if ( has_post_thumbnail( $car_id ) ) : ?>
                                    <?php echo get_the_post_thumbnail( $car_id, array( 56, 42 ), array( 'class' => 'acrb-car-img' ) ); ?>
                                <?php else : ?>
                                    <div class="acrb-no-img"><span class="dashicons dashicons-car"></span></div>
                                <?php endif; ?>
                            </td>

                            <!-- 2. Car Details -->
                            <td>
                                <strong class="acrb-car-title"><?php echo esc_html( $car->post_title ); ?></strong><br>
                                <div class="acrb-vin-label">
                                    <?php echo $vin ? esc_html__( 'VIN:', 'awesome-car-rental' ) . ' ' . esc_html( $vin ) : esc_html__( 'ID:', 'awesome-car-rental' ) . ' #' . esc_html( $car_id ); ?>
                                </div>
                            </td>

                            <!-- 3. Category -->
                            <td>
                                <?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : foreach ( $cats as $c ) : ?>
                                    <span class="acrb-cat-badge"><?php echo esc_html( $c->name ); ?></span>
                                <?php endforeach; else : ?>
                                    <span style="color:#64748b;">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- 4. Base Price -->
                            <td data-order="<?php echo esc_attr( $price_num ); ?>">
                                <span class="acrb-price-val"><?php echo esc_html( $price_output ); ?></span>
                                <span class="acrb-price-unit"><?php esc_html_e( '/day', 'awesome-car-rental' ); ?></span>
                            </td>

                            <!-- 5. Actions -->
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
                            <td colspan="5" style="text-align:center; padding: 40px; color:#64748b;">
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