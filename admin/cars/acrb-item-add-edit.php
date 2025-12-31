<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Full Vehicle Editor - SaaS Style with Advanced Gallery Management
 */
function acrb_add_edit_car_tab( $edit_car_id = 0 ) {
    $car = $edit_car_id ? get_post( $edit_car_id ) : null;

    // 1. DATA PREPARATION
    $global_features  = get_option( 'acrb_custom_features', array() );
    $global_amenities = get_option( 'acrb_custom_amenities', array() );
    $global_locations = get_option( 'acrb_custom_locations', array() );

    // 2. FORM PROCESSING
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['acrb_car_name'] ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            $post_args = array(
                'post_type'    => 'acrb_cars',
                'post_title'   => sanitize_text_field( wp_unslash( $_POST['acrb_car_name'] ) ),
                'post_content' => isset( $_POST['acrb_car_desc'] ) ? wp_kses_post( wp_unslash( $_POST['acrb_car_desc'] ) ) : '',
                'post_status'  => 'publish',
            );

            if ( $car ) {
                $post_args['ID'] = $edit_car_id;
                wp_update_post( $post_args );
            } else {
                $edit_car_id = wp_insert_post( $post_args );
            }

            if ( $edit_car_id && ! is_wp_error( $edit_car_id ) ) {
                update_post_meta( $edit_car_id, '_thumbnail_id', absint( $_POST['acrb_car_thumbnail_id'] ?? 0 ) );
                update_post_meta( $edit_car_id, 'acrb_car_video', esc_url_raw( $_POST['acrb_car_video'] ?? '' ) );
                update_post_meta( $edit_car_id, 'acrb_car_gallery', sanitize_text_field( $_POST['acrb_car_gallery'] ?? '' ) );
                update_post_meta( $edit_car_id, 'price_per_day', floatval( $_POST['acrb_car_price'] ?? 0 ) );
                update_post_meta( $edit_car_id, 'acrb_default_pickup', sanitize_text_field( $_POST['acrb_default_pickup'] ?? '' ) );
                update_post_meta( $edit_car_id, 'acrb_default_dropoff', sanitize_text_field( $_POST['acrb_default_dropoff'] ?? '' ) );
                update_post_meta( $edit_car_id, 'acrb_car_features', map_deep( wp_unslash( $_POST['car_features'] ?? array() ), 'sanitize_text_field' ) );
                update_post_meta( $edit_car_id, 'acrb_car_amenities', map_deep( wp_unslash( $_POST['car_amenities'] ?? array() ), 'sanitize_text_field' ) );

                if ( isset( $_POST['acrb_car_cat'] ) ) {
                    wp_set_post_terms( $edit_car_id, array( intval( $_POST['acrb_car_cat'] ) ), 'acrb_categories' );
                }

                // translators: 1: Booking ID, 2: New status name.
                $acrb_msg = sprintf( esc_html__( 'Car  updated to %2$s.', 'awesome-car-rental' ), absint( $edit_car_id ), esc_html__( 'Updated', 'awesome-car-rental' ) );
                echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $acrb_msg ) . '</p></div>';
            }
        }
    }

    $saved_features  = get_post_meta( $edit_car_id, 'acrb_car_features', true ) ?: array();
    $saved_amenities = get_post_meta( $edit_car_id, 'acrb_car_amenities', true ) ?: array();
    $gallery_ids     = get_post_meta( $edit_car_id, 'acrb_car_gallery', true ) ?: '';
    $video_url       = get_post_meta( $edit_car_id, 'acrb_car_video', true ) ?: '';
    $thumbnail_id    = get_post_thumbnail_id( $edit_car_id );
    ?>

    <style>
        .acrb-editor-wrap { max-width: 1200px; margin-top: 20px; color: #2c3338; }
        .acrb-field-hero { width: 100%; font-size: 26px; padding: 15px; margin-bottom: 25px; border: 1px solid #ccd0d4; border-radius: 6px; }
        .acrb-flex-grid { display: flex; gap: 20px; }
        .acrb-col-main { flex: 2; }
        .acrb-col-sidebar { flex: 1; }
        .acrb-panel { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; }
        .acrb-panel-head { padding: 12px 15px; border-bottom: 1px solid #eee; background: #f9f9f9; font-weight: 600; }
        .acrb-panel-body { padding: 20px; }
        .acrb-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .acrb-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; }
        
        /* Toggle Switch */
        .acrb-switch { position: relative; display: inline-block; width: 34px; height: 18px; margin-right: 8px; }
        .acrb-switch input { opacity: 0; width: 0; height: 0; }
        .acrb-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
        .acrb-slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .acrb-slider { background-color: #2271b1; }
        input:checked + .acrb-slider:before { transform: translateX(16px); }

        /* Gallery Management UI */
        .acrb-gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 15px; }
        .acrb-gallery-item { position: relative; border-radius: 4px; overflow: hidden; border: 1px solid #ddd; padding-top: 100%; }
        .acrb-gallery-item img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .acrb-gallery-remove { 
            position: absolute; top: 2px; right: 2px; background: rgba(220, 53, 69, 0.9); 
            color: #fff; width: 18px; height: 18px; font-size: 10px; line-height: 18px; 
            text-align: center; border-radius: 50%; cursor: pointer; z-index: 5;
        }
        .acrb-gallery-remove:hover { background: #dc3545; }

        .acrb-btn-save { background: #2271b1; color: #fff; border: none; padding: 15px; width: 100%; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: 600; }
    </style>

    <div class="acrb-editor-wrap">
        <form method="post" id="acrb-car-form">
            <input type="text" name="acrb_car_name" class="acrb-field-hero" value="<?php echo esc_attr( $car ? $car->post_title : '' ); ?>" placeholder="Enter Vehicle Name..." required>

            <div class="acrb-flex-grid">
                <div class="acrb-col-main">
                    <div class="acrb-panel">
                        <div class="acrb-panel-head">Location Configuration</div>
                        <div class="acrb-panel-body acrb-grid-2">
                            <div>
                                <label class="acrb-label">Default Pick Up</label>
                                <select name="acrb_default_pickup" class="widefat">
                                    <option value="">Select Location</option>
                                    <?php foreach ( $global_locations as $loc ) : ?>
                                        <option value="<?php echo esc_attr( $loc['name'] ); ?>" <?php selected( get_post_meta( $edit_car_id, 'acrb_default_pickup', true ), $loc['name'] ); ?>><?php echo esc_html( $loc['name'] ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="acrb-label">Default Drop Off</label>
                                <select name="acrb_default_dropoff" class="widefat">
                                    <option value="">Select Location</option>
                                    <?php foreach ( $global_locations as $loc ) : ?>
                                        <option value="<?php echo esc_attr( $loc['name'] ); ?>" <?php selected( get_post_meta( $edit_car_id, 'acrb_default_dropoff', true ), $loc['name'] ); ?>><?php echo esc_html( $loc['name'] ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="acrb-panel">
                        <div class="acrb-panel-head">Technical Specifications</div>
                        <div class="acrb-panel-body">
                            <div class="acrb-grid-2">
                                <?php foreach ( $global_features as $idx => $g ) : 
                                    $val = ''; $en = 1;
                                    foreach($saved_features as $s) { if(($s['name']??'')==$g['name']){ $val=$s['value']??''; $en=$s['enabled']??0; break; }}
                                ?>
                                    <div style="display:flex; align-items:center; justify-content:space-between; background:#f9f9f9; padding:8px; border-radius:4px;">
                                        <div style="display:flex; align-items:center;">
                                            <label class="acrb-switch">
                                                <input type="checkbox" name="car_features[<?php echo esc_attr($idx); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                                <span class="acrb-slider"></span>
                                            </label>
                                            <span style="font-size:12px;"><?php echo esc_html( $g['name'] ); ?></span>
                                            <input type="hidden" name="car_features[<?php echo esc_attr($idx); ?>][name]" value="<?php echo esc_attr( $g['name'] ); ?>">
                                            <input type="hidden" name="car_features[<?php echo esc_attr($idx); ?>][icon]" value="<?php echo esc_attr( $g['icon'] ); ?>">
                                        </div>
                                        <input type="text" name="car_features[<?php echo esc_attr($idx); ?>][value]" style="width:60px;" value="<?php echo esc_attr( $val ?: ($g['value']??'') ); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="acrb-panel">
                        <div class="acrb-panel-head">Amenities & Equipment</div>
                        <div class="acrb-panel-body">
                            <div class="acrb-grid-2">
                                <?php foreach ( $global_amenities as $idx => $g ) : 
                                    $en = 1;
                                    foreach($saved_amenities as $s) { if(($s['name']??'')==$g['name']){ $en=$s['enabled']??0; break; }}
                                ?>
                                    <div style="display:flex; align-items:center; background:#f9f9f9; padding:8px; border-radius:4px;">
                                        <label class="acrb-switch">
                                            <input type="checkbox" name="car_amenities[<?php echo esc_attr($idx); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                            <span class="acrb-slider"></span>
                                        </label>
                                        <span style="font-size:12px;"><?php echo esc_html( $g['name'] ); ?></span>
                                        <input type="hidden" name="car_amenities[<?php echo esc_attr($idx); ?>][name]" value="<?php echo esc_attr( $g['name'] ); ?>">
                                        <input type="hidden" name="car_amenities[<?php echo esc_attr($idx); ?>][icon]" value="<?php echo esc_attr( $g['icon'] ); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="acrb-panel">
                        <div class="acrb-panel-head">Description</div>
                        <div class="acrb-panel-body">
                            <?php wp_editor( $car ? $car->post_content : '', 'acrb_car_desc', array( 'textarea_rows' => 8 ) ); ?>
                        </div>
                    </div>
                </div>

                <div class="acrb-col-sidebar">
                    <div class="acrb-panel">
                        <div class="acrb-panel-head">Vehicle Cover Image</div>
                        <div class="acrb-panel-body" style="text-align:center;">
                            <div id="acrb-thumb-preview" style="background:#f5f5f5; margin-bottom:10px; border-radius:4px; overflow:hidden; min-height:100px;">
                                <?php if($thumbnail_id) echo wp_get_attachment_image($thumbnail_id, 'medium', false, array('style'=>'width:100%;height:auto;display:block;')); ?>
                            </div>
                            <input type="hidden" name="acrb_car_thumbnail_id" id="acrb_car_thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>">
                            <button type="button" class="button widefat" id="acrb_set_thumb">Select Featured Image</button>
                        </div>
                    </div>

                    <div class="acrb-panel">
                        <div class="acrb-panel-head">Media Gallery</div>
                        <div class="acrb-panel-body">
                            <label class="acrb-label">Video URL (YouTube/Vimeo)</label>
                            <input type="url" name="acrb_car_video" class="widefat" value="<?php echo esc_url($video_url); ?>" placeholder="https://...">
                            
                            <hr style="margin:15px 0; border:0; border-top:1px solid #eee;">
                            
                            <input type="hidden" name="acrb_car_gallery" id="acrb_gallery_ids" value="<?php echo esc_attr($gallery_ids); ?>">
                            <button type="button" id="acrb_manage_gallery" class="button widefat">Add Images to Gallery</button>
                            
                            <div id="acrb_gallery_preview" class="acrb-gallery-grid">
                                <?php 
                                if($gallery_ids) { 
                                    $ids = explode(',', $gallery_ids); 
                                    foreach($ids as $id) {
                                        $img = wp_get_attachment_image_src($id, 'thumbnail');
                                        if($img) {
                                            echo '<div class="acrb-gallery-item" data-id="'.esc_attr($id).'">
                                                    <span class="acrb-gallery-remove">×</span>
                                                    <img src="'.esc_url($img[0]).'">
                                                  </div>';
                                        }
                                    } 
                                } 
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="acrb-panel">
                        <div class="acrb-panel-head">Pricing (£)</div>
                        <div class="acrb-panel-body">
                            <label class="acrb-label">Daily Rate (£)</label>
                            <input type="number" step="0.01" name="acrb_car_price" class="widefat" value="<?php echo esc_attr( get_post_meta( $edit_car_id, 'price_per_day', true ) ); ?>">
                            
                            <label class="acrb-label" style="margin-top:15px;">Category</label>
                            <?php 
$cats = get_terms( array(
    'taxonomy'   => 'acrb_categories',
    'hide_empty' => false,
) ); 
?>
                            <select name="acrb_car_cat" class="widefat">
                                <option value="">Select Category</option>
                                <?php foreach($cats as $c) : ?>
                                   <option value="<?php echo (int) $c->term_id; ?>" <?php 
    if ( $car ) {
        // Get the first term ID safely
        $terms = wp_get_post_terms( $edit_car_id, 'acrb_categories', array( 'fields' => 'ids' ) );
        $current_term = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : 0;
        selected( $current_term, $c->term_id ); 
    }
?>>
    <?php echo esc_html( $c->name ); ?>
</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="acrb-btn-save">Save Vehicle Changes</button>
                </div>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($){
        // 1. Thumbnail Selection
        $('#acrb_set_thumb').click(function(e){
            e.preventDefault();
            var frame = wp.media({ title: 'Select Cover Image', button: { text: 'Use Image' }, multiple: false });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#acrb_car_thumbnail_id').val(attachment.id);
                $('#acrb-thumb-preview').html('<img src="'+attachment.url+'" style="width:100%;height:auto;display:block;">');
            }).open();
        });

        // 2. Gallery Management
        var galleryFrame;
        $('#acrb_manage_gallery').click(function(e){
            e.preventDefault();
            if(galleryFrame) { galleryFrame.open(); return; }

            galleryFrame = wp.media({ title: 'Add to Gallery', button: { text: 'Add to Gallery' }, multiple: true });
            galleryFrame.on('select', function(){
                var selection = galleryFrame.state().get('selection');
                var currentIds = $('#acrb_gallery_ids').val() ? $('#acrb_gallery_ids').val().split(',') : [];
                
                selection.map(function(attachment){
                    attachment = attachment.toJSON();
                    if($.inArray(attachment.id.toString(), currentIds) === -1){
                        currentIds.push(attachment.id);
                        $('#acrb_gallery_preview').append(
                            '<div class="acrb-gallery-item" data-id="'+attachment.id+'">' +
                            '<span class="acrb-gallery-remove">×</span>' +
                            '<img src="'+attachment.sizes.thumbnail.url+'">' +
                            '</div>'
                        );
                    }
                });
                $('#acrb_gallery_ids').val(currentIds.join(','));
            });
            galleryFrame.open();
        });

        // 3. Remove Single Image from Gallery
        $(document).on('click', '.acrb-gallery-remove', function(){
            var item = $(this).closest('.acrb-gallery-item');
            var idToRemove = item.data('id').toString();
            var currentIds = $('#acrb_gallery_ids').val().split(',');

            var updatedIds = currentIds.filter(function(id){
                return id !== idToRemove;
            });

            $('#acrb_gallery_ids').val(updatedIds.join(','));
            item.fadeOut(300, function(){ $(this).remove(); });
        });
    });
    </script>
    <?php
}