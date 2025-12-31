<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function acrb_cars_list() {
    // 1. DYNAMIC SETTINGS FETCHING
    $currency     = get_option( 'acrb_currency', '£' );
    $currency_pos = get_option( 'acrb_currency_pos', 'left' );

    $cars = get_posts([
        'post_type'   => 'acrb_cars',
        'numberposts' => -1, 
        'orderby'     => 'ID',
        'order'       => 'DESC',
    ]);

    // Handle Success Messages
    if ( isset( $_GET['msg'] ) && 'deleted' === $_GET['msg'] ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Car moved to trash.', 'awesome-car-rental' ) . '</p></div>';
    }
    ?>

    <div class="wrap acrb-dashboard-container">
        <h1 class="wp-heading-inline"><?php esc_html_e('Fleet Inventory', 'awesome-car-rental'); ?></h1>
        <hr class="wp-header-end">

        <table id="acrb-cars-table" class="widefat acrb-table">
            <thead>
                <tr>
                    <th class="acrb-col-img"><?php esc_html_e('Image', 'awesome-car-rental'); ?></th>
                    <th><?php esc_html_e('Car Details', 'awesome-car-rental'); ?></th>
                    <th><?php esc_html_e('Class/Category', 'awesome-car-rental'); ?></th>
                    <th class="acrb-col-actions"><?php esc_html_e('Actions', 'awesome-car-rental'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $cars ) : 
                    foreach ( $cars as $car ) :
                        $car_id   = $car->ID;
                        $cats     = wp_get_post_terms( $car_id, 'acrb_categories' );
                        $vin      = get_post_meta( $car_id, 'vin_number', true );

                        // URLs
                        $edit_url = admin_url( 'admin.php?page=awesome_car_rental&tab=cars&sub=edit&car_id=' . intval( $car_id ) );
                        $view_url = admin_url( 'admin.php?page=awesome_car_rental&tab=cars&sub=view&car_id=' . intval( $car_id ) );
                        
                        // Secure Delete URL with Nonce
                        $del_url_raw = admin_url( 'admin-post.php?action=acrb_delete_car&car=' . intval( $car_id ) );
                        $del_url     = wp_nonce_url( $del_url_raw, 'acrb_delete_car_' . $car_id );
                ?>
                    <tr>
                        <td>
                            <?php if ( has_post_thumbnail( $car_id ) ) : ?>
                                <?php echo get_the_post_thumbnail( $car_id, [60, 45], ['class' => 'acrb-car-img'] ); ?>
                            <?php else : ?>
                                <div class="acrb-no-img"><span class="dashicons dashicons-car"></span></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="acrb-car-title"><?php echo esc_html( $car->post_title ); ?></strong>
                            <div class="acrb-vin-label">
                                <?php echo $vin ? esc_html__( 'VIN:', 'awesome-car-rental' ) . ' ' . esc_html( $vin ) : esc_html__( 'ID:', 'awesome-car-rental' ) . ' #' . esc_html( $car_id ); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ( $cats ) : foreach ( $cats as $c ) : ?>
                                <span class="acrb-badge acrb-cat-badge"><?php echo esc_html( $c->name ); ?></span>
                            <?php endforeach; else : ?>
                                <span class="acrb-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="acrb-actions-cell">
                            <div class="acrb-btn-group">
                                <a class="acrb-btn" href="<?php echo esc_url( $view_url ); ?>" title="<?php esc_attr_e( 'View', 'awesome-car-rental' ); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                                <a class="acrb-btn" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a class="acrb-btn acrb-btn-danger acrb-delete-confirm" href="<?php echo esc_url( $del_url ); ?>" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr>
                        <td colspan="4" class="acrb-empty-row">
                            <?php esc_html_e( 'Your car is empty.', 'awesome-car-rental' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}