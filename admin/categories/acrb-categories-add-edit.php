<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Vehicle Category Creator - Logic & Template with Elite SaaS Aesthetic
 */
function acrb_category_add_edit() {
    $plugin_page = 'awesome_car_rental';
    
    // 1. DATA PROCESSING (Save Logic)
    $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

    if ( 'POST' === $request_method && isset( $_POST['acrb_type_nonce'] ) ) {
        // Verify capability first
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'awesome-car-rental' ) );
        }

        $nonce = sanitize_key( wp_unslash( $_POST['acrb_type_nonce'] ) );
        
        if ( wp_verify_nonce( $nonce, 'acrb_type_action' ) ) {
            $category_name = isset( $_POST['acrb_type_name'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_type_name'] ) ) : '';
            $image_id      = isset( $_POST['acrb_type_image_id'] ) ? absint( wp_unslash( $_POST['acrb_type_image_id'] ) ) : 0;
            $edit_id       = isset( $_POST['acrb_type_edit'] ) ? absint( wp_unslash( $_POST['acrb_type_edit'] ) ) : 0;

            if ( empty( $category_name ) ) {
                echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Error:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Category name cannot be empty.', 'awesome-car-rental' ) . '</p></div>';
            } elseif ( $edit_id > 0 ) { 
                $updated = wp_update_term( $edit_id, 'acrb_categories', array( 'name' => $category_name ) );
                if ( ! is_wp_error( $updated ) ) {
                    update_term_meta( $edit_id, 'acrb_type_image', $image_id );
                    
                    $success_msg = sprintf( 
                        /* translators: %s: The name of the vehicle category. */
                        esc_html__( 'Vehicle category "%s" has been updated.', 'awesome-car-rental' ), 
                        esc_html( $category_name ) 
                    );
                    echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . wp_kses_post( $success_msg ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Error:', 'awesome-car-rental' ) . '</strong> ' . esc_html( $updated->get_error_message() ) . '</p></div>';
                }
            } else { 
                $term = wp_insert_term( $category_name, 'acrb_categories' );
                if ( ! is_wp_error( $term ) ) {
                    $new_id = $term['term_id'];
                    if ( $image_id > 0 ) {
                        update_term_meta( $new_id, 'acrb_type_image', $image_id );
                    }
                    
                    /* translators: %s: The name of the vehicle category. */
                    $success_msg = sprintf( esc_html__( 'Vehicle category "%s" has been created.', 'awesome-car-rental' ), esc_html( $category_name ) );
                    echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . wp_kses_post( $success_msg ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Error:', 'awesome-car-rental' ) . '</strong> ' . esc_html( $term->get_error_message() ) . '</p></div>';
                }
            }
        }
    }

    // 2. STATE INITIALIZATION
    $edit_id       = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
    $edit_term     = $edit_id > 0 ? get_term( $edit_id, 'acrb_categories' ) : null;
    $edit_img_id   = $edit_id > 0 ? get_term_meta( $edit_id, 'acrb_type_image', true ) : '';
    $edit_img_url  = $edit_img_id ? wp_get_attachment_url( $edit_img_id ) : '';
    ?>

    <style>
        .acrb-form-card { max-width: 750px; }
        .acrb-form-header { margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }
        .acrb-form-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
        .acrb-form-subtitle { font-size: 13px; color: #64748b; margin: 0; }
        
        .acrb-form-group { margin-bottom: 20px; }
        .acrb-form-label { display: block; font-weight: 600; font-size: 13px; color: #1e293b; margin-bottom: 8px; }
        .acrb-form-input { width: 100%; max-width: 450px; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 13px; color: #1e293b; box-sizing: border-box; }
        .acrb-form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; outline: none; }

        /* Dropzone UI */
        .acrb-upload-dropzone { border: 2px dashed #cbd5e1; border-radius: 10px; padding: 24px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease; max-width: 450px; }
        .acrb-upload-dropzone:hover { border-color: #3b82f6; background: #f1f5f9; }
        .acrb-preview-container { display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .acrb-preview-img { width: 64px; height: 64px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
        .acrb-upload-icon { font-size: 32px; width: 32px; height: 32px; color: #3b82f6; margin-bottom: 4px; }
        .acrb-upload-hint-main { font-weight: 600; font-size: 14px; color: #0f172a; margin: 0; }
        .acrb-upload-hint-sub { font-size: 12px; color: #64748b; margin: 0; }
        .acrb-upload-hint-active { font-size: 12px; color: #3b82f6; font-weight: 600; margin: 0; }

        /* Form Footer Actions */
        .acrb-form-footer { margin-top: 32px; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 16px; }
        .acrb-link-cancel { color: #64748b; text-decoration: none; font-weight: 600; font-size: 13px; transition: color 0.2s; }
        .acrb-link-cancel:hover { color: #0f172a; }
        .acrb-btn-submit { background: #3b82f6; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; }
        .acrb-btn-submit:hover { background: #2563eb; }
    </style>

    <div class="acrb-form-wrap">
        <div class="acrb-form-card">
            <div class="acrb-form-header">
                <h2 class="acrb-form-title"><?php echo $edit_id > 0 ? esc_html__( 'Update Vehicle Category', 'awesome-car-rental' ) : esc_html__( 'Create Vehicle Category', 'awesome-car-rental' ); ?></h2>
                <p class="acrb-form-subtitle"><?php esc_html_e( 'Organize your fleet (e.g. SUVs, Sedans, Luxury)', 'awesome-car-rental' ); ?></p>
            </div>
            
            <form method="post" id="acrb-category-form">
                <?php wp_nonce_field( 'acrb_type_action', 'acrb_type_nonce' ); ?>
                <input type="hidden" name="acrb_type_edit" value="<?php echo esc_attr( $edit_id ); ?>">
                
                <div class="acrb-form-body">
                    <div class="acrb-form-group">
                        <label class="acrb-form-label"><?php esc_html_e( 'Category Name', 'awesome-car-rental' ); ?></label>
                        <input type="text" name="acrb_type_name" class="acrb-form-input" 
                               placeholder="<?php esc_attr_e( 'e.g. Economy Hatchbacks', 'awesome-car-rental' ); ?>" 
                               value="<?php echo esc_attr( isset( $edit_term->name ) ? $edit_term->name : '' ); ?>" required>
                    </div>

                    <div class="acrb-form-group">
                        <label class="acrb-form-label"><?php esc_html_e( 'Category Icon / Representative Image', 'awesome-car-rental' ); ?></label>
                        <input type="hidden" name="acrb_type_image_id" id="acrb_type_image_id" value="<?php echo esc_attr( $edit_img_id ); ?>">
                        
                        <div id="acrb_type_dropzone" class="acrb-upload-dropzone">
                            <div id="acrb_type_preview" class="acrb-preview-container">
                                <?php if ( $edit_img_url ) : ?>
                                    <img src="<?php echo esc_url( $edit_img_url ); ?>" class="acrb-preview-img" alt="">
                                    <p class="acrb-upload-hint-active"><?php esc_html_e( 'Click to change image', 'awesome-car-rental' ); ?></p>
                                <?php else : ?>
                                    <span class="dashicons dashicons-category acrb-upload-icon"></span>
                                    <p class="acrb-upload-hint-main"><?php esc_html_e( 'Assign Icon', 'awesome-car-rental' ); ?></p>
                                    <p class="acrb-upload-hint-sub"><?php esc_html_e( 'Best for: Transparent PNGs', 'awesome-car-rental' ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="acrb-form-footer">
                    <?php 
                    $back_url = admin_url( 'admin.php?page=' . $plugin_page . '&tab=categories&sub=all' );
                    ?>
                    <button type="submit" class="acrb-btn-submit">
                        <?php echo $edit_id > 0 ? esc_html__( 'Update Category', 'awesome-car-rental' ) : esc_html__( 'Create Category', 'awesome-car-rental' ); ?>
                    </button>
                    <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-link-cancel">
                        <?php esc_html_e( 'Back to List', 'awesome-car-rental' ); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php
}