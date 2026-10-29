<?php
/**
 * [acrb_account]
 * SaaS Account Dashboard with Style Customizer Token Binding & Crisp Inline SVGs
 * Location: templates/shortcodes/account.php (or shortcodes directory)
 *
 * @package Awesome_Car_Rental
 * @since   3.3.1
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'acrb_account', 'acrb_render_account_dashboard_shortcode' );

function acrb_render_account_dashboard_shortcode(): string {
    // 1. Fetch Dynamic Styling & Typography Tokens from Style Customizer
    $primary_color   = sanitize_hex_color( (string) get_option( 'acrb_style_primary_color', '#4f46e5' ) );
    $secondary_color = sanitize_hex_color( (string) get_option( 'acrb_style_secondary_color', '#64748b' ) );
    $text_color      = sanitize_hex_color( (string) get_option( 'acrb_style_text_color', '#0f172a' ) );
    $accent_color    = sanitize_hex_color( (string) get_option( 'acrb_style_accent_color', '#059669' ) );
    $font_slug       = sanitize_text_field( (string) get_option( 'acrb_style_font_family', 'jakarta' ) );

    $font_stacks = array(
        'jakarta'    => "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif",
        'inter'      => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
        'poppins'    => "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif",
        'montserrat' => "'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif",
        'roboto'     => "'Roboto', -apple-system, BlinkMacSystemFont, sans-serif",
    );
    $selected_font = $font_stacks[ $font_slug ] ?? $font_stacks['jakarta'];

    // 2. Authentication Check & Lock Screen
    if ( ! is_user_logged_in() ) {
        $login_url = wp_login_url( get_permalink() );
        ob_start();
        ?>
        <style>
            :root {
                --acrb-portal-primary: <?php echo esc_html( $primary_color ); ?>;
                --acrb-portal-secondary: <?php echo esc_html( $secondary_color ); ?>;
                --acrb-portal-text: <?php echo esc_html( $text_color ); ?>;
                --acrb-portal-accent: <?php echo esc_html( $accent_color ); ?>;
                --acrb-portal-font: <?php echo esc_html( $selected_font ); ?>;
            }
            .acrb-auth-locked {
                max-width: 440px;
                margin: 60px auto;
                padding: 44px 32px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);
                font-family: var(--acrb-portal-font);
                color: var(--acrb-portal-text);
                box-sizing: border-box;
            }
            .acrb-auth-locked-icon {
                width: 60px;
                height: 60px;
                margin: 0 auto 18px;
                background: #f1f5f9;
                color: var(--acrb-portal-primary);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .acrb-auth-locked-icon svg {
                width: 28px;
                height: 28px;
            }
            .acrb-auth-locked h2 {
                margin: 0 0 8px;
                font-size: 20px;
                font-weight: 800;
                color: var(--acrb-portal-text);
            }
            .acrb-auth-locked p {
                color: var(--acrb-portal-secondary);
                font-size: 13.5px;
                line-height: 1.5;
                margin: 0 0 24px;
            }
            .acrb-auth-btn-primary {
                background: var(--acrb-portal-primary);
                color: #ffffff !important;
                text-decoration: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 13.5px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
                transition: opacity 0.15s ease, transform 0.15s ease;
            }
            .acrb-auth-btn-primary:hover {
                opacity: 0.92;
                transform: translateY(-1px);
            }
        </style>
        <div class="acrb-auth-locked">
            <div class="acrb-auth-locked-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0110 0v4"></path>
                </svg>
            </div>
            <h2><?php esc_html_e( 'Secure Customer Portal', 'awesome-car-rental' ); ?></h2>
            <p><?php esc_html_e( 'Please sign in to access your reservation history, download receipts, and manage your profile details.', 'awesome-car-rental' ); ?></p>
            <a href="<?php echo esc_url( $login_url ); ?>" class="acrb-auth-btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span><?php esc_html_e( 'Sign In to Your Account', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    $user    = wp_get_current_user();
    $user_id = $user->ID;

    // 3. Dynamic Currency Settings & Formatting
    $currency_symbol = get_option( 'acrb_currency', '$' );
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );
    $rental_unit     = get_option( 'acrb_rental_unit', 'day' );
    $update_msg      = '';

    $format_price = static function( float $val ) use ( $currency_symbol, $currency_pos ): string {
        $formatted = number_format( $val, 2 );
        return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
    };

    // 4. Handle Profile Updates & Password Changes
    if ( isset( $_POST['acrb_update_account'], $_POST['acrb_acc_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['acrb_acc_nonce'] ) ), 'acrb_save_details' ) ) {
        $fname   = isset( $_POST['fname'] ) ? sanitize_text_field( wp_unslash( $_POST['fname'] ) ) : '';
        $lname   = isset( $_POST['lname'] ) ? sanitize_text_field( wp_unslash( $_POST['lname'] ) ) : '';
        $phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $address = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

        update_user_meta( $user_id, 'first_name', $fname );
        update_user_meta( $user_id, 'last_name', $lname );
        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, 'acrb_address', $address );

        $new_pass  = ! empty( $_POST['new_pass'] ) ? (string) wp_unslash( $_POST['new_pass'] ) : '';
        $conf_pass = ! empty( $_POST['conf_pass'] ) ? (string) wp_unslash( $_POST['conf_pass'] ) : '';

        if ( ! empty( $new_pass ) ) {
            if ( $new_pass === $conf_pass ) {
                wp_set_password( $new_pass, $user_id );
                $update_msg = '<div class="acrb-alert success">' . esc_html__( 'Password updated successfully. Please log in again.', 'awesome-car-rental' ) . '</div>';
            } else {
                $update_msg = '<div class="acrb-alert error">' . esc_html__( 'The new passwords provided do not match.', 'awesome-car-rental' ) . '</div>';
            }
        } else {
            $update_msg = '<div class="acrb-alert success">' . esc_html__( 'Profile details saved and updated successfully.', 'awesome-car-rental' ) . '</div>';
        }
    }

    // 5. Data Extraction: User Bookings
    $normalized_bookings = array();

    $cpt_bookings = get_posts( array(
        'post_type'   => 'acrb_bookings',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query'  => array(
            array(
                'key'     => 'user_id',
                'value'   => $user_id,
                'compare' => '=',
            ),
        ),
        'orderby'     => 'date',
        'order'       => 'DESC',
    ) );

    if ( ! empty( $cpt_bookings ) ) {
        foreach ( $cpt_bookings as $b ) {
            $b_id       = absint( $b->ID );
            $car_id     = absint( get_post_meta( $b_id, 'car_id', true ) );
            $raw_price  = get_post_meta( $b_id, 'total_price', true ) ?: get_post_meta( $b_id, 'total_amount', true );
            $clean_amt  = floatval( preg_replace( '/[^0-9.]/', '', (string) $raw_price ) );
            $pickup     = (string) get_post_meta( $b_id, 'pickup_date', true );
            $return_d   = (string) get_post_meta( $b_id, 'return_date', true );
            $status     = sanitize_key( (string) ( get_post_meta( $b_id, 'status', true ) ?: 'pending' ) );
            $car_title  = $car_id ? get_the_title( $car_id ) : __( 'Rental Fleet Car', 'awesome-car-rental' );

            $normalized_bookings[] = array(
                'id'          => $b_id,
                'code'        => 'ACR-' . $b_id,
                'car_name'    => $car_title,
                'pickup_date' => $pickup ?: '—',
                'return_date' => $return_d ?: '—',
                'total_amt'   => $clean_amt,
                'status'      => $status,
            );
        }
    } else {
        global $wpdb;
        $table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $table_exists   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_bookings ) );

        if ( $table_exists === $table_bookings ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $db_bookings = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_bookings} WHERE user_id = %d ORDER BY id DESC",
                    $user_id
                ),
                ARRAY_A
            );

            if ( ! empty( $db_bookings ) ) {
                foreach ( $db_bookings as $dbb ) {
                    $b_id = absint( $dbb['id'] );
                    $normalized_bookings[] = array(
                        'id'          => $b_id,
                        'code'        => ! empty( $dbb['booking_code'] ) ? $dbb['booking_code'] : 'ACR-' . $b_id,
                        'car_name'    => ! empty( $dbb['car_name'] ) ? $dbb['car_name'] : __( 'Fleet Vehicle', 'awesome-car-rental' ),
                        'pickup_date' => ! empty( $dbb['pickup_date'] ) ? $dbb['pickup_date'] : '—',
                        'return_date' => ! empty( $dbb['return_date'] ) ? $dbb['return_date'] : '—',
                        'total_amt'   => floatval( $dbb['total_amount'] ?? 0 ),
                        'status'      => sanitize_key( (string) ( $dbb['booking_status'] ?? 'pending' ) ),
                    );
                }
            }
        }
    }

    $total_trips = count( $normalized_bookings );
    $reg_year    = gmdate( 'Y', strtotime( $user->user_registered ) );

    ob_start();
    ?>
    <style>
        :root {
            --acrb-portal-primary: <?php echo esc_html( $primary_color ); ?>;
            --acrb-portal-secondary: <?php echo esc_html( $secondary_color ); ?>;
            --acrb-portal-text: <?php echo esc_html( $text_color ); ?>;
            --acrb-portal-accent: <?php echo esc_html( $accent_color ); ?>;
            --acrb-portal-font: <?php echo esc_html( $selected_font ); ?>;
        }

        .acrb-acc-wrapper {
            display: flex;
            gap: 28px;
            margin: 30px auto;
            max-width: 1200px;
            font-family: var(--acrb-portal-font);
            color: var(--acrb-portal-text);
            box-sizing: border-box;
        }
        .acrb-acc-wrapper *,
        .acrb-acc-wrapper *::before,
        .acrb-acc-wrapper *::after {
            box-sizing: border-box;
        }

        /* SVG Sizing */
        .acrb-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Sidebar Navigation */
        .acrb-acc-nav {
            width: 280px;
            flex-shrink: 0;
        }
        .acrb-nav-user {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            padding: 18px 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .acrb-nav-avatar img {
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        .acrb-nav-info strong {
            display: block;
            color: var(--acrb-portal-text);
            font-size: 14.5px;
            font-weight: 800;
            line-height: 1.25;
        }
        .acrb-nav-info span {
            font-size: 11px;
            color: var(--acrb-portal-primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        #acrb-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        #acrb-nav-list li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            text-decoration: none;
            color: var(--acrb-portal-secondary);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
        }
        #acrb-nav-list li a .acrb-icon {
            color: var(--acrb-portal-secondary);
            transition: color 0.15s ease;
        }
        #acrb-nav-list li.active a {
            background: #eff6ff;
            color: var(--acrb-portal-primary);
            border-left-color: var(--acrb-portal-primary);
        }
        #acrb-nav-list li.active a .acrb-icon {
            color: var(--acrb-portal-primary);
        }
        #acrb-nav-list li a:hover:not(.active) {
            background: #f8fafc;
            color: var(--acrb-portal-text);
        }
        .acrb-nav-logout a {
            color: #dc2626 !important;
            border-top: 1px solid #f1f5f9;
        }
        .acrb-nav-logout a:hover {
            background: #fef2f2 !important;
        }
        .acrb-nav-logout a .acrb-icon {
            color: #dc2626 !important;
        }

        /* Main Content Container */
        .acrb-acc-content {
            flex: 1;
            min-width: 0;
        }

        /* Dashboard Banner */
        .acrb-dash-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-left: 4px solid var(--acrb-portal-primary);
            border-radius: 14px;
            padding: 22px 26px;
            margin-bottom: 22px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .acrb-dash-header h2 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: 800;
            color: var(--acrb-portal-text);
            letter-spacing: -0.02em;
        }
        .acrb-dash-header p {
            margin: 0;
            color: var(--acrb-portal-secondary);
            font-size: 13.5px;
        }

        /* Stats Grid */
        .acrb-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }
        .acrb-stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 22px;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .acrb-stat-icon {
            width: 44px;
            height: 44px;
            background: #f1f5f9;
            color: var(--acrb-portal-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .acrb-stat-icon .acrb-icon {
            width: 22px;
            height: 22px;
        }
        .acrb-stat-val {
            font-size: 24px;
            font-weight: 800;
            color: var(--acrb-portal-text);
            letter-spacing: -0.02em;
        }
        .acrb-stat-label {
            font-size: 11.5px;
            color: var(--acrb-portal-secondary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Promo CTA Banner */
        .acrb-promo-banner {
            background: linear-gradient(135deg, var(--acrb-portal-primary) 0%, #1e1b4b 100%);
            color: #ffffff;
            border-radius: 14px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.15);
            gap: 16px;
        }
        .acrb-promo-content h3 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 800;
            color: #ffffff;
        }
        .acrb-promo-content p {
            margin: 0;
            font-size: 13.5px;
            opacity: 0.9;
            color: #ffffff;
        }
        .acrb-btn-white {
            background: #ffffff;
            color: var(--acrb-portal-primary) !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.15s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .acrb-btn-white:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }

        /* Tab Panels */
        .acrb-tab-panel {
            display: none;
        }
        .acrb-tab-panel.active {
            display: block;
            animation: acrbFade 0.25s ease;
        }
        @keyframes acrbFade {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .acrb-panel-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--acrb-portal-text);
            margin: 0 0 18px;
            letter-spacing: -0.01em;
        }

        /* Rental History Cards */
        .acrb-booking-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .acrb-booking-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 20px -4px rgba(15, 23, 42, 0.06);
        }
        .acrb-card-main {
            display: flex;
            gap: 18px;
            padding: 20px 22px;
            align-items: center;
        }
        .acrb-car-icon {
            background: #f8fafc;
            color: var(--acrb-portal-primary);
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .acrb-car-icon .acrb-icon {
            width: 24px;
            height: 24px;
        }
        .acrb-booking-details {
            flex: 1;
        }
        .acrb-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .acrb-car-name {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            color: var(--acrb-portal-text);
        }

        /* Status Badges */
        .acrb-status-pill {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: inline-block;
        }
        .status-completed, .status-confirmed, .status-active {
            background: #ecfdf5;
            color: var(--acrb-portal-accent);
            border: 1px solid #a7f3d0;
        }
        .status-pending {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .status-cancelled {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .acrb-card-meta {
            display: flex;
            gap: 16px;
            font-size: 12.5px;
            color: var(--acrb-portal-secondary);
            font-weight: 500;
            flex-wrap: wrap;
        }
        .acrb-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .acrb-meta-item .acrb-icon {
            width: 14px;
            height: 14px;
            color: var(--acrb-portal-secondary);
        }

        .acrb-card-footer {
            background: #f8fafc;
            padding: 14px 22px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .acrb-price-box {
            display: flex;
            flex-direction: column;
        }
        .acrb-price-label {
            font-size: 11px;
            color: var(--acrb-portal-secondary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .acrb-price-amount {
            font-weight: 800;
            color: var(--acrb-portal-accent);
            font-size: 18px;
            letter-spacing: -0.01em;
        }

        .acrb-btn-outline {
            background: #ffffff;
            color: var(--acrb-portal-text);
            border: 1px solid #cbd5e1;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
            font-family: inherit;
        }
        .acrb-btn-outline:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .acrb-btn-outline .acrb-icon {
            width: 14px;
            height: 14px;
            color: var(--acrb-portal-secondary);
        }

        /* Profile & Security Forms */
        .acrb-settings-form {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .acrb-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .acrb-form-group {
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
        }
        .acrb-form-group label {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--acrb-portal-text);
        }
        .acrb-input,
        .acrb-textarea {
            width: 100% !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
            font-size: 13.5px !important;
            color: var(--acrb-portal-text) !important;
            background: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
            transition: border-color 0.15s ease, background-color 0.15s ease !important;
            font-family: inherit !important;
        }
        .acrb-input {
            height: 42px !important;
            line-height: 40px !important;
        }
        .acrb-textarea {
            padding: 12px 14px !important;
            height: auto !important;
            resize: vertical;
        }
        .acrb-input:hover,
        .acrb-textarea:hover {
            border-color: #94a3b8 !important;
            background: #ffffff !important;
        }
        .acrb-input:focus,
        .acrb-textarea:focus {
            background: #ffffff !important;
            border-color: var(--acrb-portal-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08) !important;
            outline: none !important;
        }
        .acrb-readonly {
            background: #f1f5f9 !important;
            color: var(--acrb-portal-secondary) !important;
            cursor: not-allowed !important;
            border-color: #e2e8f0 !important;
        }

        .acrb-btn-submit {
            background: var(--acrb-portal-primary);
            color: #ffffff;
            border: none;
            padding: 11px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
            transition: opacity 0.15s ease, transform 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .acrb-btn-submit:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        /* Alerts */
        .acrb-alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .acrb-alert.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .acrb-alert.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Empty State */
        .acrb-empty-box {
            padding: 48px 20px;
            text-align: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            color: var(--acrb-portal-secondary);
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .acrb-empty-box .acrb-icon {
            width: 42px;
            height: 42px;
            color: #cbd5e1;
            margin-bottom: 10px;
        }
        .acrb-empty-box p {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
        }

        /* Modal */
        .acrb-modal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }
        .acrb-modal-content {
            background: #ffffff;
            margin: 6% auto;
            padding: 32px;
            border-radius: 16px;
            width: 440px;
            max-width: 90%;
            position: relative;
            box-shadow: 0 25px 35px -10px rgba(0, 0, 0, 0.15);
            border: 1px solid #e2e8f0;
            font-family: var(--acrb-portal-font);
        }
        .acrb-modal-close {
            position: absolute;
            right: 20px;
            top: 18px;
            font-size: 22px;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.15s ease;
            line-height: 1;
        }
        .acrb-modal-close:hover {
            color: #0f172a;
        }
        .acrb-receipt-header {
            text-align: center;
            margin-bottom: 22px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 18px;
        }
        .acrb-receipt-icon-wrap {
            width: 52px;
            height: 52px;
            background: #f1f5f9;
            color: var(--acrb-portal-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        .acrb-receipt-icon-wrap svg {
            width: 24px;
            height: 24px;
        }
        .acrb-receipt-header h3 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
            color: var(--acrb-portal-text);
        }
        .acrb-receipt-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        .acrb-r-item {
            display: flex;
            justify-content: space-between;
            font-size: 13.5px;
            color: var(--acrb-portal-secondary);
            font-weight: 500;
        }
        .acrb-r-total {
            display: flex;
            justify-content: space-between;
            border-top: 2px dashed #e2e8f0;
            padding-top: 16px;
            font-weight: 800;
            font-size: 17px;
            color: var(--acrb-portal-text);
        }
        .acrb-btn-print {
            width: 100%;
            margin-top: 20px;
            height: 42px;
            background: var(--acrb-portal-text);
            color: #ffffff;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: opacity 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .acrb-btn-print:hover {
            opacity: 0.88;
        }

        /* Printable Invoice Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            #acrb-receipt-modal,
            #acrb-receipt-modal * {
                visibility: visible;
            }
            #acrb-receipt-modal {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: #ffffff;
                backdrop-filter: none;
            }
            .acrb-modal-content {
                box-shadow: none;
                border: 1px solid #ccc;
                margin: 0 auto;
                width: 100%;
            }
            .acrb-modal-close,
            .acrb-btn-print {
                display: none !important;
            }
        }

        @media (max-width: 860px) {
            .acrb-acc-wrapper {
                flex-direction: column;
            }
            .acrb-acc-nav {
                width: 100%;
            }
            .acrb-form-row {
                grid-template-columns: 1fr;
            }
            .acrb-stats-grid {
                grid-template-columns: 1fr;
            }
            .acrb-promo-banner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <div class="acrb-acc-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="acrb-acc-nav">
            <div class="acrb-nav-user">
                <div class="acrb-nav-avatar"><?php echo get_avatar( $user_id, 46 ); ?></div>
                <div class="acrb-nav-info">
                    <strong><?php echo esc_html( $user->display_name ); ?></strong>
                    <span><?php esc_html_e( 'Customer Portal', 'awesome-car-rental' ); ?></span>
                </div>
            </div>
            <ul id="acrb-nav-list">
                <li class="active">
                    <a class="acrb-tab-trigger" data-tab="acrb-dash">
                        <svg class="acrb-icon" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="7" height="9"></rect>
                            <rect x="14" y="3" width="7" height="5"></rect>
                            <rect x="14" y="12" width="7" height="9"></rect>
                            <rect x="3" y="16" width="7" height="5"></rect>
                        </svg>
                        <span><?php esc_html_e( 'Dashboard', 'awesome-car-rental' ); ?></span>
                    </a>
                </li>
                <li>
                    <a class="acrb-tab-trigger" data-tab="acrb-bookings-list">
                        <svg class="acrb-icon" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span><?php esc_html_e( 'Rental History', 'awesome-car-rental' ); ?></span>
                    </a>
                </li>
                <li>
                    <a class="acrb-tab-trigger" data-tab="acrb-profile-form">
                        <svg class="acrb-icon" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span><?php esc_html_e( 'Profile Details', 'awesome-car-rental' ); ?></span>
                    </a>
                </li>
                <li>
                    <a class="acrb-tab-trigger" data-tab="acrb-security-form">
                        <svg class="acrb-icon" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span><?php esc_html_e( 'Security', 'awesome-car-rental' ); ?></span>
                    </a>
                </li>
                <li class="acrb-nav-logout">
                    <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
                        <svg class="acrb-icon" viewBox="0 0 24 24">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span><?php esc_html_e( 'Sign Out', 'awesome-car-rental' ); ?></span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="acrb-acc-content">
            <?php 
            if ( ! empty( $update_msg ) ) {
                $allowed = array( 'div' => array( 'class' => array() ) );
                echo wp_kses( $update_msg, $allowed );
            }
            ?>

            <!-- Tab 1: Dashboard Home -->
            <div id="acrb-dash" class="acrb-tab-panel active">
                <div class="acrb-dash-header">
                    <h2><?php 
                        /* translators: %s: User first or display name */
                        printf( esc_html__( 'Welcome back, %s', 'awesome-car-rental' ), esc_html( $user->first_name ?: $user->display_name ) ); 
                    ?></h2>
                    <p><?php esc_html_e( 'Manage your upcoming car reservations, travel history, and security preferences.', 'awesome-car-rental' ); ?></p>
                </div>

                <div class="acrb-stats-grid">
                    <div class="acrb-stat-card">
                        <div class="acrb-stat-icon">
                            <svg class="acrb-icon" viewBox="0 0 24 24">
                                <path d="M5 17h14v-5l-2-6H7l-2 6v5z"></path>
                                <circle cx="7.5" cy="17.5" r="2.5"></circle>
                                <circle cx="16.5" cy="17.5" r="2.5"></circle>
                            </svg>
                        </div>
                        <div class="acrb-stat-val"><?php echo absint( $total_trips ); ?></div>
                        <div class="acrb-stat-label"><?php esc_html_e( 'Total Rentals Booked', 'awesome-car-rental' ); ?></div>
                    </div>
                    <div class="acrb-stat-card">
                        <div class="acrb-stat-icon">
                            <svg class="acrb-icon" viewBox="0 0 24 24">
                                <circle cx="12" cy="8" r="7"></circle>
                                <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                            </svg>
                        </div>
                        <div class="acrb-stat-val"><?php echo esc_html( $reg_year ); ?></div>
                        <div class="acrb-stat-label"><?php esc_html_e( 'Member Since', 'awesome-car-rental' ); ?></div>
                    </div>
                </div>

                <div class="acrb-promo-banner">
                    <div class="acrb-promo-content">
                        <h3><?php esc_html_e( 'Planning your next road trip?', 'awesome-car-rental' ); ?></h3>
                        <p><?php esc_html_e( 'Explore our available fleet models with real-time reservation confirmation.', 'awesome-car-rental' ); ?></p>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/acrb-cars/' ) ); ?>" class="acrb-btn-white">
                        <span><?php esc_html_e( 'Browse Fleet', 'awesome-car-rental' ); ?></span>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Tab 2: Rental History -->
            <div id="acrb-bookings-list" class="acrb-tab-panel">
                <h2 class="acrb-panel-title"><?php esc_html_e( 'Rental History & Reservations', 'awesome-car-rental' ); ?></h2>
                
                <?php if ( ! empty( $normalized_bookings ) ) : foreach ( $normalized_bookings as $booking ) : 
                    $b_code       = esc_html( $booking['code'] );
                    $b_car        = esc_html( $booking['car_name'] );
                    $b_date       = esc_html( $booking['pickup_date'] . ' → ' . $booking['return_date'] );
                    $b_status     = esc_html( $booking['status'] );
                    $b_price_disp = $format_price( $booking['total_amt'] );
                    $status_class = 'status-' . sanitize_html_class( $booking['status'] );
                ?>
                <div class="acrb-booking-card">
                    <div class="acrb-card-main">
                        <div class="acrb-car-icon">
                            <svg class="acrb-icon" viewBox="0 0 24 24">
                                <path d="M5 17h14v-5l-2-6H7l-2 6v5z"></path>
                                <circle cx="7.5" cy="17.5" r="2.5"></circle>
                                <circle cx="16.5" cy="17.5" r="2.5"></circle>
                            </svg>
                        </div>
                        <div class="acrb-booking-details">
                            <div class="acrb-card-top">
                                <h4 class="acrb-car-name"><?php echo esc_html( $b_car ); ?></h4>
                                <span class="acrb-status-pill <?php echo esc_attr( $status_class ); ?>">
                                    ● <?php echo esc_html( ucfirst( $b_status ) ); ?>
                                </span>
                            </div>
                            <div class="acrb-card-meta">
                                <span class="acrb-meta-item">
                                    <svg class="acrb-icon" viewBox="0 0 24 24">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span><?php echo esc_html( $b_date ); ?></span>
                                </span>
                                <span class="acrb-meta-item">
                                    <svg class="acrb-icon" viewBox="0 0 24 24">
                                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"></path>
                                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                                    </svg>
                                    <span>#<?php echo esc_html( $b_code ); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="acrb-card-footer">
                        <div class="acrb-price-box">
                            <span class="acrb-price-label"><?php esc_html_e( 'Settled Total', 'awesome-car-rental' ); ?></span>
                            <span class="acrb-price-amount"><?php echo esc_html( $b_price_disp ); ?></span>
                        </div>
                        <button type="button" class="acrb-btn-outline open-receipt" 
                                data-id="<?php echo esc_attr( $b_code ); ?>" 
                                data-car="<?php echo esc_attr( $b_car ); ?>" 
                                data-total="<?php echo esc_attr( $b_price_disp ); ?>" 
                                data-date="<?php echo esc_attr( $b_date ); ?>"
                                data-status="<?php echo esc_attr( ucfirst( $b_status ) ); ?>">
                            <svg class="acrb-icon" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span><?php esc_html_e( 'View Receipt', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>
                </div>
                <?php endforeach; else : ?>
                    <div class="acrb-empty-box">
                        <svg class="acrb-icon" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <p><?php esc_html_e( 'You have not completed any vehicle rentals yet.', 'awesome-car-rental' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 3: Profile Settings -->
            <div id="acrb-profile-form" class="acrb-tab-panel">
                <h2 class="acrb-panel-title"><?php esc_html_e( 'Profile Details', 'awesome-car-rental' ); ?></h2>
                <form method="post" class="acrb-settings-form">
                    <?php wp_nonce_field( 'acrb_save_details', 'acrb_acc_nonce' ); ?>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label for="acrb_fname"><?php esc_html_e( 'First Name', 'awesome-car-rental' ); ?></label>
                            <input type="text" id="acrb_fname" name="fname" value="<?php echo esc_attr( $user->first_name ); ?>" required class="acrb-input">
                        </div>
                        <div class="acrb-form-group">
                            <label for="acrb_lname"><?php esc_html_e( 'Last Name', 'awesome-car-rental' ); ?></label>
                            <input type="text" id="acrb_lname" name="lname" value="<?php echo esc_attr( $user->last_name ); ?>" required class="acrb-input">
                        </div>
                    </div>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label for="acrb_phone"><?php esc_html_e( 'Contact Phone Number', 'awesome-car-rental' ); ?></label>
                            <input type="text" id="acrb_phone" name="phone" value="<?php echo esc_attr( (string) get_user_meta( $user_id, 'billing_phone', true ) ); ?>" class="acrb-input">
                        </div>
                        <div class="acrb-form-group">
                            <label for="acrb_email"><?php esc_html_e( 'Email Address (Account Identifier)', 'awesome-car-rental' ); ?></label>
                            <input type="text" id="acrb_email" value="<?php echo esc_attr( $user->user_email ); ?>" disabled class="acrb-input acrb-readonly">
                        </div>
                    </div>
                    <div class="acrb-form-group">
                        <label for="acrb_address"><?php esc_html_e( 'Residential / Billing Address', 'awesome-car-rental' ); ?></label>
                        <textarea id="acrb_address" name="address" rows="3" class="acrb-textarea"><?php echo esc_textarea( (string) get_user_meta( $user_id, 'acrb_address', true ) ); ?></textarea>
                    </div>
                    <div>
                        <button type="submit" name="acrb_update_account" class="acrb-btn-submit">
                            <svg class="acrb-icon" style="width:16px;height:16px;" viewBox="0 0 24 24">
                                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span><?php esc_html_e( 'Save Profile Details', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab 4: Security -->
            <div id="acrb-security-form" class="acrb-tab-panel">
                <h2 class="acrb-panel-title"><?php esc_html_e( 'Security & Password', 'awesome-car-rental' ); ?></h2>
                <form method="post" class="acrb-settings-form">
                    <?php wp_nonce_field( 'acrb_save_details', 'acrb_acc_nonce' ); ?>
                    <div class="acrb-form-row">
                        <div class="acrb-form-group">
                            <label for="acrb_new_pass"><?php esc_html_e( 'New Password', 'awesome-car-rental' ); ?></label>
                            <input type="password" id="acrb_new_pass" name="new_pass" placeholder="••••••••" class="acrb-input" autocomplete="new-password">
                        </div>
                        <div class="acrb-form-group">
                            <label for="acrb_conf_pass"><?php esc_html_e( 'Confirm New Password', 'awesome-car-rental' ); ?></label>
                            <input type="password" id="acrb_conf_pass" name="conf_pass" placeholder="••••••••" class="acrb-input" autocomplete="new-password">
                        </div>
                    </div>
                    <div>
                        <button type="submit" name="acrb_update_account" class="acrb-btn-submit">
                            <svg class="acrb-icon" style="width:16px;height:16px;" viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0110 0v4"></path>
                            </svg>
                            <span><?php esc_html_e( 'Update Password', 'awesome-car-rental' ); ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Booking Details Modal -->
    <div id="acrb-receipt-modal" class="acrb-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="acrb-modal-content">
            <span class="acrb-modal-close" aria-label="<?php esc_attr_e( 'Close', 'awesome-car-rental' ); ?>">&times;</span>
            <div class="acrb-receipt-header">
                <div class="acrb-receipt-icon-wrap">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3><?php esc_html_e( 'Rental Voucher & Invoice', 'awesome-car-rental' ); ?></h3>
                <span id="acrb-m-id" style="color:var(--acrb-portal-secondary); font-size:12px; font-weight:700;"></span>
            </div>
            <div class="acrb-receipt-list">
                <div class="acrb-r-item">
                    <span><?php esc_html_e( 'Vehicle Model:', 'awesome-car-rental' ); ?></span>
                    <strong id="acrb-m-car" style="color:var(--acrb-portal-text);"></strong>
                </div>
                <div class="acrb-r-item">
                    <span><?php esc_html_e( 'Rental Window:', 'awesome-car-rental' ); ?></span>
                    <span id="acrb-m-date" style="color:var(--acrb-portal-secondary);"></span>
                </div>
                <div class="acrb-r-item">
                    <span><?php esc_html_e( 'Booking Status:', 'awesome-car-rental' ); ?></span>
                    <span id="acrb-m-status" class="acrb-status-pill status-confirmed"></span>
                </div>
            </div>
            <div class="acrb-r-total">
                <span><?php esc_html_e( 'Settled Amount:', 'awesome-car-rental' ); ?></span>
                <span id="acrb-m-total" style="color:var(--acrb-portal-accent);"></span>
            </div>
            <button type="button" onclick="window.print()" class="acrb-btn-print">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span><?php esc_html_e( 'Print Rental Voucher (PDF)', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const triggers = document.querySelectorAll('.acrb-tab-trigger');
        triggers.forEach(function(t) {
            t.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-tab');
                document.querySelectorAll('.acrb-tab-panel').forEach(function(p) {
                    p.classList.remove('active');
                });
                const targetPanel = document.getElementById(targetId);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
                document.querySelectorAll('.acrb-acc-nav li').forEach(function(li) {
                    li.classList.remove('active');
                });
                this.parentElement.classList.add('active');
            });
        });

        const modal      = document.getElementById('acrb-receipt-modal');
        const detailBtns = document.querySelectorAll('.open-receipt');
        const closeBtn   = document.querySelector('.acrb-modal-close');

        if (modal && detailBtns.length > 0) {
            detailBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('acrb-m-id').textContent     = '#' + this.getAttribute('data-id');
                    document.getElementById('acrb-m-car').textContent    = this.getAttribute('data-car');
                    document.getElementById('acrb-m-date').textContent   = this.getAttribute('data-date');
                    document.getElementById('acrb-m-total').textContent  = this.getAttribute('data-total');
                    
                    const statusElem = document.getElementById('acrb-m-status');
                    if (statusElem) {
                        statusElem.textContent = this.getAttribute('data-status');
                    }

                    modal.style.display = 'block';
                    modal.setAttribute('aria-hidden', 'false');
                });
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                });
            }

            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}