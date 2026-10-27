<?php
/**
 * View Vehicle - Comprehensive Single View (SaaS Modernized)
 * Location: admin/cars/acrb-cars-view.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_view_car_tab( $car_id ) {
    $car = get_post( $car_id );
    if ( ! $car ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Vehicle not found.', 'awesome-car-rental' ) . '</p></div>';
        return;
    }

    // 1. DYNAMIC SETTINGS & DATA FETCHING
    $currency     = get_option( 'acrb_currency', '£' );
    $currency_pos = get_option( 'acrb_currency_pos', 'left' );

    $price            = get_post_meta( $car_id, 'price_per_day', true );
    $vin              = get_post_meta( $car_id, 'vin_number', true );
    $cats             = wp_get_post_terms( $car_id, 'acrb_categories' );
    $features         = get_post_meta( $car_id, 'acrb_car_features', true );
    $amenities        = get_post_meta( $car_id, 'acrb_car_amenities', true );
    $seasonal_rates   = get_post_meta( $car_id, 'acrb_seasonal_rates', true );
    $gallery_ids      = get_post_meta( $car_id, 'acrb_car_gallery', true );
    $video_url        = get_post_meta( $car_id, 'acrb_car_video', true );
    $pickup_loc       = get_post_meta( $car_id, 'acrb_default_pickup', true );
    $dropoff_loc      = get_post_meta( $car_id, 'acrb_default_dropoff', true );

    // Fleet Specifications Meta
    $vehicle_status   = get_post_meta( $car_id, 'acrb_vehicle_status', true ) ?: 'active';
    $seating_cap      = get_post_meta( $car_id, 'acrb_seating_capacity', true ) ?: 5;
    $baggage_cap      = get_post_meta( $car_id, 'acrb_baggage_capacity', true ) ?: 2;
    $transmission     = get_post_meta( $car_id, 'acrb_transmission', true ) ?: 'Automatic';
    $fuel_type        = get_post_meta( $car_id, 'acrb_fuel_type', true ) ?: 'Petrol';
    $min_rental_days  = get_post_meta( $car_id, 'acrb_min_rental_days', true ) ?: 1;
    $security_deposit = get_post_meta( $car_id, 'acrb_security_deposit', true );
    $insurance_fee    = get_post_meta( $car_id, 'acrb_insurance_fee', true );

    // Dynamic Price Formatting Logic
    $formatted_price = number_format( floatval( $price ), 2 );
    $display_price   = ( 'left' === $currency_pos ) ? $currency . $formatted_price : $formatted_price . $currency;
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
            overflow: hidden;
        }
        .acrb-title-wrapper { display: flex; align-items: center; gap: 16px; }
        .acrb-title-icon { width: 48px; height: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
        .acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-main-title { font-size: 22px; font-weight: 700; margin: 0 0 4px 0; color: #0f172a; letter-spacing: -0.5px; }
        .acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; }
        
        /* Header Actions & Buttons */
        .btn-saas { 
            background: #ffffff; 
            color: #334155; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 9px 18px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.25s ease; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            cursor: pointer;
            font-size: 13px;
            height: 38px;
            box-sizing: border-box;
        }
        .btn-saas .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; }
        .btn-saas:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-primary { background: #3b82f6; color: #fff; border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); }
        .btn-primary:hover { background: #2563eb; color: #fff; border-color: #3b82f6; }

        /* Layout Grid */
        .acrb-view-grid { display: grid; grid-template-columns: 350px 1fr; gap: 24px; }
        .acrb-sidebar-col, .acrb-main-col { display: flex; flex-direction: column; gap: 24px; }
        
        /* Cards */
        .acrb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); overflow: hidden; }
        .acrb-card-head { padding: 18px 22px; border-bottom: 1px solid #f1f5f9; background: #fafafa; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: #64748b; }
        .acrb-card-body { padding: 22px; }

        /* Media */
        .acrb-main-img { width: 100%; height: 200px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 12px; }
        .acrb-gallery-mini { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 12px; }
        .acrb-gallery-mini img { width: 100%; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; }
        .acrb-video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 10px; background: #000; }
        .acrb-video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }

        /* Specs & Tags */
        .acrb-detail-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .acrb-detail-row:last-child { border-bottom: none; padding-bottom: 0; }
        .acrb-detail-row:first-child { padding-top: 0; }
        .acrb-detail-label { color: #64748b; font-weight: 500; }
        .acrb-detail-value { font-weight: 600; color: #0f172a; text-align: right; }
        
        .acrb-tag { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; background: #f1f5f9; font-size: 12px; font-weight: 500; margin: 0 6px 6px 0; border: 1px solid #e2e8f0; color: #334155; }
        .acrb-tag-amenity { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }

        .acrb-price-large { font-size: 28px; font-weight: 700; color: #16a34a; text-align: center; display: block; letter-spacing: -0.5px; }
        .acrb-status-pill { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-maintenance { background: #fef3c7; color: #d97706; }
        .status-rented { background: #e0f2fe; color: #0369a1; }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-view-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <div class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-car"></span>
                </div>
                <div>
                    <h1 class="acrb-main-title"><?php echo esc_html( $car->post_title ); ?></h1>
                    <p class="acrb-sub-title">
                        <?php 
                        printf(
                            /* translators: 1: vehicle ID, 2: VIN number */
                            esc_html__( 'Vehicle ID: #%1$d • VIN: %2$s', 'awesome-car-rental' ),
                            absint( $car_id ),
                            $vin ? esc_html( $vin ) : '---'
                        );
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="acrb-header-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=cars&sub=edit&car_id=' . absint( $car_id ) ) ); ?>" class="btn-saas btn-primary">
                    <span class="dashicons dashicons-edit" style="font-size:14px; width:14px; height:14px;"></span> 
                    <span><?php esc_html_e( 'Edit Vehicle', 'awesome-car-rental' ); ?></span>
                </a>
            </div>
        </div>

        <div class="acrb-view-grid">
            <div class="acrb-sidebar-col">
                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Vehicle Media & Rate', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body" style="text-align:center;">
                        <?php if ( has_post_thumbnail( $car_id ) ) : ?>
                            <?php echo get_the_post_thumbnail( $car_id, 'medium', ['class' => 'acrb-main-img'] ); ?>
                        <?php else : ?>
                            <div style="width:100%; height:160px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; color:#cbd5e1; margin-bottom:12px;">
                                <span class="dashicons dashicons-car" style="font-size:40px; width:40px; height:40px;"></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $gallery_ids ) : ?>
                            <div class="acrb-gallery-mini">
                                <?php 
                                $ids = explode( ',', $gallery_ids );
                                foreach ( array_slice( $ids, 0, 8 ) as $id ) {
                                    echo wp_get_attachment_image( $id, 'thumbnail' ); 
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top:20px; padding-top:20px; border-top:1px solid #f1f5f9;">
                            <span class="acrb-price-large"><?php echo esc_html( $display_price ); ?></span>
                            <span style="color:#64748b; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Per Day', 'awesome-car-rental' ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Location Configuration', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body">
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Pick-up', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo esc_html( $pickup_loc ?: __( 'Global / Any Hub', 'awesome-car-rental' ) ); ?></span>
                        </div>
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Drop-off', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo esc_html( $dropoff_loc ?: __( 'Global / Any Hub', 'awesome-car-rental' ) ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Deposits & Insurance', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body">
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Security Deposit', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo $security_deposit !== '' ? esc_html( $currency . number_format( floatval( $security_deposit ), 2 ) ) : '—'; ?></span>
                        </div>
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Insurance Fee', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo $insurance_fee !== '' ? esc_html( $currency . number_format( floatval( $insurance_fee ), 2 ) ) : '—'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="acrb-main-col">
                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Fleet Specifications & Status', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body">
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Operational Status', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value">
                                <span class="acrb-status-pill status-<?php echo esc_attr( $vehicle_status ); ?>">
                                    <?php echo esc_html( ucfirst( $vehicle_status ) ); ?>
                                </span>
                            </span>
                        </div>
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Seating Capacity', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo absint( $seating_cap ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></span>
                        </div>
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Luggage Capacity', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo absint( $baggage_cap ); ?> <?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></span>
                        </div>
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo esc_html( $transmission ); ?></span>
                        </div>
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Fuel / Engine Type', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo esc_html( $fuel_type ); ?></span>
                        </div>
                        <div class="acrb-detail-row">
                            <span class="acrb-detail-label"><?php esc_html_e( 'Min. Rental Duration', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-detail-value"><?php echo absint( $min_rental_days ); ?> <?php esc_html_e( 'Days', 'awesome-car-rental' ); ?></span>
                        </div>
                    </div>
                </div>

                <?php if ( ! empty( $seasonal_rates ) && is_array( $seasonal_rates ) ) : ?>
                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Seasonal Rates', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body" style="padding: 0;">
                        <table class="widefat striped" style="border: none; box-shadow: none;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: #64748b;"><?php esc_html_e( 'Season Name', 'awesome-car-rental' ); ?></th>
                                    <th style="padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: #64748b;"><?php esc_html_e( 'Start Date', 'awesome-car-rental' ); ?></th>
                                    <th style="padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: #64748b;"><?php esc_html_e( 'End Date', 'awesome-car-rental' ); ?></th>
                                    <th style="padding: 12px 16px; font-size: 11px; text-transform: uppercase; color: #64748b; text-align: right;"><?php esc_html_e( 'Override Rate', 'awesome-car-rental' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $seasonal_rates as $rate ) : ?>
                                    <tr>
                                        <td style="padding: 12px 16px; font-weight: 600; color: #0f172a;"><?php echo esc_html( $rate['name'] ?? '—' ); ?></td>
                                        <td style="padding: 12px 16px; color: #334155;"><?php echo esc_html( $rate['start'] ?? '—' ); ?></td>
                                        <td style="padding: 12px 16px; color: #334155;"><?php echo esc_html( $rate['end'] ?? '—' ); ?></td>
                                        <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: #16a34a;"><?php echo esc_html( $currency . number_format( floatval( $rate['price'] ?? 0 ), 2 ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Description', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body">
                        <div style="line-height:1.6; color:#334155; font-size:13px;">
                            <?php 
                            if ( ! empty( $car->post_content ) ) {
                                echo wp_kses_post( wpautop( $car->post_content ) );
                            } else {
                                echo '<em style="color:#64748b;">' . esc_html__( 'No description provided.', 'awesome-car-rental' ) . '</em>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Specifications & Features', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body">
                        <div style="margin-bottom:20px;">
                            <?php if ( $cats ) : foreach ( $cats as $c ) : ?>
                                <span class="acrb-tag" style="background:#eff6ff; color:#3b82f6; border-color:#bfdbfe;">
                                    <span class="dashicons dashicons-category"></span> <?php echo esc_html( $c->name ); ?>
                                </span>
                            <?php endforeach; endif; ?>
                            
                            <?php 
                            if ( ! empty( $features ) ) : 
                                foreach ( $features as $f ) : 
                                    if ( ! empty( $f['enabled'] ) && ! empty( $f['value'] ) ) :
                            ?>
                                    <span class="acrb-tag">
                                        <span class="dashicons <?php echo esc_attr( $f['icon'] ); ?>"></span> 
                                        <?php echo esc_html( $f['value'] ); ?>
                                    </span>
                            <?php 
                                    endif; 
                                endforeach; 
                            endif; 
                            ?>
                        </div>

                        <h4 style="font-size:11px; text-transform:uppercase; color:#64748b; margin:0 0 10px 0; font-weight:600; letter-spacing:0.5px;"><?php esc_html_e( 'Included Amenities', 'awesome-car-rental' ); ?></h4>
                        <div>
                            <?php 
                            $has_amenities = false;
                            if ( ! empty( $amenities ) ) {
                                foreach ( $amenities as $a ) {
                                    if ( ! empty( $a['enabled'] ) ) {
                                        $has_amenities = true;
                                        echo '<span class="acrb-tag acrb-tag-amenity">
                                                <span class="dashicons ' . esc_attr( $a['icon'] ) . '"></span> 
                                                ' . esc_html( $a['name'] ) . '
                                              </span>';
                                    }
                                }
                            }
                            if ( ! $has_amenities ) {
                                echo '<span style="color:#64748b; font-size:13px;">---</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <?php if ( $video_url ) : ?>
                <div class="acrb-card">
                    <div class="acrb-card-head"><?php esc_html_e( 'Vehicle Video', 'awesome-car-rental' ); ?></div>
                    <div class="acrb-card-body">
                        <div class="acrb-video-container">
                            <?php 
                            $embed_url = str_replace( 'watch?v=', 'embed/', $video_url );
                            echo '<iframe src="' . esc_url( $embed_url ) . '" frameborder="0" allowfullscreen></iframe>'; 
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 24px;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=cars&sub=all' ) ); ?>" class="btn-saas">
                <span class="dashicons dashicons-arrow-left-alt2"></span> 
                <span><?php esc_html_e( 'Return to Fleet', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
    </div>
    <?php
}