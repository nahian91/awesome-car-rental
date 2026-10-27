<?php
/**
 * ACRB Extras Tab - Full configuration for Pickup/Drop-off Locations, Surcharges, Add-on Extras, Specs, and Amenities
 * Features: Expanded Icon Library, Real-time Icon Search & Elite SaaS Layout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_extras_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // 1. SAVE LOGIC
    if ( isset( $_POST['acrb_save_extras'] ) && check_admin_referer( 'acrb_save_extras_action', 'acrb_extras_nonce' ) ) {
        
        $pickup_locs  = isset( $_POST['pickup_locations'] ) ? array_map( function( $item ) { return array_map( 'sanitize_text_field', $item ); }, wp_unslash( (array) $_POST['pickup_locations'] ) ) : array();
        $dropoff_locs = isset( $_POST['dropoff_locations'] ) ? array_map( function( $item ) { return array_map( 'sanitize_text_field', $item ); }, wp_unslash( (array) $_POST['dropoff_locations'] ) ) : array();
        $surcharges   = isset( $_POST['surcharges'] ) ? array_map( function( $item ) { return array_map( 'sanitize_text_field', $item ); }, wp_unslash( (array) $_POST['surcharges'] ) ) : array();
        $addon_extras = isset( $_POST['addon_extras'] ) ? array_map( function( $item ) { return array_map( 'sanitize_text_field', $item ); }, wp_unslash( (array) $_POST['addon_extras'] ) ) : array();
        $features     = isset( $_POST['features'] ) ? array_map( function( $item ) { return array_map( 'sanitize_text_field', $item ); }, wp_unslash( (array) $_POST['features'] ) ) : array();
        $amenities    = isset( $_POST['amenities'] ) ? array_map( function( $item ) { return array_map( 'sanitize_text_field', $item ); }, wp_unslash( (array) $_POST['amenities'] ) ) : array();

        update_option( 'acrb_custom_pickup_locations', array_values( $pickup_locs ) );
        update_option( 'acrb_custom_dropoff_locations', array_values( $dropoff_locs ) );
        update_option( 'acrb_location_surcharges', array_values( $surcharges ) );
        update_option( 'acrb_global_addon_extras', array_values( $addon_extras ) );
        update_option( 'acrb_custom_features', array_values( $features ) );
        update_option( 'acrb_custom_amenities', array_values( $amenities ) );

        // Clear dashboard transient caches
        delete_transient( 'acrb_dashboard_revenue' );

        echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'All extra configurations saved.', 'awesome-car-rental' ) . '</p></div>';
    }

    // 2. DATA FETCHING
    $legacy_locations = get_option( 'acrb_custom_locations', array() );
    $pickup_locs      = get_option( 'acrb_custom_pickup_locations', $legacy_locations );
    $dropoff_locs     = get_option( 'acrb_custom_dropoff_locations', $legacy_locations );
    $surcharges       = get_option( 'acrb_location_surcharges', array() );
    $addon_extras     = get_option( 'acrb_global_addon_extras', array() );
    $features         = get_option( 'acrb_custom_features', array() );
    $amenities        = get_option( 'acrb_custom_amenities', array() );
    $currency         = get_option( 'acrb_currency', '£' );

    // 3. EXPANDED ICON LIBRARY (Over 60 icons)
    $icon_lib = array(
        'dashicons-location', 'dashicons-location-alt', 'dashicons-airplane', 'dashicons-building', 'dashicons-store', 'dashicons-admin-home',
        'dashicons-car', 'dashicons-performance', 'dashicons-dashboard', 'dashicons-admin-tools', 'dashicons-oil', 'dashicons-hammer',
        'dashicons-flash', 'dashicons-star-filled', 'dashicons-yes', 'dashicons-shield', 'dashicons-heart', 'dashicons-awards',
        'dashicons-unlock', 'dashicons-lock', 'dashicons-info', 'dashicons-hidden', 'dashicons-visibility', 'dashicons-admin-network',
        'dashicons-palmtree', 'dashicons-universal-access', 'dashicons-tickets-alt', 'dashicons-groups', 'dashicons-businessman',
        'dashicons-money-alt', 'dashicons-calendar-alt', 'dashicons-clock', 'dashicons-warning', 'dashicons-thumbs-up', 'dashicons-phone',
        'dashicons-email-alt', 'dashicons-share', 'dashicons-cloud', 'dashicons-cart', 'dashicons-products', 'dashicons-tag',
        'dashicons-insert', 'dashicons-move', 'dashicons-exit', 'dashicons-external', 'dashicons-rest-view', 'dashicons-camera',
        'dashicons-images-alt2', 'dashicons-video-alt3', 'dashicons-database', 'dashicons-database-export', 'dashicons-update'
    );
    ?>

    <style>
        /* Modern SaaS Layout Theme */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-dash-wrap *, .acrb-dash-wrap *::before, .acrb-dash-wrap *::after { box-sizing: border-box; }
        
        /* Ultra-Premium Top Header Bar */
        .acrb-header-flex { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); 
            border: 1px solid #e2e8f0; 
            border-top: 3px solid #3b82f6;
            border-radius: 14px; 
            padding: 24px 32px; 
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03); 
            margin-bottom: 28px; 
            position: relative;
        }
        .acrb-title-wrapper { display: flex; align-items: center; gap: 18px; width: 100%; }
        .acrb-title-icon { width: 48px; height: 48px; min-width: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
        .acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-title-content { display: flex; flex-direction: column; gap: 4px; }
        .acrb-main-title { font-size: 22px; font-weight: 700; margin: 0; color: #0f172a; letter-spacing: -0.5px; line-height: 1.2; }
        .acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; line-height: 1.4; }

        .acrb-admin-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-admin-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; }
        .acrb-admin-card-title { margin: 0; font-size: 16px; font-weight: 600; color: #0f172a; flex: 1; }
        
        .acrb-admin-row-list { display: flex; flex-direction: column; gap: 12px; }
        .acrb-admin-item-row { display: flex; gap: 12px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; position: relative; transition: all 0.2s ease; }
        .acrb-admin-item-row:hover { border-color: #cbd5e1; background: #fff; }
        
        /* Icon Selector UI */
        .acrb-admin-icon-trigger { cursor: pointer; background: #fff; border: 1px solid #cbd5e1; padding: 5px; border-radius: 6px; min-width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; transition: 0.2s; color: #334155; }
        .acrb-admin-icon-trigger:hover { border-color: #3b82f6; color: #3b82f6; }
        .acrb-admin-icon-dropdown { display: none; position: absolute; top: 52px; left: 12px; z-index: 1000; background: #fff; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 14px; width: 280px; border-radius: 10px; }
        
        /* Search Box inside Dropdown */
        .acrb-icon-search-input { width: 100%; margin-bottom: 10px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
        
        .acrb-admin-icon-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; max-height: 180px; overflow-y: auto; }
        .acrb-admin-icon-opt { padding: 8px; cursor: pointer; text-align: center; border-radius: 6px; border: 1px solid transparent; color: #334155; }
        .acrb-admin-icon-opt:hover { background: #eff6ff; border-color: #3b82f6; color: #3b82f6; }
        .acrb-admin-icon-opt.hidden { display: none; }
        
        .acrb-admin-input, .acrb-admin-select { border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; height: 38px; font-size: 13px; color: #1e293b; background: #fff; }
        .acrb-admin-input:focus, .acrb-admin-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 1px #3b82f6; outline: none; }
        .acrb-admin-flex-grow { flex: 1; }
        .acrb-admin-w-180 { width: 180px; }
        .acrb-admin-w-140 { width: 140px; }
        
        .acrb-admin-remove-btn { color: #b91c1c; cursor: pointer; opacity: 0.6; transition: 0.2s; padding: 6px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-admin-remove-btn:hover { opacity: 1; background: #fee2e2; border-radius: 6px; }
        
        .acrb-admin-add-btn { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; color: #3b82f6; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s; background: #eff6ff; padding: 8px 14px; border-radius: 8px; border: 1px solid #bfdbfe; width: fit-content; }
        .acrb-admin-add-btn:hover { background: #dbeafe; color: #2563eb; }

        .acrb-admin-footer { margin-top: 24px; padding: 20px 28px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; display: flex; justify-content: flex-end; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-admin-btn-save { background: #3b82f6; color: #fff; border: none; padding: 10px 28px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; height: 40px; }
        .acrb-admin-btn-save:hover { background: #2563eb; }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <div class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-plus-alt"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Fleet Extras & Amenities', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'Configure pickup/drop-off locations, location surcharges, rentable add-on extras, specs, and amenities.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </div>

        <form method="post" id="acrb-extras-form">
            <?php wp_nonce_field( 'acrb_save_extras_action', 'acrb_extras_nonce' ); ?>
            
            <!-- Pickup Locations Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-location" style="color:#3b82f6;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Pickup Locations', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-pickup-locations-list" class="acrb-admin-row-list">
                    <?php foreach ( $pickup_locs as $idx => $l ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $l['icon'] ?? 'dashicons-location' ); ?>"></span></div>
                            <input type="hidden" name="pickup_locations[<?php echo esc_attr( $idx ); ?>][icon]" value="<?php echo esc_attr( $l['icon'] ?? 'dashicons-location' ); ?>" class="acrb-icon-val">
                            <input type="text" name="pickup_locations[<?php echo esc_attr( $idx ); ?>][name]" value="<?php echo esc_attr( $l['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Pickup Location Name', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                            
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="<?php esc_attr_e( 'Search icons...', 'awesome-car-rental' ); ?>">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach ( $icon_lib as $i ) : ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>"><span class="dashicons <?php echo esc_attr( $i ); ?>"></span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-pickup-locations-list" data-type="pickup_locations">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span> 
                    <span><?php esc_html_e( 'Add Pickup Location', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <!-- Drop-off Locations Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-location-alt" style="color:#3b82f6;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Drop-off Locations', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-dropoff-locations-list" class="acrb-admin-row-list">
                    <?php foreach ( $dropoff_locs as $idx => $l ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $l['icon'] ?? 'dashicons-location-alt' ); ?>"></span></div>
                            <input type="hidden" name="dropoff_locations[<?php echo esc_attr( $idx ); ?>][icon]" value="<?php echo esc_attr( $l['icon'] ?? 'dashicons-location-alt' ); ?>" class="acrb-icon-val">
                            <input type="text" name="dropoff_locations[<?php echo esc_attr( $idx ); ?>][name]" value="<?php echo esc_attr( $l['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Drop-off Location Name', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                            
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="<?php esc_attr_e( 'Search icons...', 'awesome-car-rental' ); ?>">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach ( $icon_lib as $i ) : ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>"><span class="dashicons <?php echo esc_attr( $i ); ?>"></span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-dropoff-locations-list" data-type="dropoff_locations">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span> 
                    <span><?php esc_html_e( 'Add Drop-off Location', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <!-- Location Surcharges Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-money-alt" style="color:#3b82f6;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Location Surcharges & Fees', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-surcharges-list" class="acrb-admin-row-list">
                    <?php foreach ( $surcharges as $idx => $s ) : ?>
                        <div class="acrb-admin-item-row">
                            <input type="text" name="surcharges[<?php echo esc_attr( $idx ); ?>][location]" value="<?php echo esc_attr( $s['location'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Hub / Location Name (e.g. Airport Terminal)', 'awesome-car-rental' ); ?>">
                            <input type="number" step="0.01" name="surcharges[<?php echo esc_attr( $idx ); ?>][fee]" value="<?php echo esc_attr( $s['fee'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-w-140" placeholder="Fee (<?php echo esc_html( $currency ); ?>)">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-surcharges-list" data-type="surcharges">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span> 
                    <span><?php esc_html_e( 'Add Location Surcharge', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <!-- Global Add-on Extras Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-cart" style="color:#3b82f6;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Global Add-on Extras (Rentables)', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-addon-extras-list" class="acrb-admin-row-list">
                    <?php foreach ( $addon_extras as $idx => $addon ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $addon['icon'] ?? 'dashicons-star-filled' ); ?>"></span></div>
                            <input type="hidden" name="addon_extras[<?php echo esc_attr( $idx ); ?>][icon]" value="<?php echo esc_attr( $addon['icon'] ?? 'dashicons-star-filled' ); ?>" class="acrb-icon-val">
                            <input type="text" name="addon_extras[<?php echo esc_attr( $idx ); ?>][name]" value="<?php echo esc_attr( $addon['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Add-on Name (e.g. GPS Navigation, Child Seat)', 'awesome-car-rental' ); ?>">
                            <input type="number" step="0.01" name="addon_extras[<?php echo esc_attr( $idx ); ?>][price]" value="<?php echo esc_attr( $addon['price'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-w-140" placeholder="Price (<?php echo esc_html( $currency ); ?>)">
                            <select name="addon_extras[<?php echo esc_attr( $idx ); ?>][type]" class="acrb-admin-select acrb-admin-w-140">
                                <option value="day" <?php selected( $addon['type'] ?? 'day', 'day' ); ?>><?php esc_html_e( 'Per Day', 'awesome-car-rental' ); ?></option>
                                <option value="flat" <?php selected( $addon['type'] ?? 'day', 'flat' ); ?>><?php esc_html_e( 'Flat Fee', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                            
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="<?php esc_attr_e( 'Search icons...', 'awesome-car-rental' ); ?>">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach ( $icon_lib as $i ) : ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>"><span class="dashicons <?php echo esc_attr( $i ); ?>"></span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-addon-extras-list" data-type="addon_extras">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span> 
                    <span><?php esc_html_e( 'Add Rentable Extra', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <!-- Technical Specifications Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-admin-tools" style="color:#3b82f6;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-features-list" class="acrb-admin-row-list">
                    <?php foreach ( $features as $idx => $f ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $f['icon'] ?? 'dashicons-car' ); ?>"></span></div>
                            <input type="hidden" name="features[<?php echo (int) $idx; ?>][icon]" value="<?php echo esc_attr( $f['icon'] ?? 'dashicons-car' ); ?>" class="acrb-icon-val">
                            <input type="text" name="features[<?php echo (int) $idx; ?>][name]" value="<?php echo esc_attr( $f['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-w-180" placeholder="<?php echo esc_attr__( 'Label', 'awesome-car-rental' ); ?>">
                            <input type="text" name="features[<?php echo (int) $idx; ?>][value]" value="<?php echo esc_attr( $f['value'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_attr__( 'Value', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                            
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="<?php esc_attr_e( 'Search icons...', 'awesome-car-rental' ); ?>">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach ( $icon_lib as $i ) : ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>">
                                            <span class="dashicons <?php echo esc_attr( $i ); ?>"></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-features-list" data-type="features">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span> 
                    <span><?php esc_html_e( 'Add Specification', 'awesome-car-rental' ); ?></span>
                </div>
            </div>

            <!-- Comfort Amenities Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-star-filled" style="color:#3b82f6;"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Comfort Amenities', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-amenities-list" class="acrb-admin-row-list">
                    <?php foreach ( $amenities as $idx => $a ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $a['icon'] ?? 'dashicons-yes' ); ?>"></span></div>
                            <input type="hidden" name="amenities[<?php echo (int) $idx; ?>][icon]" value="<?php echo esc_attr( $a['icon'] ?? 'dashicons-yes' ); ?>" class="acrb-icon-val">
                            <input type="text" name="amenities[<?php echo (int) $idx; ?>][name]" value="<?php echo esc_attr( $a['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_attr__( 'Amenity Name', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                            
                            <div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="<?php esc_attr_e( 'Search icons...', 'awesome-car-rental' ); ?>">
                                <div class="acrb-admin-icon-grid">
                                    <?php foreach ( $icon_lib as $i ) : ?>
                                        <div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>">
                                            <span class="dashicons <?php echo esc_attr( $i ); ?>"></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="acrb-admin-add-btn" data-target="acrb-amenities-list" data-type="amenities">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span> 
                    <span><?php esc_html_e( 'Add Amenity', 'awesome-car-rental' ); ?></span>
                </div>
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
            
            if (type === 'pickup_locations') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-location"></span></div>
                            <input type="hidden" name="pickup_locations[${index}][icon]" value="dashicons-location" class="acrb-icon-val">
                            <input type="text" name="pickup_locations[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Pickup location name">`;
            } else if (type === 'dropoff_locations') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-location-alt"></span></div>
                            <input type="hidden" name="dropoff_locations[${index}][icon]" value="dashicons-location-alt" class="acrb-icon-val">
                            <input type="text" name="dropoff_locations[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Drop-off location name">`;
            } else if (type === 'surcharges') {
                rowHtml += `<input type="text" name="surcharges[${index}][location]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Hub / Location Name">
                            <input type="number" step="0.01" name="surcharges[${index}][fee]" class="acrb-admin-input acrb-admin-w-140" placeholder="Fee (<?php echo esc_js( $currency ); ?>)">`;
            } else if (type === 'addon_extras') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-star-filled"></span></div>
                            <input type="hidden" name="addon_extras[${index}][icon]" value="dashicons-star-filled" class="acrb-icon-val">
                            <input type="text" name="addon_extras[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Add-on Name (e.g. GPS)">
                            <input type="number" step="0.01" name="addon_extras[${index}][price]" class="acrb-admin-input acrb-admin-w-140" placeholder="Price (<?php echo esc_js( $currency ); ?>)">
                            <select name="addon_extras[${index}][type]" class="acrb-admin-select acrb-admin-w-140">
                                <option value="day">Per Day</option>
                                <option value="flat">Flat Fee</option>
                            </select>`;
            } else if (type === 'features') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-car"></span></div>
                            <input type="hidden" name="features[${index}][icon]" value="dashicons-car" class="acrb-icon-val">
                            <input type="text" name="features[${index}][name]" class="acrb-admin-input acrb-admin-w-180" placeholder="Label">
                            <input type="text" name="features[${index}][value]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Value">`;
            } else if (type === 'amenities') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-yes"></span></div>
                            <input type="hidden" name="amenities[${index}][icon]" value="dashicons-yes" class="acrb-icon-val">
                            <input type="text" name="amenities[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="Amenity name">`;
            }

            rowHtml += `<span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="Remove"></span>`;
            
            if (type !== 'surcharges') {
                rowHtml += `<div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="Search icons...">
                                <div class="acrb-admin-icon-grid">${iconsHtml}</div>
                            </div>`;
            }

            rowHtml += `</div>`;
            
            var $newRow = $(rowHtml);
            list.append($newRow);
            $newRow.fadeIn(200);
        });
    });
    </script>
    <?php
}