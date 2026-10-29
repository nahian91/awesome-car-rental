<?php
/**
 * [acrb_all_cars]
 * Ultra-Modern Elite SaaS Fleet Archive with Live Filtering & Professional List/Grid Toggles
 * Location: templates/shortcodes/all-cars.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.6.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'acrb_all_cars', 'acrb_render_all_cars_archive_shortcode' );

function acrb_render_all_cars_archive_shortcode(): string {
    global $wpdb;
    $table_cars       = $wpdb->prefix . 'dev_nahian_cars';
    $table_categories = $wpdb->prefix . 'dev_nahian_categories';

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
    $currency_symbol = get_option( 'acrb_currency', '£' );
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );
    $rental_unit     = get_option( 'acrb_rental_unit', 'day' );

    $format_price = static function( float $val ) use ( $currency_symbol, $currency_pos ): string {
        $formatted = number_format( $val, 2 );
        return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
    };

    // 3. Fetch Categories and Cars directly from Custom Tables
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $categories = $wpdb->get_results( "SELECT * FROM {$table_categories} ORDER BY name ASC", ARRAY_A );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $cars = $wpdb->get_results( "SELECT * FROM {$table_cars} WHERE status != 'draft' AND status != 'retired' ORDER BY id DESC", ARRAY_A );

    // Calculate category counts dynamically
    $cat_counts = array();
    if ( ! empty( $cars ) ) {
        foreach ( $cars as $car_item ) {
            $c_cat = trim( (string) ( $car_item['category'] ?? '' ) );
            if ( ! empty( $c_cat ) ) {
                $cat_counts[ $c_cat ] = ( $cat_counts[ $c_cat ] ?? 0 ) + 1;
            }
        }
    }

    $single_car_page_id = get_option( 'acrb_single_car_page' );
    $base_details_url   = $single_car_page_id ? get_permalink( (int) $single_car_page_id ) : '';

    if ( empty( $cars ) ) {
        return sprintf(
            '<div class="acrb-empty" style="text-align:center; padding:60px 20px; font-family:%s; color:%s; font-size:14.5px;">%s</div>',
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

        /* Layout Container: Left Sidebar & Right Content */
        .acrb-layout-container {
            display: flex;
            gap: 30px;
            align-items: flex-start;
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

        /* Left Side Filter Sidebar (Non-Sticky) */
        .acrb-filter-sidebar {
            width: 280px;
            flex-shrink: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.03);
        }
        .acrb-filter-section {
            margin-bottom: 22px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .acrb-filter-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .acrb-filter-section h4 {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--acrb-grid-secondary);
            margin: 0 0 12px 0;
            font-weight: 800;
        }
        .acrb-search-box {
            position: relative;
        }
        .acrb-search-box input {
            width: 100% !important;
            height: 40px !important;
            padding: 0 14px 0 38px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            background: #f8fafc !important;
            color: var(--acrb-grid-text) !important;
            outline: none !important;
        }
        .acrb-search-box input:focus {
            background: #ffffff !important;
            border-color: var(--acrb-grid-primary) !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
        }
        .acrb-search-box .acrb-grid-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .acrb-filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .acrb-filter-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .acrb-filter-item label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: #334155;
            font-weight: 600;
            cursor: pointer;
        }
        .acrb-filter-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--acrb-grid-primary);
            cursor: pointer;
            border-radius: 4px;
        }
        .acrb-count-badge {
            background: #f1f5f9;
            color: var(--acrb-grid-secondary);
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 99px;
        }

        /* Right Side Content Header & View Toolbar */
        .acrb-right-content {
            flex: 1;
            min-width: 0;
        }
        .acrb-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .acrb-result-count {
            font-size: 13.5px;
            color: var(--acrb-grid-secondary);
            font-weight: 600;
        }
        .acrb-view-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .acrb-view-btn {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #64748b;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .acrb-view-btn:hover,
        .acrb-view-btn.active {
            background: var(--acrb-grid-primary);
            border-color: var(--acrb-grid-primary);
            color: #ffffff;
        }

        /* Grid View Layout (Default) */
        .acrb-car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        /* Professional List View Layout */
        .acrb-car-grid.list-view {
            grid-template-columns: 1fr !important;
            gap: 16px;
        }
        .acrb-car-grid.list-view .acrb-car-card {
            flex-direction: row;
            align-items: stretch;
        }
        .acrb-car-grid.list-view .acrb-img-container {
            width: 320px;
            flex-shrink: 0;
            aspect-ratio: auto;
            height: 100%;
        }
        .acrb-car-grid.list-view .acrb-card-body {
            flex-direction: column;
            justify-content: space-between;
            padding: 24px;
        }
        .acrb-car-grid.list-view .acrb-card-main-info {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .acrb-car-grid.list-view .acrb-card-title {
            margin: 0 0 12px 0;
            font-size: 18px;
        }
        .acrb-car-grid.list-view .acrb-spec-grid {
            border-top: none;
            padding-top: 0;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .acrb-car-grid.list-view .acrb-card-footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 16px;
            margin-top: auto;
            flex-direction: row;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
            width: 100%;
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
            transform: translateY(-4px);
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
        .acrb-card-main-info {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .acrb-card-title {
            font-size: 17px;
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

        @media (max-width: 960px) {
            .acrb-layout-container {
                flex-direction: column;
            }
            .acrb-filter-sidebar {
                width: 100%;
            }
            .acrb-car-grid.list-view .acrb-car-card {
                flex-direction: column;
            }
            .acrb-car-grid.list-view .acrb-img-container {
                width: 100%;
                aspect-ratio: 16 / 10;
            }
            .acrb-car-grid.list-view .acrb-card-footer {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
    </style>

    <div class="acrb-grid-wrapper">
        <div class="acrb-layout-container">
            <!-- Left Side Filter Sidebar (Non-Sticky) -->
            <aside class="acrb-filter-sidebar">
                <div class="acrb-filter-section">
                    <h4><?php esc_html_e( 'Search Fleet', 'awesome-car-rental' ); ?></h4>
                    <div class="acrb-search-box">
                        <svg class="acrb-grid-icon" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="acrb-filter-search" placeholder="<?php esc_attr_e( 'Search model or brand...', 'awesome-car-rental' ); ?>">
                    </div>
                </div>

                <?php if ( ! empty( $categories ) ) : ?>
                <div class="acrb-filter-section">
                    <h4><?php esc_html_e( 'Categories', 'awesome-car-rental' ); ?></h4>
                    <ul class="acrb-filter-list">
                        <?php foreach ( $categories as $cat ) : 
                            $c_name  = esc_html( $cat['name'] );
                            $c_slug  = sanitize_title( $cat['slug'] ?? $c_name );
                            $c_count = absint( $cat_counts[ $c_name ] ?? 0 );
                        ?>
                            <li class="acrb-filter-item">
                                <label>
                                    <input type="checkbox" class="acrb-cat-filter" value="<?php echo esc_attr( $c_slug ); ?>">
                                    <span><?php echo $c_name; ?></span>
                                </label>
                                <span class="acrb-count-badge"><?php echo $c_count; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="acrb-filter-section">
                    <h4><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></h4>
                    <ul class="acrb-filter-list">
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-filter" value="automatic"> <span><?php esc_html_e( 'Automatic', 'awesome-car-rental' ); ?></span></label>
                        </li>
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-filter" value="manual"> <span><?php esc_html_e( 'Manual', 'awesome-car-rental' ); ?></span></label>
                        </li>
                    </ul>
                </div>

                <div class="acrb-filter-section">
                    <h4><?php esc_html_e( 'Fuel Type', 'awesome-car-rental' ); ?></h4>
                    <ul class="acrb-filter-list">
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-filter" value="petrol"> <span><?php esc_html_e( 'Petrol', 'awesome-car-rental' ); ?></span></label>
                        </li>
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-filter" value="diesel"> <span><?php esc_html_e( 'Diesel', 'awesome-car-rental' ); ?></span></label>
                        </li>
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-filter" value="electric"> <span><?php esc_html_e( 'Electric', 'awesome-car-rental' ); ?></span></label>
                        </li>
                        <li class="acrb-filter-item">
                            <label><input type="checkbox" class="acrb-spec-filter" value="hybrid"> <span><?php esc_html_e( 'Hybrid', 'awesome-car-rental' ); ?></span></label>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Right Side Content Area -->
            <div class="acrb-right-content">
                <!-- Header Toolbar with View Toggle Icons -->
                <div class="acrb-content-header">
                    <span class="acrb-result-count" id="acrb-result-count">
                        <?php
                        /* translators: %d: total vehicles count */
                        printf( esc_html__( 'Showing %d vehicles', 'awesome-car-rental' ), count( $cars ) );
                        ?>
                    </span>
                    <div class="acrb-view-toolbar">
                        <button type="button" class="acrb-view-btn active" id="acrb-btn-grid" title="<?php esc_attr_e( 'Grid View', 'awesome-car-rental' ); ?>">
                            <svg class="acrb-grid-icon" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </button>
                        <button type="button" class="acrb-view-btn" id="acrb-btn-list" title="<?php esc_attr_e( 'List View', 'awesome-car-rental' ); ?>">
                            <svg class="acrb-grid-icon" viewBox="0 0 24 24">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Fleet Grid Container -->
                <div class="acrb-car-grid" id="acrb-fleet-grid">
                    <?php 
                    foreach ( $cars as $car ) :
                        $car_id       = absint( $car['id'] );
                        $car_name     = esc_html( $car['name'] );
                        $car_brand    = esc_html( $car['brand'] ?? '' );
                        $car_year     = absint( $car['year'] ?? 2026 );
                        $full_title   = trim( ( $car_brand ? $car_brand . ' ' : '' ) . $car_name . ( $car_year ? ' (' . $car_year . ')' : '' ) );
                        
                        $price_val    = floatval( $car['price'] ?? 0.00 );

                        $category_name = esc_html( $car['category'] ?? '' );
                        $cat_slug      = sanitize_title( $category_name );

                        $trans        = (string) ( $car['transmission'] ?: 'Automatic' );
                        $seats        = absint( $car['seats'] ?: 5 );
                        $fuel         = (string) ( $car['fuel'] ?: 'Petrol' );
                        $baggage      = absint( $car['bags'] ?: 2 );

                        $raw_img      = (string) ( $car['image'] ?? '' );
                        $thumb_url    = '';
                        if ( is_numeric( $raw_img ) && absint( $raw_img ) > 0 ) {
                            $thumb_url = wp_get_attachment_image_url( absint( $raw_img ), 'medium_large' ) ?: '';
                        } elseif ( ! empty( $raw_img ) && filter_var( $raw_img, FILTER_VALIDATE_URL ) ) {
                            $thumb_url = $raw_img;
                        }

                        $single_car_page_id = get_option( 'acrb_single_car_page' );
                        $link = $single_car_page_id 
                            ? add_query_arg( 'car_id', $car_id, get_permalink( (int) $single_car_page_id ) ) 
                            : add_query_arg( array( 'sub' => 'view', 'car_id' => $car_id ), admin_url( 'admin.php?page=awesome_car_rental&tab=cars' ) );

                        $searchable_text = strtolower( $full_title . ' ' . $trans . ' ' . $fuel . ' ' . $category_name );
                    ?>
                        <!-- Super SEO Friendly JSON-LD Structured Data for Each Vehicle -->
                        <script type="application/ld+json">
                        {
                          "@context": "https://schema.org/",
                          "@type": "Car",
                          "name": "<?php echo esc_js( $full_title ); ?>",
                          "brand": {
                            "@type": "Brand",
                            "name": "<?php echo esc_js( $car_brand ?: 'Fleet Vehicle' ); ?>"
                          },
                          "model": "<?php echo esc_js( $car_name ); ?>"<?php if ( $car_year ) : ?>,
                          "vehicleModelDate": "<?php echo esc_js( (string) $car_year ); ?>"<?php endif; ?><?php if ( ! empty( $thumb_url ) ) : ?>,
                          "image": "<?php echo esc_url( $thumb_url ); ?>"<?php endif; ?>,
                          "vehicleTransmission": "<?php echo esc_js( $trans ); ?>",
                          "fuelType": "<?php echo esc_js( $fuel ); ?>",
                          "seatingCapacity": "<?php echo absint( $seats ); ?>",
                          "offers": {
                            "@type": "Offer",
                            "price": "<?php echo esc_js( number_format( $price_val, 2, '.', '' ) ); ?>",
                            "priceCurrency": "GBP",
                            "eligibleQuantity": {
                              "@type": "QuantitativeValue",
                              "unitCode": "DAY",
                              "unitText": "Per Day"
                            }
                          }
                        }
                        </script>

                        <article class="acrb-car-card <?php echo esc_attr( $cat_slug ); ?>"
                                 data-search="<?php echo esc_attr( $searchable_text ); ?>"
                                 data-category="<?php echo esc_attr( $cat_slug ); ?>"
                                 data-trans="<?php echo esc_attr( strtolower( $trans ) ); ?>"
                                 data-fuel="<?php echo esc_attr( strtolower( $fuel ) ); ?>">
                            
                            <!-- Media Header -->
                            <div class="acrb-img-container">
                                <a href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $full_title ); ?>">
                                    <?php if ( ! empty( $thumb_url ) ) : ?>
                                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $full_title ); ?>">
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
                                    <div class="acrb-badge-cat"><?php echo esc_html( $category_name ); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Body -->
                            <div class="acrb-card-body">
                                <div class="acrb-card-main-info">
                                    <h3 class="acrb-card-title">
                                        <a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $full_title ); ?></a>
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
                    <?php endforeach; ?>

                    <div id="acrb-no-cars-msg" style="display:none; grid-column: 1 / -1; text-align:center; padding:60px 20px; color:var(--acrb-grid-secondary);">
                        <svg class="acrb-grid-icon" style="width:48px; height:48px; color:#cbd5e1; margin-bottom:12px;" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <h3 style="margin:0 0 6px 0; font-size:18px; color:var(--acrb-grid-text);"><?php esc_html_e( 'No vehicles found', 'awesome-car-rental' ); ?></h3>
                        <p style="margin:0; font-size:14px;"><?php esc_html_e( 'Try clearing your filters or search query.', 'awesome-car-rental' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('acrb-filter-search');
        const catCheckboxes = document.querySelectorAll('.acrb-cat-filter');
        const specCheckboxes = document.querySelectorAll('.acrb-spec-filter');
        const cards = document.querySelectorAll('.acrb-car-card');
        const countDisplay = document.getElementById('acrb-result-count');
        const noCarsMsg = document.getElementById('acrb-no-cars-msg');

        const btnGrid = document.getElementById('acrb-btn-grid');
        const btnList = document.getElementById('acrb-btn-list');
        const fleetGrid = document.getElementById('acrb-fleet-grid');

        // View Mode Switcher
        if (btnGrid && btnList && fleetGrid) {
            btnGrid.addEventListener('click', function() {
                btnGrid.classList.add('active');
                btnList.classList.remove('active');
                fleetGrid.classList.remove('list-view');
            });
            btnList.addEventListener('click', function() {
                btnList.classList.add('active');
                btnGrid.classList.remove('active');
                fleetGrid.classList.add('list-view');
            });
        }

        function applyFilters() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedCats = Array.from(catCheckboxes).filter(i => i.checked).map(i => i.value);
            const selectedSpecs = Array.from(specCheckboxes).filter(i => i.checked).map(i => i.value);
            
            let visible = 0;

            cards.forEach(card => {
                const text = card.getAttribute('data-search') || '';
                const cat = card.getAttribute('data-category') || '';
                const trans = card.getAttribute('data-trans') || '';
                const fuel = card.getAttribute('data-fuel') || '';

                const matchesSearch = !query || text.includes(query);
                const matchesCat = selectedCats.length === 0 || selectedCats.includes(cat);
                
                let matchesSpec = true;
                if (selectedSpecs.length > 0) {
                    matchesSpec = selectedSpecs.every(spec => {
                        return trans.includes(spec) || fuel.includes(spec);
                    });
                }

                if (matchesSearch && matchesCat && matchesSpec) {
                    card.style.display = 'flex';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (countDisplay) {
                countDisplay.textContent = "<?php esc_html_e( 'Showing', 'awesome-car-rental' ); ?> " + visible + " <?php esc_html_e( 'of', 'awesome-car-rental' ); ?> " + cards.length + " <?php esc_html_e( 'vehicles', 'awesome-car-rental' ); ?>";
            }
            if (noCarsMsg) {
                noCarsMsg.style.display = visible === 0 ? 'block' : 'none';
            }
        }

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        catCheckboxes.forEach(cb => cb.addEventListener('change', applyFilters));
        specCheckboxes.forEach(cb => cb.addEventListener('change', applyFilters));
    });
    </script>
    <?php
    return ob_get_clean();
}