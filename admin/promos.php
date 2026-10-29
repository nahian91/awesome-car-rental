<?php
/**
 * Promo Codes & Discounts Module Dispatcher (`promos.php`)
 * Location: admin/promos.php
 *
 * @package Awesome_Car_Rental
 * @since   2.9.8
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_promo_codes_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    $page_slug = 'awesome_car_rental';
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $promo_id  = isset( $_GET['promo_id'] ) ? absint( wp_unslash( $_GET['promo_id'] ) ) : 0;
    $edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

    if ( $edit_id > 0 ) {
        $sub_state = 'edit';
        $promo_id  = $edit_id;
    }

    $tabs = array(
        'all' => array(
            'label' => __( 'All Promo Codes', 'awesome-car-rental' ),
            'icon'  => 'dashicons-tag',
        ),
        'add' => array(
            'label' => __( 'Create Promo Code', 'awesome-car-rental' ),
            'icon'  => 'dashicons-plus-alt2',
        ),
    );
    ?>
    <style>
        .acrb-promos-wrapper {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-promos-wrapper *,
        .acrb-promos-wrapper *::before,
        .acrb-promos-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-promos-topbar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .acrb-promos-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-promos-topbar-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            background: #eef2ff;
            color: #4f46e5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.15);
            flex-shrink: 0;
        }
        .acrb-promos-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-promos-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-promos-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Sub-Navigation Strip */
        .acrb-promos-nav-wrapper {
            display: flex;
            gap: 8px;
            margin-bottom: 22px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
            flex-wrap: wrap;
        }
        .acrb-promos-nav-item {
            background: #ffffff;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 9px 16px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        }
        .acrb-promos-nav-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: #64748b;
            transition: color 0.15s ease;
        }
        .acrb-promos-nav-item:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .acrb-promos-nav-item:hover .dashicons {
            color: #0f172a;
        }
        .acrb-promos-nav-item.is-active {
            background: #eff6ff;
            color: #4f46e5;
            border-color: #c7d2fe;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
        }
        .acrb-promos-nav-item.is-active .dashicons {
            color: #4f46e5;
        }

        /* Action Feedback Notices */
        .acrb-promos-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .acrb-promos-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>

    <div class="acrb-promos-wrapper">
        <!-- Topbar Banner -->
        <header class="acrb-promos-topbar-header">
            <div class="acrb-promos-topbar-title-group">
                <div class="acrb-promos-topbar-icon">
                    <span class="dashicons dashicons-tag"></span>
                </div>
                <div class="acrb-promos-topbar-meta">
                    <h1><?php esc_html_e( 'Promo Codes & Discounts', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Create and govern promotional pricing campaigns, voucher rules, and coupon codes.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Feedback Notices -->
        <?php if ( isset( $_GET['created'] ) && '1' === $_GET['created'] ) : ?>
            <div class="acrb-promos-alert-success">
                ✓ <?php esc_html_e( 'New promo code created successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
            <div class="acrb-promos-alert-success">
                ✓ <?php esc_html_e( 'Promo code updated successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) : ?>
            <div class="acrb-promos-alert-success">
                ✓ <?php esc_html_e( 'Promo code removed successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php endif; ?>

        <!-- Sub-Navigation Tabs -->
        <nav class="acrb-promos-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url          = add_query_arg( array( 'page' => $page_slug, 'tab' => 'promo_codes', 'sub' => $k ), admin_url( 'admin.php' ) );
                $is_active    = ( $sub_state === $k || ( 'all' === $k && 'edit' === $sub_state ) );
                $active_class = $is_active ? ' is-active' : '';
            ?>
                <a href="<?php echo esc_url( $url ); ?>" class="acrb-promos-nav-item<?php echo esc_attr( $active_class ); ?>">
                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
                    <span><?php echo esc_html( $data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <main>
            <?php
            switch ( $sub_state ) {
                case 'add':
                case 'edit':
                    if ( file_exists( ACRB_PATH . 'admin/promos/promos-add-edit.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/promos-add-edit.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/promos/acrb-promos-add-edit.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/acrb-promos-add-edit.php';
                    } else {
                        echo '<div class="acrb-promos-alert-error">' . esc_html__( 'Promo add/edit view template missing.', 'awesome-car-rental' ) . '</div>';
                    }
                    break;

                case 'delete':
                    if ( file_exists( ACRB_PATH . 'admin/promos/promos-delete.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/promos-delete.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/promos/acrb-promos-delete.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/acrb-promos-delete.php';
                    }
                    break;

                case 'all':
                default:
                    if ( file_exists( ACRB_PATH . 'admin/promos/promos-list.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/promos-list.php';
                    } elseif ( file_exists( ACRB_PATH . 'admin/promos/acrb-promos-list.php' ) ) {
                        require_once ACRB_PATH . 'admin/promos/acrb-promos-list.php';
                    } else {
                        echo '<div class="acrb-promos-alert-error">' . esc_html__( 'Promo list view template missing.', 'awesome-car-rental' ) . '</div>';
                    }
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}