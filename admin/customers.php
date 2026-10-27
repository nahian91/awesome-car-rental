<?php
/**
 * Customers Tab - Professional CRM Layout
 * Location: admin/customers.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_customers_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    // 1. DYNAMIC SETTINGS FETCHING
    $currency_symbol = get_option( 'acrb_currency', '£' );
    $currency_pos    = get_option( 'acrb_currency_pos', 'left' );

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'awesome_car_rental';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_view = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
    ?>

    <style>
        /* Modern SaaS Layout Theme */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 20px 20px 0 0; }
        
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
        .acrb-header-actions { display: flex; align-items: center; gap: 12px; }
        .acrb-header-flex .button, .acrb-header-flex a.afon-btn-action, .acrb-header-flex a.afon-btn-add { 
            background: #ffffff; 
            color: #334155; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 10px 18px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.25s ease; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .acrb-header-flex .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-header-flex a:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .acrb-header-flex a.afon-btn-add { background: #3b82f6; color: #fff; border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); }
        .acrb-header-flex a.afon-btn-add:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

        /* CRM Profile Grid & Cards */
        .profile-grid { display: grid; grid-template-columns: 350px 1fr; gap: 24px; }
        .profile-side-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px 24px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.01); height: fit-content; }
        .avatar-main { border-radius: 50%; margin-bottom: 16px; border: 3px solid #eff6ff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .profile-name { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; }
        
        .afon-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-vip { background: #eff6ff; color: #3b82f6; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-light { background: #f1f5f9; color: #475569; }

        .profile-info-list { margin-top: 24px; text-align: left; border-top: 1px solid #f1f5f9; padding-top: 20px; display: flex; flex-direction: column; gap: 14px; }
        .info-item { display: flex; justify-content: space-between; font-size: 13px; }
        .info-label { color: #64748b; font-weight: 500; }
        .info-value { color: #0f172a; font-weight: 600; }

        .acrb-main-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .card-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
        .card-title { margin: 0; font-size: 16px; font-weight: 600; color: #0f172a; }

        /* Tables */
        .wp-list-table { border: none !important; margin: 0 !important; box-shadow: none !important; }
        .wp-list-table thead th { background: #f8fafc !important; color: #475569 !important; font-weight: 600 !important; font-size: 12px !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; padding: 14px 16px !important; border-bottom: 1px solid #e2e8f0 !important; }
        .wp-list-table tbody td { padding: 16px !important; vertical-align: middle !important; border-bottom: 1px solid #f1f5f9 !important; color: #334155 !important; }
        .wp-list-table tbody tr:last-child td { border-bottom: none !important; }
        .wp-list-table tbody tr:hover { background: #f8fafc !important; }

        .user-cell { display: flex; align-items: center; gap: 12px; }
        .user-avatar { border-radius: 50%; }
        .txt-brand { color: #3b82f6; }
        .txt-slate { color: #64748b; }

        .afon-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .afon-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .profile-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-wrap">

        <?php if ( $current_view > 0 ) : 
            $user = get_userdata( $current_view );
            if ( ! $user ) {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer not found.', 'awesome-car-rental' ) . '</p></div>';
                return;
            }

            $phone = get_user_meta( $current_view, 'billing_phone', true ) ?: 'N/A';

            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            $orders = get_posts( array(
                'post_type'     => 'acrb_bookings',
                'post_status'   => 'any',
                'numberposts'   => -1,
                'fields'        => 'ids',
                'no_found_rows' => true,
                'meta_query'    => array(
                    'relation' => 'OR',
                    array( 'key' => 'customer_id', 'value' => $current_view, 'compare' => '=' ),
                    array( 'key' => 'customer_email', 'value' => $user->user_email, 'compare' => '=' ),
                ),
            ) );
        ?>
            <div class="acrb-header-flex">
                <div class="acrb-title-wrapper">
                    <div class="acrb-title-icon">
                        <span class="dashicons dashicons-businessman"></span>
                    </div>
                    <div>
                        <h1 class="acrb-main-title"><?php echo esc_html( $user->display_name ); ?></h1>
                        <p class="acrb-sub-title"><?php esc_html_e( 'Customer CRM Profile & Booking History', 'awesome-car-rental' ); ?></p>
                    </div>
                </div>
                
                <div class="acrb-header-actions">
                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, 'tab' => 'customers' ), admin_url( 'admin.php' ) ) ); ?>" class="afon-btn-action">
                        <span class="dashicons dashicons-arrow-left-alt"></span> 
                        <span><?php esc_html_e( 'Back to Directory', 'awesome-car-rental' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( get_edit_user_link( $current_view ) ); ?>" class="afon-btn-add">
                        <span class="dashicons dashicons-edit"></span> 
                        <span><?php esc_html_e( 'Edit Profile', 'awesome-car-rental' ); ?></span>
                    </a>
                </div>
            </div>

            <div class="profile-grid">
                <div class="profile-side-card">
                    <?php echo get_avatar( $current_view, 100, '', '', array( 'class' => 'avatar-main' ) ); ?>
                    <h2 class="profile-name"><?php echo esc_html( $user->display_name ); ?></h2>
                    <span class="afon-badge badge-vip"><?php esc_html_e( 'Account Holder', 'awesome-car-rental' ); ?></span>

                    <div class="profile-info-list">
                        <div class="info-item"><label class="info-label"><?php esc_html_e( 'Email', 'awesome-car-rental' ); ?></label><span class="info-value"><?php echo esc_html( $user->user_email ); ?></span></div>
                        <div class="info-item"><label class="info-label"><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></label><span class="info-value"><?php echo esc_html( $phone ); ?></span></div>
                        <div class="info-item"><label class="info-label"><?php esc_html_e( 'Joined', 'awesome-car-rental' ); ?></label><span class="info-value"><?php echo esc_html( date_i18n( 'M Y', strtotime( $user->user_registered ) ) ); ?></span></div>
                    </div>
                </div>

                <div class="acrb-main-card">
                    <div class="card-header">
                        <h3 class="card-title"><?php esc_html_e( 'Booking History', 'awesome-car-rental' ); ?></h3>
                    </div>
                    <div class="afon-table-responsive">
                        <table id="history-table" class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Order ID', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Total Price', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $orders ) ) : foreach ( $orders as $o_id ) : 
                                    $status     = get_post_meta( $o_id, 'status', true ) ?: 'pending';
                                    $raw_price  = get_post_meta( $o_id, 'total_price', true );
                                    
                                    $clean_price   = preg_replace( '/[^0-9.]/', '', $raw_price );
                                    $formatted_num = number_format_i18n( floatval( $clean_price ), 2 );
                                    $display_price = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted_num : $formatted_num . $currency_symbol;
                                    
                                    $edit_url = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&action=edit&booking_id=' . $o_id );
                                ?>
                                <tr>
                                    <td><strong>#<?php echo absint( $o_id ); ?></strong></td>
                                    <td><strong class="txt-brand"><?php echo esc_html( $display_price ); ?></strong></td>
                                    <td><span class="afon-badge badge-success"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
                                    <td><?php echo esc_html( get_the_date( 'Y/m/d', $o_id ) ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="afon-btn-action">
                                            <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Booking', 'awesome-car-rental' ); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; else : ?>
                                    <tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No bookings found.', 'awesome-car-rental' ); ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else : ?>
            <div class="acrb-header-flex">
                <div class="acrb-title-wrapper">
                    <div class="acrb-title-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div>
                        <h1 class="acrb-main-title"><?php esc_html_e( 'Customer Directory', 'awesome-car-rental' ); ?></h1>
                        <p class="acrb-sub-title"><?php esc_html_e( 'Manage registered users, track lifetime value (LTV), and inspect CRM profiles.', 'awesome-car-rental' ); ?></p>
                    </div>
                </div>
                
                <div class="acrb-header-actions">
                    <a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" class="afon-btn-add">
                        <span class="dashicons dashicons-plus-alt2" style="font-size:14px; width:14px; height:14px;"></span> 
                        <span><?php esc_html_e( 'Create New Client', 'awesome-car-rental' ); ?></span>
                    </a>
                </div>
            </div>

            <div class="acrb-main-card">
                <table id="crm-table" class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Customer Name', 'awesome-car-rental' ); ?></th>
                            <th><?php esc_html_e( 'Contact Email', 'awesome-car-rental' ); ?></th>
                            <th><?php esc_html_e( 'LTV (Total Spent)', 'awesome-car-rental' ); ?></th>
                            <th><?php esc_html_e( 'Activity', 'awesome-car-rental' ); ?></th>
                            <th width="150" style="text-align:right;"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $users = get_users( array( 'number' => 100, 'orderby' => 'registered', 'order' => 'DESC' ) );
                        foreach ( $users as $u ) :
                            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                            $user_orders = get_posts( array(
                                'post_type'     => 'acrb_bookings',
                                'post_status'   => 'any',
                                'numberposts'   => -1,
                                'fields'        => 'ids',
                                'no_found_rows' => true,
                                'meta_query'    => array(
                                    'relation' => 'OR',
                                    array( 'key' => 'customer_id', 'value' => $u->ID, 'compare' => '=' ),
                                    array( 'key' => 'customer_email', 'value' => $u->user_email, 'compare' => '=' ),
                                ),
                            ) );
                            
                            $order_count = count( $user_orders );
                            $user_ltv    = 0; 
                            foreach ( $user_orders as $bo_id ) { 
                                $raw_val   = get_post_meta( $bo_id, 'total_price', true );
                                $clean_val = preg_replace( '/[^0-9.]/', '', $raw_val );
                                $user_ltv += floatval( $clean_val ); 
                            }

                            $formatted_ltv = number_format_i18n( $user_ltv, 2 );
                            $display_ltv   = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted_ltv : $formatted_ltv . $currency_symbol;
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <?php echo get_avatar( $u->ID, 35, '', '', array( 'class' => 'user-avatar' ) ); ?>
                                    <strong><?php echo esc_html( $u->display_name ); ?></strong>
                                </div>
                            </td>
                            <td class="txt-slate"><?php echo esc_html( $u->user_email ); ?></td>
                            <td><strong class="txt-brand"><?php echo esc_html( $display_ltv ); ?></strong></td>
                            <td><span class="afon-badge badge-light"><?php echo absint( $order_count ); ?> <?php esc_html_e( 'Bookings', 'awesome-car-rental' ); ?></span></td>
                            <td style="text-align:right;">
                                <a href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, 'tab' => 'customers', 'view' => $u->ID ), admin_url( 'admin.php' ) ) ); ?>" class="afon-btn-action">
                                    <span class="dashicons dashicons-visibility"></span> <span><?php esc_html_e( 'View CRM', 'awesome-car-rental' ); ?></span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}