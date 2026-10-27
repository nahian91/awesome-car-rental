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

                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Vehicle class removed successfully.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    $terms_list = get_terms( array( 'taxonomy' => 'acrb_categories', 'hide_empty' => false ) );
    ?>

    <style>
        .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-table-wrap table.widefat { border: none; margin: 0; box-shadow: none; }
        .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
        .acrb-table-wrap table.widefat tbody td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
        .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

        .acrb-cat-thumb { width: 42px; height: 42px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
        .acrb-cat-no-img { width: 42px; height: 42px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #e2e8f0; }
        
        .acrb-term-name { font-size: 14px; font-weight: 600; color: #0f172a; }
        .acrb-term-meta { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; }
        
        .acrb-count-badge { background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        .acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        .acrb-btn-danger { color: #b91c1c; border-color: #fecaca; }
        .acrb-btn-danger:hover { background: #fee2e2; color: #991b1b; border-color: #f87171; }
    </style>

    <div class="acrb-table-wrap">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width: 70px;"><?php esc_html_e( 'Icon', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Class Name', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Linked Vehicles', 'awesome-car-rental' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
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
                            echo wp_get_attachment_image( $thumbnail_id, [42, 42], false, ['class' => 'acrb-cat-thumb'] );
                        else : ?>
                            <div class="acrb-cat-no-img"><span class="dashicons dashicons-category"></span></div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <strong class="acrb-term-name"><?php echo esc_html( $term_item->name ); ?></strong><br>
                        <code class="acrb-term-meta">ID: #<?php echo absint( $term_item->term_id ); ?> | SLUG: <?php echo esc_html( $term_item->slug ); ?></code>
                    </td>

                    <td>
                        <span class="acrb-count-badge">
                            <?php echo absint( $term_item->count ); ?> <?php esc_html_e( 'Vehicles', 'awesome-car-rental' ); ?>
                        </span>
                    </td>

                    <td style="text-align:right;">
                        <a class="acrb-btn-action" href="<?php echo esc_url( $edit_url ); ?>">
                            <span class="dashicons dashicons-edit" style="font-size:14px; width:14px; height:14px;"></span> 
                            <span><?php esc_html_e( 'Edit', 'awesome-car-rental' ); ?></span>
                        </a>
                        <a class="acrb-btn-action acrb-btn-danger" 
                           href="<?php echo esc_url( $delete_url ); ?>" 
                           onclick="return confirm('<?php esc_attr_e( 'Archive this class? Vehicles linked to it will remain but become uncategorized.', 'awesome-car-rental' ); ?>')"
                           title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-trash" style="font-size:14px; width:14px; height:14px;"></span>
                        </a>
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
    <?php
}