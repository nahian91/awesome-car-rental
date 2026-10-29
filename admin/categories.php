<?php
/**
 * Vehicle Categories Management Module Dispatcher (`categories.php`)
 * Location: admin/categories.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_category_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    global $wpdb;
    $table_categories = $wpdb->prefix . 'dev_nahian_categories';

    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

    if ( $edit_id > 0 ) {
        $sub_state = 'edit';
    }

    // 1. Process Form Submissions (Add / Update Category with Image)
    if ( isset( $_POST['acrb_save_category'] ) && check_admin_referer( 'acrb_type_action', 'acrb_type_nonce' ) ) {
        $id            = isset( $_POST['acrb_type_edit'] ) ? absint( $_POST['acrb_type_edit'] ) : 0;
        $category_name = sanitize_text_field( wp_unslash( $_POST['acrb_type_name'] ?? '' ) );
        $category_desc = sanitize_textarea_field( wp_unslash( $_POST['acrb_type_desc'] ?? '' ) );
        
        // Resolve image URL from direct string or media ID
        $image_url = esc_url_raw( wp_unslash( $_POST['acrb_type_image_url'] ?? '' ) );
        $image_id  = isset( $_POST['acrb_type_image_id'] ) ? absint( wp_unslash( $_POST['acrb_type_image_id'] ) ) : 0;
        if ( $image_id > 0 ) {
            $attachment_url = wp_get_attachment_url( $image_id );
            if ( $attachment_url ) {
                $image_url = $attachment_url;
            }
        }

        if ( ! empty( $category_name ) ) {
            // Generate clean, collision-free slug
            $base_slug   = sanitize_title( $category_name );
            $slug        = ! empty( $base_slug ) ? $base_slug : 'category-' . time();
            $slug_suffix = 1;

            while ( true ) {
                if ( $id > 0 ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_categories} WHERE slug = %s AND id != %d", $slug, $id ) );
                } else {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_categories} WHERE slug = %s", $slug ) );
                }

                if ( ! $exists ) {
                    break;
                }
                $slug = $base_slug . '-' . $slug_suffix;
                $slug_suffix++;
            }

            $data = array(
                'name'        => $category_name,
                'slug'        => $slug,
                'description' => $category_desc,
                'image'       => $image_url,
            );
            $formats = array( '%s', '%s', '%s', '%s' );

            if ( $id > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update( $table_categories, $data, array( 'id' => $id ), $formats, array( '%d' ) );
            } else {
                $data['created_at'] = current_time( 'mysql' );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_categories, $data, array_merge( $formats, array( '%s' ) ) );
            }

            $redirect_url = admin_url( 'admin.php?page=awesome_car_rental&tab=categories' );

            // Check if headers have already been sent before redirecting
            if ( headers_sent() ) {
                echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
    ?>
    <style>
        .acrb-cat-wrapper {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-cat-wrapper *, 
        .acrb-cat-wrapper *::before, 
        .acrb-cat-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-cat-topbar-header {
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
        .acrb-cat-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-cat-topbar-icon {
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
        .acrb-cat-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-cat-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-cat-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }
    </style>

    <div class="acrb-cat-wrapper">
        <!-- Topbar Heading Banner -->
        <header class="acrb-cat-topbar-header">
            <div class="acrb-cat-topbar-title-group">
                <div class="acrb-cat-topbar-icon">
                    <span class="dashicons dashicons-category"></span>
                </div>
                <div class="acrb-cat-topbar-meta">
                    <h1><?php esc_html_e( 'Fleet Car Categories', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Organize your vehicle catalog into distinct classifications.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php
        // Sub-navigation tabs (All Categories / Add Category)
        if ( file_exists( ACRB_PATH . 'admin/categories/categories-tab.php' ) ) {
            require_once ACRB_PATH . 'admin/categories/categories-tab.php';
        } elseif ( file_exists( ACRB_PATH . 'admin/categories/categories-tabs.php' ) ) {
            require_once ACRB_PATH . 'admin/categories/categories-tabs.php';
        }

        // Submodule view routing
        switch ( $sub_state ) {
            case 'add':
            case 'edit':
                if ( file_exists( ACRB_PATH . 'admin/categories/categories-add-edit.php' ) ) {
                    require_once ACRB_PATH . 'admin/categories/categories-add-edit.php';
                }
                break;

            case 'view':
                if ( file_exists( ACRB_PATH . 'admin/categories/categories-view.php' ) ) {
                    require_once ACRB_PATH . 'admin/categories/categories-view.php';
                }
                break;

            case 'delete':
                if ( file_exists( ACRB_PATH . 'admin/categories/categories-delete.php' ) ) {
                    require_once ACRB_PATH . 'admin/categories/categories-delete.php';
                }
                break;

            case 'list':
            default:
                if ( file_exists( ACRB_PATH . 'admin/categories/categories-list.php' ) ) {
                    require_once ACRB_PATH . 'admin/categories/categories-list.php';
                }
                break;
        }
        ?>
    </div>
    <?php
}