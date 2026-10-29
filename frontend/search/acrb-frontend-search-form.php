<?php
/**
 * [acrb_search_form]
 * Standalone Search Bar for the Homepage and Results Page (Elite SaaS Modernized)
 * Location: templates/shortcodes/search-form.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.3.7
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'acrb_search_form', 'acrb_render_search_form_shortcode' );

function acrb_render_search_form_shortcode(): string {
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

    // 2. Pull locations from settings
    $locations = get_option( 'acrb_custom_pickup_locations', array() ) ?: get_option( 'acrb_custom_locations', array() );
    
    // 3. Define Results Page URL
    $results_page_url = home_url( '/acrb-cars/' ); 

    // 4. Get current Search Values (to keep fields filled on results page)
    $current_ploc  = isset( $_GET['ploc'] ) ? sanitize_text_field( wp_unslash( $_GET['ploc'] ) ) : '';
    $current_dloc  = isset( $_GET['dloc'] ) ? sanitize_text_field( wp_unslash( $_GET['dloc'] ) ) : '';
    $current_pdate = isset( $_GET['pdate'] ) ? sanitize_text_field( wp_unslash( $_GET['pdate'] ) ) : '';
    $current_rdate = isset( $_GET['rdate'] ) ? sanitize_text_field( wp_unslash( $_GET['rdate'] ) ) : '';

    ob_start();
    ?>
    
    <style>
        :root {
            --acrb-search-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-search-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-search-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-search-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-search-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-search-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            font-family: var(--acrb-search-font);
            color: var(--acrb-search-text);
            box-sizing: border-box;
        }
        .acrb-search-wrapper *,
        .acrb-search-wrapper *::before,
        .acrb-search-wrapper *::after {
            box-sizing: border-box;
        }

        /* SVG Icon Standards */
        .acrb-search-icon {
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

        .acrb-search-form {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 26px 30px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
        }
        .acrb-search-main {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
            gap: 18px;
            align-items: flex-end;
        }

        .acrb-search-field {
            display: flex;
            flex-direction: column;
        }
        .acrb-search-field label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 700;
            color: var(--acrb-search-text);
            margin-bottom: 6px;
        }
        .acrb-search-field label .acrb-search-icon {
            color: var(--acrb-search-primary);
        }

        .acrb-search-select,
        .acrb-search-input {
            width: 100% !important;
            height: 42px !important;
            padding: 0 14px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            color: var(--acrb-search-text) !important;
            background: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
            transition: border-color 0.15s ease, background-color 0.15s ease !important;
            font-family: inherit !important;
            line-height: 40px !important;
        }
        .acrb-search-select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2220%22%20height%3D%2220%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 18px;
            padding-right: 38px !important;
            cursor: pointer;
        }
        
        .acrb-search-select:hover,
        .acrb-search-input:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-search-select:focus,
        .acrb-search-input:focus {
            background: #ffffff !important;
            border-color: var(--acrb-search-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
            outline: none !important;
        }

        .acrb-search-submit {
            height: 42px;
            background: var(--acrb-search-primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            padding: 0 24px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
            transition: opacity 0.15s ease, transform 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
            font-family: inherit;
        }
        .acrb-search-submit:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }
        .acrb-search-submit .acrb-search-icon {
            width: 15px;
            height: 15px;
        }

        @media (max-width: 1024px) {
            .acrb-search-main {
                grid-template-columns: 1fr 1fr;
            }
            .acrb-search-submit {
                grid-column: span 2;
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .acrb-search-main {
                grid-template-columns: 1fr;
            }
            .acrb-search-submit {
                grid-column: span 1;
            }
            .acrb-search-form {
                padding: 20px;
            }
        }
    </style>

    <div class="acrb-search-wrapper">
        <form action="<?php echo esc_url( $results_page_url ); ?>" method="GET" class="acrb-search-form">
            
            <div class="acrb-search-main">
                
                <!-- Pickup Point -->
                <div class="acrb-search-field">
                    <label for="acrb_ploc_in">
                        <svg class="acrb-search-icon" viewBox="0 0 24 24">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?php esc_html_e( 'Pickup Point', 'awesome-car-rental' ); ?></span>
                    </label>
                    <select id="acrb_ploc_in" name="ploc" class="acrb-search-select" required>
                        <option value="" disabled <?php selected( $current_ploc, '' ); ?>><?php esc_html_e( 'Select Location', 'awesome-car-rental' ); ?></option>
                        <?php if ( ! empty( $locations ) ) : foreach ( (array) $locations as $loc ) : 
                            $loc_name = is_array( $loc ) ? ( $loc['name'] ?? '' ) : $loc;
                            if ( ! empty( $loc_name ) ) :
                        ?>
                            <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $current_ploc, $loc_name ); ?>>
                                <?php echo esc_html( $loc_name ); ?>
                            </option>
                        <?php endif; endforeach; endif; ?>
                    </select>
                </div>

                <!-- Return Point -->
                <div class="acrb-search-field">
                    <label for="acrb_dloc_in">
                        <svg class="acrb-search-icon" viewBox="0 0 24 24">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?php esc_html_e( 'Return Point', 'awesome-car-rental' ); ?></span>
                    </label>
                    <select id="acrb_dloc_in" name="dloc" class="acrb-search-select" required>
                        <option value="" disabled <?php selected( $current_dloc, '' ); ?>><?php esc_html_e( 'Select Location', 'awesome-car-rental' ); ?></option>
                        <?php if ( ! empty( $locations ) ) : foreach ( (array) $locations as $loc ) : 
                            $loc_name = is_array( $loc ) ? ( $loc['name'] ?? '' ) : $loc;
                            if ( ! empty( $loc_name ) ) :
                        ?>
                            <option value="<?php echo esc_attr( $loc_name ); ?>" <?php selected( $current_dloc, $loc_name ); ?>>
                                <?php echo esc_html( $loc_name ); ?>
                            </option>
                        <?php endif; endforeach; endif; ?>
                    </select>
                </div>

                <!-- Pickup Date -->
                <div class="acrb-search-field">
                    <label for="acrb_pdate_in">
                        <svg class="acrb-search-icon" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span><?php esc_html_e( 'Pickup Date', 'awesome-car-rental' ); ?></span>
                    </label>
                    <input type="date" 
                           name="pdate" 
                           id="acrb_pdate_in" 
                           value="<?php echo esc_attr( $current_pdate ); ?>"
                           class="acrb-search-input" 
                           min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" 
                           required>
                </div>

                <!-- Return Date -->
                <div class="acrb-search-field">
                    <label for="acrb_rdate_in">
                        <svg class="acrb-search-icon" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span><?php esc_html_e( 'Return Date', 'awesome-car-rental' ); ?></span>
                    </label>
                    <input type="date" 
                           name="rdate" 
                           id="acrb_rdate_in" 
                           value="<?php echo esc_attr( $current_rdate ); ?>"
                           class="acrb-search-input" 
                           min="<?php echo $current_pdate ? esc_attr( $current_pdate ) : esc_attr( gmdate( 'Y-m-d' ) ); ?>" 
                           required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="acrb-search-submit">
                    <span><?php esc_html_e( 'Search Fleet', 'awesome-car-rental' ); ?></span>
                    <svg class="acrb-search-icon" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pDate = document.getElementById('acrb_pdate_in');
        const rDate = document.getElementById('acrb_rdate_in');

        if (pDate && rDate) {
            pDate.addEventListener('change', function() {
                rDate.min = this.value;
                if (rDate.value && rDate.value < this.value) {
                    rDate.value = this.value;
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}