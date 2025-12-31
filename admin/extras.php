<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ACRB Extras Tab - Full configuration for Locations, Specs, and Amenities
 * Features: Expanded Icon Library & Real-time Icon Search
 */
function acrb_extras_tab() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // 1. SAVE LOGIC
    if ( isset( $_POST['acrb_save_extras'] ) && check_admin_referer( 'acrb_save_extras_action', 'acrb_extras_nonce' ) ) {
        
        $locations = isset( $_POST['locations'] ) ? array_map( function($item) { return array_map('sanitize_text_field', $item); }, wp_unslash( (array) $_POST['locations'] ) ) : array();
        $features  = isset( $_POST['features'] ) ? array_map( function($item) { return array_map('sanitize_text_field', $item); }, wp_unslash( (array) $_POST['features'] ) ) : array();
        $amenities = isset( $_POST['amenities'] ) ? array_map( function($item) { return array_map('sanitize_text_field', $item); }, wp_unslash( (array) $_POST['amenities'] ) ) : array();

        update_option( 'acrb_custom_locations', array_values($locations) );
        update_option( 'acrb_custom_features', array_values($features) );
        update_option( 'acrb_custom_amenities', array_values($amenities) );

        echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'All extra configurations saved.', 'awesome-car-rental' ) . '</p></div>';
    }

    // 2. DATA FETCHING
    $locations = get_option('acrb_custom_locations', []);
    $features  = get_option('acrb_custom_features', []);
    $amenities = get_option('acrb_custom_amenities', []);

    // 3. EXPANDED ICON LIBRARY (Over 60 icons)
    $icon_lib = [
        'dashicons-location', 'dashicons-location-alt', 'dashicons-airplane', 'dashicons-building', 'dashicons-store', 'dashicons-admin-home',
        'dashicons-car', 'dashicons-performance', 'dashicons-dashboard', 'dashicons-admin-tools', 'dashicons-oil', 'dashicons-hammer',
        'dashicons-flash', 'dashicons-star-filled', 'dashicons-yes', 'dashicons-shield', 'dashicons-heart', 'dashicons-awards',
        'dashicons-unlock', 'dashicons-lock', 'dashicons-info', 'dashicons-hidden', 'dashicons-visibility', 'dashicons-admin-network',
        'dashicons-palmtree', 'dashicons-universal-access', 'dashicons-tickets-alt', 'dashicons-groups', 'dashicons-businessman',
        'dashicons-money-alt', 'dashicons-calendar-alt', 'dashicons-clock', 'dashicons-warning', 'dashicons-thumbs-up', 'dashicons-phone',
        'dashicons-email-alt', 'dashicons-share', 'dashicons-cloud', 'dashicons-cart', 'dashicons-products', 'dashicons-tag',
        'dashicons-insert', 'dashicons-move', 'dashicons-exit', 'dashicons-external', 'dashicons-rest-view', 'dashicons-camera',
        'dashicons-images-alt2', 'dashicons-video-alt3', 'dashicons-database', 'dashicons-database-export', 'dashicons-update'
    ];
    ?>

    <style>
        .acrb-admin-wrapper { margin-top: 20px; max-width: 1100px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .acrb-admin-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .acrb-admin-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; }
        .acrb-admin-card-title { margin: 0; font-size: 16px; font-weight: 600; color: #1d2327; flex: 1; }
        
        .acrb-admin-row-list { display: flex; flex-direction: column; gap: 10px; }
        .acrb-admin-item-row { display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; position: relative; }
        
        /* Icon Selector UI */
        .acrb-admin-icon-trigger { cursor: pointer; background: #fff; border: 1px solid #8c8f94; padding: 5px; border-radius: 4px; min-width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .acrb-admin-icon-trigger:hover { border-color: #2271b1; color: #2271b1; }
        .acrb-admin-icon-dropdown { display: none; position: absolute; top: 48px; left: 10px; z-index: 1000; background: #fff; border: 1px solid #ccc; box-shadow: 0 10px 25px rgba(0,0,0,0.15); padding: 12px; width: 280px; border-radius: 8px; }
        
        /* Search Box inside Dropdown */
        .acrb-icon-search-input { width: 100%; margin-bottom: 10px; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        
        .acrb-admin-icon-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; max-height: 180px; overflow-y: auto; }
        .acrb-admin-icon-opt { padding: 8px; cursor: pointer; text-align: center; border-radius: 4px; border: 1px solid transparent; }
        .acrb-admin-icon-opt:hover { background: #f0f6fb; border-color: #2271b1; color: #2271b1; }
        .acrb-admin-icon-opt.hidden { display: none; }
        
        .acrb-admin-input { border: 1px solid #8c8f94; border-radius: 4px; padding: 6px 12px; height: 38px; font-size: 13px; }
        .acrb-admin-flex-grow { flex: 1; }
        .acrb-admin-w-180 { width: 180px; }
        
        .acrb-admin-remove-btn { color: #d63638; cursor: pointer; opacity: 0.5; transition: 0.2s; padding: 5px; }
        .acrb-admin-remove-btn:hover { opacity: 1; background: #fbeaea; border-radius: 4px; }
        .acrb-admin-add-btn { display: inline-flex; align-items: center; gap: 6px; margin-top: 15px; color: #2271b1; font-weight: 600; cursor: pointer; transition: 0.2s; }

        .acrb-admin-footer { margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; display: flex; justify-content: flex-end; }
        .acrb-admin-btn-save { background: #2271b1; color: #fff; border: none; padding: 12px 35px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; }
    </style>

    <div class="acrb-admin-wrapper">
        <form method="post" id="acrb-extras-form">
            <?php wp_nonce_field( 'acrb_save_extras_action', 'acrb_extras_nonce' ); ?>
            
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-location" style="color:#2271b1;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Pickup & Drop-off Locations', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-locations-list" class="acrb-admin-row-list">
                    <?php foreach ($locations as $idx => $l): ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr($l['icon'] ?? 'dashicons-location'); ?>"></span></div>
                            <input type="hidden" name="locations[<?php echo esc_attr($idx); ?>][icon]" value="<?php echo esc_attr($l['icon'] ?? 'dashicons-location'); ?>" class="acrb-icon-val">
                            <input type="text" name="locations[<?php echo esc_attr($idx); ?>][name]" value="<?php echo esc_attr($l['name'] ?? ''); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Location Name">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn"></span>
                            
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="Search icons...">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach($icon_lib as $i): ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>"><span class="dashicons <?php echo esc_attr( $i ); ?>"></span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-locations-list" data-type="locations"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Add Location', 'awesome-car-rental' ); ?></div>
            </div>

            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-admin-tools" style="color:#2271b1;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-features-list" class="acrb-admin-row-list">
                    <?php foreach ($features as $idx => $f): ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr($f['icon'] ?? 'dashicons-car'); ?>"></span></div>
                            <input type="hidden" name="features[<?php echo (int) $idx; ?>][icon]" value="<?php echo esc_attr( $f['icon'] ?? 'dashicons-car' ); ?>" class="acrb-icon-val">

<input type="text" name="features[<?php echo (int) $idx; ?>][name]" value="<?php echo esc_attr( $f['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-w-180" placeholder="<?php echo esc_attr__( 'Label', 'awesome-car-rental' ); ?>">

<input type="text" name="features[<?php echo (int) $idx; ?>][value]" value="<?php echo esc_attr( $f['value'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_attr__( 'Value', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn"></span>
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="Search icons...">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach($icon_lib as $i): ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>">
    <span class="dashicons <?php echo esc_attr( $i ); ?>"></span>
</div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-features-list" data-type="features"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Add Specification', 'awesome-car-rental' ); ?></div>
            </div>

            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-star-filled" style="color:#2271b1;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Comfort Amenities', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-amenities-list" class="acrb-admin-row-list">
                    <?php foreach ($amenities as $idx => $a): ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr($a['icon'] ?? 'dashicons-yes'); ?>"></span></div>
                            <input type="hidden" name="amenities[<?php echo (int) $idx; ?>][icon]" value="<?php echo esc_attr( $a['icon'] ?? 'dashicons-yes' ); ?>" class="acrb-icon-val">

<input type="text" name="amenities[<?php echo (int) $idx; ?>][name]" value="<?php echo esc_attr( $a['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_attr__( 'Amenity Name', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn"></span>
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="Search icons...">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach($icon_lib as $i): ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>">
    <span class="dashicons <?php echo esc_attr( $i ); ?>"></span>
</div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-amenities-list" data-type="amenities"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Add Amenity', 'awesome-car-rental' ); ?></div>
            </div>

            <div class="acrb-admin-footer">
                <button type="submit" name="acrb_save_extras" class="acrb-admin-btn-save">
                    <?php esc_html_e( 'Save All Extra Settings', 'awesome-car-rental' ); ?>
                </button>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Toggle Dropdown & Focus Search
        $(document).on('click', '.acrb-admin-icon-trigger', function(e) {
            e.stopPropagation();
            var $dropdown = $(this).siblings('.acrb-admin-icon-dropdown');
            $('.acrb-admin-icon-dropdown').not($dropdown).hide();
            $dropdown.fadeToggle(100, function() {
                if ($(this).is(':visible')) {
                    $(this).find('.acrb-icon-search-input').val('').focus();
                    $(this).find('.acrb-admin-icon-opt').removeClass('hidden');
                }
            });
        });

        // LIVE SEARCH LOGIC
        $(document).on('keyup', '.acrb-icon-search-input', function() {
            var searchVal = $(this).val().toLowerCase();
            var $grid = $(this).siblings('.acrb-admin-icon-grid');
            
            $grid.find('.acrb-admin-icon-opt').each(function() {
                var iconName = $(this).data('icon').toLowerCase();
                if (iconName.indexOf(searchVal) !== -1) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        });

        // Icon Selection
        $(document).on('click', '.acrb-admin-icon-opt', function(e) {
            e.stopPropagation();
            var icon = $(this).data('icon');
            var $row = $(this).closest('.acrb-admin-item-row');
            $row.find('.acrb-admin-icon-trigger .dashicons').attr('class', 'dashicons ' + icon);
            $row.find('.acrb-icon-val').val(icon);
            $('.acrb-admin-icon-dropdown').hide();
        });

        $(document).on('click', function() { $('.acrb-admin-icon-dropdown').hide(); });
        $(document).on('click', '.acrb-admin-icon-dropdown', function(e) { e.stopPropagation(); });

        $(document).on('click', '.acrb-admin-remove-btn', function() {
            $(this).closest('.acrb-admin-item-row').fadeOut(200, function() { $(this).remove(); });
        });

        // Dynamic Row Addition
        $('.acrb-admin-add-btn').on('click', function() {
            var targetId = $(this).data('target');
            var type = $(this).data('type');
            var list = $('#' + targetId);
            var index = new Date().getTime(); 
            var iconsHtml = `<?php foreach ( $icon_lib as $i ) : ?><div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>"><span class="dashicons <?php echo esc_attr( $i ); ?>"></span></div><?php endforeach; ?>`;
            
            var rowHtml = '<div class="acrb-admin-item-row" style="display:none;">';
            
            if(type === 'locations') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-location"></span></div>
                            <input type="hidden" name="locations[${index}][icon]" value="dashicons-location" class="acrb-icon-val">
                            <input type="text" name="locations[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Location name">`;
            } else if(type === 'features') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-car"></span></div>
                            <input type="hidden" name="features[${index}][icon]" value="dashicons-car" class="acrb-icon-val">
                            <input type="text" name="features[${index}][name]" class="acrb-admin-input acrb-admin-w-180" placeholder="Label">
                            <input type="text" name="features[${index}][value]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Value">`;
            } else if(type === 'amenities') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-yes"></span></div>
                            <input type="hidden" name="amenities[${index}][icon]" value="dashicons-yes" class="acrb-icon-val">
                            <input type="text" name="amenities[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Amenity name">`;
            }

            rowHtml += `<span class="dashicons dashicons-no-alt acrb-admin-remove-btn"></span>
                        <div class="acrb-admin-icon-dropdown">
                            <input type="text" class="acrb-icon-search-input" placeholder="Search icons...">
                            <div class="acrb-admin-icon-grid">${iconsHtml}</div>
                        </div></div>`;
            
            var $newRow = $(rowHtml);
            list.append($newRow);
            $newRow.fadeIn(200);
        });
    });
    </script>
    <?php
}