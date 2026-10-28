<?php
/**
 * Promo Codes & Discounts Module - Professional SaaS Layout with Sub-Navigation Tabs
 * Location: admin/promo-codes.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_promo_codes_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $page_slug       = 'awesome_car_rental';
    $currency_symbol = get_option( 'acrb_currency', '£' );
    $sub_state       = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $edit_id         = isset( $_GET['promo_id'] ) ? absint( wp_unslash( $_GET['promo_id'] ) ) : 0;

    $tabs = array(
        'all' => array(
            'label' => __( 'All Promo Codes', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-tag',
        ),
        'add' => array(
            'label' => __( 'Create Promo Code', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-plus-alt2',
        ),
    );

    // 1. HANDLE FORM SUBMISSION (Add / Update Promo Code)
    if ( isset( $_POST['acrb_save_promo'] ) ) {
        $nonce = isset( $_POST['acrb_promo_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_promo_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'acrb_promo_action' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security verification failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            $promo_id      = isset( $_POST['promo_id'] ) ? absint( $_POST['promo_id'] ) : 0;
            $coupon_input  = isset( $_POST['promo_code'] ) ? sanitize_text_field( wp_unslash( $_POST['promo_code'] ) ) : '';
            $discount_type = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'percentage';
            $discount_val  = isset( $_POST['discount_value'] ) ? floatval( wp_unslash( $_POST['discount_value'] ) ) : 0;
            $min_spend     = isset( $_POST['min_spend'] ) ? floatval( wp_unslash( $_POST['min_spend'] ) ) : 0;
            $expiry_date   = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
            $max_uses      = isset( $_POST['max_uses'] ) ? absint( $_POST['max_uses'] ) : 0;
            $status        = isset( $_POST['promo_status'] ) ? sanitize_key( wp_unslash( $_POST['promo_status'] ) ) : 'active';

            if ( ! empty( $coupon_input ) ) {
                $promo_code_string = strtoupper( sanitize_title( $coupon_input ) );
                
                $post_data = array(
                    'post_title'  => $promo_code_string,
                    'post_type'   => 'acrb_promo',
                    'post_status' => 'publish',
                );

                if ( $promo_id > 0 ) {
                    $post_data['ID'] = $promo_id;
                    wp_update_post( $post_data );
                    $target_id   = $promo_id;
                    $success_msg = __( 'Promo code updated successfully.', 'awesome-car-rental' );
                } else {
                    $target_id   = wp_insert_post( $post_data );
                    $success_msg = __( 'New promo code created.', 'awesome-car-rental' );
                }

                if ( $target_id && ! is_wp_error( $target_id ) ) {
                    update_post_meta( $target_id, 'promo_code', $promo_code_string );
                    update_post_meta( $target_id, 'discount_type', $discount_type );
                    update_post_meta( $target_id, 'discount_value', $discount_val );
                    update_post_meta( $target_id, 'min_spend', $min_spend );
                    update_post_meta( $target_id, 'expiry_date', $expiry_date );
                    update_post_meta( $target_id, 'max_uses', $max_uses );
                    update_post_meta( $target_id, 'promo_status', $status );

                    echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html( $success_msg ) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Promo code string cannot be empty.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    // 2. HANDLE DELETE LOGIC
    if ( isset( $_GET['delete_promo'] ) && current_user_can( 'manage_options' ) ) {
        $delete_id = absint( $_GET['delete_promo'] );
        $nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( wp_verify_nonce( $nonce, 'acrb_del_promo_' . $delete_id ) ) {
            wp_delete_post( $delete_id, true );
            echo '<div class="notice notice-warning is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Promo code deleted.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    // Register internal custom post type for promo codes if not already registered elsewhere
    if ( ! post_type_exists( 'acrb_promo' ) ) {
        register_post_type( 'acrb_promo', array(
            'public'  => false,
            'show_ui' => false,
        ) );
    }
    ?>

    <style>
        .acrb-dash-wrap { width: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
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
            margin-bottom: 24px; 
            position: relative;
        }
        .acrb-title-wrapper { display: flex; align-items: center; gap: 18px; width: 100%; }
        .acrb-title-icon { width: 48px; height: 48px; min-width: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
        .acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
        
        .acrb-title-content { display: flex; flex-direction: column; gap: 4px; }
        .acrb-main-title { font-size: 22px; font-weight: 700; margin: 0; color: #0f172a; letter-spacing: -0.5px; line-height: 1.2; }
        .acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; line-height: 1.4; }
        
        /* Modern Sub-Navigation Tabs */
        .acrb-nav-wrapper { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
        .acrb-nav-item { 
            background: #fff; 
            color: #475569; 
            border: 1px solid #e2e8f0; 
            border-radius: 8px; 
            padding: 10px 18px; 
            font-weight: 600; 
            font-size: 13px; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.2s ease; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.01);
        }
        .acrb-nav-item .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; color: #64748b; }
        .acrb-nav-item:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
        .acrb-nav-item.is-active { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.1); }
        .acrb-nav-item.is-active .dashicons { color: #3b82f6; }

        /* Card Form Container */
        .acrb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-card h2 { font-size: 15px; font-weight: 600; margin: 0 0 20px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .acrb-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .acrb-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .acrb-form-group { display: flex; flex-direction: column; }

        /* Form Controls */
        .acrb-input, .acrb-select { width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-input:hover, .acrb-select:hover { border-color: #94a3b8; }
        .acrb-input:focus, .acrb-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }
        .acrb-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #334155; }
        .acrb-btn-primary { background: #3b82f6; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none; }
        .acrb-btn-primary:hover { background: #2563eb; color: #fff; }

        /* Table Card Container */
        .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .acrb-table-wrap .dataTables_wrapper { padding: 16px 20px; font-family: inherit; }
        .acrb-table-wrap .dataTables_wrapper::before,
        .acrb-table-wrap .dataTables_wrapper::after { content: ''; display: table; clear: both; }

        /* Top Controls: Length & Search */
        .acrb-table-wrap .dataTables_length { float: left; margin-bottom: 16px; font-size: 13px; font-weight: 500; color: #64748b; }
        .acrb-table-wrap .dataTables_length select { border: 1px solid #cbd5e1 !important; border-radius: 8px !important; padding: 5px 10px !important; font-size: 13px !important; color: #0f172a !important; background: #ffffff !important; outline: none !important; }

        .acrb-table-wrap .dataTables_filter { float: right; margin-bottom: 16px; }
        .acrb-table-wrap .dataTables_filter input { width: 240px; height: 36px; padding: 0 14px !important; border-radius: 8px !important; border: 1px solid #cbd5e1 !important; background: #ffffff !important; font-size: 13px !important; color: #0f172a !important; outline: none !important; transition: all 0.15s ease-in-out; }
        .acrb-table-wrap .dataTables_filter input:focus { border-color: #4f46e5 !important; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important; }

        /* Table Design */
        .acrb-table-wrap table.widefat { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin: 0; box-shadow: none; width: 100% !important; }
        .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        .acrb-table-wrap table.widefat tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
        .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

        /* Bottom Controls */
        .acrb-table-wrap .dataTables_info { float: left; padding-top: 14px !important; font-size: 12.5px !important; color: #64748b !important; font-weight: 500; }
        .acrb-table-wrap .dataTables_paginate { float: right; padding-top: 10px !important; }
        .acrb-table-wrap .dataTables_paginate .paginate_button { border-radius: 6px !important; border: 1px solid transparent !important; padding: 4px 10px !important; margin: 0 2px !important; font-size: 12.5px !important; color: #64748b !important; }
        .acrb-table-wrap .dataTables_paginate .paginate_button:hover { background: #f1f5f9 !important; color: #0f172a !important; border-color: #e2e8f0 !important; }
        .acrb-table-wrap .dataTables_paginate .paginate_button.current,
        .acrb-table-wrap .dataTables_paginate .paginate_button.current:hover { background: #4f46e5 !important; color: #ffffff !important; border-color: #4f46e5 !important; }

        /* Column Badges & Action Buttons */
        .acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
        .acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; flex-shrink: 0; }
        .acrb-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        .acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-nav-wrapper { flex-direction: column; }
            .acrb-form-grid-2, .acrb-form-grid-3 { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-tag"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Promo Codes & Discounts', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'Create and govern promotional pricing campaigns and seasonal coupon codes.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( array(
                    'page' => $page_slug, 
                    'tab'  => 'promo_codes', 
                    'sub'  => $k,
                ), admin_url( 'admin.php' ) );

                $active_class = ( $sub_state === $k || ( 'all' === $k && 'edit' === $sub_state ) ) ? ' is-active' : '';
                ?>
                <a href="<?php echo esc_url( $url ); ?>" class="acrb-nav-item<?php echo esc_attr( $active_class ); ?>">
                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
                    <span class="acrb-nav-label"><?php echo esc_html( $data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <main>
            <?php if ( 'edit' === $sub_state || 'add' === $sub_state ) : 
                $promo_post  = $edit_id > 0 ? get_post( $edit_id ) : null;
                $coupon_code = $promo_post ? get_post_meta( $edit_id, 'promo_code', true ) : '';
                $disc_type   = $promo_post ? get_post_meta( $edit_id, 'discount_type', true ) : 'percentage';
                $disc_val    = $promo_post ? get_post_meta( $edit_id, 'discount_value', true ) : '';
                $min_val     = $promo_post ? get_post_meta( $edit_id, 'min_spend', true ) : '';
                $expiry_val  = $promo_post ? get_post_meta( $edit_id, 'expiry_date', true ) : '';
                $uses_val    = $promo_post ? get_post_meta( $edit_id, 'max_uses', true ) : '';
                $status_val  = $promo_post ? get_post_meta( $edit_id, 'promo_status', true ) : 'active';
            ?>
                <!-- ADD / EDIT PROMO FORM -->
                <div class="acrb-card">
                    <h2><?php echo $edit_id > 0 ? esc_html__( 'Edit Promo Code', 'awesome-car-rental' ) : esc_html__( 'Create New Promo Code', 'awesome-car-rental' ); ?></h2>
                    
                    <form method="post">
                        <?php wp_nonce_field( 'acrb_promo_action', 'acrb_promo_nonce' ); ?>
                        <input type="hidden" name="promo_id" value="<?php echo absint( $edit_id ); ?>">

                        <div class="acrb-form-group" style="margin-bottom:20px; max-width:500px;">
                            <label class="acrb-label"><?php esc_html_e( 'Coupon Code String', 'awesome-car-rental' ); ?></label>
                            <input type="text" name="promo_code" value="<?php echo esc_attr( $coupon_code ); ?>" class="acrb-input" style="text-transform:uppercase; font-weight:bold;" placeholder="e.g. SUMMER2026" required>
                        </div>

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Discount Type', 'awesome-car-rental' ); ?></label>
                                <select name="discount_type" class="acrb-select">
                                    <option value="percentage" <?php selected( $disc_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Discount (%)', 'awesome-car-rental' ); ?></option>
                                    <option value="fixed" <?php selected( $disc_type, 'fixed' ); ?>><?php printf( esc_html__( 'Fixed Amount (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></option>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Discount Value', 'awesome-car-rental' ); ?></label>
                                <input type="number" step="0.01" name="discount_value" value="<?php echo esc_attr( $disc_val ); ?>" class="acrb-input" placeholder="e.g. 15 for 15%" required>
                            </div>
                        </div>

                        <div class="acrb-form-grid-3">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php printf( esc_html__( 'Min. Spend (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></label>
                                <input type="number" step="0.01" name="min_spend" value="<?php echo esc_attr( $min_val ); ?>" class="acrb-input" placeholder="0.00">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Expiration Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" name="expiry_date" value="<?php echo esc_attr( $expiry_val ); ?>" class="acrb-input">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Max Uses (0 = Unlimited)', 'awesome-car-rental' ); ?></label>
                                <input type="number" name="max_uses" value="<?php echo esc_attr( $uses_val ); ?>" class="acrb-input" placeholder="0">
                            </div>
                        </div>

                        <div class="acrb-form-group" style="margin-bottom:24px; max-width:400px;">
                            <label class="acrb-label"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
                            <select name="promo_status" class="acrb-select">
                                <option value="active" <?php selected( $status_val, 'active' ); ?>><?php esc_html_e( '🟢 Active', 'awesome-car-rental' ); ?></option>
                                <option value="disabled" <?php selected( $status_val, 'disabled' ); ?>><?php esc_html_e( '🔴 Disabled', 'awesome-car-rental' ); ?></option>
                            </select>
                        </div>

                        <button type="submit" name="acrb_save_promo" class="acrb-btn-primary">
                            <?php echo $edit_id > 0 ? esc_html__( 'Update Promo Code', 'awesome-car-rental' ) : esc_html__( 'Save Promo Code', 'awesome-car-rental' ); ?>
                        </button>
                    </form>
                </div>

            <?php else : ?>
                <!-- PROMO CODES TABLE -->
                <div class="acrb-table-wrap">
                    <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Promo Code', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Discount', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Min. Spend', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Expiry Date', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                                <th class="all" style="text-align:right; width:100px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $promo_list = get_posts( array(
                                'post_type'   => 'acrb_promo',
                                'numberposts' => -1,
                                'post_status' => 'publish',
                                'orderby'     => 'date',
                                'order'       => 'DESC',
                            ) );

                            if ( ! empty( $promo_list ) ) :
                                foreach ( $promo_list as $promo_item ) :
                                    $item_code   = get_post_meta( $promo_item->ID, 'promo_code', true );
                                    $item_type   = get_post_meta( $promo_item->ID, 'discount_type', true );
                                    $item_val    = floatval( get_post_meta( $promo_item->ID, 'discount_value', true ) );
                                    $item_min    = floatval( get_post_meta( $promo_item->ID, 'min_spend', true ) );
                                    $item_expiry = get_post_meta( $promo_item->ID, 'expiry_date', true ) ?: 'Never';
                                    $item_status = get_post_meta( $promo_item->ID, 'promo_status', true ) ?: 'active';
                                    
                                    $discount_display = ( 'percentage' === $item_type ) ? $item_val . '%' : $currency_symbol . number_format( $item_val, 2 );

                                    $edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=promo_codes&sub=edit&promo_id=' . $promo_item->ID );
                                    $del_url  = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=promo_codes&delete_promo=' . $promo_item->ID ), 'acrb_del_promo_' . $promo_item->ID );
                            ?>
                            <tr>
                                <td><code style="background:#f1f5f9; padding:4px 8px; font-size:13px; font-weight:bold; color:#0f172a; border-radius:6px; border:1px solid #e2e8f0;"><?php echo esc_html( $item_code ); ?></code></td>
                                <td data-order="<?php echo esc_attr( $item_val ); ?>"><strong><?php echo esc_html( $discount_display ); ?></strong></td>
                                <td data-order="<?php echo esc_attr( $item_min ); ?>"><?php echo esc_html( $item_min > 0 ? $currency_symbol . number_format( $item_min, 2 ) : '—' ); ?></td>
                                <td data-order="<?php echo esc_attr( 'Never' === $item_expiry ? '9999-12-31' : $item_expiry ); ?>"><?php echo esc_html( $item_expiry ); ?></td>
                                <td>
                                    <?php 
                                    $badge_bg    = '#dcfce7'; 
                                    $badge_color = '#15803d';
                                    if ( 'disabled' === $item_status ) { 
                                        $badge_bg    = '#fee2e2'; 
                                        $badge_color = '#b91c1c'; 
                                    }
                                    ?>
                                    <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; display:inline-block; white-space:nowrap;">
                                        <?php echo esc_html( $item_status ); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div class="acrb-actions-group">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this promo code?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No promo codes found. Use the "Create Promo Code" tab above to add coupons.', 'awesome-car-rental' ); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php
}