<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [acrb_all_cars]
 * Optimized for £ currency, modern layout, and high-end styling.
 */
add_shortcode('acrb_all_cars', function() {
    $categories = get_terms(['taxonomy' => 'acrb_categories', 'hide_empty' => false]);
    $cars = get_posts([
        'post_type'      => 'acrb_cars',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC'
    ]);

    $single_car_page_id = get_option('acrb_single_car_page'); 
    $base_details_url = $single_car_page_id ? get_permalink($single_car_page_id) : '';
    $currency = '£'; // Your required currency

    if (empty($cars)) {
        return '<div class="acrb-empty">' . esc_html__('Fleet currently unavailable.', 'awesome-car-rental') . '</div>';
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
                    $price = get_post_meta($car->ID, 'price_per_day', true) ?: '0';
                    $features = get_post_meta($car->ID, 'acrb_car_features', true);
                    $terms = wp_get_post_terms($car->ID, 'acrb_categories');
                    $slugs = implode(' ', wp_list_pluck($terms, 'slug'));
                    $details_link = $base_details_url ? add_query_arg('car_id', $car->ID, $base_details_url) : get_permalink($car->ID);
                    
                    $spec_string = '';
                    if(!empty($features)) {
                        foreach($features as $f) { $spec_string .= strtolower($f['value'] ?? '') . ' '; }
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
                        <div class="acrb-badge"><?php echo esc_html($terms[0]->name ?? 'Rental'); ?></div>
                    </div>
                    
                    <div class="acrb-card-body">
                        <a href="<?php echo esc_url($details_link); ?>" class="acrb-card-title"><?php echo esc_html($car->post_title); ?></a>
                        
                        <div class="acrb-spec-grid">
                            <?php if(!empty($features) && is_array($features)): 
                                $count = 0;
                                foreach($features as $f): 
                                    if(!empty($f['enabled']) && !empty($f['value']) && $count < 4): 
                            ?>
                                    <div class="acrb-spec-pill">
                                        <span class="dashicons <?php echo esc_attr($f['icon']); ?>"></span>
                                        <span><?php echo esc_html($f['value']); ?></span>
                                    </div>
                            <?php $count++; endif; endforeach; endif; ?>
                        </div>

                        <div class="acrb-card-footer">
                            <div class="acrb-price-wrap">
                                <span class="acrb-price-val">$<?php echo esc_html( $price); ?></span>
                                <span class="acrb-price-label">/day</span>
                            </div>
                            <a href="<?php echo esc_url($details_link); ?>" class="acrb-btn-book"><?php esc_html_e('Rent Now', 'awesome-car-rental'); ?></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div id="acrb-no-results" style="display:none; width:100%; text-align:center; padding:50px;">
                    <span class="dashicons dashicons-search" style="font-size:50px; height:50px; width:50px; color:#ccc;"></span>
                    <h3><?php esc_html_e('No cars found', 'awesome-car-rental'); ?></h3>
                    <p><?php esc_html_e('Try adjusting your search or filters.', 'awesome-car-rental'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root { --acrb-blue: #2563eb; --acrb-text: #1e293b; --acrb-bg: #f8fafc; }
        .acrb-main-wrapper { display: flex; gap: 30px; align-items: flex-start; max-width: 1200px; margin: 0 auto; font-family: sans-serif; }
        
        /* Sidebar Styles */
        .acrb-sidebar { width: 300px; position: sticky; top: 20px; }
        .acrb-filter-card { background: #fff; padding: 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .acrb-filter-group { margin-bottom: 25px; }
        .acrb-filter-group h4 { font-size: 15px; margin-bottom: 15px; color: var(--acrb-text); text-transform: uppercase; letter-spacing: 1px; }
        
        .acrb-search-wrap { position: relative; }
        .acrb-search-wrap .dashicons { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        #acrb-car-search { width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s; }
        #acrb-car-search:focus { border-color: var(--acrb-blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

        .acrb-filter-list { list-style: none; padding: 0; margin: 0; }
        .acrb-filter-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .acrb-filter-item label { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; color: #475569; }
        .acrb-count-badge { background: #f1f5f9; padding: 2px 8px; border-radius: 20px; font-size: 11px; color: #64748b; font-weight: bold; }

        /* Grid Content */
        .acrb-content { flex: 1; }
        .acrb-car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        
        /* Card Styling */
        .acrb-card { background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; transition: transform 0.3s, box-shadow 0.3s; position: relative; }
        .acrb-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }
        
        .acrb-img-container { height: 200px; overflow: hidden; position: relative; background: #f1f5f9; }
        .acrb-img-container img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .acrb-card:hover .acrb-img-container img { transform: scale(1.05); }
        .acrb-badge { position: absolute; top: 12px; left: 12px; background: rgba(255,255,255,0.9); padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; color: var(--acrb-blue); backdrop-filter: blur(4px); }

        .acrb-card-body { padding: 20px; }
        .acrb-card-title { font-size: 18px; font-weight: 700; color: var(--acrb-text); text-decoration: none; display: block; margin-bottom: 15px; }
        .acrb-card-title:hover { color: var(--acrb-blue); }

        .acrb-spec-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .acrb-spec-pill { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; }
        .acrb-spec-pill .dashicons { font-size: 16px; width: 16px; height: 16px; color: #94a3b8; }

        .acrb-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #f1f5f9; }
        .acrb-price-val { font-size: 20px; font-weight: 800; color: var(--acrb-text); }
        .acrb-price-label { font-size: 12px; color: #94a3b8; }
        
        .acrb-btn-book { background: var(--acrb-blue); color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; transition: 0.3s; }
        .acrb-btn-book:hover { background: #1d4ed8; }

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

        function filterCars() {
            const query = searchInput.value.toLowerCase();
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
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        searchInput.addEventListener('input', filterCars);
        catChecks.forEach(c => c.addEventListener('change', filterCars));
        specChecks.forEach(s => s.addEventListener('change', filterCars));
    });
    </script>
    <?php
    return ob_get_clean();
});