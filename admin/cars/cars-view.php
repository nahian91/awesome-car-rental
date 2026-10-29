<?php
/**
 * Car CRM View Component (`cars-view.php`)
 * Location: admin/cars/cars-view.php
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$car_id          = isset( $_GET['car_id'] ) ? absint( $_GET['car_id'] ) : 0;
$car             = $car_id ? get_post( $car_id ) : null;
$currency_symbol = get_option( 'acrb_currency', '£' );
$page_slug       = 'awesome_car_rental';
$base_url        = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars' );

if ( ! $car || 'acrb_cars' !== $car->post_type ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Vehicle not found.', 'awesome-car-rental' ) . '</p></div>';
    return;
}

// Basic Metadata
$c_title          = esc_html( $car->post_title );
$c_price          = floatval( get_post_meta( $car_id, 'price_per_day', true ) );
$c_vin            = esc_html( get_post_meta( $car_id, 'vin', true ) ?: '—' );
$c_plate          = esc_html( get_post_meta( $car_id, 'plate', true ) ?: '—' );
$c_status         = sanitize_key( get_post_meta( $car_id, 'acrb_vehicle_status', true ) ?: 'active' );
$seats            = absint( get_post_meta( $car_id, 'acrb_seating_capacity', true ) ?: 5 );
$bags             = absint( get_post_meta( $car_id, 'acrb_baggage_capacity', true ) ?: 2 );
$trans            = esc_html( get_post_meta( $car_id, 'acrb_transmission', true ) ?: 'Automatic' );
$fuel             = esc_html( get_post_meta( $car_id, 'acrb_fuel_type', true ) ?: 'Petrol' );
$min_rental       = absint( get_post_meta( $car_id, 'acrb_min_rental_days', true ) ?: 1 );
$deposit          = floatval( get_post_meta( $car_id, 'acrb_security_deposit', true ) );
$insurance        = floatval( get_post_meta( $car_id, 'acrb_insurance_fee', true ) );
$default_pickup   = esc_html( get_post_meta( $car_id, 'acrb_default_pickup', true ) ?: '—' );
$default_dropoff  = esc_html( get_post_meta( $car_id, 'acrb_default_dropoff', true ) ?: '—' );
$video_url        = esc_url( get_post_meta( $car_id, 'acrb_car_video', true ) );
$gallery_ids      = get_post_meta( $car_id, 'acrb_car_gallery', true ) ?: '';
$thumb_url        = get_the_post_thumbnail_url( $car_id, 'large' );

// Collections & Arrays
$saved_features   = get_post_meta( $car_id, 'acrb_car_features', true ) ?: array();
$saved_amenities  = get_post_meta( $car_id, 'acrb_car_amenities', true ) ?: array();
$seasonal_rates   = get_post_meta( $car_id, 'acrb_seasonal_rates', true ) ?: array();

// Category Mapping
$selected_category = get_post_meta( $car_id, 'category', true );
if ( empty( $selected_category ) ) {
    $terms = wp_get_post_terms( $car_id, 'acrb_categories', array( 'fields' => 'names' ) );
    $selected_category = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : __( 'Uncategorized', 'awesome-car-rental' );
}

$edit_url = add_query_arg( array( 'sub' => 'edit', 'car_id' => $car_id ), $base_url );
$back_url = add_query_arg( array( 'sub' => 'list' ), $base_url );
?>

<div style="margin-bottom: 20px;">
    <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
        <span class="dashicons dashicons-arrow-left-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
        <?php esc_html_e( 'Back to Fleet List', 'awesome-car-rental' ); ?>
    </a>
</div>

<div style="display: grid; grid-template-columns: 340px 1fr; gap: 24px; align-items: start;">
    <!-- Vehicle Profile Dossier Card -->
    <div class="acrb-card" style="text-align: center;">
        <div style="width: 100%; height: 190px; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">
            <?php if ( $thumb_url ) : ?>
                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else : ?>
                <span class="dashicons dashicons-car" style="font-size: 48px; width: 48px; height: 48px; color: #cbd5e1;"></span>
            <?php endif; ?>
        </div>
        
        <h3 style="margin: 0 0 6px; font-size: 18px; font-weight: 800; color: #0f172a;"><?php echo esc_html( $c_title ); ?></h3>
        <span style="background: #eff6ff; color: #3b82f6; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
            <?php echo esc_html( $selected_category ); ?>
        </span>

        <div style="margin-top: 20px; text-align: left; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Daily Rate', 'awesome-car-rental' ); ?></span>
                <strong style="color: #059669; font-size: 15px;"><?php echo esc_html( $currency_symbol . number_format( $c_price, 2 ) ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></span>
                <?php 
                $is_act = ( 'active' === $c_status );
                $bg     = $is_act ? '#ecfdf5' : '#fef3c7';
                $color  = $is_act ? '#059669' : '#d97706';
                ?>
                <span style="background:<?php echo esc_attr( $bg ); ?>; color:<?php echo esc_attr( $color ); ?>; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                    ● <?php echo esc_html( ucfirst( $c_status ) ); ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Min. Rental Days', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( (string) $min_rental ); ?> <?php esc_html_e( 'Days', 'awesome-car-rental' ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Default Pickup', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $default_pickup ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Default Drop-off', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $default_dropoff ); ?></strong>
            </div>
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-primary" style="width: 100%;">
                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span><?php esc_html_e( 'Edit Vehicle', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
    </div>

    <!-- Specifications & Details Column -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Fleet Specifications -->
        <div class="acrb-card">
            <h2 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px;"><?php esc_html_e( 'Fleet Specifications', 'awesome-car-rental' ); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></span>
                    <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;"><?php echo esc_html( $trans ); ?></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Engine / Fuel', 'awesome-car-rental' ); ?></span>
                    <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;"><?php echo esc_html( $fuel ); ?></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Passenger Capacity', 'awesome-car-rental' ); ?></span>
                    <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;"><?php echo esc_html( (string) $seats ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Luggage Capacity', 'awesome-car-rental' ); ?></span>
                    <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;"><?php echo esc_html( (string) $bags ); ?> <?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></div>
                </div>
            </div>

            <?php if ( $deposit > 0 || $insurance > 0 ) : ?>
                <div style="margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; gap: 24px;">
                    <?php if ( $deposit > 0 ) : ?>
                        <div><span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Security Deposit:', 'awesome-car-rental' ); ?></span> <strong style="color: #0f172a;"><?php echo esc_html( $currency_symbol . number_format( $deposit, 2 ) ); ?></strong></div>
                    <?php endif; ?>
                    <?php if ( $insurance > 0 ) : ?>
                        <div><span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Insurance Fee:', 'awesome-car-rental' ); ?></span> <strong style="color: #0f172a;"><?php echo esc_html( $currency_symbol . number_format( $insurance, 2 ) ); ?></strong></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Seasonal Rates -->
        <?php if ( ! empty( $seasonal_rates ) && is_array( $seasonal_rates ) ) : ?>
            <div class="acrb-card">
                <h2 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px;"><?php esc_html_e( 'Seasonal Rate Overrides', 'awesome-car-rental' ); ?></h2>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ( $seasonal_rates as $rate ) : ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px;">
                            <div>
                                <strong style="color: #0f172a;"><?php echo esc_html( $rate['name'] ?? 'Season' ); ?></strong>
                                <span style="color: #64748b; margin-left: 8px; font-size: 12px;">(<?php echo esc_html( $rate['start'] ?? '' ); ?> → <?php echo esc_html( $rate['end'] ?? '' ); ?>)</span>
                            </div>
                            <strong style="color: #059669;"><?php echo esc_html( $currency_symbol . number_format( floatval( $rate['price'] ?? 0 ), 2 ) ); ?> / day</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Active Technical Specifications & Features -->
        <?php if ( ! empty( $saved_features ) && is_array( $saved_features ) ) : ?>
            <div class="acrb-card">
                <h2 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px;"><?php esc_html_e( 'Assigned Technical Specifications', 'awesome-car-rental' ); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <?php foreach ( $saved_features as $feat ) : 
                        if ( empty( $feat['enabled'] ) ) continue;
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px;">
                            <span style="color: #334155; font-weight: 600;"><?php echo esc_html( $feat['name'] ?? '' ); ?></span>
                            <span style="font-family: monospace; background: #eef2ff; color: #4f46e5; padding: 2px 8px; border-radius: 6px; font-weight: 700;"><?php echo esc_html( $feat['value'] ?? '—' ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Active Comfort Amenities & Add-ons -->
        <?php if ( ! empty( $saved_amenities ) && is_array( $saved_amenities ) ) : ?>
            <div class="acrb-card">
                <h2 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px;"><?php esc_html_e( 'Included Amenities & Add-on Extras', 'awesome-car-rental' ); ?></h2>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ( $saved_amenities as $amenity ) : 
                        if ( empty( $amenity['enabled'] ) ) continue;
                    ?>
                        <div style="display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 8px; font-size: 13px; color: #334155; font-weight: 600;">
                            <span class="dashicons <?php echo esc_attr( $amenity['icon'] ?? 'dashicons-yes' ); ?>" style="color: #4f46e5; font-size: 16px; width: 16px; height: 16px;"></span>
                            <span><?php echo esc_html( $amenity['name'] ?? '' ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Image Gallery Preview -->
        <?php if ( ! empty( $gallery_ids ) ) : 
            $img_ids = explode( ',', $gallery_ids );
        ?>
            <div class="acrb-card">
                <h2 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px;"><?php esc_html_e( 'Vehicle Photo Gallery', 'awesome-car-rental' ); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 10px;">
                    <?php foreach ( $img_ids as $img_id ) : 
                        $img_src = wp_get_attachment_image_url( (int) $img_id, 'medium' );
                        if ( $img_src ) :
                    ?>
                        <div style="aspect-ratio: 1; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc;">
                            <img src="<?php echo esc_url( $img_src ); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Vehicle Description -->
        <?php if ( ! empty( $car->post_content ) ) : ?>
            <div class="acrb-card">
                <h2 style="margin-top: 0; font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px;"><?php esc_html_e( 'Vehicle Overview & Description', 'awesome-car-rental' ); ?></h2>
                <div style="color: #475569; font-size: 14px; line-height: 1.6;">
                    <?php echo wp_kses_post( wpautop( $car->post_content ) ); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>