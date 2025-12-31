<?php
if (!defined('ABSPATH')) exit;

/**
 * List All Vehicle Categories - Clean Logic
 */
function acrb_category_list() {
    $acrb_page_slug = 'awesome_car_rental';

    // Single Delete Logic
    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        $acrb_term_id = intval($_GET['delete']);
        $acrb_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if (wp_verify_nonce($acrb_nonce, 'acrb_delete_cat_' . $acrb_term_id)) {
            wp_delete_term($acrb_term_id, 'acrb_categories');
            echo '<div class="notice notice-success is-dismissible acrb-admin-notice"><p>' . esc_html__('Vehicle class removed successfully.', 'awesome-car-rental') . '</p></div>';
        }
    }

    $acrb_terms = get_terms(['taxonomy' => 'acrb_categories', 'hide_empty' => false]);
    ?>

    <div class="wrap acrb-main-wrapper">
        <div class="acrb-header-flex">
            <div class="acrb-header-title-group">
                <h1 class="acrb-page-title"><?php esc_html_e('Vehicle Classes', 'awesome-car-rental'); ?></h1>
                <p class="acrb-page-subtitle"><?php esc_html_e('Manage your fleet categories and types.', 'awesome-car-rental'); ?></p>
            </div>
            <a href="<?php echo esc_url(admin_url("admin.php?page=$acrb_page_slug&tab=categories&sub=add")); ?>" class="button button-primary acrb-btn-add">
                + <?php esc_html_e('Register New Class', 'awesome-car-rental'); ?>
            </a>
        </div>

        <table id="acrb-category-table" class="widefat">
            <thead>
                <tr>
                    <th class="acrb-col-icon"><?php esc_html_e('Icon', 'awesome-car-rental'); ?></th>
                    <th class="acrb-col-name"><?php esc_html_e('Class Name', 'awesome-car-rental'); ?></th>
                    <th class="acrb-col-count"><?php esc_html_e('Linked Vehicles', 'awesome-car-rental'); ?></th>
                    <th class="acrb-col-actions"><?php esc_html_e('Management', 'awesome-car-rental'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($acrb_terms) && !is_wp_error($acrb_terms)) : ?>
                <?php foreach ($acrb_terms as $acrb_term) :
                    $acrb_img_id = get_term_meta($acrb_term->term_id, 'acrb_type_image', true);
                    $acrb_edit_url = admin_url("admin.php?page=$acrb_page_slug&tab=categories&sub=add&edit={$acrb_term->term_id}");
                    $acrb_delete_url = wp_nonce_url(admin_url("admin.php?page=$acrb_page_slug&tab=categories&sub=all&delete={$acrb_term->term_id}"), 'acrb_delete_cat_' . $acrb_term->term_id);
                ?>
                <tr>
                    <td>
                        <?php if ($acrb_img_id) : 
                            echo wp_get_attachment_image($acrb_img_id, [50, 50], false, ['class' => 'acrb-cat-thumb']);
                        else : ?>
                            <div class="acrb-cat-no-img"><span class="dashicons dashicons-category"></span></div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <strong class="acrb-term-name"><?php echo esc_html($acrb_term->name); ?></strong><br>
                        <code class="acrb-term-meta">ID: #<?php echo absint($acrb_term->term_id); ?> | SLUG: <?php echo esc_html($acrb_term->slug); ?></code>
                    </td>

                    <td>
                        <span class="acrb-count-badge">
                            <?php echo absint($acrb_term->count); ?> <?php esc_html_e('Vehicles', 'awesome-car-rental'); ?>
                        </span>
                    </td>

                    <td class="acrb-actions-cell">
                        <a class="acrb-btn" href="<?php echo esc_url($acrb_edit_url); ?>">
                            <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Edit', 'awesome-car-rental'); ?>
                        </a>
                        <a class="acrb-btn acrb-btn-danger" 
                           href="<?php echo esc_url($acrb_delete_url); ?>" 
                           onclick="return confirm('<?php esc_attr_e('Archive this class? Vehicles linked to it will remain but become uncategorized.', 'awesome-car-rental'); ?>')">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding: 20px;">
                        <?php esc_html_e('No vehicle classes found.', 'awesome-car-rental'); ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php
}