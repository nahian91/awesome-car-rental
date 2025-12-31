<?php
if(!defined('ABSPATH')) exit;

/**
 * View Vehicle - Comprehensive Single View
 */
function acrb_view_car_tab($car_id){

    $car = get_post($car_id);
    if(!$car){
        echo '<div class="notice notice-error"><p>' . esc_html__('Vehicle not found.', 'awesome-car-rental') . '</p></div>';
        return;
    }

    // 1. DYNAMIC SETTINGS & DATA FETCHING
    $currency     = get_option('acrb_currency', '£');
    $currency_pos = get_option('acrb_currency_pos', 'left');

    $price            = get_post_meta($car_id, 'price_per_day', true);
    $vin              = get_post_meta($car_id, 'vin_number', true);
    $cats             = wp_get_post_terms($car_id, 'acrb_categories');
    $features         = get_post_meta($car_id, 'acrb_car_features', true);
    $amenities        = get_post_meta($car_id, 'acrb_car_amenities', true);
    $gallery_ids      = get_post_meta($car_id, 'acrb_car_gallery', true);
    $video_url        = get_post_meta($car_id, 'acrb_car_video', true);
    $pickup_loc       = get_post_meta($car_id, 'acrb_default_pickup', true);
    $dropoff_loc      = get_post_meta($car_id, 'acrb_default_dropoff', true);

    // Dynamic Price Formatting Logic
    $formatted_price = number_format(floatval($price), 2);
    $display_price   = ( 'left' === $currency_pos ) ? $currency . $formatted_price : $formatted_price . $currency;
    ?>

    <style>
        :root { --acrb-primary: #4f46e5; --acrb-dark: #1d2327; --acrb-border: #ccd0d4; }
        .acrb-view-wrapper { margin-top: 20px; max-width: 1100px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        
        /* Layout */
        .acrb-view-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .acrb-view-grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }
        
        /* Cards */
        .acrb-card { background: #fff; border: 1px solid var(--acrb-border); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .acrb-card-head { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; background: #fafafa; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; color: #646970; }
        .acrb-card-body { padding: 20px; }

        /* Media */
        .acrb-main-img { width: 100%; border-radius: 8px; border: 1px solid #eee; }
        .acrb-gallery-mini { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 15px; }
        .acrb-gallery-mini img { width: 100%; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
        .acrb-video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; background: #000; }
        .acrb-video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }

        /* Specs & Tags */
        .acrb-detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f9f9f9; }
        .acrb-detail-label { color: #8c8f94; font-size: 13px; }
        .acrb-detail-value { font-weight: 600; color: var(--acrb-dark); }
        .acrb-tag { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; background: #f0f0f1; font-size: 12px; margin: 0 5px 5px 0; border: 1px solid #dcdcde; }
        .acrb-tag-amenity { background: #e7f9ed; color: #15803d; border-color: #bbf7d0; }

        .acrb-price-large { font-size: 28px; font-weight: 800; color: var(--acrb-primary); text-align: center; display: block; }
        .btn-edit { background: var(--acrb-primary); color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
    </style>

    <div class="wrap acrb-view-wrapper">
        <div class="acrb-view-header">
            <div>
                <h1 style="margin:0;"><?php echo esc_html($car->post_title); ?></h1>
                <p style="margin:5px 0 0; color:#646970;">ID: #<?php echo intval($car_id); ?> | VIN: <?php echo $vin ? esc_html($vin) : '---'; ?></p>
            </div>
            <a href="?page=awesome_car_rental&tab=cars&sub=edit&car_id=<?php echo esc_attr($car_id); ?>" class="btn-edit">
                <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Edit Vehicle', 'awesome-car-rental'); ?>
            </a>
        </div>

        <div class="acrb-view-grid">
            <div class="acrb-sidebar-col">
                <div class="acrb-card">
                    <div class="acrb-card-body" style="text-align:center;">
                        <?php if(has_post_thumbnail($car_id)): ?>
                            <?php echo get_the_post_thumbnail($car_id, 'medium', ['class' => 'acrb-main-img']); ?>
                        <?php endif; ?>
                        
                        <?php if($gallery_ids): ?>
                            <div class="acrb-gallery-mini">
                                <?php 
                                $ids = explode(',', $gallery_ids);
                                foreach(array_slice($ids, 0, 8) as $id) echo wp_get_attachment_image($id, 'thumbnail'); 
                                ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                            <span class="acrb-price-large"><?php echo esc_html($display_price); ?></span>
                            <small style="color:#646970; font-weight:600;"><?php esc_html_e('Per Day', 'awesome-car-rental'); ?></small>
                        </div>
                    </div>
                </div>

                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e('Location Info', 'awesome-car-rental'); ?></div>
                    <div class="acrb-card-body">
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label">Pick-up</span>
                            <span class="acrb-detail-value"><?php echo esc_html($pickup_loc ?: 'Global'); ?></span>
                        </div>
                        <div class="acrb-detail-row" style="border:0;">
                            <span class="acrb-detail-label">Drop-off</span>
                            <span class="acrb-detail-value"><?php echo esc_html($dropoff_loc ?: 'Global'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="acrb-main-col">
                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e('Description', 'awesome-car-rental'); ?></div>
                    <div class="acrb-card-body">
                        <div style="line-height:1.6; color:#2c3338;">
                            <?php 
if ( $car->post_content ) {
    echo wp_kses_post( wpautop( $car->post_content ) );
} else {
    // We use printf or a variable here to keep the HTML and text clean
    echo '<i>' . esc_html__( 'No description provided.', 'awesome-car-rental' ) . '</i>';
}
?>
                        </div>
                    </div>
                </div>

                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e('Specifications & Features', 'awesome-car-rental'); ?></div>
                    <div class="acrb-card-body">
                        <div style="margin-bottom:20px;">
                            <?php if($cats): foreach($cats as $c): ?>
                                <span class="acrb-tag" style="background:#e0e7ff; color:#4338ca; border-color:#c7d2fe;">
                                    <span class="dashicons dashicons-category"></span> <?php echo esc_html($c->name); ?>
                                </span>
                            <?php endforeach; endif; ?>
                            
                            <?php 
                            if(!empty($features)): foreach($features as $f): 
                                if(!empty($f['enabled']) && !empty($f['value'])):
                            ?>
                                <span class="acrb-tag">
                                    <span class="dashicons <?php echo esc_attr($f['icon']); ?>"></span> 
                                    <?php echo esc_html($f['value']); ?>
                                </span>
                            <?php endif; endforeach; endif; ?>
                        </div>

                        <h4 style="font-size:11px; text-transform:uppercase; color:#8c8f94; margin-bottom:10px;"><?php esc_html_e('Included Amenities', 'awesome-car-rental'); ?></h4>
                        <div>
                            <?php 
                            if(!empty($amenities)): foreach($amenities as $a): 
                                if(!empty($a['enabled'])):
                            ?>
                                <span class="acrb-tag acrb-tag-amenity">
                                    <span class="dashicons <?php echo esc_attr($a['icon']); ?>"></span> 
                                    <?php echo esc_html($a['name']); ?>
                                </span>
                            <?php endif; endforeach; else: echo '---'; endif; ?>
                        </div>
                    </div>
                </div>

                <?php if($video_url): ?>
                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e('Vehicle Video', 'awesome-car-rental'); ?></div>
                    <div class="acrb-card-body">
                        <div class="acrb-video-container">
                            <?php 
                                $embed_url = str_replace('watch?v=', 'embed/', $video_url);
                                echo '<iframe src="'.esc_url($embed_url).'" frameborder="0" allowfullscreen></iframe>'; 
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <p><a href="?page=awesome_car_rental&tab=cars&sub=all" style="text-decoration:none; color:#646970;">
            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e('Return to Fleet', 'awesome-car-rental'); ?>
        </a></p>
    </div>
    <?php
}