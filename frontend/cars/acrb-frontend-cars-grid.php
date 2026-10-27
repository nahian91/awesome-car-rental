<?php
/**
 * [acrb_car_grid limit="6" orderby="rand"]
 * Modernized Elite SaaS Car Grid Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
            'taxonomy' => 'acrb_car_category',
            'field'    => 'slug',
            'terms'    => $a['cat'],
        ]];
    }

    $cars = new WP_Query($args);
    $currency = get_option('acrb_currency', '£');

    if (!$cars->have_posts()) {
        return '<div class="acrb-empty" style="text-align:center; padding:60px 20px; font-family:-apple-system, sans-serif; color:#64748b; font-size:15px;">' . esc_html__('No vehicles found matching your criteria.', 'awesome-car-rental') . '</div>';
    }

    ob_start(); ?>

    <style>
        /* Elite SaaS Grid Styles */
        .acrb-grid-wrapper { padding: 30px 0; max-width: 1250px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; box-sizing: border-box; }
        .acrb-grid-wrapper *, .acrb-grid-wrapper *::before, .acrb-grid-wrapper *::after { box-sizing: border-box; }
        
        .acrb-car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }
        
        /* Card Design */
        .acrb-card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04); transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column; border: 1px solid #e2e8f0; position: relative; }
        .acrb-card:hover { transform: translateY(-6px); box-shadow: 0 20px 30px -10px rgba(59, 130, 246, 0.12); border-color: #cbd5e1; }
        
        /* Image Area */
        .acrb-img-container { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: #f8fafc; }
        .acrb-img-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .acrb-card:hover .acrb-img-container img { transform: scale(1.06); }
        
        /* Floating Badge */
        .acrb-badge { position: absolute; top: 14px; left: 14px; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); color: #fff; padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; z-index: 2; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.1); }
        
        /* Body Content */
        .acrb-card-body { padding: 24px; flex-grow: 1; display: flex; flex-direction: column; }
        .acrb-card-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; line-height: 1.35; letter-spacing: -0.3px; }
        .acrb-card-title a { text-decoration: none; color: #0f172a; transition: color 0.2s ease; }
        .acrb-card-title a:hover { color: #3b82f6; }
        
        /* Features/Specs Grid */
        .acrb-spec-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
        .acrb-spec-item { display: flex; align-items: center; gap: 8px; color: #475569; font-size: 13px; font-weight: 500; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9; }
        .acrb-spec-item .dashicons { font-size: 16px; width: 16px; height: 16px; color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; }
        
        /* Footer/Price */
        .acrb-card-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #f1f5f9; }
        .acrb-price-block { display: flex; align-items: baseline; gap: 2px; }
        .acrb-price-val { font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .acrb-price-label { font-size: 12px; color: #64748b; font-weight: 500; }
        
        /* Action Button */
        .acrb-btn-book { background: #3b82f6; color: #fff !important; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.2s ease; text-align: center; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); display: inline-flex; align-items: center; gap: 6px; }
        .acrb-btn-book:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); text-decoration: none; color: #fff !important; }

        /* Fallback Thumbnail */
        .acrb-no-thumb { height: 100%; display: flex; align-items: center; justify-content: center; color: #cbd5e1; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
        .acrb-no-thumb .dashicons { font-size: 54px; width: 54px; height: 54px; opacity: 0.7; }

        @media(max-width: 768px) {
            .acrb-car-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-grid-wrapper">
        <div class="acrb-car-grid">
            <?php while ($cars->have_posts()) : $cars->the_post(); 
                $price = get_post_meta(get_the_ID(), 'daily_price', true) ?: get_post_meta(get_the_ID(), 'price_per_day', true);
                if ( !$price ) { $price = '0.00'; }
                
                $features = get_post_meta(get_the_ID(), 'acrb_car_features', true);
                if ( empty($features) ) {
                    // Fallback to standard meta specs if available
                    $features = get_post_meta(get_the_ID(), 'car_specs', true);
                }
                
                $terms = wp_get_post_terms(get_the_ID(), 'acrb_car_category');
                if ( empty($terms) ) {
                    $terms = wp_get_post_terms(get_the_ID(), 'acrb_categories');
                }

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
                        <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                            <div class="acrb-badge"><?php echo esc_html($terms[0]->name); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="acrb-card-body">
                        <div class="acrb-card-title">
                            <a href="<?php echo esc_url($link); ?>"><?php the_title(); ?></a>
                        </div>
                        
                        <div class="acrb-spec-grid">
                            <?php 
                            if (!empty($features) && is_array($features)):
                                $count = 0;
                                foreach($features as $key => $f): 
                                    if ($count < 4): 
                                        // Handle both associative array specs and indexed feature lists
                                        $icon = 'dashicons-yes';
                                        $val = '';
                                        if (is_array($f)) {
                                            if (empty($f['enabled'])) continue;
                                            $icon = !empty($f['icon']) ? $f['icon'] : 'dashicons-yes';
                                            $val  = !empty($f['value']) ? $f['value'] : '';
                                        } else {
                                            $icon = 'dashicons-admin-generic';
                                            $val  = is_string($key) ? ucfirst($key) . ': ' . $f : $f;
                                        }
                                        if (!empty($val)):
                                    ?>
                                        <div class="acrb-spec-item">
                                            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo esc_html($val); ?></span>
                                        </div>
                                    <?php 
                                        $count++; 
                                        endif;
                                    endif; 
                                endforeach; 
                            else: 
                            ?>
                                <div class="acrb-spec-item">
                                    <span class="dashicons dashicons-dashboard"></span>
                                    <span><?php esc_html_e('Instant Booking', 'awesome-car-rental'); ?></span>
                                </div>
                                <div class="acrb-spec-item">
                                    <span class="dashicons dashicons-shield"></span>
                                    <span><?php esc_html_e('Fully Insured', 'awesome-car-rental'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="acrb-card-footer">
                            <div class="acrb-price-block">
                                <span class="acrb-price-val"><?php echo esc_html($currency . number_format(floatval($price), 2)); ?></span>
                                <span class="acrb-price-label">/day</span>
                            </div>
                            <a href="<?php echo esc_url($link); ?>" class="acrb-btn-book">
                                <span><?php esc_html_e('Rent Now', 'awesome-car-rental'); ?></span>
                                <span class="dashicons dashicons-arrow-right-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center;"></span>
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