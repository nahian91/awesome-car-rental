<?php
/**
 * Category CRM View Component (`categories-view.php`)
 * Location: admin/categories/categories-view.php
 *
 * @package Awesome_Car_Rental
 * @since   2.4.9
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$table_categories = $wpdb->prefix . 'dev_nahian_categories';
$table_cars       = $wpdb->prefix . 'dev_nahian_cars';
$cat_id           = isset( $_GET['cat_id'] ) ? absint( $_GET['cat_id'] ) : 0;
$base_url         = admin_url( 'admin.php?page=awesome_car_rental&tab=categories' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_categories} WHERE id = %d", $cat_id ), ARRAY_A );

if ( ! $cat ) {
    ?>
    <div class="acrb-view-notfound">
        <p><?php esc_html_e( 'Category not found.', 'awesome-car-rental' ); ?></p>
        <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-view-btn-back">
            &larr; <?php esc_html_e( 'Back to Categories', 'awesome-car-rental' ); ?>
        </a>
    </div>
    <?php
    return;
}

$c_name = esc_html( $cat['name'] );
$c_slug = esc_html( $cat['slug'] );
$c_desc = ! empty( $cat['description'] ) ? esc_html( $cat['description'] ) : __( 'No description provided for this category.', 'awesome-car-rental' );
$c_img  = esc_url( $cat['image'] ?? '' );
$c_date = ! empty( $cat['created_at'] ) ? esc_html( (string) $cat['created_at'] ) : '—';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$car_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_cars} WHERE category = %s", $cat['name'] ) );
$edit_url  = add_query_arg( array( 'sub' => 'edit', 'cat_id' => $cat_id ), $base_url );
?>

<style>
    .acrb-cat-view-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 20px;
        align-items: start;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-cat-view-grid *,
    .acrb-cat-view-grid *::before,
    .acrb-cat-view-grid *::after {
        box-sizing: border-box;
    }

    /* Cards */
    .acrb-cat-view-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
    }
    .acrb-cat-dossier {
        text-align: center;
    }

    /* Icon / Image Frame */
    .acrb-cat-avatar-wrap {
        width: 80px;
        height: 80px;
        margin: 0 auto 14px auto;
        border-radius: 14px;
        overflow: hidden;
        border: 2px solid #e0e7ff;
        background: #eef2ff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .acrb-cat-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .acrb-cat-avatar-icon {
        font-size: 34px;
        width: 34px;
        height: 34px;
        color: #4f46e5;
    }

    /* Titles & Slug */
    .acrb-cat-view-name {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
    }
    .acrb-cat-view-slug {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        background: #f1f5f9;
        color: #64748b;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-block;
    }

    /* Stats List */
    .acrb-cat-meta-list {
        margin-top: 20px;
        text-align: left;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 13px;
    }
    .acrb-cat-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .acrb-cat-meta-label {
        color: #64748b;
        font-weight: 500;
    }
    .acrb-cat-meta-value {
        color: #0f172a;
        font-weight: 700;
    }
    .acrb-cat-fleet-badge {
        background: #eff6ff;
        color: #3b82f6;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 700;
    }

    /* Action Links */
    .acrb-cat-view-actions {
        margin-top: 20px;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .acrb-cat-btn-edit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        transition: background 0.15s ease;
    }
    .acrb-cat-btn-edit:hover {
        background: #4338ca;
        color: #ffffff;
    }
    .acrb-cat-btn-edit .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .acrb-cat-btn-back {
        color: #64748b;
        text-decoration: none;
        font-size: 12.5px;
        font-weight: 600;
        text-align: center;
        padding: 6px;
    }
    .acrb-cat-btn-back:hover {
        color: #0f172a;
    }

    /* Detail / Overview Card */
    .acrb-cat-overview-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 16px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }
    .acrb-cat-desc-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
    }
    .acrb-cat-desc-heading {
        margin: 0 0 10px 0;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .acrb-cat-desc-text {
        margin: 0;
        font-size: 13.5px;
        color: #334155;
        line-height: 1.6;
    }

    /* Not Found Notice */
    .acrb-view-notfound {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .acrb-view-btn-back {
        color: #991b1b;
        text-decoration: underline;
        font-weight: 700;
    }

    @media (max-width: 900px) {
        .acrb-cat-view-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="acrb-cat-view-grid">
    <!-- Left: Category Dossier Card -->
    <div class="acrb-cat-view-card acrb-cat-dossier">
        <div class="acrb-cat-avatar-wrap">
            <?php if ( ! empty( $c_img ) ) : ?>
                <img src="<?php echo esc_url( $c_img ); ?>" alt="<?php echo esc_attr( $c_name ); ?>" class="acrb-cat-avatar-img">
            <?php else : ?>
                <span class="dashicons dashicons-category acrb-cat-avatar-icon"></span>
            <?php endif; ?>
        </div>

        <h3 class="acrb-cat-view-name"><?php echo esc_html( $c_name ); ?></h3>
        <code class="acrb-cat-view-slug"><?php echo esc_html( $c_slug ); ?></code>

        <div class="acrb-cat-meta-list">
            <div class="acrb-cat-meta-row">
                <span class="acrb-cat-meta-label"><?php esc_html_e( 'Fleet Allocation', 'awesome-car-rental' ); ?></span>
                <span class="acrb-cat-fleet-badge">
                    <?php echo esc_html( (string) $car_count ); ?> <?php esc_html_e( 'Cars', 'awesome-car-rental' ); ?>
                </span>
            </div>
            <div class="acrb-cat-meta-row">
                <span class="acrb-cat-meta-label"><?php esc_html_e( 'Created On', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-cat-meta-value"><?php echo esc_html( $c_date ); ?></strong>
            </div>
        </div>

        <div class="acrb-cat-view-actions">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-cat-btn-edit">
                <span class="dashicons dashicons-edit"></span>
                <span><?php esc_html_e( 'Edit Category', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-cat-btn-back">
                &larr; <?php esc_html_e( 'Back to All Categories', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </div>

    <!-- Right: Description & Overview -->
    <div class="acrb-cat-view-card">
        <h2 class="acrb-cat-overview-title"><?php esc_html_e( 'Category Overview', 'awesome-car-rental' ); ?></h2>
        <div class="acrb-cat-desc-box">
            <h4 class="acrb-cat-desc-heading"><?php esc_html_e( 'Description', 'awesome-car-rental' ); ?></h4>
            <p class="acrb-cat-desc-text">
                <?php echo esc_html( $c_desc ); ?>
            </p>
        </div>
    </div>
</div>