<?php
/**
 * Car CRM View Component (`cars-view.php`)
 * Location: admin/cars/cars-view.php
 *
 * @package Awesome_Car_Rental
 * @since   3.2.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

$car_id          = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : ( isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0 );
$car             = $car_id ? get_post( $car_id ) : null;
$currency_symbol = get_option( 'acrb_currency', '$' );
$currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$rental_unit     = get_option( 'acrb_rental_unit', 'day' );
$page_slug       = 'awesome_car_rental';
$base_url        = admin_url( 'admin.php?page=' . $page_slug . '&tab=cars' );
$back_url        = add_query_arg( array( 'sub' => 'list' ), $base_url );

$format_price = static function( float $val ) use ( $currency_symbol, $currency_pos ): string {
    $formatted = number_format( $val, 2 );
    return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
};

if ( ! $car || 'acrb_cars' !== $car->post_type ) {
    ?>
    <div class="acrb-car-view-notfound">
        <p><?php esc_html_e( 'Vehicle profile not found or has been removed from the fleet.', 'awesome-car-rental' ); ?></p>
        <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-car-btn-back">
            &larr; <?php esc_html_e( 'Back to Fleet Inventory', 'awesome-car-rental' ); ?>
        </a>
    </div>
    <?php
    return;
}

// Basic Metadata
$c_title          = esc_html( $car->post_title );
$c_price          = floatval( get_post_meta( $car_id, 'price_per_day', true ) );
$c_vin            = esc_html( (string) ( get_post_meta( $car_id, 'vin', true ) ?: '—' ) );
$c_plate          = esc_html( (string) ( get_post_meta( $car_id, 'plate', true ) ?: '—' ) );
$c_status         = sanitize_key( (string) ( get_post_meta( $car_id, 'acrb_vehicle_status', true ) ?: 'active' ) );
$seats            = absint( get_post_meta( $car_id, 'acrb_seating_capacity', true ) ?: 5 );
$bags             = absint( get_post_meta( $car_id, 'acrb_baggage_capacity', true ) ?: 2 );
$trans            = esc_html( (string) ( get_post_meta( $car_id, 'acrb_transmission', true ) ?: 'Automatic' ) );
$fuel             = esc_html( (string) ( get_post_meta( $car_id, 'acrb_fuel_type', true ) ?: 'Petrol' ) );
$min_rental       = absint( get_post_meta( $car_id, 'acrb_min_rental_days', true ) ?: 1 );
$deposit          = floatval( get_post_meta( $car_id, 'acrb_security_deposit', true ) );
$insurance        = floatval( get_post_meta( $car_id, 'acrb_insurance_fee', true ) );
$default_pickup   = esc_html( (string) ( get_post_meta( $car_id, 'acrb_default_pickup', true ) ?: '—' ) );
$default_dropoff  = esc_html( (string) ( get_post_meta( $car_id, 'acrb_default_dropoff', true ) ?: '—' ) );
$video_url        = esc_url( (string) get_post_meta( $car_id, 'acrb_car_video', true ) );
$gallery_ids      = (string) ( get_post_meta( $car_id, 'acrb_car_gallery', true ) ?: '' );
$thumb_url        = get_the_post_thumbnail_url( $car_id, 'large' );

// Collections & Arrays
$saved_features   = get_post_meta( $car_id, 'acrb_car_features', true ) ?: array();
$saved_amenities  = get_post_meta( $car_id, 'acrb_car_amenities', true ) ?: array();
$seasonal_rates   = get_post_meta( $car_id, 'acrb_seasonal_rates', true ) ?: array();

// Category Mapping
$selected_category = get_post_meta( $car_id, 'category', true );
if ( empty( $selected_category ) ) {
    $terms             = wp_get_post_terms( $car_id, 'acrb_categories', array( 'fields' => 'names' ) );
    $selected_category = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : __( 'Uncategorized', 'awesome-car-rental' );
}

$edit_url = add_query_arg( array( 'sub' => 'edit', 'car_id' => $car_id ), $base_url );
$del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'car_id' => $car_id ), $base_url ), 'acrb_del_car_' . $car_id );

$status_class = 'acrb-car-status-active';
$status_label = __( 'Available', 'awesome-car-rental' );

if ( 'maintenance' === $c_status ) {
    $status_class = 'acrb-car-status-maintenance';
    $status_label = __( 'Maintenance', 'awesome-car-rental' );
} elseif ( 'rented' === $c_status ) {
    $status_class = 'acrb-car-status-rented';
    $status_label = __( 'In Service', 'awesome-car-rental' );
}
?>

<style>
    .acrb-car-view-wrapper {
        width: 100%;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #1e293b;
    }
    .acrb-car-view-wrapper *,
    .acrb-car-view-wrapper *::before,
    .acrb-car-view-wrapper *::after {
        box-sizing: border-box;
    }

    /* Top Bar Navigation Link */
    .acrb-car-view-nav-bar {
        margin-bottom: 20px;
    }
    .acrb-car-btn-back {
        background: #ffffff;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 9px 16px;
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
    }
    .acrb-car-btn-back:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .acrb-car-btn-back .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    /* Main Grid Layout */
    .acrb-car-view-grid {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 24px;
        align-items: start;
    }

    /* Card Scaffolding */
    .acrb-car-view-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
    }
    .acrb-car-view-card-title {
        margin: 0 0 16px 0;
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .acrb-car-view-card-title .dashicons {
        color: #4f46e5;
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    /* Left Dossier Card */
    .acrb-car-dossier {
        text-align: center;
    }
    .acrb-car-dossier-thumb {
        width: 100%;
        height: 200px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .acrb-car-dossier-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .acrb-car-dossier-thumb-placeholder {
        color: #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .acrb-car-dossier-thumb-placeholder .dashicons {
        font-size: 52px;
        width: 52px;
        height: 52px;
    }
    .acrb-car-dossier-name {
        margin: 0 0 8px 0;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.3;
    }

    /* Badges */
    .acrb-car-category-badge {
        background: #eff6ff;
        color: #4f46e5;
        border: 1px solid #dbeafe;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
    }
    .acrb-car-status-badge {
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
        white-space: nowrap;
    }
    .acrb-car-status-active {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    .acrb-car-status-maintenance {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .acrb-car-status-rented {
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }

    /* Meta Rows */
    .acrb-car-meta-list {
        margin-top: 20px;
        text-align: left;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 13px;
    }
    .acrb-car-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .acrb-car-meta-label {
        color: #64748b;
        font-weight: 500;
        flex-shrink: 0;
    }
    .acrb-car-meta-val {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
    }
    .acrb-car-rate-highlight {
        color: #059669;
        font-size: 15px;
        font-weight: 800;
    }

    /* Action Buttons */
    .acrb-car-dossier-actions {
        margin-top: 20px;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .acrb-car-btn-edit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 11px 18px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .acrb-car-btn-edit:hover {
        background: #4338ca;
        color: #ffffff;
        transform: translateY(-1px);
    }
    .acrb-car-btn-edit .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .acrb-car-btn-del {
        color: #dc2626;
        background: #fff5f5;
        border: 1px solid #fee2e2;
        padding: 9px 14px;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .acrb-car-btn-del:hover {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }
    .acrb-car-btn-del .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
    }

    /* Right Specifications Column */
    .acrb-car-details-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Specifications Matrix */
    .acrb-car-spec-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
    .acrb-car-spec-tile {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
    }
    .acrb-car-spec-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: block;
    }
    .acrb-car-spec-val {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        margin-top: 4px;
    }

    /* Financial Sub-Bar */
    .acrb-car-financial-bar {
        margin-top: 18px;
        border-top: 1px solid #f1f5f9;
        padding-top: 14px;
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
    }
    .acrb-car-fin-item {
        font-size: 13px;
    }
    .acrb-car-fin-label {
        color: #64748b;
        margin-right: 4px;
    }
    .acrb-car-fin-val {
        color: #0f172a;
        font-weight: 700;
    }

    /* Seasonal Overrides List */
    .acrb-seasonal-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .acrb-seasonal-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
    }
    .acrb-seasonal-name {
        color: #0f172a;
        font-weight: 700;
    }
    .acrb-seasonal-range {
        color: #64748b;
        margin-left: 8px;
        font-size: 12px;
    }
    .acrb-seasonal-rate {
        color: #059669;
        font-weight: 800;
    }

    /* Technical Features Grid */
    .acrb-features-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .acrb-feature-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
    }
    .acrb-feature-name {
        color: #334155;
        font-weight: 600;
    }
    .acrb-feature-val {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        background: #eef2ff;
        color: #4f46e5;
        padding: 2px 8px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }

    /* Amenities Pill Wrap */
    .acrb-amenities-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .acrb-amenity-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        color: #334155;
        font-weight: 600;
    }
    .acrb-amenity-pill .dashicons {
        color: #4f46e5;
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    /* Gallery Grid */
    .acrb-gallery-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 12px;
    }
    .acrb-gallery-photo-box {
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .acrb-gallery-photo-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* Overview Content */
    .acrb-car-overview-text {
        color: #475569;
        font-size: 13.5px;
        line-height: 1.6;
    }
    .acrb-car-overview-text p:first-child {
        margin-top: 0;
    }
    .acrb-car-overview-text p:last-child {
        margin-bottom: 0;
    }

    /* Not Found Box */
    .acrb-car-view-notfound {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    @media (max-width: 960px) {
        .acrb-car-view-grid {
            grid-template-columns: 1fr;
        }
        .acrb-car-spec-grid,
        .acrb-features-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="acrb-car-view-wrapper">

    <!-- Back to Directory Navigation -->
    <div class="acrb-car-view-nav-bar">
        <a href="<?php echo esc_url( $back_url ); ?>" class="acrb-car-btn-back">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <span><?php esc_html_e( 'Back to Fleet Inventory', 'awesome-car-rental' ); ?></span>
        </a>
    </div>

    <div class="acrb-car-view-grid">

        <!-- Vehicle Profile Dossier Card -->
        <div class="acrb-car-view-card acrb-car-dossier">
            <div class="acrb-car-dossier-thumb">
                <?php if ( $thumb_url ) : ?>
                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $c_title ); ?>">
                <?php else : ?>
                    <div class="acrb-car-dossier-thumb-placeholder">
                        <span class="dashicons dashicons-car"></span>
                    </div>
                <?php endif; ?>
            </div>

            <h3 class="acrb-car-dossier-name"><?php echo esc_html( $c_title ); ?></h3>
            <span class="acrb-car-category-badge"><?php echo esc_html( $selected_category ); ?></span>

            <div class="acrb-car-meta-list">
                <div class="acrb-car-meta-row">
                    <span class="acrb-car-meta-label"><?php esc_html_e( 'Daily Rate', 'awesome-car-rental' ); ?></span>
                    <strong class="acrb-car-meta-val acrb-car-rate-highlight">
                        <?php echo esc_html( $format_price( $c_price ) ); ?> <span style="font-size:11.5px; font-weight:500; color:#64748b;">/ <?php echo esc_html( $rental_unit ); ?></span>
                    </strong>
                </div>

                <div class="acrb-car-meta-row">
                    <span class="acrb-car-meta-label"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></span>
                    <span class="acrb-car-status-badge <?php echo esc_attr( $status_class ); ?>">
                        ● <?php echo esc_html( $status_label ); ?>
                    </span>
                </div>

                <div class="acrb-car-meta-row">
                    <span class="acrb-car-meta-label"><?php esc_html_e( 'VIN Identifier', 'awesome-car-rental' ); ?></span>
                    <strong class="acrb-car-meta-val" style="font-family:ui-monospace,SFMono-Regular,monospace;"><?php echo esc_html( $c_vin ); ?></strong>
                </div>

                <div class="acrb-car-meta-row">
                    <span class="acrb-car-meta-label"><?php esc_html_e( 'License Plate', 'awesome-car-rental' ); ?></span>
                    <strong class="acrb-car-meta-val" style="font-family:ui-monospace,SFMono-Regular,monospace;"><?php echo esc_html( $c_plate ); ?></strong>
                </div>

                <div class="acrb-car-meta-row">
                    <span class="acrb-car-meta-label"><?php esc_html_e( 'Min. Rental Duration', 'awesome-car-rental' ); ?></span>
                    <strong class="acrb-car-meta-val"><?php echo esc_html( (string) $min_rental ); ?> <?php echo esc_html( ucfirst( $rental_unit ) . ( $min_rental > 1 ? 's' : '' ) ); ?></strong>
                </div>

                <div class="acrb-car-meta-row">
                    <span class="acrb-car-meta-label"><?php esc_html_e( 'Primary Pickup', 'awesome-car-rental' ); ?></span>
                    <strong class="acrb-car-meta-val"><?php echo esc_html( $default_pickup ); ?></strong>
                </div>

                <div class="acrb-car-meta-row">
                    <span class="acrb-car-meta-label"><?php esc_html_e( 'Primary Drop-off', 'awesome-car-rental' ); ?></span>
                    <strong class="acrb-car-meta-val"><?php echo esc_html( $default_dropoff ); ?></strong>
                </div>
            </div>

            <div class="acrb-car-dossier-actions">
                <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-car-btn-edit">
                    <span class="dashicons dashicons-edit"></span>
                    <span><?php esc_html_e( 'Edit Fleet Vehicle', 'awesome-car-rental' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-car-btn-del" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this fleet vehicle?', 'awesome-car-rental' ); ?>');">
                    <span class="dashicons dashicons-trash"></span>
                    <span><?php esc_html_e( 'Delete Vehicle', 'awesome-car-rental' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Specifications & Details Column -->
        <div class="acrb-car-details-stack">

            <!-- Fleet Specifications -->
            <div class="acrb-car-view-card">
                <h3 class="acrb-car-view-card-title">
                    <span class="dashicons dashicons-performance"></span>
                    <span><?php esc_html_e( 'Core Specifications', 'awesome-car-rental' ); ?></span>
                </h3>

                <div class="acrb-car-spec-grid">
                    <div class="acrb-car-spec-tile">
                        <span class="acrb-car-spec-label"><?php esc_html_e( 'Transmission', 'awesome-car-rental' ); ?></span>
                        <div class="acrb-car-spec-val"><?php echo esc_html( $trans ); ?></div>
                    </div>
                    <div class="acrb-car-spec-tile">
                        <span class="acrb-car-spec-label"><?php esc_html_e( 'Engine & Fuel', 'awesome-car-rental' ); ?></span>
                        <div class="acrb-car-spec-val"><?php echo esc_html( $fuel ); ?></div>
                    </div>
                    <div class="acrb-car-spec-tile">
                        <span class="acrb-car-spec-label"><?php esc_html_e( 'Passenger Capacity', 'awesome-car-rental' ); ?></span>
                        <div class="acrb-car-spec-val"><?php echo esc_html( (string) $seats ); ?> <?php esc_html_e( 'Seats', 'awesome-car-rental' ); ?></div>
                    </div>
                    <div class="acrb-car-spec-tile">
                        <span class="acrb-car-spec-label"><?php esc_html_e( 'Luggage Allowance', 'awesome-car-rental' ); ?></span>
                        <div class="acrb-car-spec-val"><?php echo esc_html( (string) $bags ); ?> <?php esc_html_e( 'Bags', 'awesome-car-rental' ); ?></div>
                    </div>
                </div>

                <?php if ( $deposit > 0 || $insurance > 0 ) : ?>
                    <div class="acrb-car-financial-bar">
                        <?php if ( $deposit > 0 ) : ?>
                            <div class="acrb-car-fin-item">
                                <span class="acrb-car-fin-label"><?php esc_html_e( 'Security Deposit Hold:', 'awesome-car-rental' ); ?></span>
                                <strong class="acrb-car-fin-val"><?php echo esc_html( $format_price( $deposit ) ); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ( $insurance > 0 ) : ?>
                            <div class="acrb-car-fin-item">
                                <span class="acrb-car-fin-label"><?php esc_html_e( 'Mandatory Insurance Fee:', 'awesome-car-rental' ); ?></span>
                                <strong class="acrb-car-fin-val"><?php echo esc_html( $format_price( $insurance ) ); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Seasonal Rate Overrides -->
            <?php if ( ! empty( $seasonal_rates ) && is_array( $seasonal_rates ) ) : ?>
                <div class="acrb-car-view-card">
                    <h3 class="acrb-car-view-card-title">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php esc_html_e( 'Seasonal Rate Overrides', 'awesome-car-rental' ); ?></span>
                    </h3>
                    <div class="acrb-seasonal-list">
                        <?php foreach ( $seasonal_rates as $rate ) : ?>
                            <div class="acrb-seasonal-item">
                                <div>
                                    <strong class="acrb-seasonal-name"><?php echo esc_html( $rate['name'] ?? __( 'Season', 'awesome-car-rental' ) ); ?></strong>
                                    <span class="acrb-seasonal-range">(<?php echo esc_html( $rate['start'] ?? '' ); ?> &rarr; <?php echo esc_html( $rate['end'] ?? '' ); ?>)</span>
                                </div>
                                <strong class="acrb-seasonal-rate">
                                    <?php echo esc_html( $format_price( floatval( $rate['price'] ?? 0 ) ) ); ?> / <?php echo esc_html( $rental_unit ); ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Technical Specifications -->
            <?php if ( ! empty( $saved_features ) && is_array( $saved_features ) ) : ?>
                <div class="acrb-car-view-card">
                    <h3 class="acrb-car-view-card-title">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span><?php esc_html_e( 'Assigned Technical Specifications', 'awesome-car-rental' ); ?></span>
                    </h3>
                    <div class="acrb-features-grid">
                        <?php foreach ( $saved_features as $feat ) : 
                            if ( empty( $feat['enabled'] ) ) {
                                continue;
                            }
                        ?>
                            <div class="acrb-feature-item">
                                <span class="acrb-feature-name"><?php echo esc_html( $feat['name'] ?? '' ); ?></span>
                                <span class="acrb-feature-val"><?php echo esc_html( $feat['value'] ?? '—' ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Comfort Amenities & Add-on Extras -->
            <?php if ( ! empty( $saved_amenities ) && is_array( $saved_amenities ) ) : ?>
                <div class="acrb-car-view-card">
                    <h3 class="acrb-car-view-card-title">
                        <span class="dashicons dashicons-star-empty"></span>
                        <span><?php esc_html_e( 'Included Amenities & Add-ons', 'awesome-car-rental' ); ?></span>
                    </h3>
                    <div class="acrb-amenities-wrap">
                        <?php foreach ( $saved_amenities as $amenity ) : 
                            if ( empty( $amenity['enabled'] ) ) {
                                continue;
                            }
                        ?>
                            <div class="acrb-amenity-pill">
                                <span class="dashicons <?php echo esc_attr( $amenity['icon'] ?? 'dashicons-yes' ); ?>"></span>
                                <span><?php echo esc_html( $amenity['name'] ?? '' ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Photo Gallery Showcase -->
            <?php if ( ! empty( $gallery_ids ) ) : 
                $img_ids = explode( ',', $gallery_ids );
            ?>
                <div class="acrb-car-view-card">
                    <h3 class="acrb-car-view-card-title">
                        <span class="dashicons dashicons-images-alt2"></span>
                        <span><?php esc_html_e( 'Vehicle Gallery', 'awesome-car-rental' ); ?></span>
                    </h3>
                    <div class="acrb-gallery-preview-grid">
                        <?php foreach ( $img_ids as $img_id ) : 
                            $img_src = wp_get_attachment_image_url( (int) $img_id, 'medium' );
                            if ( $img_src ) :
                        ?>
                            <div class="acrb-gallery-photo-box">
                                <img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $c_title ); ?>">
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Overview Description -->
            <?php if ( ! empty( $car->post_content ) ) : ?>
                <div class="acrb-car-view-card">
                    <h3 class="acrb-car-view-card-title">
                        <span class="dashicons dashicons-editor-alignleft"></span>
                        <span><?php esc_html_e( 'Overview & Description', 'awesome-car-rental' ); ?></span>
                    </h3>
                    <div class="acrb-car-overview-text">
                        <?php echo wp_kses_post( wpautop( $car->post_content ) ); ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>