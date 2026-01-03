<?php

if ( ! defined( 'ABSPATH' ) ) exit; 
/**
 * ACRB PROFESSIONAL SINGLE CAR PAGE [acrb_single_car]
 * Features: Tabs (Overview, Specs, Gallery, Video), £ Currency, and Empty State Messages.
 */

// 1. DATA SUBMISSION HANDLER
function acrb_process_car_booking_to_db() {
    if (isset($_POST['action']) && $_POST['action'] == 'acrb_submit_booking_action') {
        
        if ( ! isset( $_POST['acrb_booking_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['acrb_booking_nonce'] ) ), 'acrb_submit_booking' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'awesome-car-rental' ) );
        }

        $car_id      = isset( $_POST['car_id'] ) ? intval( wp_unslash( $_POST['car_id'] ) ) : 0;
        $cus_name    = isset( $_POST['cus_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_name'] ) ) : '';
        $cus_email   = isset( $_POST['cus_email'] ) ? sanitize_email( wp_unslash( $_POST['cus_email'] ) ) : '';
        $cus_phone   = isset( $_POST['cus_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cus_phone'] ) ) : '';
        $pickup_loc  = isset( $_POST['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_location'] ) ) : '';
        $pickup_date = isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : '';
        $return_date = isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : '';
        $total       = isset( $_POST['order_total'] ) ? floatval( wp_unslash( $_POST['order_total'] ) ) : 0;
        $method      = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
        $user_id     = get_current_user_id();

        $booking_id = wp_insert_post( array(
            'post_type'   => 'acrb_bookings',
            'post_status' => 'publish',
            'meta_input'  => array(
                'user_id'         => absint( $user_id ),
                'car_id'          => absint( $car_id ),
                'customer_name'   => $cus_name,
                'customer_email'  => $cus_email,
                'customer_phone'  => $cus_phone,
                'pickup_location' => $pickup_loc,
                'pickup_date'     => $pickup_date,
                'return_date'     => $return_date,
                'total_price'     => $total,
                'payment_method'  => $method,
                'status'          => 'pending',
                'booking_time'    => current_time( 'mysql' ),
            ),
        ) );

        if ( $booking_id ) {
            // translators: 1: Booking ID, 2: New status name.
            $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $booking_id ), esc_html__( 'pending', 'awesome-car-rental' ) );
            wp_update_post( array( 'ID' => $booking_id, 'post_title' => $acrb_msg ) );
        }

        $success_page_id = get_option('acrb_success_page');
        $redirect_url = $success_page_id ? get_permalink($success_page_id) : home_url('/acrb-thanks/');
        $redirect_url = add_query_arg('booking_id', $booking_id, $redirect_url);
        
        wp_safe_redirect( esc_url_raw( $redirect_url ) );
        exit;
    }
}
add_action('admin_post_nopriv_acrb_submit_booking_action', 'acrb_process_car_booking_to_db');
add_action('admin_post_acrb_submit_booking_action', 'acrb_process_car_booking_to_db');

// 2. THE UI SHORTCODE
function acrb_car_details_page_shortcode() {
    
    if (!is_user_logged_in()) {
        $current_car_url = get_permalink() . ( isset( $_GET['car_id'] ) ? '?car_id=' . intval( wp_unslash( $_GET['car_id'] ) ) : '' );
        $login_page_url = home_url('/acrb-login/');
        $final_url = add_query_arg('redirect_to', urlencode($current_car_url), $login_page_url);
        return '<script>window.location.href="' . esc_url($final_url) . '";</script><div style="padding:50px; text-align:center;">Redirecting to login...</div>';
    }

    $car_id = isset( $_GET['car_id'] ) ? intval( wp_unslash( $_GET['car_id'] ) ) : 0;
    if (!$car_id || get_post_type($car_id) !== 'acrb_cars') {
        return '<div class="acrb-alert error">Vehicle not found.</div>';
    }

    $current_user = wp_get_current_user();
    $full_name = trim($current_user->first_name . ' ' . $current_user->last_name) ?: $current_user->display_name;
    $user_phone = get_user_meta($current_user->ID, 'billing_phone', true) ?: get_user_meta($current_user->ID, 'phone', true);
    $display_phone = !empty($user_phone) ? $user_phone : 'Missing in Profile';

    $price    = get_post_meta($car_id, 'price_per_day', true) ?: '0';
    $currency = '£'; 

    $features  = get_post_meta($car_id, 'acrb_car_features', true) ?: [];
    $amenities = get_post_meta($car_id, 'acrb_car_amenities', true) ?: [];
    $gallery   = get_post_meta($car_id, 'acrb_car_gallery', true);
    $vin       = get_post_meta($car_id, 'vin_number', true);
    $video_url = get_post_meta($car_id, 'acrb_car_video', true);
    $custom_locations = get_option('acrb_custom_locations', []);
    
    $enabled_methods = [];
    if (get_option('acrb_enable_pay_later') === 'yes') $enabled_methods['pay_later'] = 'Pay at Pickup';
    if (get_option('acrb_enable_bank') === 'yes')      $enabled_methods['bank']      = 'Bank Transfer';
    if (get_option('acrb_enable_cash') === 'yes')      $enabled_methods['cash']      = 'Cash';

    ob_start(); ?>

    <div class="acrb-main-container" data-day-rate="<?php echo esc_attr($price); ?>">
        <div class="acrb-flex-grid" style="display: flex; flex-wrap: wrap; gap: 30px;">
            
            <div class="acrb-content-left" style="flex: 1; min-width: 300px;">
                <div class="acrb-gallery-stage">
                    <img src="<?php echo esc_url(get_the_post_thumbnail_url($car_id, 'large')); ?>" id="acrb-hero-view" style="width:100%; border-radius:15px; border: 1px solid #eee; height: 450px; object-fit: cover;">
                </div>

                <div class="acrb-header-box" style="margin-top:25px;">
                    <div style="background: #f0f0f0; display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #666; margin-bottom: 10px;">ID: <?php echo esc_html($vin ?: 'REF-'.$car_id); ?></div>
                    <h1 style="margin:0; font-size: 32px; color: #111;"><?php echo esc_html(get_the_title($car_id)); ?></h1>
                </div>

                <div class="acrb-tabs-nav">
                    <button class="acrb-tab-btn active" data-tab="overview">Overview</button>
                    <button class="acrb-tab-btn" data-tab="specs">Specifications</button>
                    <button class="acrb-tab-btn" data-tab="gallery">Gallery</button>
                    <button class="acrb-tab-btn" data-tab="video">Video</button>
                </div>

                <div class="acrb-tabs-content" style="margin-top: 25px;">
                    
                    <div id="tab-overview" class="acrb-tab-pane active">
                        <div class="acrb-description">
                            <?php 
                            $content = get_post_field( 'post_content', $car_id );
                            echo !empty($content) ? wp_kses_post( wpautop( $content ) ) : '<p class="acrb-empty-msg">No description available for this vehicle.</p>'; 
                            ?>
                        </div>
                    </div>

                    <div id="tab-specs" class="acrb-tab-pane">
                        <div class="acrb-spec-wrap" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:15px;">
                            <?php foreach($features as $f): if(!empty($f['enabled'])): ?>
                            <div class="acrb-spec-node" style="padding:15px; background:#fafafa; border: 1px solid #f0f0f0; border-radius:12px;">
                                <span class="dashicons <?php echo esc_attr($f['icon']); ?>" style="color: #2563eb; margin-bottom: 8px;"></span>
                                <div style="font-size:11px; color: #888; text-transform:uppercase;"><?php echo esc_html($f['name']); ?></div>
                                <div style="font-weight:bold; color: #333; font-size: 15px;"><?php echo esc_html($f['value']); ?></div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php if(!empty($amenities)): ?>
                        <div style="margin-top:30px;">
                            <h3 style="margin-bottom: 15px;">Included Amenities</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <?php foreach($amenities as $a): if(!empty($a['enabled'])): ?>
                                    <div style="display: flex; align-items: center; gap: 10px; color: #444;">
                                        <span class="dashicons <?php echo esc_attr($a['icon']); ?>" style="color: #10b981;"></span>
                                        <span style="font-size: 15px;"><?php echo esc_html($a['name']); ?></span>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div id="tab-gallery" class="acrb-tab-pane">
                        <?php if($gallery): $ids = explode(',', $gallery); ?>
                            <div class="acrb-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                                 <img src="<?php echo esc_url(get_the_post_thumbnail_url($car_id, 'large')); ?>" class="acrb-gallery-item" style="width:100%; height:120px; object-fit:cover; border-radius:10px; cursor:pointer;">
                                 <?php foreach($ids as $id): ?>
                                    <img src="<?php echo esc_url(wp_get_attachment_image_url($id, 'large')); ?>" class="acrb-gallery-item" style="width:100%; height:120px; object-fit:cover; border-radius:10px; cursor:pointer;">
                                 <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="acrb-empty-box">
                                <span class="dashicons dashicons-format-image"></span>
                                <p>No additional images available.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="tab-video" class="acrb-tab-pane">
                        <?php if($video_url): 
                            $embed = wp_oembed_get($video_url);
                            if($embed): ?>
                                <div class="acrb-video-container"><?php echo esc_url($embed); ?></div>
                            <?php else: ?>
                                <p class="acrb-empty-msg">Invalid video URL.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="acrb-empty-box">
                                <span class="dashicons dashicons-video-alt3"></span>
                                <p>No video walkthrough available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="acrb-sidebar-right" style="width: 400px; min-width: 320px;">
                <div class="acrb-booking-card">
                    <div class="acrb-price-hero">
                        <span style="font-size:36px; font-weight:900; color: #000;">$<?php echo esc_html($price); ?></span>
                        <span style="color: #888; font-size: 14px;">/ per day</span>
                    </div>

                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" id="acrb-booking-form">
                        <?php wp_nonce_field('acrb_submit_booking', 'acrb_booking_nonce'); ?>
                        <input type="hidden" name="action" value="acrb_submit_booking_action">
                        <input type="hidden" name="car_id" value="<?php echo esc_attr($car_id); ?>">
                        <input type="hidden" id="acrb_hidden_total" name="order_total" value="0.00">

                        <div style="margin-bottom:12px;">
                            <label class="acrb-label">Full Name</label>
                            <input type="text" name="cus_name" value="<?php echo esc_attr($full_name); ?>" readonly class="acrb-readonly">
                        </div>

                        <div style="margin-bottom:12px;">
                            <label class="acrb-label">Email Address</label>
                            <input type="email" name="cus_email" value="<?php echo esc_attr($current_user->user_email); ?>" readonly class="acrb-readonly">
                        </div>

                        <div style="margin-bottom:15px;">
                            <label class="acrb-label">Phone Number</label>
                            <input type="text" name="cus_phone" value="<?php echo esc_attr($display_phone); ?>" readonly class="acrb-readonly">
                        </div>

                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <div style="flex:1;">
                                <label class="acrb-label">Pickup Date</label>
                                <input type="date" id="p_date" name="pickup_date" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                            <div style="flex:1;">
                                <label class="acrb-label">Return Date</label>
                                <input type="date" id="r_date" name="return_date" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label class="acrb-label">Pickup Location</label>
                            <select name="pickup_location" required class="acrb-input">
                                <option value="">Select Location</option>
                                <?php foreach($custom_locations as $loc): ?>
                                    <option value="<?php echo esc_attr($loc['name']); ?>"><?php echo esc_html($loc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label class="acrb-label">Payment Method</label>
                            <div class="acrb-payment-wrapper">
                                <?php foreach($enabled_methods as $k => $v): ?>
                                <label class="acrb-pay-option">
                                    <input type="radio" name="payment_method" value="<?php echo esc_attr($k); ?>" required>
                                    <span style="margin-left:10px;"><?php echo esc_html($v); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="acrb-total-box">
                            <span style="font-weight:700;">Estimated Total</span>
                            <span style="font-size:22px; font-weight:900;">$<span id="acrb_sum_total">0.00</span></span>
                        </div>

                        <button type="submit" class="acrb-submit-btn">Reserve This Vehicle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab System
        const tabBtns = document.querySelectorAll('.acrb-tab-btn');
        const tabPanes = document.querySelectorAll('.acrb-tab-pane');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });

        // Price Calc Logic
        const pDate = document.getElementById('p_date');
        const rDate = document.getElementById('r_date');
        const display = document.getElementById('acrb_sum_total');
        const hidden = document.getElementById('acrb_hidden_total');
        const rate = parseFloat(document.querySelector('.acrb-main-container').dataset.dayRate);

        function calc() {
            if (pDate.value && rDate.value) {
                const start = new Date(pDate.value);
                const end = new Date(rDate.value);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                if (days > 0) {
                    const total = (days * rate).toFixed(2);
                    display.innerText = total;
                    hidden.value = total;
                } else {
                    display.innerText = "0.00";
                    hidden.value = "0.00";
                }
            }
        }
        pDate.addEventListener('change', calc);
        rDate.addEventListener('change', calc);

        // Gallery Hero Switcher
        document.querySelectorAll('.acrb-gallery-item').forEach(img => {
            img.addEventListener('click', function() {
                document.getElementById('acrb-hero-view').src = this.src;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    });
    </script>
    
    <style>
        .acrb-tabs-nav { display: flex; gap: 20px; margin-top: 30px; border-bottom: 2px solid #eee; overflow-x: auto; }
        .acrb-tab-btn { background: none; border: none; padding: 15px 5px; font-weight: 700; font-size: 16px; color: #666; cursor: pointer; border-bottom: 3px solid transparent; transition: 0.3s; white-space: nowrap; }
        .acrb-tab-btn.active { color: #2563eb; border-bottom-color: #2563eb; }
        .acrb-tab-pane { display: none; padding: 20px 0; }
        .acrb-tab-pane.active { display: block; animation: acrbFade 0.4s; }
        @keyframes acrbFade { from { opacity:0; transform: translateY(5px); } to { opacity:1; transform: translateY(0); } }

        .acrb-empty-box { padding:40px; text-align:center; background:#f9f9f9; border-radius:12px; color:#888; border: 1px dashed #ddd; }
        .acrb-empty-box .dashicons { font-size:40px; width:40px; height:40px; margin-bottom:10px; color: #ccc; }
        .acrb-empty-msg { color:#888; font-style:italic; }

        .acrb-video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 15px; background: #000; }
        .acrb-video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }

        .acrb-booking-card { padding:30px; border:1px solid #e0e0e0; border-radius:20px; background: #fff; position:sticky; top:20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .acrb-price-hero { margin-bottom:25px; border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; }
        .acrb-label { display:block; margin-bottom:5px; font-size: 12px; font-weight:700; color:#666; }
        .acrb-readonly { width:100%; padding:10px; border:1px solid #eee; border-radius:8px; background:#f5f5f5; color:#777; cursor:not-allowed; }
        .acrb-input { width:100%; padding:12px; border:1px solid #ddd; border-radius:10px; }
        .acrb-pay-option { display:flex; align-items:center; margin-bottom:8px; cursor:pointer; padding: 12px; border: 1px solid #f0f0f0; border-radius: 10px; font-size: 14px; transition: 0.2s; }
        .acrb-pay-option:hover { border-color: #2563eb; background: #f4f9ff; }
        .acrb-total-box { padding:20px; background:#f4f9ff; border-radius:12px; display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; color: #2563eb; }
        .acrb-submit-btn { width:100%; padding:18px; background:#2563eb; color:#fff; border:none; border-radius:12px; font-weight:800; font-size: 16px; cursor:pointer; transition: 0.3s; }
        .acrb-submit-btn:hover { background: #1d4ed8; }

        @media (max-width: 900px) {
            .acrb-sidebar-right, .acrb-content-left { width: 100% !important; min-width: 100% !important; }
            .acrb-booking-card { position: static; }
        }
    </style>
    <?php return ob_get_clean();
}
add_shortcode('acrb_single_car', 'acrb_car_details_page_shortcode');