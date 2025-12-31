<?php

if ( ! defined( 'ABSPATH' ) ) exit; 
/**
 * Add/Edit Vehicle Type - Car Rental SaaS UI
 */
function acrb_vehicle_type_add_edit() {
    $item_id = isset($_GET['item']) ? intval($_GET['item']) : 0;
    $term    = $item_id ? get_term($item_id, 'vehicle_type') : null;
    $img_id  = $item_id ? get_term_meta($item_id, 'acrb_type_image', true) : '';

    // Handle Form Submission
    if (isset($_POST['acrb_save_type']) && check_admin_referer('acrb_type_nonce')) {
        $name = isset( $_POST['type_name'] ) ? sanitize_text_field( wp_unslash( $_POST['type_name'] ) ) : '';
$img  = isset( $_POST['type_image_id'] ) ? absint( wp_unslash( $_POST['type_image_id'] ) ) : 0;

        if ($item_id) {
            // Update Existing
            wp_update_term($item_id, 'vehicle_type', ['name' => $name]);
            update_term_meta($item_id, 'acrb_type_image', $img);
            echo '<div class="updated"><p>Vehicle type updated successfully.</p></div>';
        } else {
            // Create New
            $new_term = wp_insert_term($name, 'vehicle_type');
            if (!is_wp_error($new_term)) {
                update_term_meta($new_term['term_id'], 'acrb_type_image', $img);
                echo '<div class="updated"><p>New vehicle type created.</p></div>';
                // Refresh data for form
                $item_id = $new_term['term_id'];
                $term = get_term($item_id, 'vehicle_type');
            }
        }
    }
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo $item_id ? 'Edit Type' : 'Add New Vehicle Type'; ?></h1>
        
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('acrb_type_nonce'); ?>
            <div class="acrb-card" style="background:#fff; border:1px solid #ccd0d4; padding:25px; border-radius:8px; max-width: 800px;">
                
                <div class="acrb-form-group" style="margin-bottom: 20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">Category Name</label>
                    <input type="text" name="type_name" value="<?php echo $term ? esc_attr($term->name) : ''; ?>" 
                           class="regular-text" placeholder="e.g. Luxury SUV, Economy Sedan" required>
                    <p class="description">This name will appear in the booking filters for customers.</p>
                </div>

                <div class="acrb-form-group" style="margin-bottom: 20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:8px;">Category Image</label>
                    <div id="acrb-image-preview" style="margin-bottom:10px;">
                        <?php if($img_id) echo wp_get_attachment_image($img_id, [150, 150]); ?>
                    </div>
                    <input type="hidden" name="type_image_id" id="acrb-type-image-id" value="<?php echo esc_attr($img_id); ?>">
                    <button type="button" class="button" id="acrb-upload-btn">Select Image</button>
                    <button type="button" class="button acrb-remove-btn <?php echo !$img_id ? 'hidden' : ''; ?>" style="color:#d63638;">Remove</button>
                </div>

                <hr>

                <div class="acrb-form-actions">
                    <input type="submit" name="acrb_save_type" class="button button-primary" value="Save Vehicle Type">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=awesome_car_rental&tab=fleet&sub=types')); ?>" class="button">Cancel</a>
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
                $('#acrb-image-preview').html('<img src="'+attachment.url+'" style="max-width:150px; height:auto; border-radius:4px; border:1px solid #ddd;">');
                $('#acrb-type-image-id').val(attachment.id);
                $('.acrb-remove-btn').removeClass('hidden');
            });
            frame.open();
        });

        $('.acrb-remove-btn').click(function(){
            $('#acrb-image-preview').empty();
            $('#acrb-type-image-id').val('');
            $(this).addClass('hidden');
        });
    });
    </script>
    <?php
}