<?php
/**
 * [acrb_account]
 * Ultra-Modern Elite SaaS Account Dashboard with Dynamic Currency & Translation Ready
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode('acrb_account', function() {
    // 1. SECURITY & AUTHENTICATION
    if (!is_user_logged_in()) {
        return '
        <div class="acrb-auth-locked">
            <span class="dashicons dashicons-lock"></span>
            <h2>' . esc_html__('Secure Portal', 'awesome-car-rental') . '</h2>
            <p>' . esc_html__('Please sign in to access your bookings and profile settings.', 'awesome-car-rental') . '</p>
            <a href="'.esc_url(wp_login_url(get_permalink())).'" class="acrb-btn">' . esc_html__('Login to My Account', 'awesome-car-rental') . '</a>
        </div>';
    }

    $user    = wp_get_current_user();
    $user_id = $user->ID;
    
    // 2. DYNAMIC SETTINGS
    $currency_symbol = get_option('acrb_currency', '£');
    $currency_pos    = get_option('acrb_currency_pos', 'left');
    $update_msg      = '';

    // 3. LOGIC: PROFILE & PASSWORD UPDATES
    if ( isset( $_POST['acrb_update_account'], $_POST['acrb_acc_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['acrb_acc_nonce'] ) ), 'acrb_save_details' ) ) {
        
        $fname   = isset( $_POST['fname'] ) ? sanitize_text_field( wp_unslash( $_POST['fname'] ) ) : '';
        $lname   = isset( $_POST['lname'] ) ? sanitize_text_field( wp_unslash( $_POST['lname'] ) ) : '';
        $phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

        update_user_meta( $user_id, 'first_name', $fname );
        update_user_meta( $user_id, 'last_name', $lname );
        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, 'acrb_address', $address );
        
        $new_pass  = ! empty( $_POST['new_pass'] ) ? wp_unslash( $_POST['new_pass'] ) : '';
        $conf_pass = ! empty( $_POST['conf_pass'] ) ? wp_unslash( $_POST['conf_pass'] ) : '';

        if ( ! empty( $new_pass ) ) {
            if ( $new_pass === $conf_pass ) {
                wp_set_password( $new_pass, $user_id );
                $update_msg = '<div class="acrb-alert success">✓ ' . esc_html__( 'Password changed. Please log in again.', 'awesome-car-rental' ) . '</div>';
            } else {
                $update_msg = '<div class="acrb-alert error">⚠ ' . esc_html__( 'Passwords do not match.', 'awesome-car-rental' ) . '</div>';
            }
        } else {
            $update_msg = '<div class="acrb-alert success">✓ ' . esc_attr__( 'Profile updated successfully.', 'awesome-car-rental' ) . '</div>';
        }
    }

    // 4. DATA: USER BOOKINGS
    $all_bookings = get_posts([
        'post_type'   => 'acrb_bookings',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query'  => [['key' => 'user_id', 'value' => $user_id, 'compare' => '=']],
        'orderby'     => 'date',
        'order'       => 'DESC'
    ]);

    ob_start(); ?>

    <div class="acrb-acc-wrapper">
        <aside class="acrb-acc-nav">
            <div class="acrb-nav-user">
                <div class="acrb-nav-avatar"><?php echo get_avatar($user_id, 48); ?></div>
                <div class="acrb-nav-info">
                    <strong><?php echo esc_html($user->display_name); ?></strong>
                    <span><?php esc_html_e('Premium Member', 'awesome-car-rental'); ?></span>
                </div>
            </div>
            <ul id="acrb-nav-list">
                <li class="active">
                    <a class="acrb-tab-trigger" data-tab="acrb-dash"><span class="dashicons dashicons-dashboard"></span> <?php esc_html_e('Dashboard', 'awesome-car-rental'); ?></a>
                </li>
                <li>
                    <a class="acrb-tab-trigger" data-tab="acrb-bookings-list"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Rental History', 'awesome-car-rental'); ?></a>
                </li>
                <li>
                    <a class="acrb-tab-trigger" data-tab="acrb-profile-form"><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('My Profile', 'awesome-car-rental'); ?></a>
                </li>
                <li>
                    <a class="acrb-tab-trigger" data-tab="acrb-security-form"><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e('Password', 'awesome-car-rental'); ?></a>
                </li>
                <li class="acrb-nav-logout">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                        <span class="dashicons dashicons-exit"></span> <?php esc_html_e('Sign Out', 'awesome-car-rental'); ?>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="acrb-acc-content">
            <?php 
            if ( ! empty( $update_msg ) ) {
                $allowed_html = array( 'div' => array( 'class' => array() ) );
                echo wp_kses( $update_msg, $allowed_html );
            }
            ?>

            <div id="acrb-dash" class="acrb-tab-panel active">
                <div class="acrb-dash-header">
                    <h2><?php
                        $greeting_msg = sprintf( esc_html__( 'Good Day, %1$s', 'awesome-car-rental' ), esc_html( $user->first_name ?: $user->display_name ) );
                        echo wp_kses_post( $greeting_msg );
                    ?> ✨</h2>
                    <p><?php esc_html_e('Welcome to your exclusive rental portal.', 'awesome-car-rental'); ?></p>
                </div>

                <div class="acrb-stats-grid">
                    <div class="acrb-stat-card">
                        <div class="acrb-stat-icon"><span class="dashicons dashicons-car"></span></div>
                        <div class="acrb-stat-val"><?php echo count($all_bookings); ?></div>
                        <div class="acrb-stat-label"><?php esc_html_e('Total Trips', 'awesome-car-rental'); ?></div>
                    </div>
                    <div class="acrb-stat-card">
                        <div class="acrb-stat-icon"><span class="dashicons dashicons-awards"></span></div>
                        <div class="acrb-stat-val">
                            <?php 
                                $member_msg = sprintf( esc_html__( '%1$s Member', 'awesome-car-rental' ), esc_html( gmdate( 'Y', strtotime( $user->user_registered ) ) ) );
                                echo wp_kses_post( $member_msg );
                            ?>
                        </div>
                        <div class="acrb-stat-label"><?php esc_html_e('Registration Year', 'awesome-car-rental'); ?></div>
                    </div>
                </div>

                <div class="acrb-promo-banner">
                    <div class="acrb-promo-content">
                        <h3><?php esc_html_e('Ready for your next journey?', 'awesome-car-rental'); ?></h3>
                        <p><?php esc_html_e('Explore our luxury fleet and book with your member discount.', 'awesome-car-rental'); ?></p>
                    </div>
                    <a href="<?php echo esc_url(home_url('/acrb-cars')); ?>" class="acrb-btn-white"><?php esc_html_e('Book Now', 'awesome-car-rental'); ?></a>
                </div>
            </div>

            <div id="acrb-bookings-list" class="acrb-tab-panel">
                <h2 class="acrb-panel-title"><?php esc_html_e('Rental History', 'awesome-car-rental'); ?></h2>
                
                <?php if($all_bookings): foreach($all_bookings as $b): 
                    $acrb_bid    = $b->ID;
                    $car_id      = get_post_meta($acrb_bid, 'car_id', true);
                    $raw_price   = get_post_meta($acrb_bid, 'total_price', true);
                    $pickup_date = get_post_meta($acrb_bid, 'pickup_date', true);
                    $status      = get_post_meta($acrb_bid, 'status', true) ?: 'pending';

                    // Currency Processing
                    $clean_price     = preg_replace('/[^0-9.]/', '', $raw_price);
                    $formatted_price = number_format(floatval($clean_price), 2);
                    $display_price   = ($currency_pos === 'left') ? $currency_symbol . $formatted_price : $formatted_price . $currency_symbol;
                ?>
                <div class="acrb-booking-card">
                    <div class="acrb-card-main">
                        <div class="acrb-car-icon"><span class="dashicons dashicons-car"></span></div>
                        <div class="acrb-booking-details">
                            <div class="acrb-card-top">
                                <h4 class="acrb-car-name"><?php echo esc_html(get_the_title($car_id)); ?></h4>
                                <span class="acrb-status-pill status-<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html(ucfirst($status)); ?>
                                </span>
                            </div>
                            <div class="acrb-card-meta">
                                <span class="acrb-meta-item"><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html($pickup_date); ?></span>
                                <span class="acrb-meta-item"><span class="dashicons dashicons-tag"></span> ID: #<?php echo esc_html($acrb_bid); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="acrb-card-footer">
                        <div class="acrb-price-box">
                            <span class="acrb-price-label"><?php esc_html_e('Total Amount', 'awesome-car-rental'); ?></span>
                            <span class="acrb-price-amount"><?php echo esc_html($display_price); ?></span>
                        </div>
                        <button class="acrb-btn-outline open-receipt" 
                                data-id="<?php echo esc_attr($acrb_bid); ?>" 
                                data-car="<?php echo esc_attr(get_the_title($car_id)); ?>" 
                                data-total="<?php echo esc_attr($display_price); ?>" 
                                data-date="<?php echo esc_attr($pickup_date); ?>">
                            <span class="dashicons dashicons-media-text"></span> <?php esc_html_e('Details', 'awesome-car-rental'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <div class="acrb-empty-box">
                        <span class="dashicons dashicons-calendar"></span>
                        <p><?php esc_html_e('You haven\'t made any bookings yet.', 'awesome-car-rental'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="acrb-profile-form" class="acrb-tab-panel">
                <h2 class="acrb-panel-title"><?php esc_html_e('Profile Settings', 'awesome-car-rental'); ?></h2>
                <form method="POST" class="acrb-settings-form">
                    <?php wp_nonce_field('acrb_save_details', 'acrb_acc_nonce'); ?>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('First Name', 'awesome-car-rental'); ?></label>
                            <input type="text" name="fname" value="<?php echo esc_attr($user->first_name); ?>" required class="acrb-input">
                        </div>
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Last Name', 'awesome-car-rental'); ?></label>
                            <input type="text" name="lname" value="<?php echo esc_attr($user->last_name); ?>" required class="acrb-input">
                        </div>
                    </div>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Contact Phone', 'awesome-car-rental'); ?></label>
                            <input type="text" name="phone" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_phone', true)); ?>" class="acrb-input">
                        </div>
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Email (Fixed)', 'awesome-car-rental'); ?></label>
                            <input type="text" value="<?php echo esc_attr($user->user_email); ?>" disabled class="acrb-input acrb-readonly">
                        </div>
                    </div>
                    <div class="acrb-form-group">
                        <label><?php esc_html_e('Residential Address', 'awesome-car-rental'); ?></label>
                        <textarea name="address" rows="3" class="acrb-textarea"><?php echo esc_textarea(get_user_meta($user_id, 'acrb_address', true)); ?></textarea>
                    </div>
                    <button type="submit" name="acrb_update_account" class="acrb-btn-submit"><?php esc_html_e('Update Profile', 'awesome-car-rental'); ?></button>
                </form>
            </div>

            <div id="acrb-security-form" class="acrb-tab-panel">
                <h2 class="acrb-panel-title"><?php esc_html_e('Security Settings', 'awesome-car-rental'); ?></h2>
                <form method="POST" class="acrb-settings-form">
                    <?php wp_nonce_field('acrb_save_details', 'acrb_acc_nonce'); ?>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('New Password', 'awesome-car-rental'); ?></label>
                            <input type="password" name="new_pass" placeholder="••••••••" class="acrb-input">
                        </div>
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Confirm Password', 'awesome-car-rental'); ?></label>
                            <input type="password" name="conf_pass" placeholder="••••••••" class="acrb-input">
                        </div>
                    </div>
                    <button type="submit" name="acrb_update_account" class="acrb-btn-submit"><?php esc_html_e('Update Password', 'awesome-car-rental'); ?></button>
                </form>
            </div>
        </main>
    </div>

    <!-- Booking Details Modal -->
    <div id="acrb-receipt-modal" class="acrb-modal">
        <div class="acrb-modal-content">
            <span class="acrb-modal-close">&times;</span>
            <div class="acrb-receipt-header">
                <div class="acrb-receipt-icon">🧾</div>
                <h3><?php esc_html_e('Booking Details', 'awesome-car-rental'); ?></h3>
                <span id="acrb-m-id" style="color:#64748b; font-size:12px; font-weight:600;"></span>
            </div>
            <div class="acrb-receipt-list">
                <div class="acrb-r-item"><span><?php esc_html_e('Car Model:', 'awesome-car-rental'); ?></span><strong id="acrb-m-car" style="color:#0f172a;"></strong></div>
                <div class="acrb-r-item"><span><?php esc_html_e('Pickup Date:', 'awesome-car-rental'); ?></span><span id="acrb-m-date" style="color:#334155;"></span></div>
                <div class="acrb-r-item"><span><?php esc_html_e('Payment Status:', 'awesome-car-rental'); ?></span><span class="acrb-status-pill status-completed">PAID</span></div>
            </div>
            <div class="acrb-receipt-footer">
                <div class="acrb-r-total">
                    <span><?php esc_html_e('Total Amount:', 'awesome-car-rental'); ?></span>
                    <span id="acrb-m-total" style="color:#0f172a;"></span>
                </div>
                <button onclick="window.print()" class="acrb-btn-print"><?php esc_html_e('Print PDF', 'awesome-car-rental'); ?></button>
            </div>
        </div>
    </div>

    <style>
        /* Modern SaaS Account Wrapper */
        .acrb-acc-wrapper { display: flex; gap: 32px; margin: 30px auto; max-width: 1280px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; box-sizing: border-box; }
        .acrb-acc-wrapper *, .acrb-acc-wrapper *::before, .acrb-acc-wrapper *::after { box-sizing: border-box; }

        /* Sidebar Navigation */
        .acrb-acc-nav { width: 280px; flex-shrink: 0; }
        .acrb-nav-user { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; padding: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); }
        .acrb-nav-avatar img { border-radius: 50%; object-fit: cover; }
        .acrb-nav-info strong { display: block; color: #0f172a; font-size: 15px; font-weight: 700; }
        .acrb-nav-info span { font-size: 11px; color: #3b82f6; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; }

        #acrb-nav-list { list-style: none; padding: 0; margin: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); }
        #acrb-nav-list li a { display: flex; align-items: center; gap: 12px; padding: 15px 22px; text-decoration: none; color: #475569; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; border-left: 3px solid transparent; }
        #acrb-nav-list li.active a { background: #eff6ff; color: #2563eb; border-left-color: #3b82f6; }
        #acrb-nav-list li a:hover:not(.active) { background: #f8fafc; color: #0f172a; }
        .acrb-nav-logout a { color: #b91c1c !important; border-top: 1px solid #f1f5f9; }
        .acrb-nav-logout a:hover { background: #fef2f2 !important; }

        /* Main Content Area */
        .acrb-acc-content { flex: 1; min-width: 0; }
        
        .acrb-dash-header { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px; margin-bottom: 28px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); }
        .acrb-dash-header h2 { margin: 0 0 6px 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .acrb-dash-header p { margin: 0; color: #64748b; font-size: 14px; }

        .acrb-stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 28px; }
        .acrb-stat-card { background: #fff; border: 1px solid #e2e8f0; padding: 24px; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .acrb-stat-icon { width: 44px; height: 44px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .acrb-stat-val { font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .acrb-stat-label { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; }

        .acrb-promo-banner { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; border-radius: 20px; padding: 28px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3); }
        .acrb-promo-content h3 { margin: 0 0 6px 0; font-size: 18px; font-weight: 700; color: #fff; }
        .acrb-promo-content p { margin: 0; font-size: 14px; opacity: 0.9; }
        .acrb-btn-white { background: #fff; color: #2563eb; text-decoration: none; padding: 11px 22px; border-radius: 10px; font-weight: 700; font-size: 13px; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: inline-flex; align-items: center; }
        .acrb-btn-white:hover { background: #f8fafc; transform: translateY(-1px); }

        /* Rental History Panel */
        .acrb-panel-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 20px 0; letter-spacing: -0.3px; }
        
        .acrb-booking-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); transition: all 0.3s ease; }
        .acrb-booking-card:hover { border-color: #cbd5e1; box-shadow: 0 15px 30px -10px rgba(15, 23, 42, 0.08); }
        
        .acrb-card-main { display: flex; gap: 20px; padding: 24px; align-items: center; }
        .acrb-car-icon { background: #eff6ff; color: #3b82f6; width: 50px; height: 50px; min-width: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .acrb-booking-details { flex: 1; }
        .acrb-card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .acrb-car-name { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; }
        
        .acrb-status-pill { font-size: 11px; padding: 4px 12px; border-radius: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; }
        .status-completed, .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-pending { background: #fef3c7; color: #d97706; }
        
        .acrb-card-meta { display: flex; gap: 18px; font-size: 13px; color: #64748b; font-weight: 500; }
        .acrb-meta-item { display: inline-flex; align-items: center; gap: 6px; }
        .acrb-meta-item .dashicons { font-size: 16px; width: 16px; height: 16px; color: #94a3b8; }

        .acrb-card-footer { background: #f8fafc; padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .acrb-price-box { display: flex; flex-direction: column; }
        .acrb-price-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; }
        .acrb-price-amount { font-weight: 900; color: #0f172a; font-size: 20px; letter-spacing: -0.5px; }

        .acrb-btn-outline { background: #fff; color: #334155; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        .acrb-btn-outline:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        .acrb-btn-outline .dashicons { font-size: 15px; width: 15px; height: 15px; color: #64748b; }

        /* Profile & Security Forms */
        .acrb-settings-form { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); }
        .acrb-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .acrb-form-group { margin-bottom: 22px; }
        .acrb-form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #334155; }
        .acrb-input, .acrb-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 16px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-input { height: 44px; }
        .acrb-textarea { padding: 14px 16px; height: auto; resize: vertical; }
        .acrb-input:hover, .acrb-textarea:hover { border-color: #94a3b8; }
        .acrb-input:focus, .acrb-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12); outline: none; }
        .acrb-readonly { background: #f8fafc; color: #64748b; cursor: not-allowed; }
        
        .acrb-btn-submit { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); transition: all 0.2s; height: 44px; display: inline-flex; align-items: center; justify-content: center; width: 100%; }
        .acrb-btn-submit:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }

        /* Alerts */
        .acrb-alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 13px; font-weight: 600; }
        .acrb-alert.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .acrb-alert.error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Modal */
        .acrb-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
        .acrb-modal-content { background: #fff; margin: 8% auto; padding: 36px; border-radius: 20px; width: 440px; position: relative; box-shadow: 0 25px 35px -10px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; }
        .acrb-modal-close { position: absolute; right: 24px; top: 24px; font-size: 24px; cursor: pointer; color: #94a3b8; transition: color 0.2s; }
        .acrb-modal-close:hover { color: #0f172a; }
        
        .acrb-receipt-header { text-align: center; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .acrb-receipt-icon { font-size: 36px; margin-bottom: 8px; }
        .acrb-receipt-header h3 { margin: 0 0 4px 0; font-size: 19px; font-weight: 800; color: #0f172a; }
        
        .acrb-receipt-list { display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px; }
        .acrb-r-item { display: flex; justify-content: space-between; font-size: 14px; color: #475569; font-weight: 500; }
        
        .acrb-r-total { display: flex; justify-content: space-between; border-top: 2px dashed #e2e8f0; padding-top: 18px; font-weight: 900; font-size: 18px; color: #0f172a; }
        .acrb-btn-print { width: 100%; margin-top: 24px; height: 44px; background: #0f172a; color: #fff; border-radius: 10px; border: none; font-weight: 700; font-size: 14px; cursor: pointer; transition: background 0.2s; }
        .acrb-btn-print:hover { background: #334155; }

        .acrb-empty-box { padding: 60px 20px; text-align: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; color: #64748b; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); }
        .acrb-empty-box .dashicons { font-size: 48px; width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 12px; }
        .acrb-empty-box p { margin: 0; font-size: 14px; font-weight: 600; }

        .acrb-auth-locked { max-width: 440px; margin: 60px auto; padding: 40px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); }
        .acrb-auth-locked .dashicons { font-size: 48px; width: 48px; height: 48px; color: #3b82f6; margin-bottom: 16px; }
        .acrb-auth-locked h2 { margin: 0 0 8px 0; font-size: 20px; font-weight: 800; color: #0f172a; }
        .acrb-auth-locked p { color: #64748b; font-size: 14px; margin: 0 0 24px 0; line-height: 1.5; }
        .acrb-btn { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; display: inline-block; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); transition: all 0.2s; }
        .acrb-btn:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #fff; text-decoration: none; }

        .acrb-tab-panel { display: none; }
        .acrb-tab-panel.active { display: block; animation: acrbFade 0.3s ease; }

        @keyframes acrbFade {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            .acrb-acc-wrapper { flex-direction: column; }
            .acrb-acc-nav { width: 100%; }
            .acrb-form-row { grid-template-columns: 1fr; }
            .acrb-stats-grid { grid-template-columns: 1fr; }
            .acrb-promo-banner { flex-direction: column; align-items: flex-start; gap: 16px; }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // TAB SWITCHING
        const triggers = document.querySelectorAll('.acrb-tab-trigger');
        triggers.forEach(t => t.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.acrb-tab-panel').forEach(p => p.classList.remove('active'));
            document.getElementById(this.dataset.tab).classList.add('active');
            document.querySelectorAll('.acrb-acc-nav li').forEach(li => li.classList.remove('active'));
            this.parentElement.classList.add('active');
        }));

        // MODAL LOGIC
        const modal = document.getElementById('acrb-receipt-modal');
        const detailBtns = document.querySelectorAll('.open-receipt');
        const closeBtn = document.querySelector('.acrb-modal-close');

        if (modal && detailBtns.length > 0) {
            detailBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('acrb-m-id').innerText = 'ID: #' + this.dataset.id;
                    document.getElementById('acrb-m-car').innerText = this.dataset.car;
                    document.getElementById('acrb-m-date').innerText = this.dataset.date;
                    document.getElementById('acrb-m-total').innerText = this.dataset.total;
                    modal.style.display = 'block';
                });
            });

            if (closeBtn) {
                closeBtn.onclick = () => modal.style.display = 'none';
            }
            window.onclick = (e) => { if(e.target == modal) modal.style.display = 'none'; };
        }
    });
    </script>

    <?php
    return ob_get_clean();
});