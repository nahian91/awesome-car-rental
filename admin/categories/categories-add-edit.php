<?php
/**
 * Add / Edit Category Component (`categories-add-edit.php`)
 * Location: admin/categories/categories-add-edit.php
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

wp_enqueue_media();

$table_categories = $wpdb->prefix . 'dev_nahian_categories';
$cat_id           = isset( $_GET['cat_id'] ) ? absint( $_GET['cat_id'] ) : ( isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0 );
$list_url         = admin_url( 'admin.php?page=awesome_car_rental&tab=categories' );

$category = null;
if ( $cat_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $category = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_categories} WHERE id = %d", $cat_id ), ARRAY_A );
}

$c_name = $category['name'] ?? '';
$c_desc = $category['description'] ?? '';
$c_img  = $category['image'] ?? '';
?>

<style>
    .acrb-cat-card {
        max-width: 680px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 28px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        box-sizing: border-box;
    }
    .acrb-cat-header-title {
        margin: 0 0 20px;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 14px;
    }
    .acrb-cat-form-group {
        margin-bottom: 20px;
    }
    .acrb-cat-label {
        display: block;
        font-weight: 700;
        color: #1e293b;
        font-size: 13.5px;
        margin-bottom: 6px;
    }
    .acrb-cat-input,
    .acrb-cat-textarea {
        width: 100%;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13.5px;
        color: #1e293b;
        box-sizing: border-box;
        transition: all 0.15s ease;
    }
    .acrb-cat-input:focus,
    .acrb-cat-textarea:focus {
        background: #ffffff;
        border-color: #4f46e5;
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
    .acrb-media-preview-box {
        display: flex;
        align-items: center;
        gap: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
    }
    .acrb-media-thumb {
        width: 80px;
        height: 60px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        object-fit: contain;
    }
    .acrb-media-placeholder {
        width: 80px;
        height: 60px;
        border-radius: 6px;
        border: 1px dashed #cbd5e1;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
    }
    .acrb-media-btn-group {
        display: flex;
        gap: 8px;
    }
    .acrb-btn-media {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-weight: 700;
        font-size: 13px;
        border-radius: 8px;
        padding: 9px 16px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .acrb-btn-media:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .acrb-btn-remove-media {
        background: transparent;
        border: 1px solid #fecaca;
        color: #dc2626;
        font-weight: 700;
        font-size: 13px;
        border-radius: 8px;
        padding: 9px 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .acrb-btn-remove-media:hover {
        background: #fee2e2;
    }
    .acrb-cat-btn-actions {
        margin-top: 24px;
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .acrb-cat-btn-submit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 10px 22px;
        font-size: 13.5px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
    }
    .acrb-cat-btn-submit:hover {
        background: #4338ca;
    }
    .acrb-cat-btn-cancel {
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
    }
    .acrb-cat-btn-cancel:hover {
        color: #0f172a;
    }
</style>

<div class="acrb-cat-card">
    <h2 class="acrb-cat-header-title">
        <?php echo $cat_id > 0 ? sprintf( esc_html__( 'Edit Category: %s', 'awesome-car-rental' ), esc_html( $c_name ) ) : esc_html__( 'Add New Vehicle Category', 'awesome-car-rental' ); ?>
    </h2>

    <form method="post" id="acrb-category-form">
        <?php wp_nonce_field( 'acrb_type_action', 'acrb_type_nonce' ); ?>
        <input type="hidden" name="acrb_type_edit" value="<?php echo absint( $cat_id ); ?>">

        <!-- Category Name -->
        <div class="acrb-cat-form-group">
            <label class="acrb-cat-label" for="acrb_type_name"><?php esc_html_e( 'Category Name', 'awesome-car-rental' ); ?></label>
            <input type="text" id="acrb_type_name" name="acrb_type_name" value="<?php echo esc_attr( $c_name ); ?>" class="acrb-cat-input" placeholder="<?php esc_attr_e( 'e.g. Luxury Sedan, SUV, Sports Coupe', 'awesome-car-rental' ); ?>" required>
        </div>

        <!-- Category Description -->
        <div class="acrb-cat-form-group">
            <label class="acrb-cat-label" for="acrb_type_desc"><?php esc_html_e( 'Category Description', 'awesome-car-rental' ); ?></label>
            <textarea id="acrb_type_desc" name="acrb_type_desc" rows="3" class="acrb-cat-textarea" placeholder="<?php esc_attr_e( 'Brief overview of this vehicle class...', 'awesome-car-rental' ); ?>"><?php echo esc_textarea( $c_desc ); ?></textarea>
        </div>

        <!-- Media Library Image Picker -->
        <div class="acrb-cat-form-group">
            <label class="acrb-cat-label"><?php esc_html_e( 'Category Image', 'awesome-car-rental' ); ?></label>
            
            <!-- Both ID and URL hidden inputs provided for full compatibility -->
            <input type="hidden" name="acrb_type_image_id" id="acrb_type_image_id" value="">
            <input type="hidden" name="acrb_type_image_url" id="acrb_type_image_url" value="<?php echo esc_url( $c_img ); ?>">

            <div class="acrb-media-preview-box">
                <div id="acrb_preview_container">
                    <?php if ( ! empty( $c_img ) ) : ?>
                        <img src="<?php echo esc_url( $c_img ); ?>" id="acrb_image_thumb" class="acrb-media-thumb" alt="">
                    <?php else : ?>
                        <div id="acrb_image_placeholder" class="acrb-media-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="acrb-media-btn-group">
                    <button type="button" class="acrb-btn-media" id="acrb_upload_media_btn">
                        <span class="dashicons dashicons-upload"></span>
                        <span id="acrb_media_btn_label"><?php echo ! empty( $c_img ) ? esc_html__( 'Change Image', 'awesome-car-rental' ) : esc_html__( 'Select from Media Library', 'awesome-car-rental' ); ?></span>
                    </button>
                    
                    <button type="button" class="acrb-btn-remove-media" id="acrb_remove_media_btn" style="<?php echo empty( $c_img ) ? 'display: none;' : ''; ?>">
                        <span class="dashicons dashicons-trash"></span>
                        <span><?php esc_html_e( 'Remove', 'awesome-car-rental' ); ?></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="acrb-cat-btn-actions">
            <button type="submit" name="acrb_save_category" class="acrb-cat-btn-submit">
                <span class="dashicons <?php echo $cat_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>"></span>
                <span><?php echo $cat_id > 0 ? esc_html__( 'Update Category', 'awesome-car-rental' ) : esc_html__( 'Save Category', 'awesome-car-rental' ); ?></span>
            </button>
            <a href="<?php echo esc_url( $list_url ); ?>" class="acrb-cat-btn-cancel">
                <?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var mediaFrame;

    $('#acrb_upload_media_btn').on('click', function(e) {
        e.preventDefault();

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: '<?php echo esc_js( __( 'Select or Upload Category Image', 'awesome-car-rental' ) ); ?>',
            button: { text: '<?php echo esc_js( __( 'Use this image', 'awesome-car-rental' ) ); ?>' },
            library: { type: 'image' },
            multiple: false
        });

        mediaFrame.on('select', function() {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            $('#acrb_type_image_id').val(attachment.id);
            $('#acrb_type_image_url').val(attachment.url);

            $('#acrb_preview_container').html('<img src="' + attachment.url + '" class="acrb-media-thumb" alt="">');
            $('#acrb_media_btn_label').text('<?php echo esc_js( __( 'Change Image', 'awesome-car-rental' ) ); ?>');
            $('#acrb_remove_media_btn').show();
        });

        mediaFrame.open();
    });

    $('#acrb_remove_media_btn').on('click', function(e) {
        e.preventDefault();
        $('#acrb_type_image_id').val('');
        $('#acrb_type_image_url').val('');
        $('#acrb_preview_container').html('<div class="acrb-media-placeholder"><span class="dashicons dashicons-format-image"></span></div>');
        $('#acrb_media_btn_label').text('<?php echo esc_js( __( 'Select from Media Library', 'awesome-car-rental' ) ); ?>');
        $(this).hide();
    });
});
</script>