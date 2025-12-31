<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [acrb_search_form]
 * Standalone Search Bar for the Homepage.
 */
add_shortcode('acrb_search_form', function() {
    // 1. Pull locations
    $locations = get_option('acrb_custom_locations', []);
    
    // 2. Define Results Page
    $results_page_url = home_url('/all-cars/'); 

    ob_start(); ?>
    
    <div class="acrb-search-wrapper">
        <form action="<?php echo esc_url($results_page_url); ?>" method="GET">
            
            <div class="acrb-search-main">
                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-location"></span> 
                        <?php esc_html_e('Pickup Point', 'awesome-car-rental'); ?>
                    </label>
                    <select name="ploc" class="acrb-search-select" required>
                        <option value="" disabled selected><?php esc_html_e('Select Location', 'awesome-car-rental'); ?></option>
                        <?php if(!empty($locations)): foreach((array)$locations as $loc): ?>
                            <option value="<?php echo esc_attr($loc['name']); ?>">
                                <?php echo esc_html($loc['name']); ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-location-alt"></span> 
                        <?php esc_html_e('Return Point', 'awesome-car-rental'); ?>
                    </label>
                    <select name="dloc" class="acrb-search-select" required>
                        <option value="" disabled selected><?php esc_html_e('Select Location', 'awesome-car-rental'); ?></option>
                        <?php if(!empty($locations)): foreach((array)$locations as $loc): ?>
                            <option value="<?php echo esc_attr($loc['name']); ?>">
                                <?php echo esc_html($loc['name']); ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-calendar-alt"></span> 
                        <?php esc_html_e('Pickup Date', 'awesome-car-rental'); ?>
                    </label>
                    <input type="date" name="pdate" id="acrb_pdate_in" class="acrb-search-input" min="<?php echo esc_attr(gmdate('Y-m-d')); ?>" required>
                </div>

                <div class="acrb-search-field">
                    <label>
                        <span class="dashicons dashicons-calendar-alt"></span> 
                        <?php esc_html_e('Return Date', 'awesome-car-rental'); ?>
                    </label>
                    <input type="date" name="rdate" id="acrb_rdate_in" class="acrb-search-input" min="<?php echo esc_attr(gmdate('Y-m-d')); ?>" required>
                </div>

                <button type="submit" class="acrb-search-submit acrb-btn-primary">
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
            pDate.addEventListener('change', function() {
                rDate.min = this.value;
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
});