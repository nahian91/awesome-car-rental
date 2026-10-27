<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Full Vehicle Editor - SaaS Style with Single Column, Accordions, Seasonal Rates & Fleet Specs
 * Secured with nonces and optimized data handling.
 */
function acrb_add_edit_car_tab( $edit_car_id = 0 ) {
    $car = $edit_car_id ? get_post( $edit_car_id ) : null;

    // 1. DATA PREPARATION
    $global_features  = get_option( 'acrb_custom_features', array() );
    $global_amenities = get_option( 'acrb_custom_amenities', array() );
    $global_locations = get_option( 'acrb_custom_locations', array() );

    // 2. FORM PROCESSING & SECURITY CHECK
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['acrb_car_name'] ) ) {
        // Verify Nonce and Capabilities
        if ( ! isset( $_POST['acrb_car_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acrb_car_nonce'] ) ), 'acrb_save_car_nonce' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security check failed. Please try again.', 'awesome-car-rental' ) . '</p></div>';
            return;
        }

        if ( current_user_can( 'manage_options' ) ) {
            $post_args = array(
                'post_type'    => 'acrb_cars',
                'post_title'   => sanitize_text_field( wp_unslash( $_POST['acrb_car_name'] ) ),
                'post_content' => isset( $_POST['acrb_car_desc'] ) ? wp_kses_post( wp_unslash( $_POST['acrb_car_desc'] ) ) : '',
                'post_status'  => 'publish',
            );

            $is_update = (bool) $car;

            if ( $is_update ) {
                $post_args['ID'] = $edit_car_id;
                wp_update_post( $post_args );
            } else {
                $edit_car_id = wp_insert_post( $post_args );
            }

            if ( $edit_car_id && ! is_wp_error( $edit_car_id ) ) {
                update_post_meta( $edit_car_id, '_thumbnail_id', absint( $_POST['acrb_car_thumbnail_id'] ?? 0 ) );
                update_post_meta( $edit_car_id, 'acrb_car_video', esc_url_raw( $_POST['acrb_car_video'] ?? '' ) );
                update_post_meta( $edit_car_id, 'acrb_car_gallery', sanitize_text_field( $_POST['acrb_car_gallery'] ?? '' ) );
                update_post_meta( $edit_car_id, 'price_per_day', floatval( $_POST['acrb_car_price'] ?? 0 ) );
                
                // New Fleet Specification Meta Fields
                update_post_meta( $edit_car_id, 'acrb_vehicle_status', sanitize_text_field( $_POST['acrb_vehicle_status'] ?? 'active' ) );
                update_post_meta( $edit_car_id, 'acrb_seating_capacity', absint( $_POST['acrb_seating_capacity'] ?? 5 ) );
                update_post_meta( $edit_car_id, 'acrb_baggage_capacity', absint( $_POST['acrb_baggage_capacity'] ?? 2 ) );
                update_post_meta( $edit_car_id, 'acrb_transmission', sanitize_text_field( $_POST['acrb_transmission'] ?? 'Automatic' ) );
                update_post_meta( $edit_car_id, 'acrb_fuel_type', sanitize_text_field( $_POST['acrb_fuel_type'] ?? 'Petrol' ) );
                update_post_meta( $edit_car_id, 'acrb_min_rental_days', absint( $_POST['acrb_min_rental_days'] ?? 1 ) );
                update_post_meta( $edit_car_id, 'acrb_security_deposit', floatval( $_POST['acrb_security_deposit'] ?? 0 ) );
                update_post_meta( $edit_car_id, 'acrb_insurance_fee', floatval( $_POST['acrb_insurance_fee'] ?? 0 ) );

                update_post_meta( $edit_car_id, 'acrb_default_pickup', sanitize_text_field( $_POST['acrb_default_pickup'] ?? '' ) );
                update_post_meta( $edit_car_id, 'acrb_default_dropoff', sanitize_text_field( $_POST['acrb_default_dropoff'] ?? '' ) );
                update_post_meta( $edit_car_id, 'acrb_car_features', map_deep( wp_unslash( $_POST['car_features'] ?? array() ), 'sanitize_text_field' ) );
                update_post_meta( $edit_car_id, 'acrb_car_amenities', map_deep( wp_unslash( $_POST['car_amenities'] ?? array() ), 'sanitize_text_field' ) );
                update_post_meta( $edit_car_id, 'acrb_seasonal_rates', map_deep( wp_unslash( $_POST['seasonal_rates'] ?? array() ), 'sanitize_text_field' ) );

                if ( isset( $_POST['acrb_car_cat'] ) ) {
                    wp_set_post_terms( $edit_car_id, array( intval( $_POST['acrb_car_cat'] ) ), 'acrb_categories' );
                }

                // Clear transient caches if any
                delete_transient( 'acrb_dashboard_revenue' );

                $success_msg = $is_update 
                    ? __( 'Vehicle updated successfully.', 'awesome-car-rental' ) 
                    : __( 'New vehicle created successfully.', 'awesome-car-rental' );

                echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html( $success_msg ) . '</p></div>';
            }
        }
    }

    $saved_features   = get_post_meta( $edit_car_id, 'acrb_car_features', true ) ?: array();
    $saved_amenities  = get_post_meta( $edit_car_id, 'acrb_car_amenities', true ) ?: array();
    $seasonal_rates   = get_post_meta( $edit_car_id, 'acrb_seasonal_rates', true ) ?: array();
    $gallery_ids      = get_post_meta( $edit_car_id, 'acrb_car_gallery', true ) ?: '';
    $video_url        = get_post_meta( $edit_car_id, 'acrb_car_video', true ) ?: '';
    $thumbnail_id     = get_post_thumbnail_id( $edit_car_id );

    // Fetch new meta fields with fallbacks
    $vehicle_status   = get_post_meta( $edit_car_id, 'acrb_vehicle_status', true ) ?: 'active';
    $seating_cap      = get_post_meta( $edit_car_id, 'acrb_seating_capacity', true ) ?: 5;
    $baggage_cap      = get_post_meta( $edit_car_id, 'acrb_baggage_capacity', true ) ?: 2;
    $transmission     = get_post_meta( $edit_car_id, 'acrb_transmission', true ) ?: 'Automatic';
    $fuel_type        = get_post_meta( $edit_car_id, 'acrb_fuel_type', true ) ?: 'Petrol';
    $min_rental_days  = get_post_meta( $edit_car_id, 'acrb_min_rental_days', true ) ?: 1;
    $security_deposit = get_post_meta( $edit_car_id, 'acrb_security_deposit', true ) ?: '';
    $insurance_fee    = get_post_meta( $edit_car_id, 'acrb_insurance_fee', true ) ?: '';
    ?>

    <style>
        /* Modern SaaS Single Column Accordion Theme */
        .acrb-dash-wrap { max-width: 1000px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
        .acrb-dash-wrap *, .acrb-dash-wrap *::before, .acrb-dash-wrap *::after { box-sizing: border-box; }
        
        .acrb-field-hero { width: 100%; font-size: 22px; font-weight: 700; padding: 14px 18px; margin-bottom: 24px; border: 1px solid #cbd5e1; border-radius: 12px; background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s ease; }
        .acrb-field-hero:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; }
        
        /* Accordion Panel Styles */
        .acrb-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); overflow: hidden; margin-bottom: 20px; }
        .acrb-panel-head { padding: 18px 22px; background: #fafafa; font-weight: 600; font-size: 15px; color: #0f172a; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; border-bottom: 1px solid transparent; transition: background 0.2s; }
        .acrb-panel-head:hover { background: #f8fafc; }
        .acrb-panel.is-open .acrb-panel-head { border-bottom-color: #f1f5f9; }
        .acrb-panel-head .dashicons-arrow-down-alt2 { transition: transform 0.3s ease; color: #64748b; }
        .acrb-panel.is-open .acrb-panel-head .dashicons-arrow-down-alt2 { transform: rotate(180deg); }
        
        .acrb-panel-body { padding: 24px; display: none; }
        .acrb-panel.is-open .acrb-panel-body { display: block; }

        .acrb-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .acrb-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .acrb-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #334155; }
        
        /* Elite SaaS Inputs & Selects */
        .acrb-input, .acrb-select { 
            width: 100%; 
            height: 42px; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 0 14px; 
            font-size: 14px; 
            color: #1e293b; 
            background: #fff; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.01); 
            transition: all 0.2s ease; 
        }
        .acrb-input:hover, .acrb-select:hover { border-color: #94a3b8; }
        .acrb-input:focus, .acrb-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }

        /* Seasonal Rate Row Styling */
        .acrb-seasonal-row { display: flex; gap: 12px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 12px; }
        .acrb-seasonal-row input { flex: 1; }
        .acrb-seasonal-remove { background: #fee2e2; color: #b91c1c; border: none; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; transition: background 0.2s; }
        .acrb-seasonal-remove:hover { background: #fca5a5; }

        /* Toggle Switch */
        .acrb-switch { position: relative; display: inline-block; width: 34px; height: 18px; margin-right: 12px; flex-shrink: 0; }
        .acrb-switch input { opacity: 0; width: 0; height: 0; }
        .acrb-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 20px; }
        .acrb-slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .acrb-slider { background-color: #3b82f6; }
        input:checked + .acrb-slider:before { transform: translateX(16px); }

        /* Gallery Management UI */
        .acrb-gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
        .acrb-gallery-item { position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; padding-top: 100%; background: #f8fafc; }
        .acrb-gallery-item img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .acrb-gallery-remove { 
            position: absolute; top: 4px; right: 4px; background: rgba(185, 28, 28, 0.9); 
            color: #fff; width: 20px; height: 20px; font-size: 12px; line-height: 20px; 
            text-align: center; border-radius: 50%; cursor: pointer; z-index: 5; font-weight: bold;
        }
        .acrb-gallery-remove:hover { background: #991b1b; }

        .acrb-btn-secondary { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 18px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.25s ease; cursor: pointer; font-size: 13px; height: 40px; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        .acrb-btn-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

        .acrb-btn-save-wrap { margin-top: 30px; text-align: right; }
        .acrb-btn-save { background: #3b82f6; color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; display: inline-flex; align-items: center; justify-content: center; height: 42px; }
        .acrb-btn-save:hover { background: #2563eb; color: #fff; }

        @media(max-width: 768px) {
            .acrb-grid-2, .acrb-grid-3 { grid-template-columns: 1fr; }
            .acrb-gallery-grid { grid-template-columns: repeat(2, 1fr); }
            .acrb-seasonal-row { flex-direction: column; align-items: stretch; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <form method="post" id="acrb-car-form">
            <?php wp_nonce_field( 'acrb_save_car_nonce', 'acrb_car_nonce' ); ?>
            <input type="text" name="acrb_car_name" class="acrb-field-hero" value="<?php echo esc_attr( $car ? $car->post_title : '' ); ?>" placeholder="<?php esc_attr_e( 'Enter Vehicle Name...', 'awesome-car-rental' ); ?>" required>

            <!-- Accordion Section 1: Pricing & Taxonomy -->
            <div class="acrb-panel is-open">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Pricing & Taxonomy', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <div class="acrb-grid-2">
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Standard Daily Rate (£)', 'awesome-car-rental' ); ?></label>
                            <input type="number" step="0.01" name="acrb_car_price" class="acrb-input" value="<?php echo esc_attr( get_post_meta( $edit_car_id, 'price_per_day', true ) ); ?>">
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></label>
                            <?php 
                            $cats = get_terms( array(
                                'taxonomy'   => 'acrb_categories',
                                'hide_empty' => false,
                            ) ); 
                            ?>
                            <select name="acrb_car_cat" class="acrb-select">
                                <option value=""><?php esc_html_e( 'Select Category', 'awesome-car-rental' ); ?></option>
                                <?php foreach ( $cats as $c ) : ?>
                                    <option value="<?php echo (int) $c->term_id; ?>" <?php 
                                        if ( $car ) {
                                            $terms = wp_get_post_terms( $edit_car_id, 'acrb_categories', array( 'fields' => 'ids' ) );
                                            $current_term = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : 0;
                                            selected( $current_term, $c->term_id ); 
                                        }
                                    ?>>
                                        <?php echo esc_html( $c->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accordion Section 2: Fleet Specifications & Operational Status -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Fleet Specifications & Status', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <div class="acrb-grid-3" style="margin-bottom: 20px;">
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Operational Status', 'awesome-car-rental' ); ?></label>
                            <select name="acrb_vehicle_status" class="acrb-select">
                                <option value="active" <?php selected( $vehicle_status, 'active' ); ?>><?php esc_html_e( 'Active / Available', 'awesome-car-rental' ); ?></option>
                                <option value="maintenance" <?php selected( $vehicle_status, 'maintenance' ); ?>><?php esc_html_e( 'Under Maintenance', 'awesome-car-rental' ); ?></option>
                                <option value="rented" <?php selected( $vehicle_status, 'rented' ); ?>><?php esc_html_e( 'Currently Rented', 'awesome-car-rental' ); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Passenger Capacity (Seats)', 'awesome-car-rental' ); ?></label>
                            <input type="number" name="acrb_seating_capacity" class="acrb-input" value="<?php echo esc_attr( $seating_cap ); ?>" min="1" max="50">
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Luggage Capacity (Bags)', 'awesome-car-rental' ); ?></label>
                            <input type="number" name="acrb_baggage_capacity" class="acrb-input" value="<?php echo esc_attr( $baggage_cap ); ?>" min="0" max="20">
                        </div>
                    </div>

                    <div class="acrb-grid-3">
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></label>
                            <select name="acrb_transmission" class="acrb-select">
                                <option value="Automatic" <?php selected( $transmission, 'Automatic' ); ?>><?php esc_html_e( 'Automatic', 'awesome-car-rental' ); ?></option>
                                <option value="Manual" <?php selected( $transmission, 'Manual' ); ?>><?php esc_html_e( 'Manual', 'awesome-car-rental' ); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Fuel / Engine Type', 'awesome-car-rental' ); ?></label>
                            <select name="acrb_fuel_type" class="acrb-select">
                                <option value="Petrol" <?php selected( $fuel_type, 'Petrol' ); ?>><?php esc_html_e( 'Petrol', 'awesome-car-rental' ); ?></option>
                                <option value="Diesel" <?php selected( $fuel_type, 'Diesel' ); ?>><?php esc_html_e( 'Diesel', 'awesome-car-rental' ); ?></option>
                                <option value="Electric" <?php selected( $fuel_type, 'Electric' ); ?>><?php esc_html_e( 'Electric', 'awesome-car-rental' ); ?></option>
                                <option value="Hybrid" <?php selected( $fuel_type, 'Hybrid' ); ?>><?php esc_html_e( 'Hybrid', 'awesome-car-rental' ); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Min. Rental Duration (Days)', 'awesome-car-rental' ); ?></label>
                            <input type="number" name="acrb_min_rental_days" class="acrb-input" value="<?php echo esc_attr( $min_rental_days ); ?>" min="1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accordion Section 3: Seasonal Rates -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Seasonal Rates', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <p style="font-size: 13px; color: #64748b; margin-top: 0; margin-bottom: 16px;">
                        <?php esc_html_e( 'Define custom date ranges and override daily rates for peak seasons or holidays.', 'awesome-car-rental' ); ?>
                    </p>
                    <div id="acrb-seasonal-container">
                        <?php if ( ! empty( $seasonal_rates ) && is_array( $seasonal_rates ) ) : ?>
                            <?php foreach ( $seasonal_rates as $index => $rate ) : ?>
                                <div class="acrb-seasonal-row">
                                    <input type="text" name="seasonal_rates[<?php echo esc_attr( $index ); ?>][name]" class="acrb-input" placeholder="Season Name (e.g. Summer Peak)" value="<?php echo esc_attr( $rate['name'] ?? '' ); ?>">
                                    <input type="date" name="seasonal_rates[<?php echo esc_attr( $index ); ?>][start]" class="acrb-input" value="<?php echo esc_attr( $rate['start'] ?? '' ); ?>">
                                    <input type="date" name="seasonal_rates[<?php echo esc_attr( $index ); ?>][end]" class="acrb-input" value="<?php echo esc_attr( $rate['end'] ?? '' ); ?>">
                                    <input type="number" step="0.01" name="seasonal_rates[<?php echo esc_attr( $index ); ?>][price]" class="acrb-input" placeholder="Rate (£)" value="<?php echo esc_attr( $rate['price'] ?? '' ); ?>">
                                    <button type="button" class="acrb-seasonal-remove">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="acrb-btn-secondary" id="acrb-add-seasonal-btn">
                        <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                        <?php esc_html_e( 'Add Seasonal Rate', 'awesome-car-rental' ); ?>
                    </button>
                </div>
            </div>

            <!-- Accordion Section 4: Location Configuration -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Location Configuration', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <div class="acrb-grid-2">
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Default Pick Up', 'awesome-car-rental' ); ?></label>
                            <select name="acrb_default_pickup" class="acrb-select">
                                <option value=""><?php esc_html_e( 'Select Location', 'awesome-car-rental' ); ?></option>
                                <?php foreach ( $global_locations as $loc ) : ?>
                                    <option value="<?php echo esc_attr( $loc['name'] ); ?>" <?php selected( get_post_meta( $edit_car_id, 'acrb_default_pickup', true ), $loc['name'] ); ?>><?php echo esc_html( $loc['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Default Drop Off', 'awesome-car-rental' ); ?></label>
                            <select name="acrb_default_dropoff" class="acrb-select">
                                <option value=""><?php esc_html_e( 'Select Location', 'awesome-car-rental' ); ?></option>
                                <?php foreach ( $global_locations as $loc ) : ?>
                                    <option value="<?php echo esc_attr( $loc['name'] ); ?>" <?php selected( get_post_meta( $edit_car_id, 'acrb_default_dropoff', true ), $loc['name'] ); ?>><?php echo esc_html( $loc['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accordion Section 5: Deposits & Insurance -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Security Deposit & Insurance Fees', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <div class="acrb-grid-2">
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Security Deposit Hold (£)', 'awesome-car-rental' ); ?></label>
                            <input type="number" step="0.01" name="acrb_security_deposit" class="acrb-input" value="<?php echo esc_attr( $security_deposit ); ?>" placeholder="0.00">
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Mandatory Insurance Fee (£)', 'awesome-car-rental' ); ?></label>
                            <input type="number" step="0.01" name="acrb_insurance_fee" class="acrb-input" value="<?php echo esc_attr( $insurance_fee ); ?>" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accordion Section 6: Media & Gallery -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Media & Gallery', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <div class="acrb-grid-2" style="margin-bottom: 20px;">
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Vehicle Cover Image', 'awesome-car-rental' ); ?></label>
                            <div id="acrb-thumb-preview" style="background:#f8fafc; margin-bottom:12px; border-radius:8px; overflow:hidden; min-height:120px; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center;">
                                <?php if ( $thumbnail_id ) { echo wp_get_attachment_image( $thumbnail_id, 'medium', false, array( 'style' => 'width:100%;height:auto;display:block;' ) ); } else { echo '<span class="dashicons dashicons-car" style="font-size:40px; width:40px; height:40px; color:#cbd5e1;"></span>'; } ?>
                            </div>
                            <input type="hidden" name="acrb_car_thumbnail_id" id="acrb_car_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>">
                            <button type="button" class="acrb-btn-secondary" id="acrb_set_thumb"><?php esc_html_e( 'Select Featured Image', 'awesome-car-rental' ); ?></button>
                        </div>
                        <div>
                            <label class="acrb-label"><?php esc_html_e( 'Video URL (YouTube/Vimeo)', 'awesome-car-rental' ); ?></label>
                            <input type="url" name="acrb_car_video" class="acrb-input" style="margin-bottom:20px;" value="<?php echo esc_url( $video_url ); ?>" placeholder="https://...">
                        </div>
                    </div>

                    <label class="acrb-label"><?php esc_html_e( 'Image Gallery', 'awesome-car-rental' ); ?></label>
                    <input type="hidden" name="acrb_car_gallery" id="acrb_gallery_ids" value="<?php echo esc_attr( $gallery_ids ); ?>">
                    <button type="button" id="acrb_manage_gallery" class="acrb-btn-secondary" style="margin-bottom:10px;"><?php esc_html_e( 'Add Images to Gallery', 'awesome-car-rental' ); ?></button>
                    
                    <div id="acrb_gallery_preview" class="acrb-gallery-grid">
                        <?php 
                        if ( $gallery_ids ) { 
                            $ids = explode( ',', $gallery_ids ); 
                            foreach ( $ids as $id ) {
                                $img = wp_get_attachment_image_src( $id, 'thumbnail' );
                                if ( $img ) {
                                    echo '<div class="acrb-gallery-item" data-id="' . esc_attr( $id ) . '">
                                            <span class="acrb-gallery-remove">×</span>
                                            <img src="' . esc_url( $img[0] ) . '">
                                          </div>';
                                }
                            } 
                        } 
                        ?>
                    </div>
                </div>
            </div>

            <!-- Accordion Section 7: Technical Specifications -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Technical Specifications', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <div class="acrb-grid-2">
                        <?php foreach ( $global_features as $idx => $g ) : 
                            $val = ''; $en = 1;
                            foreach ( $saved_features as $s ) { 
                                if ( ( $s['name'] ?? '' ) === $g['name'] ) { 
                                    $val = $s['value'] ?? ''; 
                                    $en = $s['enabled'] ?? 0; 
                                    break; 
                                } 
                            }
                        ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; padding:12px 16px; border-radius:10px; border:1px solid #e2e8f0;">
                                <div style="display:flex; align-items:center;">
                                    <label class="acrb-switch">
                                        <input type="checkbox" name="car_features[<?php echo esc_attr( $idx ); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                        <span class="acrb-slider"></span>
                                    </label>
                                    <span style="font-size:13px; font-weight:500; color:#334155;"><?php echo esc_html( $g['name'] ); ?></span>
                                    <input type="hidden" name="car_features[<?php echo esc_attr( $idx ); ?>][name]" value="<?php echo esc_attr( $g['name'] ); ?>">
                                    <input type="hidden" name="car_features[<?php echo esc_attr( $idx ); ?>][icon]" value="<?php echo esc_attr( $g['icon'] ); ?>">
                                </div>
                                <input type="text" name="car_features[<?php echo esc_attr( $idx ); ?>][value]" class="acrb-input" style="width:75px; height:36px; text-align:center;" value="<?php echo esc_attr( $val ?: ( $g['value'] ?? '' ) ); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Accordion Section 8: Amenities & Equipment -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Amenities & Equipment', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <div class="acrb-grid-2">
                        <?php foreach ( $global_amenities as $idx => $g ) : 
                            $en = 1;
                            foreach ( $saved_amenities as $s ) { 
                                if ( ( $s['name'] ?? '' ) === $g['name'] ) { 
                                    $en = $s['enabled'] ?? 0; 
                                    break; 
                                } 
                            }
                        ?>
                            <div style="display:flex; align-items:center; background:#f8fafc; padding:12px 16px; border-radius:10px; border:1px solid #e2e8f0;">
                                <label class="acrb-switch">
                                    <input type="checkbox" name="car_amenities[<?php echo esc_attr( $idx ); ?>][enabled]" value="1" <?php checked( $en, 1 ); ?>>
                                    <span class="acrb-slider"></span>
                                </label>
                                <span style="font-size:13px; font-weight:500; color:#334155;"><?php echo esc_html( $g['name'] ); ?></span>
                                <input type="hidden" name="car_amenities[<?php echo esc_attr( $idx ); ?>][name]" value="<?php echo esc_attr( $g['name'] ); ?>">
                                <input type="hidden" name="car_amenities[<?php echo esc_attr( $idx ); ?>][icon]" value="<?php echo esc_attr( $g['icon'] ); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Accordion Section 9: Description -->
            <div class="acrb-panel">
                <div class="acrb-panel-head">
                    <span><?php esc_html_e( 'Description', 'awesome-car-rental' ); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="acrb-panel-body">
                    <?php wp_editor( $car ? $car->post_content : '', 'acrb_car_desc', array( 'textarea_rows' => 8 ) ); ?>
                </div>
            </div>

            <!-- Submit Button Wrapper -->
            <div class="acrb-btn-save-wrap">
                <button type="submit" class="acrb-btn-save"><?php esc_html_e( 'Save Vehicle Changes', 'awesome-car-rental' ); ?></button>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($){
        // Accordion Toggle Behavior
        $('.acrb-panel-head').click(function(){
            $(this).parent('.acrb-panel').toggleClass('is-open');
        });

        // Dynamic Seasonal Rate Rows
        $('#acrb-add-seasonal-btn').click(function(e){
            e.preventDefault();
            var index = $('#acrb-seasonal-container .acrb-seasonal-row').length;
            var newRow = '<div class="acrb-seasonal-row">' +
                '<input type="text" name="seasonal_rates['+index+'][name]" class="acrb-input" placeholder="Season Name (e.g. Summer Peak)">' +
                '<input type="date" name="seasonal_rates['+index+'][start]" class="acrb-input">' +
                '<input type="date" name="seasonal_rates['+index+'][end]" class="acrb-input">' +
                '<input type="number" step="0.01" name="seasonal_rates['+index+'][price]" class="acrb-input" placeholder="Rate (£)">' +
                '<button type="button" class="acrb-seasonal-remove">&times;</button>' +
                '</div>';
            $('#acrb-seasonal-container').append(newRow);
        });

        $(document).on('click', '.acrb-seasonal-remove', function(){
            $(this).closest('.acrb-seasonal-row').fadeOut(200, function(){ $(this).remove(); });
        });

        // 1. Thumbnail Selection
        $('#acrb_set_thumb').click(function(e){
            e.preventDefault();
            var frame = wp.media({ title: 'Select Cover Image', button: { text: 'Use Image' }, multiple: false });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#acrb_car_thumbnail_id').val(attachment.id);
                $('#acrb-thumb-preview').html('<img src="'+attachment.url+'" style="width:100%;height:auto;display:block;">');
            }).open();
        });

        // 2. Gallery Management
        var galleryFrame;
        $('#acrb_manage_gallery').click(function(e){
            e.preventDefault();
            if(galleryFrame) { galleryFrame.open(); return; }

            galleryFrame = wp.media({ title: 'Add to Gallery', button: { text: 'Add to Gallery' }, multiple: true });
            galleryFrame.on('select', function(){
                var selection = galleryFrame.state().get('selection');
                var currentIds = $('#acrb_gallery_ids').val() ? $('#acrb_gallery_ids').val().split(',') : [];
                
                selection.map(function(attachment){
                    attachment = attachment.toJSON();
                    if($.inArray(attachment.id.toString(), currentIds) === -1){
                        currentIds.push(attachment.id);
                        $('#acrb_gallery_preview').append(
                            '<div class="acrb-gallery-item" data-id="'+attachment.id+'">' +
                            '<span class="acrb-gallery-remove">×</span>' +
                            '<img src="'+attachment.sizes.thumbnail.url+'">' +
                            '</div>'
                        );
                    }
                });
                $('#acrb_gallery_ids').val(currentIds.join(','));
            });
            galleryFrame.open();
        });

        // 3. Remove Single Image from Gallery
        $(document).on('click', '.acrb-gallery-remove', function(){
            var item = $(this).closest('.acrb-gallery-item');
            var idToRemove = item.data('id').toString();
            var currentIds = $('#acrb_gallery_ids').val().split(',');

            var updatedIds = currentIds.filter(function(id){
                return id !== idToRemove;
            });

            $('#acrb_gallery_ids').val(updatedIds.join(','));
            item.fadeOut(300, function(){ $(this).remove(); });
        });
    });
    </script>
    <?php
}