<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add/Edit Vehicle Type - Car Rental SaaS UI
 */
function acrb_vehicle_type_add_edit() {
    $item_id = isset( $_GET['item'] ) ? absint( wp_unslash( $_GET['item'] ) ) : 0;
    $term    = $item_id > 0 ? get_term( $item_id, 'acrb_categories' ) : null;
    $img_id  = $item_id > 0 ? get_term_meta( $item_id, 'acrb_type_image', true ) : '';

    // Handle Form Submission
    if ( isset( $_POST['acrb_save_type'] ) && check_admin_referer( 'acrb_type_nonce' ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            $name = isset( $_POST['type_name'] ) ? sanitize_text_field( wp_unslash( $_POST['type_name'] ) ) : '';
            $img  = isset( $_POST['type_image_id'] ) ? absint( wp_unslash( $_POST['type_image_id'] ) ) : 0;

            if ( $item_id > 0 ) {
                // Update Existing
                wp_update_term( $item_id, 'acrb_categories', array( 'name' => $name ) );
                update_term_meta( $item_id, 'acrb_type_image', $img );
                
                // Clear dashboard/cache transients if active
                delete_transient( 'acrb_dashboard_revenue' );

                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Vehicle type updated successfully.', 'awesome-car-rental' ) . '</p></div>';
            } else {
                // Create New
                $new_term = wp_insert_term( $name, 'acrb_categories' );
                if ( ! is_wp_error( $new_term ) ) {
                    $item_id = $new_term['term_id'];
                    update_term_meta( $item_id, 'acrb_type_image', $img );
                    
                    delete_transient( 'acrb_dashboard_revenue' );

                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'New vehicle type created.', 'awesome-car-rental' ) . '</p></div>';
                    
                    // Refresh data for form
                    $term = get_term( $item_id, 'acrb_categories' );
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $new_term->get_error_message() ) . '</p></div>';
                }
            }
        }
    }
    ?>

    <style>
        .acrb-form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; max-width: 750px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-form-header { margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }
        .acrb-form-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
        .acrb-form-subtitle { font-size: 13px; color: #64748b; margin: 0; }
        
        .acrb-form-group { margin-bottom: 22px; }
        .acrb-form-label { display: block; font-weight: 600; font-size: 13px; color: #1e293b; margin-bottom: 8px; }
        .acrb-form-input { width: 100%; max-width: 450px; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 13px; color: #1e293b; box-sizing: border-box; }
        .acrb-form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; outline: none; }
        .acrb-form-desc { font-size: 12px; color: #64748b; margin-top: 6px; }

        /* Media Upload Preview UI */
        .acrb-image-preview-box { display: inline-flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 8px; margin-bottom: 12px; min-width: 80px; min-height: 80px; }
        .acrb-image-preview-box img { max-width: 150px; height: auto; border-radius: 6px; border: 1px solid #e2e8f0; object-fit: cover; }
        .acrb-upload-actions { display: flex; gap: 10px; align-items: center; }
        
        /* Form Footer Actions */
        .acrb-form-footer { margin-top: 32px; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
        .acrb-link-cancel { color: #64748b; text-decoration: none; font-weight: 600; font-size: 13px; transition: color 0.2s; padding: 8px 12px; }
        .acrb-link-cancel:hover { color: #0f172a; }
        .acrb-btn-submit { background: #3b82f6; color: #fff; border: none; padding: 9px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; height: 38px; }
        .acrb-btn-submit:hover { background: #2563eb; }
        .acrb-btn-secondary { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 16px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; height: 38px; box-sizing: border-box; }
        .acrb-btn-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        .acrb-remove-btn { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .acrb-remove-btn:hover { background: #fecaca; color: #991b1b; }
    </style>

    <div class="acrb-dash-wrap">
        <form method="post">
            <?php wp_nonce_field( 'acrb_type_nonce' ); ?>
            <div class="acrb-form-card">
                <div class="acrb-form-header">
                    <h2 class="acrb-form-title"><?php echo $item_id > 0 ? esc_html__( 'Edit Vehicle Type', 'awesome-car-rental' ) : esc_html__( 'Add New Vehicle Type', 'awesome-car-rental' ); ?></h2>
                    <p class="acrb-form-subtitle"><?php esc_html_e( 'Organize and classify your vehicle types for customer filter listings.', 'awesome-car-rental' ); ?></p>
                </div>
                
                <div class="acrb-form-group">
                    <label class="acrb-form-label"><?php esc_html_e( 'Category Name', 'awesome-car-rental' ); ?></label>
                    <input type="text" name="type_name" value="<?php echo $term ? esc_attr( $term->name ) : ''; ?>" 
                           class="acrb-form-input" placeholder="<?php esc_attr_e( 'e.g. Luxury SUV, Economy Sedan', 'awesome-car-rental' ); ?>" required>
                    <p class="acrb-form-desc"><?php esc_html_e( 'This name will appear in the booking filters for customers.', 'awesome-car-rental' ); ?></p>
                </div>

                <div class="acrb-form-group">
                    <label class="acrb-form-label"><?php esc_html_e( 'Category Image', 'awesome-car-rental' ); ?></label>
                    <div id="acrb-image-preview" class="acrb-image-preview-box">
                        <?php if ( $img_id ) { echo wp_get_attachment_image( $img_id, [150, 150] ); } else { echo '<span class="dashicons dashicons-category" style="font-size:32px; width:32px; height:32px; color:#94a3b8;"></span>'; } ?>
                    </div>
                    <input type="hidden" name="type_image_id" id="acrb-type-image-id" value="<?php echo esc_attr( $img_id ); ?>">
                    <div class="acrb-upload-actions">
                        <button type="button" class="acrb-btn-secondary" id="acrb-upload-btn">
                            <span class="dashicons dashicons-upload" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span>
                            <span><?php esc_html_e( 'Select Image', 'awesome-car-rental' ); ?></span>
                        </button>
                        <button type="button" class="acrb-btn-secondary acrb-remove-btn <?php echo ! $img_id ? 'hidden' : ''; ?>" id="acrb-remove-image-btn">
                            <span class="dashicons dashicons-trash" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span>
                            <span><?php esc_html_e( 'Remove', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="acrb-form-footer">
                    <button type="submit" name="acrb_save_type" class="acrb-btn-submit">
                        <?php echo $item_id > 0 ? esc_html__( 'Update Vehicle Type', 'awesome-car-rental' ) : esc_html__( 'Save Vehicle Type', 'awesome-car-rental' ); ?>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=categories' ) ); ?>" class="acrb-link-cancel">
                        <?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($){
        var frame;
        $('#acrb-upload-btn').click(function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: 'Select Vehicle Type Image', button: { text: 'Use this image' }, multiple: false });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#acrb-image-preview').html('<img src="'+attachment.url+'" style="max-width:150px; height:auto; border-radius:6px; border:1px solid #e2e8f0; object-fit:cover;">');
                $('#acrb-type-image-id').val(attachment.id);
                $('#acrb-remove-image-btn').removeClass('hidden');
            });
            frame.open();
        });

        $('#acrb-remove-image-btn').click(function(){
            $('#acrb-image-preview').html('<span class="dashicons dashicons-category" style="font-size:32px; width:32px; height:32px; color:#94a3b8;"></span>');
            $('#acrb-type-image-id').val('');
            $(this).addClass('hidden');
        });
    });
    </script>
    <?php
}