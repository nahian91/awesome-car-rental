<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
/**
 * ACRB PROFESSIONAL SINGLE CAR PAGE [acrb_single_car]
 * Features: Tabs (Overview, Specs, Gallery, Video), Dynamic Currency, and Fallback Handling.
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
        return '<script>window.location.href="' . esc_url($final_url) . '";</script><div style="padding:50px; text-align:center; font-family:-apple-system,sans-serif; color:#64748b;">Redirecting to login...</div>';
    }

    // Get Car ID from URL, with fallback to the latest car if omitted during testing
    $car_id = isset( $_GET['car_id'] ) ? intval( wp_unslash( $_GET['car_id'] ) ) : 0;
    if (!$car_id) {
        $latest_cars = get_posts(array('post_type' => 'acrb_cars', 'numberposts' => 1, 'post_status' => 'publish'));
        if (!empty($latest_cars)) {
            $car_id = $latest_cars[0]->ID;
        }
    }

    if (!$car_id || get_post_type($car_id) !== 'acrb_cars') {
        return '<div class="acrb-alert error" style="padding:40px; text-align:center; background:#fee2e2; color:#b91c1c; border-radius:12px; font-family:-apple-system,sans-serif; font-weight:600;">' . esc_html__('Vehicle not found. Please select a car from the fleet grid.', 'awesome-car-rental') . '</div>';
    }

    $current_user = wp_get_current_user();
    $full_name = trim($current_user->first_name . ' ' . $current_user->last_name) ?: $current_user->display_name;
    $user_phone = get_user_meta($current_user->ID, 'billing_phone', true) ?: get_user_meta($current_user->ID, 'phone', true);
    $display_phone = !empty($user_phone) ? $user_phone : 'Missing in Profile';

    $price = get_post_meta($car_id, 'daily_price', true) ?: get_post_meta($car_id, 'price_per_day', true);
    if (!$price) { $price = '0.00'; }
    
    $currency = get_option('acrb_currency', '£'); 

    $features  = get_post_meta($car_id, 'acrb_car_features', true) ?: [];
    if (empty($features)) { $features = get_post_meta($car_id, 'car_specs', true) ?: []; }

    $amenities = get_post_meta($car_id, 'acrb_car_amenities', true) ?: [];
    $gallery   = get_post_meta($car_id, 'acrb_car_gallery', true);
    $vin       = get_post_meta($car_id, 'vin_number', true);
    $video_url = get_post_meta($car_id, 'acrb_car_video', true);
    
    // Support both custom location option keys
    $custom_locations = get_option('acrb_custom_pickup_locations', []) ?: get_option('acrb_custom_locations', []);
    
    $enabled_methods = [];
    if (get_option('acrb_enable_pay_later') === 'yes') $enabled_methods['pay_later'] = 'Pay at Pickup';
    if (get_option('acrb_enable_bank') === 'yes')      $enabled_methods['bank']      = 'Bank Transfer';
    if (get_option('acrb_enable_cash') === 'yes')      $enabled_methods['cash']      = 'Cash';

    ob_start(); ?>

    <div class="acrb-main-container" data-day-rate="<?php echo esc_attr($price); ?>">
        <div class="acrb-flex-grid" style="display: flex; flex-wrap: wrap; gap: 32px; align-items: flex-start;">
            
            <div class="acrb-content-left" style="flex: 1; min-width: 320px;">
                <div class="acrb-gallery-stage">
                    <img src="<?php echo esc_url(get_the_post_thumbnail_url($car_id, 'large')); ?>" id="acrb-hero-view" alt="<?php echo esc_attr(get_the_title($car_id)); ?>" style="width:100%; border-radius:16px; border: 1px solid #e2e8f0; height: 460px; object-fit: cover; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.04);">
                </div>

                <div class="acrb-header-box" style="margin-top:24px;">
                    <div style="background: #f1f5f9; display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">ID: <?php echo esc_html($vin ?: 'REF-'.$car_id); ?></div>
                    <h1 style="margin:0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;"><?php echo esc_html(get_the_title($car_id)); ?></h1>
                </div>

                <div class="acrb-tabs-nav">
                    <button class="acrb-tab-btn active" data-tab="overview"><?php esc_html_e('Overview', 'awesome-car-rental'); ?></button>
                    <button class="acrb-tab-btn" data-tab="specs"><?php esc_html_e('Specifications', 'awesome-car-rental'); ?></button>
                    <button class="acrb-tab-btn" data-tab="gallery"><?php esc_html_e('Gallery', 'awesome-car-rental'); ?></button>
                    <button class="acrb-tab-btn" data-tab="video"><?php esc_html_e('Video', 'awesome-car-rental'); ?></button>
                </div>

                <div class="acrb-tabs-content" style="margin-top: 24px;">
                    
                    <div id="tab-overview" class="acrb-tab-pane active">
                        <div class="acrb-description">
                            <?php 
                            $content = get_post_field( 'post_content', $car_id );
                            echo !empty($content) ? wp_kses_post( wpautop( $content ) ) : '<p class="acrb-empty-msg">' . esc_html__('No description available for this vehicle.', 'awesome-car-rental') . '</p>'; 
                            ?>
                        </div>
                    </div>

                    <div id="tab-specs" class="acrb-tab-pane">
                        <div class="acrb-spec-wrap" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:16px;">
                            <?php foreach($features as $f): 
                                $icon = is_array($f) ? ($f['icon'] ?? 'dashicons-yes') : 'dashicons-admin-generic';
                                $name = is_array($f) ? ($f['name'] ?? '') : '';
                                $val  = is_array($f) ? ($f['value'] ?? '') : $f;
                                $enabled = is_array($f) ? !empty($f['enabled']) : true;

                                if($enabled && !empty($val)): 
                            ?>
                            <div class="acrb-spec-node" style="padding:18px; background:#fff; border: 1px solid #e2e8f0; border-radius:12px; box-shadow: 0 2px 4px rgba(0,0,0,0.01);">
                                <span class="dashicons <?php echo esc_attr($icon); ?>" style="color: #3b82f6; margin-bottom: 10px; font-size:22px; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; border-radius:6px; padding:6px; box-sizing:content-box;"></span>
                                <div style="font-size:11px; color: #64748b; text-transform:uppercase; font-weight:600; letter-spacing:0.5px; margin-bottom:4px;"><?php echo esc_html($name ?: 'Spec'); ?></div>
                                <div style="font-weight:700; color: #0f172a; font-size: 15px;"><?php echo esc_html($val); ?></div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php if(!empty($amenities)): ?>
                        <div style="margin-top:32px; padding-top:24px; border-top:1px solid #e2e8f0;">
                            <h3 style="margin:0 0 16px 0; font-size:15px; font-weight:600; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e('Included Amenities', 'awesome-car-rental'); ?></h3>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                                <?php foreach($amenities as $a): if(!empty($a['enabled'])): ?>
                                    <div style="display: flex; align-items: center; gap: 10px; color: #334155; background: #fff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500;">
                                        <span class="dashicons <?php echo esc_attr($a['icon'] ?? 'dashicons-yes'); ?>" style="color: #16a34a;"></span>
                                        <span><?php echo esc_html($a['name']); ?></span>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div id="tab-gallery" class="acrb-tab-pane">
                        <?php if($gallery): $ids = explode(',', $gallery); ?>
                            <div class="acrb-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                                 <img src="<?php echo esc_url(get_the_post_thumbnail_url($car_id, 'large')); ?>" class="acrb-gallery-item" style="width:100%; height:120px; object-fit:cover; border-radius:10px; cursor:pointer;" alt="">
                                 <?php foreach($ids as $id): if($id): ?>
                                    <img src="<?php echo esc_url(wp_get_attachment_image_url($id, 'large')); ?>" class="acrb-gallery-item" style="width:100%; height:120px; object-fit:cover; border-radius:10px; cursor:pointer;" alt="">
                                 <?php endif; endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="acrb-empty-box">
                                <span class="dashicons dashicons-format-image"></span>
                                <p><?php esc_html_e('No additional images available.', 'awesome-car-rental'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="tab-video" class="acrb-tab-pane">
                        <?php if($video_url): 
                            $embed = wp_oembed_get($video_url);
                            if($embed): ?>
                                <div class="acrb-video-container"><?php echo wp_kses_post($embed); ?></div>
                            <?php else: ?>
                                <p class="acrb-empty-msg"><?php esc_html_e('Invalid video URL.', 'awesome-car-rental'); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="acrb-empty-box">
                                <span class="dashicons dashicons-video-alt3"></span>
                                <p><?php esc_html_e('No video walkthrough available.', 'awesome-car-rental'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="acrb-sidebar-right" style="width: 420px; min-width: 320px;">
                <div class="acrb-booking-card">
                    <div class="acrb-price-hero">
                        <span style="font-size:32px; font-weight:800; color: #0f172a; letter-spacing: -0.5px;"><?php echo esc_html($currency . number_format(floatval($price), 2)); ?></span>
                        <span style="color: #64748b; font-size: 13px; font-weight: 500;">/ per day</span>
                    </div>

                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" id="acrb-booking-form">
                        <?php wp_nonce_field('acrb_submit_booking', 'acrb_booking_nonce'); ?>
                        <input type="hidden" name="action" value="acrb_submit_booking_action">
                        <input type="hidden" name="car_id" value="<?php echo esc_attr($car_id); ?>">
                        <input type="hidden" id="acrb_hidden_total" name="order_total" value="0.00">

                        <div style="margin-bottom:16px;">
                            <label class="acrb-label"><?php esc_html_e('Full Name', 'awesome-car-rental'); ?></label>
                            <input type="text" name="cus_name" value="<?php echo esc_attr($full_name); ?>" readonly class="acrb-input acrb-readonly">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label class="acrb-label"><?php esc_html_e('Email Address', 'awesome-car-rental'); ?></label>
                            <input type="email" name="cus_email" value="<?php echo esc_attr($current_user->user_email); ?>" readonly class="acrb-input acrb-readonly">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label class="acrb-label"><?php esc_html_e('Phone Number', 'awesome-car-rental'); ?></label>
                            <input type="text" name="cus_phone" value="<?php echo esc_attr($display_phone); ?>" readonly class="acrb-input acrb-readonly">
                        </div>

                        <div style="display:flex; gap:12px; margin-bottom:16px;">
                            <div style="flex:1;">
                                <label class="acrb-label"><?php esc_html_e('Pickup Date', 'awesome-car-rental'); ?></label>
                                <input type="date" id="p_date" name="pickup_date" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                            <div style="flex:1;">
                                <label class="acrb-label"><?php esc_html_e('Return Date', 'awesome-car-rental'); ?></label>
                                <input type="date" id="r_date" name="return_date" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="acrb-input">
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label class="acrb-label"><?php esc_html_e('Pickup Location', 'awesome-car-rental'); ?></label>
                            <select name="pickup_location" required class="acrb-input acrb-select">
                                <option value=""><?php esc_html_e('Select Location', 'awesome-car-rental'); ?></option>
                                <?php foreach($custom_locations as $loc): 
                                    $loc_name = is_array($loc) ? ($loc['name'] ?? '') : $loc;
                                    if (!empty($loc_name)):
                                ?>
                                    <option value="<?php echo esc_attr($loc_name); ?>"><?php echo esc_html($loc_name); ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>

                        <?php if (!empty($enabled_methods)): ?>
                        <div style="margin-bottom:16px;">
                            <label class="acrb-label"><?php esc_html_e('Payment Method', 'awesome-car-rental'); ?></label>
                            <div class="acrb-payment-wrapper">
                                <?php foreach($enabled_methods as $k => $v): ?>
                                <label class="acrb-pay-option">
                                    <input type="radio" name="payment_method" value="<?php echo esc_attr($k); ?>" required>
                                    <span><?php echo esc_html($v); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="acrb-total-box">
                            <span style="font-weight: 600; color: #475569; font-size: 14px;"><?php esc_html_e('Estimated Total', 'awesome-car-rental'); ?></span>
                            <span style="font-size: 22px; font-weight: 800; color: #0f172a;"><?php echo esc_html($currency); ?><span id="acrb_sum_total">0.00</span></span>
                        </div>

                        <button type="submit" class="acrb-submit-btn"><?php esc_html_e('Reserve This Vehicle', 'awesome-car-rental'); ?></button>
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
        const container = document.querySelector('.acrb-main-container');
        const rate = container ? parseFloat(container.dataset.dayRate) : 0;

        function calc() {
            if (pDate && rDate && pDate.value && rDate.value) {
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
        if (pDate && rDate) {
            pDate.addEventListener('change', calc);
            rDate.addEventListener('change', calc);
        }

        // Gallery Hero Switcher
        document.querySelectorAll('.acrb-gallery-item').forEach(img => {
            img.addEventListener('click', function() {
                const hero = document.getElementById('acrb-hero-view');
                if (hero) hero.src = this.src;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    });
    </script>
    
    <style>
        .acrb-tabs-nav { display: flex; gap: 8px; margin-top: 28px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; overflow-x: auto; }
        .acrb-tab-btn { background: #fff; border: 1px solid #e2e8f0; padding: 10px 20px; font-weight: 600; font-size: 13px; color: #475569; cursor: pointer; border-radius: 8px; transition: all 0.2s ease; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        .acrb-tab-btn:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
        .acrb-tab-btn.active { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.1); }
        
        .acrb-tab-pane { display: none; padding: 24px 0; }
        .acrb-tab-pane.active { display: block; animation: acrbFade 0.3s ease; }
        @keyframes acrbFade { from { opacity:0; transform: translateY(4px); } to { opacity:1; transform: translateY(0); } }

        .acrb-empty-box { padding: 50px 20px; text-align: center; background: #f8fafc; border-radius: 14px; color: #64748b; border: 1px dashed #cbd5e1; }
        .acrb-empty-box .dashicons { font-size: 42px; width: 42px; height: 42px; margin-bottom: 10px; color: #94a3b8; }
        .acrb-empty-msg { color: #64748b; font-style: italic; }

        .acrb-video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 14px; background: #0f172a; border: 1px solid #e2e8f0; }
        .acrb-video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }

        .acrb-booking-card { padding: 28px; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; position: sticky; top: 30px; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03); }
        .acrb-price-hero { margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; display: flex; align-items: baseline; gap: 4px; }
        
        .acrb-label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #334155; }
        .acrb-readonly { background: #f8fafc; color: #64748b; cursor: not-allowed; border-color: #cbd5e1; }
        .acrb-input { width: 100%; height: 42px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; box-sizing: border-box; }
        .acrb-input:hover { border-color: #94a3b8; }
        .acrb-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; }
        .acrb-select { appearance: none; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 10px; padding-right: 36px; }

        .acrb-pay-option { display: flex; align-items: center; cursor: pointer; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-weight: 500; color: #334155; background: #fff; transition: all 0.2s ease; margin-bottom: 8px; }
        .acrb-pay-option:hover { border-color: #94a3b8; background: #f8fafc; }
        .acrb-pay-option input[type="radio"] { accent-color: #3b82f6; width: 16px; height: 16px; margin-right: 10px; }

        .acrb-total-box { padding: 16px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin: 20px 0 24px 0; }
        .acrb-submit-btn { width: 100%; height: 46px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-submit-btn:hover { background: #2563eb; }

        @media (max-width: 900px) {
            .acrb-sidebar-right, .acrb-content-left { width: 100% !important; min-width: 100% !important; }
            .acrb-booking-card { position: static; }
        }
    </style>
    <?php return ob_get_clean();
}
add_shortcode('acrb_single_car', 'acrb_car_details_page_shortcode');