<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Vehicle Category Creator - Logic & Template
 */
function acrb_category_add_edit() {
    $page_slug = 'awesome_car_rental';
    
    // 1. DATA PROCESSING (Save Logic)
    $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

    if ( 'POST' === $request_method && isset( $_POST['acrb_type_nonce'] ) ) {
        // Fix: Unslash and Sanitize Nonce
        $nonce = sanitize_key( wp_unslash( $_POST['acrb_type_nonce'] ) );
        
        if ( wp_verify_nonce( $nonce, 'acrb_type_action' ) ) {
            
            // Fix: Validate, Unslash, and Sanitize all POST inputs
            $name    = isset( $_POST['acrb_type_name'] ) ? sanitize_text_field( wp_unslash( $_POST['acrb_type_name'] ) ) : '';
            $img_id  = isset( $_POST['acrb_type_image_id'] ) ? absint( wp_unslash( $_POST['acrb_type_image_id'] ) ) : 0;
            $edit_id = isset( $_POST['acrb_type_edit'] ) ? absint( wp_unslash( $_POST['acrb_type_edit'] ) ) : 0;

            if ( $edit_id ) { 
                wp_update_term( $edit_id, 'acrb_categories', array( 'name' => $name ) );
                update_term_meta( $edit_id, 'acrb_type_image', $img_id );
                
            // Updated Message for Editing
$acrb_msg = sprintf( 
    /* translators: %s: The name of the vehicle category. */
    esc_html__( 'Vehicle category "%s" has been updated.', 'awesome-car-rental' ), 
    esc_html( $name ) 
);
                echo '<div class="updated notice is-dismissible acrb-admin-notice"><p><strong>' . esc_html__( 'Update Successful:', 'awesome-car-rental' ) . '</strong> ' . wp_kses_post( $acrb_msg ) . '</p></div>';
            } else { 
                $term = wp_insert_term( $name, 'acrb_categories' );
                if ( ! is_wp_error( $term ) ) {
                    $new_id = $term['term_id'];
                    if ( $img_id ) {
                        add_term_meta( $new_id, 'acrb_type_image', $img_id );
                    }
                    
                    // Updated Message for Creation
                    /* translators: %s: The name of the vehicle category. */
$acrb_msg = sprintf( esc_html__( 'Vehicle category "%s" has been created.', 'awesome-car-rental' ), esc_html( $name ) );
                    echo '<div class="updated notice is-dismissible acrb-admin-notice"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . wp_kses_post( $acrb_msg ) . '</p></div>';
                } else {
                    // Display WordPress error if creation fails (e.g., duplicate name)
                    echo '<div class="error notice is-dismissible acrb-admin-notice"><p><strong>' . esc_html__( 'Error:', 'awesome-car-rental' ) . '</strong> ' . esc_html( $term->get_error_message() ) . '</p></div>';
                }
            }
        }
    }

    // 2. STATE INITIALIZATION
    $edit_id      = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
    $edit_term    = $edit_id ? get_term( $edit_id, 'acrb_categories' ) : null;
    $edit_img_id  = $edit_id ? get_term_meta( $edit_id, 'acrb_type_image', true ) : '';
    $edit_img_url = $edit_img_id ? wp_get_attachment_url( $edit_img_id ) : '';
    ?>

    <div class="acrb-form-wrap">
        <div class="acrb-form-card">
            <div class="acrb-form-header">
                <h2 class="acrb-form-title"><?php echo $edit_id ? esc_html__( 'Update Vehicle Category', 'awesome-car-rental' ) : esc_html__( 'Create Vehicle Category', 'awesome-car-rental' ); ?></h2>
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
                                    <img src="<?php echo esc_url( $edit_img_url ); ?>" class="acrb-preview-img">
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
                    $back_url = admin_url( "admin.php?page=$page_slug&tab=categories&sub=all" );
                    ?>
                    <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-link-cancel">
                        <?php esc_html_e( 'Back to List', 'awesome-car-rental' ); ?>
                    </a>
                    <button type="submit" class="acrb-btn-submit">
                        <?php echo $edit_id ? esc_html__( 'Update Category', 'awesome-car-rental' ) : esc_html__( 'Create Category', 'awesome-car-rental' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}