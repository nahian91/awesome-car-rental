<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * List All Vehicle Categories - Clean Logic & Elite SaaS Table Layout
 */
function acrb_category_list() {
    $page_slug = 'awesome_car_rental';

    // Single Delete Logic
    if ( isset( $_GET['delete'] ) && current_user_can( 'manage_options' ) ) {
        $term_id = absint( $_GET['delete'] );
        $nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        
        if ( $term_id > 0 && wp_verify_nonce( $nonce, 'acrb_delete_cat_' . $term_id ) ) {
            $deleted = wp_delete_term( $term_id, 'acrb_categories' );
            if ( ! is_wp_error( $deleted ) ) {
                // Clear related transient caches
                delete_transient( 'acrb_dashboard_revenue' );

                echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Vehicle class removed successfully.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    $terms_list = get_terms( array( 'taxonomy' => 'acrb_categories', 'hide_empty' => false ) );
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

        /* Row Badges & Media */
        .acrb-cat-thumb { width: 42px; height: 42px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; display: block; }
        .acrb-cat-no-img { width: 42px; height: 42px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #e2e8f0; }

        .acrb-term-name { font-size: 14px; font-weight: 600; color: #0f172a; }
        .acrb-term-meta { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; font-family: monospace; white-space: nowrap; }

        .acrb-count-badge { background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; white-space: nowrap; }

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
                        <th style="width: 70px;" data-orderable="false"><?php esc_html_e( 'Icon', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Class Name', 'awesome-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Linked Vehicles', 'awesome-car-rental' ); ?></th>
                        <th class="all" style="text-align:right; width: 100px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( ! empty( $terms_list ) && ! is_wp_error( $terms_list ) ) : ?>
                    <?php foreach ( $terms_list as $term_item ) :
                        $thumbnail_id = get_term_meta( $term_item->term_id, 'acrb_type_image', true );
                        $edit_url     = admin_url( 'admin.php?page=' . $page_slug . '&tab=categories&sub=add&edit=' . $term_item->term_id );
                        $delete_url   = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=categories&sub=all&delete=' . $term_item->term_id ), 'acrb_delete_cat_' . $term_item->term_id );
                    ?>
                    <tr>
                        <td>
                            <?php if ( $thumbnail_id ) : 
                                echo wp_get_attachment_image( $thumbnail_id, array( 42, 42 ), false, array( 'class' => 'acrb-cat-thumb' ) );
                            else : ?>
                                <div class="acrb-cat-no-img"><span class="dashicons dashicons-category"></span></div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <strong class="acrb-term-name"><?php echo esc_html( $term_item->name ); ?></strong><br>
                            <span class="acrb-term-meta">ID: #<?php echo absint( $term_item->term_id ); ?> | SLUG: <?php echo esc_html( $term_item->slug ); ?></span>
                        </td>

                        <td data-order="<?php echo absint( $term_item->count ); ?>">
                            <span class="acrb-count-badge">
                                <?php echo absint( $term_item->count ); ?> <?php esc_html_e( 'Vehicles', 'awesome-car-rental' ); ?>
                            </span>
                        </td>

                        <td style="text-align:right;">
                            <div class="acrb-actions-group">
                                <a class="acrb-btn-action" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a class="acrb-btn-action acrb-btn-delete" 
                                   href="<?php echo esc_url( $delete_url ); ?>" 
                                   onclick="return confirm('<?php esc_attr_e( 'Archive this class? Vehicles linked to it will remain but become uncategorized.', 'awesome-car-rental' ); ?>')"
                                   title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>"
                                   style="color: #b91c1c;">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding: 40px; color:#64748b;">
                            <?php esc_html_e( 'No vehicle classes found.', 'awesome-car-rental' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}