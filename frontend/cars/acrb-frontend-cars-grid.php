<?php
/**
 * [acrb_car_grid limit="6" orderby="rand"]
 * Ultra-Modern Elite SaaS Car Grid Shortcode
 * Location: templates/shortcodes/car-grid.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.3.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'acrb_car_grid', 'acrb_render_car_grid_shortcode' );

function acrb_render_car_grid_shortcode( $atts ): string {
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

    // 3. Shortcode Attributes
    $a = shortcode_atts(
        array(
            'limit'   => 6,
            'orderby' => 'date',
            'order'   => 'DESC',
            'cat'     => '',
        ),
        $atts,
        'acrb_car_grid'
    );

    // 4. Query Arguments
    $args = array(
        'post_type'      => 'acrb_cars',
        'posts_per_page' => (int) $a['limit'],
        'orderby'        => sanitize_key( $a['orderby'] ),
        'order'          => sanitize_text_field( $a['order'] ),
        'post_status'    => 'publish',
    );

    if ( ! empty( $a['cat'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'acrb_categories',
                'field'    => 'slug',
                'terms'    => sanitize_title( $a['cat'] ),
            ),
        );
    }

    $cars = new WP_Query( $args );

    if ( ! $cars->have_posts() ) {
        return sprintf(
            '<div class="acrb-grid-empty" style="text-align:center; padding:60px 20px; font-family:%s; color:%s; font-size:14.5px;">%s</div>',
            esc_attr( $selected_font ),
            esc_attr( $secondary_color ),
            esc_html__( 'No fleet vehicles found matching your criteria.', 'awesome-car-rental' )
        );
    }

    ob_start();
    ?>
    <style>
        :root {
            --acrb-grid-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-grid-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-grid-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-grid-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-grid-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-grid-wrapper {
            padding: 30px 0;
            max-width: 1200px;
            margin: 0 auto;
            font-family: var(--acrb-grid-font);
            color: var(--acrb-grid-text);
            box-sizing: border-box;
        }
        .acrb-grid-wrapper *,
        .acrb-grid-wrapper *::before,
        .acrb-grid-wrapper *::after {
            box-sizing: border-box;
        }

        .acrb-car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 26px;
        }

        /* SVG Icon Standards */
        .acrb-grid-icon {
            display: inline-block;
            width: 15px;
            height: 15px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Card Geometry */
        .acrb-car-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .acrb-car-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 30px -8px rgba(15, 23, 42, 0.1);
            border-color: #cbd5e1;
        }

        /* Image Frame & Badges */
        .acrb-img-container {
            position: relative;
            aspect-ratio: 16 / 10;
            overflow: hidden;
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
        .acrb-car-card:hover .acrb-img-container img {
            transform: scale(1.06);
        }
        .acrb-badge-cat {
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

        /* Card Content */
        .acrb-card-body {
            padding: 22px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .acrb-card-title {
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 14px 0;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }
        .acrb-card-title a {
            text-decoration: none !important;
            color: var(--acrb-grid-text);
            transition: color 0.15s ease;
        }
        .acrb-card-title a:hover {
            color: var(--acrb-grid-primary);
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
        .acrb-spec-item {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--acrb-grid-secondary);
            font-size: 12.5px;
            font-weight: 600;
            background: #f8fafc;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .acrb-spec-item .acrb-grid-icon {
            color: var(--acrb-grid-primary);
        }
        .acrb-spec-item span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Card Footer & Pricing */
        .acrb-card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            gap: 12px;
        }
        .acrb-price-block {
            display: flex;
            align-items: baseline;
            gap: 3px;
        }
        .acrb-price-val {
            font-size: 20px;
            font-weight: 900;
            color: var(--acrb-grid-text);
            letter-spacing: -0.02em;
        }
        .acrb-price-unit {
            font-size: 12px;
            color: var(--acrb-grid-secondary);
            font-weight: 600;
        }

        /* Call-To-Action Button */
        .acrb-btn-book {
            background: var(--acrb-grid-primary);
            color: #ffffff !important;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 12.5px;
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
        .acrb-btn-book .acrb-grid-icon {
            width: 14px;
            height: 14px;
            transition: transform 0.15s ease;
        }
        .acrb-btn-book:hover .acrb-grid-icon {
            transform: translateX(2px);
        }

        /* Fallback No Thumbnail */
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

        @media (max-width: 768px) {
            .acrb-car-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="acrb-grid-wrapper">
        <div class="acrb-car-grid">
            <?php 
            while ( $cars->have_posts() ) : 
                $cars->the_post(); 
                $car_id = get_the_ID();

                // Rate Extraction
                $price_raw = get_post_meta( $car_id, 'price_per_day', true ) ?: get_post_meta( $car_id, 'daily_price', true );
                $price_val = floatval( $price_raw ?: 0 );

                // Category Detection
                $terms         = wp_get_post_terms( $car_id, 'acrb_categories' );
                $category_name = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0]->name : get_post_meta( $car_id, 'category', true );

                // Specifications Extraction
                $trans   = (string) ( get_post_meta( $car_id, 'acrb_transmission', true ) ?: 'Automatic' );
                $seats   = absint( get_post_meta( $car_id, 'acrb_seating_capacity', true ) ?: 5 );
                $fuel    = (string) ( get_post_meta( $car_id, 'acrb_fuel_type', true ) ?: 'Petrol' );
                $baggage = absint( get_post_meta( $car_id, 'acrb_baggage_capacity', true ) ?: 2 );

                // Link Resolution
                $single_car_page_id = get_option( 'acrb_single_car_page' );
                $link = $single_car_page_id 
                    ? add_query_arg( 'car_id', $car_id, get_permalink( (int) $single_car_page_id ) ) 
                    : get_permalink();
            ?>
                <article class="acrb-car-card">
                    <!-- Media Header -->
                    <div class="acrb-img-container">
                        <a href="<?php echo esc_url( $link ); ?>" title="<?php the_title_attribute(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large' ); ?>
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
                            <div class="acrb-badge-cat"><?php echo esc_html( (string) $category_name ); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="acrb-card-body">
                        <h3 class="acrb-card-title">
                            <a href="<?php echo esc_url( $link ); ?>"><?php the_title(); ?></a>
                        </h3>

                        <!-- Core Specs Matrix with SVGs -->
                        <div class="acrb-spec-grid">
                            <!-- Transmission -->
                            <div class="acrb-spec-item" title="<?php esc_attr_e( 'Transmission', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-grid-icon" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 3"></path>
                                </svg>
                                <span><?php echo esc_html( $trans ); ?></span>
                            </div>

                            <!-- Seats -->
                            <div class="acrb-spec-item" title="<?php esc_attr_e( 'Seating Capacity', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-grid-icon" viewBox="0 0 24 24">
                                    <path d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span><?php echo esc_html( (string) $seats ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></span>
                            </div>

                            <!-- Fuel / Engine -->
                            <div class="acrb-spec-item" title="<?php esc_attr_e( 'Fuel Type', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-grid-icon" viewBox="0 0 24 24">
                                    <path d="M3 22V5a2 2 0 012-2h8a2 2 0 012 2v17"></path>
                                    <path d="M15 9h2a2 2 0 012 2v2a2 2 0 002 2 2 2 0 002-2V9.5"></path>
                                    <path d="M6 12h5"></path>
                                </svg>
                                <span><?php echo esc_html( $fuel ); ?></span>
                            </div>

                            <!-- Baggage -->
                            <div class="acrb-spec-item" title="<?php esc_attr_e( 'Luggage Allowance', 'awesome-car-rental' ); ?>">
                                <svg class="acrb-grid-icon" viewBox="0 0 24 24">
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

                            <a href="<?php echo esc_url( $link ); ?>" class="acrb-btn-book">
                                <span><?php esc_html_e( 'Rent Now', 'awesome-car-rental' ); ?></span>
                                <svg class="acrb-grid-icon" viewBox="0 0 24 24">
                                    <path d="M5 12h14m-7-7l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            <?php 
            endwhile; 
            wp_reset_postdata(); 
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}