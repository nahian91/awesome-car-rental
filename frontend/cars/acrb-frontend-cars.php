<?php
/**
 * [acrb_all_cars]
 * Ultra-Modern Elite SaaS Fleet Archive with Live Filtering
 * Location: templates/shortcodes/all-cars.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.3.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'acrb_all_cars', 'acrb_render_all_cars_archive_shortcode' );

function acrb_render_all_cars_archive_shortcode(): string {
    // 1. Fetch Dynamic Styling & Typography Tokens from Style Customizer
    $primary_color   = sanitize_hex_color( (string) get_option( 'acrb_style_primary_color', '#4f46e5' ) );
    $secondary_color = sanitize_hex_color( (string) get_option( 'acrb_style_secondary_color', '#64748b' ) );
    $text_color      = sanitize_hex_color( (string) get_option( 'acrb_style_text_color', '#0f172a' ) );
    $accent_color    = sanitize_hex_color( (string) get_option( 'acrb_style_accent_color', '#059669' ) );
    $font_slug       = sanitize_text_field( (string) get_option( 'acrb_style_font_family', 'jakarta' ) );

    $font_stacks = array(
        'jakarta'    => "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif",
        'inter'      => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
        'poppins'    => "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif",
        'montserrat' => "'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif",
        'roboto'     => "'Roboto', -apple-system, BlinkMacSystemFont, sans-serif",
    );
    $selected_font = $font_stacks[ $font_slug ] ?? $font_stacks['jakarta'];

    // 2. Currency & Billing Unit Settings
    $currency_symbol = get_option( 'acrb_currency', '$' );
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );
    $rental_unit     = get_option( 'acrb_rental_unit', 'day' );

    $format_price = static function( float $val ) use ( $currency_symbol, $currency_pos ): string {
        $formatted = number_format( $val, 2 );
        return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
    };

    $categories = get_terms( array(
        'taxonomy'   => 'acrb_categories', 
        'hide_empty' => false,
    ) );
    
    // Fallback to acrb_car_category if acrb_categories is empty
    if ( ( empty( $categories ) || is_wp_error( $categories ) ) ) {
        $categories = get_terms( array(
            'taxonomy'   => 'acrb_car_category',
            'hide_empty' => false,
        ) );
    }

    $cars = get_posts( array(
        'post_type'      => 'acrb_cars',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ) );

    $single_car_page_id = get_option( 'acrb_single_car_page' ); 
    $base_details_url   = $single_car_page_id ? get_permalink( (int) $single_car_page_id ) : '';

    if ( empty( $cars ) ) {
        return sprintf(
            '<div class="acrb-empty" style="text-align:center; padding:60px 20px; font-family:%s; color:%s; font-size:14.5px;">%s</div>',
            esc_attr( $selected_font ),
            esc_attr( $secondary_color ),
            esc_html__( 'Fleet currently unavailable.', 'awesome-car-rental' )
        );
    }

    ob_start();
    ?>
    
    <style>
        :root {
            --acrb-all-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-all-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-all-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-all-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-all-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-main-wrapper {
            display: flex;
            gap: 32px;
            align-items: flex-start;
            max-width: 1200px;
            margin: 30px auto;
            font-family: var(--acrb-all-font);
            color: var(--acrb-all-text);
            box-sizing: border-box;
        }
        .acrb-main-wrapper *,
        .acrb-main-wrapper *::before,
        .acrb-main-wrapper *::after {
            box-sizing: border-box;
        }

        /* SVG Icon Standards */
        .acrb-all-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Sidebar Filters */
        .acrb-sidebar {
            width: 280px;
            flex-shrink: 0;
            position: sticky;
            top: 30px;
        }
        .acrb-filter-card {
            background: #ffffff;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            border: 1px solid #e2e8f0;
        }
        .acrb-filter-group {
            margin-bottom: 22px;
        }
        .acrb-filter-group:last-child {
            margin-bottom: 0;
        }
        .acrb-filter-group h4 {
            font-size: 11.5px;
            margin: 0 0 12px 0;
            color: var(--acrb-all-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 800;
        }

        .acrb-search-wrap {
            position: relative;
        }
        .acrb-search-wrap .acrb-all-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            width: 18px;
            height: 18px;
        }
        #acrb-car-search {
            width: 100% !important;
            height: 42px !important;
            padding: 0 14px 0 42px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            color: var(--acrb-all-text) !important;
            outline: none !important;
            background: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
            transition: border-color 0.15s ease, background-color 0.15s ease !important;
            font-family: inherit !important;
        }
        #acrb-car-search:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        #acrb-car-search:focus {
            background: #ffffff !important;
            border-color: var(--acrb-all-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
        }

        .acrb-filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .acrb-filter-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .acrb-filter-item:last-child {
            margin-bottom: 0;
        }
        .acrb-filter-item label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 13.5px;
            color: #334155;
            font-weight: 600;
        }
        .acrb-filter-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--acrb-all-primary);
            cursor: pointer;
            border-radius: 4px;
        }
        .acrb-count-badge {
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            color: var(--acrb-all-secondary);
            font-weight: 700;
        }

        /* Grid Content */
        .acrb-content {
            flex: 1;
            min-width: 0;
        }
        .acrb-car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        /* Card Styling */
        .acrb-card {
            background: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .acrb-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 30px -8px rgba(15, 23, 42, 0.1);
            border-color: #cbd5e1;
        }

        .acrb-img-container {
            aspect-ratio: 16 / 10;
            overflow: hidden;
            position: relative;
            background: #f8fafc;
        }
        .acrb-img-container a {
            display: block;
            width: 100%;
            height: 100%;
        }
        .acrb-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .acrb-card:hover .acrb-img-container img {
            transform: scale(1.06);
        }

        .acrb-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .acrb-card-body {
            padding: 22px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .acrb-card-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--acrb-all-text);
            text-decoration: none !important;
            display: block;
            margin-bottom: 14px;
            line-height: 1.3;
            letter-spacing: -0.01em;
            transition: color 0.15s ease;
        }
        .acrb-card-title:hover {
            color: var(--acrb-all-primary);
        }

        /* Specifications Grid */
        .acrb-spec-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 20px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }
        .acrb-spec-pill {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12.5px;
            color: var(--acrb-all-secondary);
            font-weight: 600;
            background: #f8fafc;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .acrb-spec-pill .acrb-all-icon {
            color: var(--acrb-all-primary);
        }
        .acrb-spec-pill span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Card Footer */
        .acrb-card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            gap: 12px;
        }
        .acrb-price-val {
            font-size: 20px;
            font-weight: 900;
            color: var(--acrb-all-text);
            letter-spacing: -0.02em;
        }
        .acrb-price-label {
            font-size: 12px;
            color: var(--acrb-all-secondary);
            font-weight: 600;
        }
        
        .acrb-btn-book {
            background: var(--acrb-all-primary);
            color: #ffffff !important;
            text-decoration: none !important;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            transition: all 0.15s ease;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .acrb-btn-book:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.14);
        }
        .acrb-btn-book .acrb-all-icon {
            width: 14px;
            height: 14px;
            transition: transform 0.15s ease;
        }
        .acrb-btn-book:hover .acrb-all-icon {
            transform: translateX(2px);
        }

        /* Fallback Thumbnail */
        .acrb-no-thumb {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            background: #f8fafc;
        }
        .acrb-no-thumb svg {
            width: 52px;
            height: 52px;
        }

        @media (max-width: 900px) {
            .acrb-main-wrapper {
                flex-direction: column;
            }
            .acrb-sidebar {
                width: 100%;
                position: static;
            }
        }
    </style>

    <div class="acrb-main-wrapper">
        <!-- Sidebar Filter Card -->
        <aside class="acrb-sidebar">
            <div class="acrb-filter-card">
                <div class="acrb-filter-group">
                    <h4><?php esc_html_e( 'Search Vehicle', 'awesome-car-rental' ); ?></h4>
                    <div class="acrb-search-wrap">
                        <svg class="acrb-all-icon" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="acrb-car-search" placeholder="<?php esc_attr_e( 'Search model...', 'awesome-car-rental' ); ?>">
                    </div>
                </div>

                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                <div class="acrb-filter-group">
                    <h4><?php esc_html_e( 'Categories', 'awesome-car-rental' ); ?></h4>
                    <ul class="acrb-filter-list">
                        <?php foreach ( $categories as $cat ) : ?>
                            <li class="acrb-filter-item">
                                <label>
                                    <input type="checkbox" class="acrb-cat-check" value="<?php echo esc_attr( $cat->slug ); ?>"> 
                                    <span><?php echo esc_html( $cat->name ); ?></span>
                                </label>
                                <span class="acrb-count-badge"><?php echo absint( $cat->count ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="acrb-filter-group">
                    <h4><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></h4>
                    <ul class="acrb-filter-list">
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-check" value="automatic"> <span><?php esc_html_e( 'Automatic', 'awesome-car-rental' ); ?></span></label>
                        </li>
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-check" value="manual"> <span><?php esc_html_e( 'Manual', 'awesome-car-rental' ); ?></span></label>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Fleet Grid Content -->
        <div class="acrb-content">
            <div class="acrb-car-grid" id="acrb-grid-container">
                <?php foreach ( $cars as $car ) : 
                    $car_id = $car->ID;
                    $price_raw = get_post_meta( $car_id, 'price_per_day', true ) ?: get_post_meta( $car_id, 'daily_price', true );
                    $price_val = floatval( $price_raw ?: 0 );

                    $trans   = (string) ( get_post_meta( $car_id, 'acrb_transmission', true ) ?: 'Automatic' );
                    $seats   = absint( get_post_meta( $car_id, 'acrb_seating_capacity', true ) ?: 5 );
                    $fuel    = (string) ( get_post_meta( $car_id, 'acrb_fuel_type', true ) ?: 'Petrol' );
                    $baggage = absint( get_post_meta( $car_id, 'acrb_baggage_capacity', true ) ?: 2 );

                    $terms = wp_get_post_terms( $car_id, 'acrb_categories' );
                    if ( empty( $terms ) || is_wp_error( $terms ) ) {
                        $terms = wp_get_post_terms( $car_id, 'acrb_car_category' );
                    }
                    $slugs = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? implode( ' ', wp_list_pluck( $terms, 'slug' ) ) : '';
                    $category_name = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : get_post_meta( $car_id, 'category', true );

                    $details_link = $base_details_url ? add_query_arg( 'car_id', $car_id, $base_details_url ) : get_permalink( $car_id );
                    
                    $spec_string = strtolower( $trans . ' ' . $fuel . ' ' . $seats . ' seats ' . $baggage . ' bags' );
                ?>
                <div class="acrb-card <?php echo esc_attr( $slugs ); ?>" 
                     data-name="<?php echo esc_attr( strtolower( $car->post_title ) ); ?>"
                     data-specs="<?php echo esc_attr( $spec_string ); ?>">
                    
                    <!-- Media Header -->
                    <div class="acrb-img-container">
                        <a href="<?php echo esc_url( $details_link ); ?>" title="<?php echo esc_attr( $car->post_title ); ?>">
                            <?php if ( has_post_thumbnail( $car_id ) ) : ?>
                                <?php echo get_the_post_thumbnail( $car_id, 'medium_large' ); ?>
                            <?php else : ?>
                                <div class="acrb-no-thumb">
                                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path d="M5 17h14v-5l-2-6H7l-2 6v5z"></path>
                                        <circle cx="7.5" cy="17.5" r="2.5"></circle>
                                        <circle cx="16.5" cy="17.5" r="2.5"></circle>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </a>

                        <?php if ( ! empty( $category_name ) ) : ?>
                            <div class="acrb-badge"><?php echo esc_html( (string) $category_name ); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="acrb-card-body">
                        <a href="<?php echo esc_url( $details_link ); ?>" class="acrb-card-title"><?php echo esc_html( $car->post_title ); ?></a>
                        
                        <!-- Core Specs Matrix with SVGs -->
                        <div class="acrb-spec-grid">
                            <!-- Transmission -->
                            <div class="acrb-spec-pill" title="<?php esc_attr_e( 'Transmission', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-all-icon" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 3"></path>
                                </svg>
                                <span><?php echo esc_html( $trans ); ?></span>
                            </div>

                            <!-- Seats -->
                            <div class="acrb-spec-pill" title="<?php esc_attr_e( 'Seating Capacity', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-all-icon" viewBox="0 0 24 24">
                                    <path d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span><?php echo esc_html( (string) $seats ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></span>
                            </div>

                            <!-- Fuel / Engine -->
                            <div class="acrb-spec-pill" title="<?php esc_attr_e( 'Fuel Type', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-all-icon" viewBox="0 0 24 24">
                                    <path d="M3 22V5a2 2 0 012-2h8a2 2 0 012 2v17"></path>
                                    <path d="M15 9h2a2 2 0 012 2v2a2 2 0 002 2 2 2 0 002-2V9.5"></path>
                                    <path d="M6 12h5"></path>
                                </svg>
                                <span><?php echo esc_html( $fuel ); ?></span>
                            </div>

                            <!-- Baggage -->
                            <div class="acrb-spec-pill" title="<?php esc_attr_e( 'Luggage Allowance', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-all-icon" viewBox="0 0 24 24">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"></path>
                                </svg>
                                <span><?php echo esc_html( (string) $baggage ); ?> <?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="acrb-card-footer">
                            <div class="acrb-price-block">
                                <span class="acrb-price-val"><?php echo esc_html( $format_price( $price_val ) ); ?></span>
                                <span class="acrb-price-unit">/ <?php echo esc_html( $rental_unit ); ?></span>
                            </div>

                            <a href="<?php echo esc_url( $details_link ); ?>" class="acrb-btn-book">
                                <span><?php esc_html_e( 'Rent Now', 'awesome-car-rental' ); ?></span>
                                <svg class="acrb-all-icon" viewBox="0 0 24 24">
                                    <path d="M5 12h14m-7-7l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div id="acrb-no-results" style="display:none; grid-column: 1 / -1; text-align:center; padding:60px 20px; color:var(--acrb-all-secondary);">
                    <svg class="acrb-all-icon" style="width:48px; height:48px; color:#cbd5e1; margin-bottom:12px;" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <h3 style="margin:0 0 6px 0; font-size:18px; color:var(--acrb-all-text);"><?php esc_html_e( 'No cars found', 'awesome-car-rental' ); ?></h3>
                    <p style="margin:0; font-size:14px;"><?php esc_html_e( 'Try adjusting your search or filters.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('acrb-car-search');
        const catChecks   = document.querySelectorAll('.acrb-cat-check');
        const specChecks  = document.querySelectorAll('.acrb-spec-check');
        const cards       = document.querySelectorAll('.acrb-card');
        const noResults   = document.getElementById('acrb-no-results');

        if (!searchInput) return;

        function filterCars() {
            const query       = searchInput.value.toLowerCase().trim();
            const activeCats  = Array.from(catChecks).filter(i => i.checked).map(i => i.value);
            const activeSpecs = Array.from(specChecks).filter(i => i.checked).map(i => i.value);
            let visibleCount  = 0;

            cards.forEach(card => {
                const name        = card.getAttribute('data-name') || '';
                const specs       = card.getAttribute('data-specs') || '';
                const cardClasses = card.className.split(' ');

                const matchesSearch = name.includes(query);
                const matchesCat    = activeCats.length === 0 || activeCats.some(c => cardClasses.includes(c));
                const matchesSpec   = activeSpecs.length === 0 || activeSpecs.every(s => specs.includes(s));

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
}