<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [acrb_account]
 * Premium Dashboard - Dynamic Currency & Translation Ready
 */
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
    $currency_symbol = get_option('acrb_currency', '$');
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
            $update_msg = '<div class="acrb-alert success">✓ ' . esc_html__( 'Profile updated successfully.', 'awesome-car-rental' ) . '</div>';
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
                <div class="acrb-nav-avatar"><?php echo get_avatar($user_id, 44); ?></div>
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
            <?php echo esc_html($update_msg); ?>

            <div id="acrb-dash" class="acrb-tab-panel active">
                <div class="acrb-dash-header">
                    <h2><?php
                        // translators: 1: Booking ID, 2: New status name.
                        $acrb_msg = sprintf( esc_html__( 'Good Day, %1$s', 'awesome-car-rental' ), esc_html( $user->first_name ?: $user->display_name ) );
                        echo wp_kses_post( $acrb_msg );
                    ?> ✨</h2>
                    <p><?php esc_html_e('Welcome to your exclusive rental portal.', 'awesome-car-rental'); ?></p>
                </div>

                <div class="acrb-stats-grid">
                    <div class="acrb-stat-card">
                        <span class="dashicons dashicons-car"></span>
                        <div class="acrb-stat-val"><?php echo count($all_bookings); ?></div>
                        <div class="acrb-stat-label"><?php esc_html_e('Total Trips', 'awesome-car-rental'); ?></div>
                    </div>
                    <div class="acrb-stat-card">
                        <span class="dashicons dashicons-awards"></span>
                        <div class="acrb-stat-val">
                            <?php 
                                // translators: 1: Booking ID, 2: New status name.
                                $acrb_msg = sprintf( esc_html__( '%1$s Member', 'awesome-car-rental' ), esc_html( gmdate( 'Y', strtotime( $user->user_registered ) ) ) );
                                echo wp_kses_post( $acrb_msg );
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
                    <a href="<?php echo esc_url(home_url('/fleet')); ?>" class="acrb-btn-white"><?php esc_html_e('Book Now', 'awesome-car-rental'); ?></a>
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
                <form method="POST">
                    <?php wp_nonce_field('acrb_save_details', 'acrb_acc_nonce'); ?>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('First Name', 'awesome-car-rental'); ?></label>
                            <input type="text" name="fname" value="<?php echo esc_attr($user->first_name); ?>" required>
                        </div>
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Last Name', 'awesome-car-rental'); ?></label>
                            <input type="text" name="lname" value="<?php echo esc_attr($user->last_name); ?>" required>
                        </div>
                    </div>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Contact Phone', 'awesome-car-rental'); ?></label>
                            <input type="text" name="phone" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_phone', true)); ?>">
                        </div>
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Email (Fixed)', 'awesome-car-rental'); ?></label>
                            <input type="text" value="<?php echo esc_attr($user->user_email); ?>" disabled style="background:#f5f5f5;">
                        </div>
                    </div>
                    <div class="acrb-form-group">
                        <label><?php esc_html_e('Residential Address', 'awesome-car-rental'); ?></label>
                        <textarea name="address" rows="3"><?php echo esc_textarea(get_user_meta($user_id, 'acrb_address', true)); ?></textarea>
                    </div>
                    <button type="submit" name="acrb_update_account" class="acrb-btn-submit"><?php esc_html_e('Update Profile', 'awesome-car-rental'); ?></button>
                </form>
            </div>

            <div id="acrb-security-form" class="acrb-tab-panel">
                <h2 class="acrb-panel-title"><?php esc_html_e('Security Settings', 'awesome-car-rental'); ?></h2>
                <form method="POST">
                    <?php wp_nonce_field('acrb_save_details', 'acrb_acc_nonce'); ?>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('New Password', 'awesome-car-rental'); ?></label>
                            <input type="password" name="new_pass" placeholder="••••••••">
                        </div>
                        <div class="acrb-form-group">
                            <label><?php esc_html_e('Confirm Password', 'awesome-car-rental'); ?></label>
                            <input type="password" name="conf_pass" placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" name="acrb_update_account" class="acrb-btn-submit"><?php esc_html_e('Update Password', 'awesome-car-rental'); ?></button>
                </form>
            </div>
        </main>
    </div>

    <div id="acrb-receipt-modal" class="acrb-modal">
        <div class="acrb-modal-content">
            <span class="acrb-modal-close">&times;</span>
            <div class="acrb-receipt-header">
                <div class="acrb-receipt-icon">🧾</div>
                <h3><?php esc_html_e('Booking Details', 'awesome-car-rental'); ?></h3>
                <span id="acrb-m-id" style="color:#999; font-size:12px;"></span>
            </div>
            <div class="acrb-receipt-list">
                <div class="acrb-r-item"><span><?php esc_html_e('Car Model:', 'awesome-car-rental'); ?></span><strong id="acrb-m-car"></strong></div>
                <div class="acrb-r-item"><span><?php esc_html_e('Pickup Date:', 'awesome-car-rental'); ?></span><span id="acrb-m-date"></span></div>
                <div class="acrb-r-item"><span><?php esc_html_e('Payment Status:', 'awesome-car-rental'); ?></span><span class="acrb-status-pill status-completed">PAID</span></div>
            </div>
            <div class="acrb-receipt-footer">
                <div class="acrb-r-total">
                    <span><?php esc_html_e('Total Amount:', 'awesome-car-rental'); ?></span>
                    <span id="acrb-m-total"></span>
                </div>
                <button onclick="window.print()" class="acrb-btn-print"><?php esc_html_e('Print PDF', 'awesome-car-rental'); ?></button>
            </div>
        </div>
    </div>

    <style>
        .acrb-acc-wrapper { display: flex; gap: 40px; margin: 20px 0; font-family: sans-serif; }
        .acrb-acc-nav { width: 250px; }
        .acrb-nav-user { display: flex; align-items: center; gap: 12px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .acrb-nav-avatar img { border-radius: 50%; }
        .acrb-nav-info strong { display: block; color: #111; }
        .acrb-nav-info span { font-size: 11px; color: #3b82f6; font-weight: 700; text-transform: uppercase; }

        #acrb-nav-list { list-style: none; padding: 0; margin: 0; }
        #acrb-nav-list li a { display: flex; align-items: center; gap: 10px; padding: 12px; text-decoration: none; color: #555; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        #acrb-nav-list li.active a { background: #f0f7ff; color: #2563eb; font-weight: 600; }
        #acrb-nav-list li a:hover:not(.active) { background: #f9fafb; }

        .acrb-acc-content { flex: 1; }
        .acrb-stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 25px 0; }
        .acrb-stat-card { background: #fff; border: 1px solid #eee; padding: 20px; border-radius: 12px; text-align: center; }
        .acrb-stat-val { font-size: 22px; font-weight: 800; color: #111; }
        .acrb-stat-label { font-size: 12px; color: #888; margin-top: 4px; }

        .acrb-booking-card { background: #fff; border: 1px solid #eee; border-radius: 12px; margin-bottom: 16px; transition: 0.2s; }
        .acrb-booking-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .acrb-card-main { display: flex; gap: 15px; padding: 15px; align-items: center; }
        .acrb-car-icon { background: #f4f4f4; color: #2563eb; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .acrb-status-pill { font-size: 10px; padding: 3px 10px; border-radius: 20px; font-weight: 700; text-transform: uppercase; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .acrb-card-footer { background: #f9fafb; padding: 10px 15px; border-top: 1px solid #f1f1f1; display: flex; justify-content: space-between; align-items: center; }
        .acrb-price-amount { font-weight: 800; color: #111; font-size: 1.1rem; }

        .acrb-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .acrb-form-group { margin-bottom: 15px; }
        .acrb-form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        .acrb-form-group input, .acrb-form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .acrb-btn-submit { background: #2563eb; color: #fff; border: none; padding: 12px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; width: 100%; }

        .acrb-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .acrb-modal-content { background: #fff; margin: 10% auto; padding: 30px; border-radius: 16px; width: 380px; position: relative; }
        .acrb-modal-close { position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer; color: #aaa; }
        .acrb-r-item { display: flex; justify-content: space-between; margin: 10px 0; font-size: 14px; }
        .acrb-r-total { display: flex; justify-content: space-between; border-top: 2px dashed #eee; padding-top: 15px; font-weight: 800; font-size: 18px; }
        .acrb-btn-print { width: 100%; margin-top: 20px; padding: 12px; background: #111; color: #fff; border-radius: 8px; border: none; cursor: pointer; }

        .acrb-tab-panel { display: none; }
        .acrb-tab-panel.active { display: block; }
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

        // MODAL LOGIC (FIXED FOR NaN)
        const modal = document.getElementById('acrb-receipt-modal');
        const detailBtns = document.querySelectorAll('.open-receipt');
        const closeBtn = document.querySelector('.acrb-modal-close');

        detailBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Pull directly from data attributes as strings
                document.getElementById('acrb-m-id').innerText = 'ID: #' + this.dataset.id;
                document.getElementById('acrb-m-car').innerText = this.dataset.car;
                document.getElementById('acrb-m-date').innerText = this.dataset.date;
                document.getElementById('acrb-m-total').innerText = this.dataset.total;
                modal.style.display = 'block';
            });
        });

        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (e) => { if(e.target == modal) modal.style.display = 'none'; };
    });
    </script>

    <?php
    return ob_get_clean();
});