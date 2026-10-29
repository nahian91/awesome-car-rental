<?php
/**
 * ACRB Extras Tab - Fleet Locations, Specs, Amenities, Insurance & Border Cross Fees
 * Location: admin/extras.php
 *
 * @package Awesome_Car_Rental
 * @since   2.5.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_extras_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    $saved_notice = false;

    // 1. SAVE LOGIC
    if ( isset( $_POST['acrb_save_extras'] ) && check_admin_referer( 'acrb_save_extras_action', 'acrb_extras_nonce' ) ) {
        $pickup_locs   = isset( $_POST['pickup_locations'] ) ? array_map( static function( $item ) { return array_map( 'sanitize_text_field', (array) $item ); }, wp_unslash( (array) $_POST['pickup_locations'] ) ) : array();
        $dropoff_locs  = isset( $_POST['dropoff_locations'] ) ? array_map( static function( $item ) { return array_map( 'sanitize_text_field', (array) $item ); }, wp_unslash( (array) $_POST['dropoff_locations'] ) ) : array();
        $features      = isset( $_POST['features'] ) ? array_map( static function( $item ) { return array_map( 'sanitize_text_field', (array) $item ); }, wp_unslash( (array) $_POST['features'] ) ) : array();
        $amenities     = isset( $_POST['amenities'] ) ? array_map( static function( $item ) { return array_map( 'sanitize_text_field', (array) $item ); }, wp_unslash( (array) $_POST['amenities'] ) ) : array();
        $insurance     = isset( $_POST['insurance_plans'] ) ? array_map( static function( $item ) { return array_map( 'sanitize_text_field', (array) $item ); }, wp_unslash( (array) $_POST['insurance_plans'] ) ) : array();
        $border_fees   = isset( $_POST['border_cross_fees'] ) ? array_map( static function( $item ) { return array_map( 'sanitize_text_field', (array) $item ); }, wp_unslash( (array) $_POST['border_cross_fees'] ) ) : array();

        update_option( 'acrb_custom_pickup_locations', array_values( $pickup_locs ) );
        update_option( 'acrb_custom_dropoff_locations', array_values( $dropoff_locs ) );
        update_option( 'acrb_custom_features', array_values( $features ) );
        update_option( 'acrb_custom_amenities', array_values( $amenities ) );
        update_option( 'acrb_insurance_plans', array_values( $insurance ) );
        update_option( 'acrb_border_cross_fees', array_values( $border_fees ) );

        delete_transient( 'acrb_dashboard_revenue' );
        $saved_notice = true;
    }

    // 2. DATA FETCHING
    $legacy_locations = get_option( 'acrb_custom_locations', array() );
    $pickup_locs      = get_option( 'acrb_custom_pickup_locations', $legacy_locations );
    $dropoff_locs     = get_option( 'acrb_custom_dropoff_locations', $legacy_locations );
    $features         = get_option( 'acrb_custom_features', array() );
    $amenities        = get_option( 'acrb_custom_amenities', array() );
    $insurance_plans  = get_option( 'acrb_insurance_plans', array() );
    $border_cross_fees = get_option( 'acrb_border_cross_fees', array() );
    $currency         = get_option( 'acrb_currency', '$' );

    // 3. ICON LIBRARY
    $icon_lib = array(
        'dashicons-location', 'dashicons-location-alt', 'dashicons-airplane', 'dashicons-building', 'dashicons-store', 'dashicons-admin-home',
        'dashicons-car', 'dashicons-performance', 'dashicons-dashboard', 'dashicons-admin-tools', 'dashicons-oil', 'dashicons-hammer',
        'dashicons-flash', 'dashicons-star-filled', 'dashicons-yes', 'dashicons-shield', 'dashicons-shield-alt', 'dashicons-heart', 'dashicons-awards',
        'dashicons-unlock', 'dashicons-lock', 'dashicons-info', 'dashicons-hidden', 'dashicons-visibility', 'dashicons-admin-network',
        'dashicons-palmtree', 'dashicons-universal-access', 'dashicons-tickets-alt', 'dashicons-groups', 'dashicons-businessman',
        'dashicons-money-alt', 'dashicons-calendar-alt', 'dashicons-clock', 'dashicons-warning', 'dashicons-thumbs-up', 'dashicons-phone',
        'dashicons-email-alt', 'dashicons-share', 'dashicons-cloud', 'dashicons-cart', 'dashicons-products', 'dashicons-tag',
        'dashicons-insert', 'dashicons-move', 'dashicons-exit', 'dashicons-external', 'dashicons-rest-view', 'dashicons-camera',
        'dashicons-images-alt2', 'dashicons-video-alt3', 'dashicons-database', 'dashicons-database-export', 'dashicons-update'
    );
    ?>

    <style>
        .acrb-extras-wrap {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-extras-wrap *,
        .acrb-extras-wrap *::before,
        .acrb-extras-wrap *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-extras-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .acrb-extras-title-wrapper {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-extras-title-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            background: #eef2ff;
            color: #4f46e5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.15);
            flex-shrink: 0;
        }
        .acrb-extras-title-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-extras-title-content h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-extras-title-content p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Success Notice */
        .acrb-extras-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 22px;
        }

        /* Card Section */
        .acrb-admin-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px -4px rgba(15, 23, 42, 0.04);
        }
        .acrb-admin-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .acrb-admin-card-header .dashicons {
            color: #4f46e5;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        .acrb-admin-card-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        /* Row Repeater */
        .acrb-admin-row-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 14px;
        }
        .acrb-admin-item-row {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .acrb-admin-icon-trigger {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #4f46e5;
            transition: all 0.15s ease;
        }
        .acrb-admin-icon-trigger:hover {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .acrb-admin-icon-trigger .dashicons {
            font-size: 19px;
            width: 19px;
            height: 19px;
        }

        /* Form Inputs */
        .acrb-admin-input,
        .acrb-admin-select {
            display: block;
            height: 40px !important;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 0 12px !important;
            font-size: 13.5px !important;
            color: #1e293b !important;
            font-family: inherit !important;
            outline: none !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        .acrb-admin-input:focus,
        .acrb-admin-select:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        }
        .acrb-admin-flex-grow {
            flex: 1;
            width: 100% !important;
        }
        .acrb-admin-w-160 {
            width: 160px !important;
        }
        .acrb-admin-w-180 {
            width: 180px !important;
        }

        /* Action Buttons */
        .acrb-admin-remove-btn {
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            font-size: 18px;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .acrb-admin-remove-btn:hover {
            color: #dc2626;
            background: #fee2e2;
        }
        .acrb-admin-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            border: 1px dashed #94a3b8;
            color: #334155;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .acrb-admin-add-btn:hover {
            background: #eef2ff;
            border-color: #4f46e5;
            color: #4f46e5;
        }
        .acrb-admin-add-btn .dashicons {
            font-size: 15px;
            width: 15px;
            height: 15px;
        }

        /* Dashicon Dropdown Modal */
        .acrb-admin-icon-dropdown {
            display: none;
            position: absolute;
            top: 52px;
            left: 14px;
            z-index: 100;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px;
            width: 320px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
        }
        .acrb-icon-search-input {
            width: 100% !important;
            height: 34px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            padding: 0 10px !important;
            font-size: 12.5px !important;
            margin-bottom: 10px !important;
            outline: none !important;
        }
        .acrb-icon-search-input:focus {
            border-color: #4f46e5 !important;
        }
        .acrb-admin-icon-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
            max-height: 180px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .acrb-admin-icon-opt {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            border: 1px solid #f1f5f9;
            border-radius: 6px;
            cursor: pointer;
            color: #475569;
            transition: all 0.12s ease;
        }
        .acrb-admin-icon-opt:hover {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
        }
        .acrb-admin-icon-opt.hidden {
            display: none;
        }
        .acrb-admin-icon-opt .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        /* Save Footer */
        .acrb-admin-footer {
            margin-top: 26px;
            display: flex;
            justify-content: flex-start;
        }
        .acrb-admin-btn-save {
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
            transition: all 0.15s ease;
        }
        .acrb-admin-btn-save:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }
    </style>

    <div class="acrb-extras-wrap">
        <!-- Header Banner -->
        <header class="acrb-extras-header-flex">
            <div class="acrb-extras-title-wrapper">
                <div class="acrb-extras-title-icon">
                    <span class="dashicons dashicons-plus-alt"></span>
                </div>
                <div class="acrb-extras-title-content">
                    <h1><?php esc_html_e( 'Fleet Extras & Amenities', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Configure pickup/drop-off locations, technical specifications, amenities, insurance options, and border cross fees.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php if ( $saved_notice ) : ?>
            <div class="acrb-extras-alert-success">
                ✓ <?php esc_html_e( 'All extra configurations saved successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="acrb-extras-form">
            <?php wp_nonce_field( 'acrb_save_extras_action', 'acrb_extras_nonce' ); ?>

            <!-- 1. Pickup Locations Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-location"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Pickup Locations', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-pickup-locations-list" class="acrb-admin-row-list">
                    <?php foreach ( $pickup_locs as $idx => $l ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $l['icon'] ?? 'dashicons-location' ); ?>"></span></div>
                            <input type="hidden" name="pickup_locations[<?php echo esc_attr( (string) $idx ); ?>][icon]" value="<?php echo esc_attr( $l['icon'] ?? 'dashicons-location' ); ?>" class="acrb-icon-val">
                            <input type="text" name="pickup_locations[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $l['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Pickup Location Name', 'awesome-car-rental' ); ?>">
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
                <button type="button" class="acrb-admin-add-btn" data-target="acrb-pickup-locations-list" data-type="pickup_locations">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php esc_html_e( 'Add Pickup Location', 'awesome-car-rental' ); ?></span>
                </button>
            </div>

            <!-- 2. Drop-off Locations Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-location-alt"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Drop-off Locations', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-dropoff-locations-list" class="acrb-admin-row-list">
                    <?php foreach ( $dropoff_locs as $idx => $l ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $l['icon'] ?? 'dashicons-location-alt' ); ?>"></span></div>
                            <input type="hidden" name="dropoff_locations[<?php echo esc_attr( (string) $idx ); ?>][icon]" value="<?php echo esc_attr( $l['icon'] ?? 'dashicons-location-alt' ); ?>" class="acrb-icon-val">
                            <input type="text" name="dropoff_locations[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $l['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Drop-off Location Name', 'awesome-car-rental' ); ?>">
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
                <button type="button" class="acrb-admin-add-btn" data-target="acrb-dropoff-locations-list" data-type="dropoff_locations">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php esc_html_e( 'Add Drop-off Location', 'awesome-car-rental' ); ?></span>
                </button>
            </div>

            <!-- 3. Technical Specifications Card (Icon + Name Only) -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-features-list" class="acrb-admin-row-list">
                    <?php foreach ( $features as $idx => $f ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $f['icon'] ?? 'dashicons-car' ); ?>"></span></div>
                            <input type="hidden" name="features[<?php echo esc_attr( (string) $idx ); ?>][icon]" value="<?php echo esc_attr( $f['icon'] ?? 'dashicons-car' ); ?>" class="acrb-icon-val">
                            <input type="text" name="features[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $f['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Specification Name (e.g. Engine, Transmission, Fuel, Doors)', 'awesome-car-rental' ); ?>">
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
                <button type="button" class="acrb-admin-add-btn" data-target="acrb-features-list" data-type="features">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php esc_html_e( 'Add Specification', 'awesome-car-rental' ); ?></span>
                </button>
            </div>

            <!-- 4. Comfort Amenities Card -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-star-filled"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Comfort Amenities', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-amenities-list" class="acrb-admin-row-list">
                    <?php foreach ( $amenities as $idx => $a ) : ?>
                        <div class="acrb-admin-item-row">
                            <div class="acrb-admin-icon-trigger"><span class="dashicons <?php echo esc_attr( $a['icon'] ?? 'dashicons-yes' ); ?>"></span></div>
                            <input type="hidden" name="amenities[<?php echo esc_attr( (string) $idx ); ?>][icon]" value="<?php echo esc_attr( $a['icon'] ?? 'dashicons-yes' ); ?>" class="acrb-icon-val">
                            <input type="text" name="amenities[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $a['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Amenity Name (e.g. Air Conditioning, Bluetooth, Heated Seats)', 'awesome-car-rental' ); ?>">
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
                <button type="button" class="acrb-admin-add-btn" data-target="acrb-amenities-list" data-type="amenities">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php esc_html_e( 'Add Amenity', 'awesome-car-rental' ); ?></span>
                </button>
            </div>

            <!-- 5. Insurance Options Card (Repeater) -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-shield"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Insurance & Protection Plans', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-insurance-list" class="acrb-admin-row-list">
                    <?php foreach ( $insurance_plans as $idx => $ins ) : ?>
                        <div class="acrb-admin-item-row">
                            <input type="text" name="insurance_plans[<?php echo esc_attr( (string) $idx ); ?>][name]" value="<?php echo esc_attr( $ins['name'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Coverage Name (e.g. Full Comprehensive, Collision Damage Waiver)', 'awesome-car-rental' ); ?>">
                            <input type="number" step="0.01" name="insurance_plans[<?php echo esc_attr( (string) $idx ); ?>][price]" value="<?php echo esc_attr( $ins['price'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-w-160" placeholder="<?php echo esc_attr( sprintf( __( 'Price (%s)', 'awesome-car-rental' ), $currency ) ); ?>">
                            <select name="insurance_plans[<?php echo esc_attr( (string) $idx ); ?>][type]" class="acrb-admin-select acrb-admin-w-160">
                                <option value="day" <?php selected( $ins['type'] ?? 'day', 'day' ); ?>><?php esc_html_e( 'Per Day', 'awesome-car-rental' ); ?></option>
                                <option value="flat" <?php selected( $ins['type'] ?? 'day', 'flat' ); ?>><?php esc_html_e( 'Flat Fee', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="acrb-admin-add-btn" data-target="acrb-insurance-list" data-type="insurance_plans">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php esc_html_e( 'Add Insurance Option', 'awesome-car-rental' ); ?></span>
                </button>
            </div>

            <!-- 6. Border Cross Fees Card (Repeater) -->
            <div class="acrb-admin-card">
                <div class="acrb-admin-card-header">
                    <span class="dashicons dashicons-admin-network"></span>
                    <h3 class="acrb-admin-card-title"><?php esc_html_e( 'Border Cross Fees & Country Permits', 'awesome-car-rental' ); ?></h3>
                </div>
                <div id="acrb-border-cross-list" class="acrb-admin-row-list">
                    <?php foreach ( $border_cross_fees as $idx => $b ) : ?>
                        <div class="acrb-admin-item-row">
                            <input type="text" name="border_cross_fees[<?php echo esc_attr( (string) $idx ); ?>][country]" value="<?php echo esc_attr( $b['country'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php esc_attr_e( 'Country / Region Name (e.g. Canada, European Union, Mexico)', 'awesome-car-rental' ); ?>">
                            <input type="number" step="0.01" name="border_cross_fees[<?php echo esc_attr( (string) $idx ); ?>][fee]" value="<?php echo esc_attr( $b['fee'] ?? '' ); ?>" class="acrb-admin-input acrb-admin-w-160" placeholder="<?php echo esc_attr( sprintf( __( 'Fee (%s)', 'awesome-car-rental' ), $currency ) ); ?>">
                            <select name="border_cross_fees[<?php echo esc_attr( (string) $idx ); ?>][type]" class="acrb-admin-select acrb-admin-w-160">
                                <option value="flat" <?php selected( $b['type'] ?? 'flat', 'flat' ); ?>><?php esc_html_e( 'Flat Fee', 'awesome-car-rental' ); ?></option>
                                <option value="day" <?php selected( $b['type'] ?? 'flat', 'day' ); ?>><?php esc_html_e( 'Per Day', 'awesome-car-rental' ); ?></option>
                            </select>
                            <span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php esc_attr_e( 'Remove', 'awesome-car-rental' ); ?>"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="acrb-admin-add-btn" data-target="acrb-border-cross-list" data-type="border_cross_fees">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span><?php esc_html_e( 'Add Border Cross Permit', 'awesome-car-rental' ); ?></span>
                </button>
            </div>

            <!-- Save Action Button -->
            <div class="acrb-admin-footer">
                <button type="submit" name="acrb_save_extras" class="acrb-admin-btn-save">
                    <?php esc_html_e( 'Save All Extra Settings', 'awesome-car-rental' ); ?>
                </button>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Toggle Icon Dropdown & Focus Search
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

        // Real-time Icon Search
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

        // Row Removal
        $(document).on('click', '.acrb-admin-remove-btn', function() {
            $(this).closest('.acrb-admin-item-row').fadeOut(180, function() { $(this).remove(); });
        });

        // Dynamic Row Addition
        $('.acrb-admin-add-btn').on('click', function(e) {
            e.preventDefault();
            var targetId = $(this).data('target');
            var type = $(this).data('type');
            var list = $('#' + targetId);
            var index = new Date().getTime();
            var iconsHtml = `<?php foreach ( $icon_lib as $i ) : ?><div class="acrb-admin-icon-opt" data-icon="<?php echo esc_attr( $i ); ?>"><span class="dashicons <?php echo esc_attr( $i ); ?>"></span></div><?php endforeach; ?>`;

            var rowHtml = '<div class="acrb-admin-item-row" style="display:none;">';

            if (type === 'pickup_locations') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-location"></span></div>
                            <input type="hidden" name="pickup_locations[${index}][icon]" value="dashicons-location" class="acrb-icon-val">
                            <input type="text" name="pickup_locations[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_js( __( 'Pickup Location Name', 'awesome-car-rental' ) ); ?>">`;
            } else if (type === 'dropoff_locations') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-location-alt"></span></div>
                            <input type="hidden" name="dropoff_locations[${index}][icon]" value="dashicons-location-alt" class="acrb-icon-val">
                            <input type="text" name="dropoff_locations[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_js( __( 'Drop-off Location Name', 'awesome-car-rental' ) ); ?>">`;
            } else if (type === 'features') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-car"></span></div>
                            <input type="hidden" name="features[${index}][icon]" value="dashicons-car" class="acrb-icon-val">
                            <input type="text" name="features[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_js( __( 'Specification Name (e.g. Engine, Fuel, Seats)', 'awesome-car-rental' ) ); ?>">`;
            } else if (type === 'amenities') {
                rowHtml += `<div class="acrb-admin-icon-trigger"><span class="dashicons dashicons-yes"></span></div>
                            <input type="hidden" name="amenities[${index}][icon]" value="dashicons-yes" class="acrb-icon-val">
                            <input type="text" name="amenities[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_js( __( 'Amenity Name (e.g. Bluetooth, GPS)', 'awesome-car-rental' ) ); ?>">`;
            } else if (type === 'insurance_plans') {
                rowHtml += `<input type="text" name="insurance_plans[${index}][name]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_js( __( 'Coverage Name (e.g. Collision Damage Waiver)', 'awesome-car-rental' ) ); ?>">
                            <input type="number" step="0.01" name="insurance_plans[${index}][price]" class="acrb-admin-input acrb-admin-w-160" placeholder="<?php echo esc_js( sprintf( __( 'Price (%s)', 'awesome-car-rental' ), $currency ) ); ?>">
                            <select name="insurance_plans[${index}][type]" class="acrb-admin-select acrb-admin-w-160">
                                <option value="day"><?php echo esc_js( __( 'Per Day', 'awesome-car-rental' ) ); ?></option>
                                <option value="flat"><?php echo esc_js( __( 'Flat Fee', 'awesome-car-rental' ) ); ?></option>
                            </select>`;
            } else if (type === 'border_cross_fees') {
                rowHtml += `<input type="text" name="border_cross_fees[${index}][country]" class="acrb-admin-input acrb-admin-flex-grow" placeholder="<?php echo esc_js( __( 'Country / Region Name (e.g. Canada, Mexico)', 'awesome-car-rental' ) ); ?>">
                            <input type="number" step="0.01" name="border_cross_fees[${index}][fee]" class="acrb-admin-input acrb-admin-w-160" placeholder="<?php echo esc_js( sprintf( __( 'Fee (%s)', 'awesome-car-rental' ), $currency ) ); ?>">
                            <select name="border_cross_fees[${index}][type]" class="acrb-admin-select acrb-admin-w-160">
                                <option value="flat"><?php echo esc_js( __( 'Flat Fee', 'awesome-car-rental' ) ); ?></option>
                                <option value="day"><?php echo esc_js( __( 'Per Day', 'awesome-car-rental' ) ); ?></option>
                            </select>`;
            }

            rowHtml += `<span class="dashicons dashicons-no-alt acrb-admin-remove-btn" title="<?php echo esc_js( __( 'Remove', 'awesome-car-rental' ) ); ?>"></span>`;

            if (type !== 'insurance_plans' && type !== 'border_cross_fees') {
                rowHtml += `<div class="acrb-admin-icon-dropdown">
                                <input type="text" class="acrb-icon-search-input" placeholder="<?php echo esc_js( __( 'Search icons...', 'awesome-car-rental' ) ); ?>">
                                <div class="acrb-admin-icon-grid">${iconsHtml}</div>
                            </div>`;
            }

            rowHtml += `</div>`;

            var $newRow = $(rowHtml);
            list.append($newRow);
            $newRow.fadeIn(180);
        });
    });
    </script>
    <?php
}