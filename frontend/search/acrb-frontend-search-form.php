<?php
/**
 * [acrb_search_form]
 * Standalone Search Bar for the Homepage and Results Page (Elite SaaS Modernized)
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode('acrb_search_form', function() {
    // 1. Pull locations from settings
    $locations = get_option('acrb_custom_pickup_locations', []) ?: get_option('acrb_custom_locations', []);
    
    // 2. Define Results Page URL
    $results_page_url = home_url('/acrb-cars/'); 

    // 3. Get current Search Values (to keep fields filled on results page)
    $current_ploc  = isset($_GET['ploc']) ? sanitize_text_field( wp_unslash( $_GET['ploc'] ) ) : '';
    $current_dloc  = isset($_GET['dloc']) ? sanitize_text_field( wp_unslash( $_GET['dloc'] ) ) : '';
    $current_pdate = isset($_GET['pdate']) ? sanitize_text_field( wp_unslash( $_GET['pdate'] ) ) : '';
    $current_rdate = isset($_GET['rdate']) ? sanitize_text_field( wp_unslash( $_GET['rdate'] ) ) : '';

    ob_start(); ?>
    
    <style>
        .acrb-search-wrapper { max-width: 1280px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; box-sizing: border-box; }
        .acrb-search-wrapper *, .acrb-search-wrapper *::before, .acrb-search-wrapper *::after { box-sizing: border-box; }

        .acrb-search-form { background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 28px 32px; box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05); }
        .acrb-search-main { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 20px; align-items: flex-end; }

        .acrb-search-field { display: flex; flex-direction: column; }
        .acrb-search-field label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; }
        .acrb-search-field label .dashicons { font-size: 16px; width: 16px; height: 16px; color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; }

        .acrb-search-select, .acrb-search-input { width: 100%; height: 46px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.25s ease; }
        .acrb-search-select { appearance: none; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 16px center; background-size: 10px; padding-right: 40px; cursor: pointer; }
        
        .acrb-search-select:hover, .acrb-search-input:hover { border-color: #94a3b8; }
        .acrb-search-select:focus, .acrb-search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12); outline: none; }

        .acrb-search-submit { height: 46px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; padding: 0 28px; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: inline-flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; }
        .acrb-search-submit:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); }
        .acrb-search-submit .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; }

        @media (max-width: 1024px) {
            .acrb-search-main { grid-template-columns: 1fr 1fr; }
            .acrb-search-submit { grid-column: span 2; width: 100%; }
        }

        @media (max-width: 640px) {
            .acrb-search-main { grid-template-columns: 1fr; }
            .acrb-search-submit { grid-column: span 1; }
            .acrb-search-form { padding: 20px; }
        }
    </style>

    <div class="acrb-search-wrapper">
        <form action="<?php echo esc_url($results_page_url); ?>" method="GET" class="acrb-search-form">
            
            <div class="acrb-search-main">
                
                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-location"></span> 
                        <?php esc_html_e('Pickup Point', 'awesome-car-rental'); ?>
                    </label>
                    <select name="ploc" class="acrb-search-select" required>
                        <option value="" disabled <?php selected($current_ploc, ''); ?>><?php esc_html_e('Select Location', 'awesome-car-rental'); ?></option>
                        <?php if(!empty($locations)): foreach((array)$locations as $loc): 
                            $loc_name = is_array($loc) ? ($loc['name'] ?? '') : $loc;
                            if (!empty($loc_name)):
                        ?>
                            <option value="<?php echo esc_attr($loc_name); ?>" <?php selected($current_ploc, $loc_name); ?>>
                                <?php echo esc_html($loc_name); ?>
                            </option>
                        <?php endif; endforeach; endif; ?>
                    </select>
                </div>

                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-location-alt"></span> 
                        <?php esc_html_e('Return Point', 'awesome-car-rental'); ?>
                    </label>
                    <select name="dloc" class="acrb-search-select" required>
                        <option value="" disabled <?php selected($current_dloc, ''); ?>><?php esc_html_e('Select Location', 'awesome-car-rental'); ?></option>
                        <?php if(!empty($locations)): foreach((array)$locations as $loc): 
                            $loc_name = is_array($loc) ? ($loc['name'] ?? '') : $loc;
                            if (!empty($loc_name)):
                        ?>
                            <option value="<?php echo esc_attr($loc_name); ?>" <?php selected($current_dloc, $loc_name); ?>>
                                <?php echo esc_html($loc_name); ?>
                            </option>
                        <?php endif; endforeach; endif; ?>
                    </select>
                </div>

                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-calendar-alt"></span> 
                        <?php esc_html_e('Pickup Date', 'awesome-car-rental'); ?>
                    </label>
                    <input type="date" 
                           name="pdate" 
                           id="acrb_pdate_in" 
                           value="<?php echo esc_attr($current_pdate); ?>"
                           class="acrb-search-input" 
                           min="<?php echo esc_attr( gmdate('Y-m-d') ); ?>" 
                           required>
                </div>

                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-calendar-alt"></span> 
                        <?php esc_html_e('Return Date', 'awesome-car-rental'); ?>
                    </label>
                    <input type="date" 
                           name="rdate" 
                           id="acrb_rdate_in" 
                           value="<?php echo esc_attr($current_rdate); ?>"
                           class="acrb-search-input" 
                           min="<?php echo $current_pdate ? esc_attr($current_pdate) : esc_attr( gmdate('Y-m-d') ); ?>" 
                           required>
                </div>

                <button type="submit" class="acrb-search-submit">
                    <span><?php esc_html_e('Search Fleet', 'awesome-car-rental'); ?></span>
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>

        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pDate = document.getElementById('acrb_pdate_in');
        const rDate = document.getElementById('acrb_rdate_in');

        if(pDate && rDate) {
            // When Pickup date changes, Return date cannot be before it
            pDate.addEventListener('change', function() {
                rDate.min = this.value;
                if(rDate.value && rDate.value < this.value) {
                    rDate.value = this.value;
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
});