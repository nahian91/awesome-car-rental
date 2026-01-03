<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [acrb_car_grid limit="6" orderby="rand"]
 * Modernized Car Grid Shortcode
 */
add_shortcode('acrb_car_grid', function($atts) {
    // 1. Attributes
    $a = shortcode_atts([
        'limit'   => 6,
        'orderby' => 'date',
        'order'   => 'DESC',
        'cat'     => '',
    ], $atts);

    // 2. Query Arguments
    $args = [
        'post_type'      => 'acrb_cars',
        'posts_per_page' => (int)$a['limit'],
        'orderby'        => sanitize_text_field($a['orderby']),
        'order'          => sanitize_text_field($a['order']),
        'post_status'    => 'publish',
    ];

    if (!empty($a['cat'])) {
        $args['tax_query'] = [[
            'taxonomy' => 'acrb_categories',
            'field'    => 'slug',
            'terms'    => $a['cat'],
        ]];
    }

    $cars = new WP_Query($args);
    $currency = get_option('acrb_currency_symbol', '£');

    if (!$cars->have_posts()) {
        return '<div class="acrb-empty">No vehicles found.</div>';
    }

    ob_start(); ?>

    <style>
        /* Improved Grid Styles */
        .acrb-grid-wrapper { padding: 20px 0; max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .acrb-car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        
        /* Card Design */
        .acrb-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s ease; display: flex; flex-direction: column; border: 1px solid #eee; position: relative; }
        .acrb-card:hover { transform: translateY(-8px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); border-color: #ddd; }
        
        /* Image Area */
        .acrb-img-container { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: #f9f9f9; }
        .acrb-img-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .acrb-card:hover .acrb-img-container img { transform: scale(1.08); }
        .acrb-badge { position: absolute; top: 12px; left: 12px; background: #0073aa; color: #fff; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        /* Body Content */
        .acrb-card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .acrb-card-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 12px; line-height: 1.3; }
        .acrb-card-title a { text-decoration: none; color: #1a1a1a; transition: color 0.2s; }
        .acrb-card-title a:hover { color: #0073aa; }
        
        /* Features/Specs */
        .acrb-spec-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; padding-top: 15px; border-top: 1px solid #f0f0f0; }
        .acrb-spec-item { display: flex; align-items: center; gap: 6px; color: #555; font-size: 13.5px; }
        .acrb-spec-item .dashicons { font-size: 17px; width: 17px; height: 17px; color: #999; }
        
        /* Footer/Price */
        .acrb-card-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #f0f0f0; }
        .acrb-price-val { font-size: 1.3rem; font-weight: 800; color: #000; letter-spacing: -0.5px; }
        .acrb-price-label { font-size: 0.85rem; color: #777; font-weight: 400; }
        
        .acrb-btn-book { background: #1a1a1a; color: #fff !important; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: background 0.2s; text-align: center; }
        .acrb-btn-book:hover { background: #0073aa; text-decoration: none; }

        .acrb-no-thumb { height: 100%; display: flex; align-items: center; justify-content: center; color: #ddd; }
        .acrb-no-thumb .dashicons { font-size: 48px; width: 48px; height: 48px; }
    </style>

    <div class="acrb-grid-wrapper">
        <div class="acrb-car-grid">
            <?php while ($cars->have_posts()) : $cars->the_post(); 
                $price = get_post_meta(get_the_ID(), 'price_per_day', true) ?: '0';
                $features = get_post_meta(get_the_ID(), 'acrb_car_features', true);
                $terms = wp_get_post_terms(get_the_ID(), 'acrb_categories');
                $single_car_page_id = get_option('acrb_single_car_page'); 
                $link = $single_car_page_id ? add_query_arg('car_id', get_the_ID(), get_permalink($single_car_page_id)) : get_permalink();
            ?>
                
                <article class="acrb-card">
                    <div class="acrb-img-container">
                        <a href="<?php echo esc_url($link); ?>">
                            <?php if (has_post_thumbnail()) : the_post_thumbnail('medium_large'); 
                                  else : echo '<div class="acrb-no-thumb"><span class="dashicons dashicons-car"></span></div>'; 
                                  endif; ?>
                        </a>
                        <?php if (!empty($terms)) : ?>
                            <div class="acrb-badge"><?php echo esc_html($terms[0]->name); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="acrb-card-body">
                        <div class="acrb-card-title">
                            <a href="<?php echo esc_url($link); ?>"><?php the_title(); ?></a>
                        </div>
                        
                        <div class="acrb-spec-grid">
                            <?php 
                            if(!empty($features) && is_array($features)):
                                $count = 0;
                                foreach($features as $f): 
                                    if(!empty($f['enabled']) && $count < 4): ?>
                                        <div class="acrb-spec-item">
                                            <span class="dashicons <?php echo esc_attr($f['icon']); ?>"></span>
                                            <span><?php echo esc_html($f['value']); ?></span>
                                        </div>
                                    <?php $count++; endif; 
                                endforeach; 
                            endif; ?>
                        </div>

                        <div class="acrb-card-footer">
                            <div class="acrb-price-block">
                                <span class="acrb-price-val">$<?php echo esc_html($price); ?></span>
                                <span class="acrb-price-label">/day</span>
                            </div>
                            <a href="<?php echo esc_url($link); ?>" class="acrb-btn-book">
                                <?php esc_html_e('Rent Now', 'awesome-car-rental'); ?>
                            </a>
                        </div>
                    </div>
                </article>

            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
});