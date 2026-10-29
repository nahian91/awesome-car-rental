<?php
/**
 * [acrb_all_cars]
 * Ultra-Modern Elite SaaS Fleet Archive with Live Filtering
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode('acrb_all_cars', function() {
    $categories = get_terms([
        'taxonomy'   => 'acrb_car_category', 
        'hide_empty' => false
    ]);
    
    $cars = get_posts([
        'post_type'      => 'acrb_cars',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC'
    ]);

    $single_car_page_id = get_option('acrb_single_car_page'); 
    $base_details_url   = $single_car_page_id ? get_permalink($single_car_page_id) : '';
    $currency           = get_option('acrb_currency', '£');

    if (empty($cars)) {
        return '<div class="acrb-empty" style="text-align:center; padding:60px 20px; font-family:-apple-system, sans-serif; color:#64748b; font-size:15px;">' . esc_html__('Fleet currently unavailable.', 'awesome-car-rental') . '</div>';
    }

    ob_start(); ?>
    
    <div class="acrb-main-wrapper">
        <aside class="acrb-sidebar">
            <div class="acrb-filter-card">
                <div class="acrb-filter-group">
                    <h4><?php esc_html_e('Search Vehicle', 'awesome-car-rental'); ?></h4>
                    <div class="acrb-search-wrap">
                        <span class="dashicons dashicons-search"></span>
                        <input type="text" id="acrb-car-search" placeholder="<?php esc_attr_e('Search model...', 'awesome-car-rental'); ?>">
                    </div>
                </div>

                <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                <div class="acrb-filter-group">
                    <h4><?php esc_html_e('Categories', 'awesome-car-rental'); ?></h4>
                    <ul class="acrb-filter-list">
                        <?php foreach ($categories as $cat) : ?>
                            <li class="acrb-filter-item">
                                <label>
                                    <input type="checkbox" class="acrb-cat-check" value="<?php echo esc_attr($cat->slug); ?>"> 
                                    <span><?php echo esc_html($cat->name); ?></span>
                                </label>
                                <span class="acrb-count-badge"><?php echo (int)$cat->count; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="acrb-filter-group">
                    <h4><?php esc_html_e('Transmission', 'awesome-car-rental'); ?></h4>
                    <ul class="acrb-filter-list">
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-check" value="automatic"> <span><?php esc_html_e('Automatic', 'awesome-car-rental'); ?></span></label>
                        </li>
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-check" value="manual"> <span><?php esc_html_e('Manual', 'awesome-car-rental'); ?></span></label>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <div class="acrb-content">
            <div class="acrb-car-grid" id="acrb-grid-container">
                <?php foreach ($cars as $car) : 
                    $price    = get_post_meta($car->ID, 'daily_price', true) ?: get_post_meta($car->ID, 'price_per_day', true);
                    if (!$price) { $price = '0.00'; }

                    $features = get_post_meta($car->ID, 'acrb_car_features', true);
                    if (empty($features)) {
                        $features = get_post_meta($car->ID, 'car_specs', true);
                    }

                    $terms = wp_get_post_terms($car->ID, 'acrb_car_category');
                    $slugs = (!empty($terms) && !is_wp_error($terms)) ? implode(' ', wp_list_pluck($terms, 'slug')) : '';
                    $details_link = $base_details_url ? add_query_arg('car_id', $car->ID, $base_details_url) : get_permalink($car->ID);
                    
                    $spec_string = '';
                    if (!empty($features) && is_array($features)) {
                        foreach($features as $f) { 
                            $val_str = is_array($f) ? ($f['value'] ?? '') : $f;
                            $spec_string .= strtolower($val_str) . ' '; 
                        }
                    }
                ?>
                <div class="acrb-card <?php echo esc_attr($slugs); ?>" 
                     data-name="<?php echo esc_attr(strtolower($car->post_title)); ?>"
                     data-specs="<?php echo esc_attr($spec_string); ?>">
                    
                    <div class="acrb-img-container">
                        <a href="<?php echo esc_url($details_link); ?>">
                            <?php if (has_post_thumbnail($car->ID)) : 
                                echo get_the_post_thumbnail($car->ID, 'medium_large'); 
                            else : ?>
                                <div class="acrb-no-thumb"><span class="dashicons dashicons-car"></span></div>
                            <?php endif; ?>
                        </a>
                        <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
                            <div class="acrb-badge"><?php echo esc_html($terms[0]->name); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="acrb-card-body">
                        <a href="<?php echo esc_url($details_link); ?>" class="acrb-card-title"><?php echo esc_html($car->post_title); ?></a>
                        
                        <div class="acrb-spec-grid">
                            <?php if(!empty($features) && is_array($features)): 
                                $count = 0;
                                foreach($features as $f): 
                                    $icon = 'dashicons-yes';
                                    $val = '';
                                    if (is_array($f)) {
                                        if (empty($f['enabled'])) continue;
                                        $icon = !empty($f['icon']) ? $f['icon'] : 'dashicons-yes';
                                        $val  = !empty($f['value']) ? $f['value'] : '';
                                    } else {
                                        $icon = 'dashicons-admin-generic';
                                        $val  = $f;
                                    }

                                    if(!empty($val) && $count < 4): 
                            ?>
                                <div class="acrb-spec-pill">
                                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                                    <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo esc_html($val); ?></span>
                                </div>
                            <?php $count++; endif; endforeach; endif; ?>
                        </div>

                        <div class="acrb-card-footer">
                            <div class="acrb-price-wrap">
                                <span class="acrb-price-val"><?php echo esc_html($currency . number_format(floatval($price), 2)); ?></span>
                                <span class="acrb-price-label">/day</span>
                            </div>
                            <a href="<?php echo esc_url($details_link); ?>" class="acrb-btn-book">
                                <span><?php esc_html_e('Rent Now', 'awesome-car-rental'); ?></span>
                                <span class="dashicons dashicons-arrow-right-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center;"></span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div id="acrb-no-results" style="display:none; grid-column: 1 / -1; text-align:center; padding:60px 20px; color:#64748b;">
                    <span class="dashicons dashicons-search" style="font-size:48px; height:48px; width:48px; color:#cbd5e1; margin-bottom:12px;"></span>
                    <h3 style="margin:0 0 6px 0; font-size:18px; color:#0f172a;"><?php esc_html_e('No cars found', 'awesome-car-rental'); ?></h3>
                    <p style="margin:0; font-size:14px;"><?php esc_html_e('Try adjusting your search or filters.', 'awesome-car-rental'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root { --acrb-blue: #3b82f6; --acrb-text: #0f172a; --acrb-bg: #f8fafc; }
        .acrb-main-wrapper { display: flex; gap: 32px; align-items: flex-start; max-width: 1280px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; box-sizing: border-box; }
        .acrb-main-wrapper *, .acrb-main-wrapper *::before, .acrb-main-wrapper *::after { box-sizing: border-box; }
        
        /* Sidebar Styles */
        .acrb-sidebar { width: 300px; flex-shrink: 0; position: sticky; top: 32px; }
        .acrb-filter-card { background: #fff; padding: 28px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0; }
        .acrb-filter-group { margin-bottom: 24px; }
        .acrb-filter-group:last-child { margin-bottom: 0; }
        .acrb-filter-group h4 { font-size: 12px; margin: 0 0 14px 0; color: #475569; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
        
        .acrb-search-wrap { position: relative; }
        .acrb-search-wrap .dashicons { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
        #acrb-car-search { width: 100%; height: 44px; padding: 0 14px 0 42px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; color: #1e293b; outline: none; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.25s ease; }
        #acrb-car-search:hover { border-color: #94a3b8; }
        #acrb-car-search:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12); }

        .acrb-filter-list { list-style: none; padding: 0; margin: 0; }
        .acrb-filter-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .acrb-filter-item:last-child { margin-bottom: 0; }
        .acrb-filter-item label { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; color: #334155; font-weight: 500; }
        .acrb-filter-item input[type="checkbox"] { width: 16px; height: 16px; accent-color: #3b82f6; cursor: pointer; border-radius: 4px; }
        .acrb-count-badge { background: #f1f5f9; padding: 2px 10px; border-radius: 20px; font-size: 12px; color: #64748b; font-weight: 600; }

        /* Grid Content */
        .acrb-content { flex: 1; min-width: 0; }
        .acrb-car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 28px; }
        
        /* Card Styling */
        .acrb-card { background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column; position: relative; }
        .acrb-card:hover { transform: translateY(-8px); box-shadow: 0 25px 35px -10px rgba(59, 130, 246, 0.12); border-color: #cbd5e1; }
        
        .acrb-img-container { height: 220px; overflow: hidden; position: relative; background: #f8fafc; }
        .acrb-img-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .acrb-card:hover .acrb-img-container img { transform: scale(1.08); }
        .acrb-img-container::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, transparent 40%); pointer-events: none; }

        .acrb-badge { position: absolute; top: 16px; left: 16px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); color: #fff; padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; z-index: 2; border: 1px solid rgba(255,255,255,0.15); }

        .acrb-card-body { padding: 24px; flex-grow: 1; display: flex; flex-direction: column; }
        .acrb-card-title { font-size: 18px; font-weight: 800; color: #0f172a; text-decoration: none; display: block; margin-bottom: 16px; line-height: 1.35; letter-spacing: -0.3px; transition: color 0.2s ease; }
        .acrb-card-title:hover { color: #3b82f6; }

        .acrb-spec-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
        .acrb-spec-pill { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; font-weight: 600; background: #f8fafc; padding: 9px 12px; border-radius: 10px; border: 1px solid #f1f5f9; transition: background 0.2s ease; }
        .acrb-spec-pill:hover { background: #f1f5f9; }
        .acrb-spec-pill .dashicons { font-size: 16px; width: 16px; height: 16px; color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; }

        .acrb-card-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 18px; border-top: 1px solid #f1f5f9; }
        .acrb-price-val { font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -0.6px; }
        .acrb-price-label { font-size: 12px; color: #64748b; font-weight: 600; }
        
        .acrb-btn-book { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; text-decoration: none; padding: 11px 22px; border-radius: 10px; font-size: 13px; font-weight: 700; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); display: inline-flex; align-items: center; gap: 8px; }
        .acrb-btn-book:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); color: #fff; text-decoration: none; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
        .acrb-btn-book:hover .dashicons { transform: translateX(3px); }
        .acrb-btn-book .dashicons { transition: transform 0.2s ease; }

        @media (max-width: 900px) {
            .acrb-main-wrapper { flex-direction: column; }
            .acrb-sidebar { width: 100%; position: static; }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('acrb-car-search');
        const catChecks = document.querySelectorAll('.acrb-cat-check');
        const specChecks = document.querySelectorAll('.acrb-spec-check');
        const cards = document.querySelectorAll('.acrb-card');
        const noResults = document.getElementById('acrb-no-results');

        if (!searchInput) return;

        function filterCars() {
            const query = searchInput.value.toLowerCase().trim();
            const activeCats = Array.from(catChecks).filter(i => i.checked).map(i => i.value);
            const activeSpecs = Array.from(specChecks).filter(i => i.checked).map(i => i.value);
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.dataset.name;
                const specs = card.dataset.specs;
                const cardClasses = card.className.split(' ');

                const matchesSearch = name.includes(query);
                const matchesCat = activeCats.length === 0 || activeCats.some(c => cardClasses.includes(c));
                const matchesSpec = activeSpecs.length === 0 || activeSpecs.every(s => specs.includes(s));

                if (matchesSearch && matchesCat && matchesSpec) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        searchInput.addEventListener('input', filterCars);
        catChecks.forEach(c => c.addEventListener('change', filterCars));
        specChecks.forEach(s => s.addEventListener('change', filterCars));
    });
    </script>
    <?php
    return ob_get_clean();
});