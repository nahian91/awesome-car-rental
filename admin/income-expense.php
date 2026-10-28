<?php
/**
 * Income & Expense Financial Ledger Module - Professional SaaS Layout with Sub-Navigation Tabs
 * Location: admin/income-expense.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_income_expense_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $page_slug = 'awesome_car_rental';
    $currency  = get_option( 'acrb_currency', '£' );
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $edit_id   = isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : 0;

    $tabs = array(
        'all' => array(
            'label' => __( 'All Transactions', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-money-alt',
        ),
        'add' => array(
            'label' => __( 'Record Transaction', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-plus-alt2',
        ),
    );

    // 1. HANDLE FORM SUBMISSION (Add / Update Ledger Entry)
    if ( isset( $_POST['acrb_save_ledger'] ) ) {
        $nonce = isset( $_POST['acrb_ledger_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_ledger_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'acrb_ledger_action' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security verification failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            $entry_id    = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
            $entry_title = isset( $_POST['entry_title'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_title'] ) ) : '';
            $entry_type  = isset( $_POST['entry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_type'] ) ) : 'expense';
            $amount      = isset( $_POST['entry_amount'] ) ? floatval( wp_unslash( $_POST['entry_amount'] ) ) : 0;
            $car_id      = isset( $_POST['entry_car_id'] ) ? absint( $_POST['entry_car_id'] ) : 0;
            $category    = isset( $_POST['entry_category'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_category'] ) ) : 'General';
            $entry_date  = isset( $_POST['entry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_date'] ) ) : current_time( 'Y-m-d' );
            $notes       = isset( $_POST['entry_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['entry_notes'] ) ) : '';

            if ( ! empty( $entry_title ) && $amount > 0 ) {
                $post_data = array(
                    'post_title'  => $entry_title,
                    'post_type'   => 'acrb_ledger',
                    'post_status' => 'publish',
                );

                if ( $entry_id > 0 ) {
                    $post_data['ID'] = $entry_id;
                    wp_update_post( $post_data );
                    $target_id = $entry_id;
                    $msg       = __( 'Ledger entry updated successfully.', 'awesome-car-rental' );
                } else {
                    $target_id = wp_insert_post( $post_data );
                    $msg       = __( 'New ledger transaction recorded.', 'awesome-car-rental' );
                }

                if ( $target_id && ! is_wp_error( $target_id ) ) {
                    update_post_meta( $target_id, 'ledger_type', $entry_type );
                    update_post_meta( $target_id, 'ledger_amount', $amount );
                    update_post_meta( $target_id, 'ledger_car_id', $car_id );
                    update_post_meta( $target_id, 'ledger_category', $category );
                    update_post_meta( $target_id, 'ledger_date', $entry_date );
                    update_post_meta( $target_id, 'ledger_notes', $notes );

                    echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html( $msg ) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Please provide a title and a valid amount greater than zero.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    // 2. HANDLE DELETE LOGIC
    if ( isset( $_GET['delete_entry'] ) && current_user_can( 'manage_options' ) ) {
        $del_id = absint( $_GET['delete_entry'] );
        $nonce  = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( wp_verify_nonce( $nonce, 'acrb_del_ledger_' . $del_id ) ) {
            wp_delete_post( $del_id, true );
            echo '<div class="notice notice-warning is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Ledger transaction deleted.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    // Register internal custom post type for ledger if not already registered elsewhere
    if ( ! post_type_exists( 'acrb_ledger' ) ) {
        register_post_type( 'acrb_ledger', array(
            'public'  => false,
            'show_ui' => false,
        ) );
    }

    $cars = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => -1, 'post_status' => 'publish' ) );

    // 3. CALCULATION STATS (Total Expenses vs Manual Income)
    $all_entries          = get_posts( array( 'post_type' => 'acrb_ledger', 'numberposts' => -1, 'post_status' => 'publish' ) );
    $total_manual_expense = 0;
    $total_manual_income  = 0;

    foreach ( $all_entries as $entry ) {
        $type = get_post_meta( $entry->ID, 'ledger_type', true );
        $amt  = floatval( get_post_meta( $entry->ID, 'ledger_amount', true ) );
        if ( 'expense' === $type ) {
            $total_manual_expense += $amt;
        } else {
            $total_manual_income += $amt;
        }
    }

    // Fetch Confirmed Booking Revenues to include in total company balance
    $confirmed_bookings = get_posts( array(
        'post_type'   => 'acrb_bookings',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query'  => array(
            array( 'key' => 'status', 'value' => 'confirmed', 'compare' => '=' ),
        ),
    ) );
    $booking_revenue = 0;
    foreach ( $confirmed_bookings as $b ) {
        $booking_revenue += floatval( get_post_meta( $b->ID, 'total_price', true ) );
    }

    $grand_total_income = $total_manual_income + $booking_revenue;
    $net_profit         = $grand_total_income - $total_manual_expense;
    $profit_color       = ( $net_profit >= 0 ) ? '#15803d' : '#b91c1c';
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

        /* Financial Stats KPI Cards */
        .acrb-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
        .acrb-stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-stat-label { display: block; color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .acrb-stat-val { display: block; font-size: 26px; font-weight: 700; margin-top: 6px; letter-spacing: -0.5px; }

        /* Card Form Container */
        .acrb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-card h2 { font-size: 15px; font-weight: 600; margin: 0 0 20px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }

        .acrb-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .acrb-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .acrb-form-group { display: flex; flex-direction: column; }

        /* Form Controls */
        .acrb-input, .acrb-select, .acrb-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-input, .acrb-select { height: 42px; }
        .acrb-textarea { padding: 12px 14px; height: auto; }
        .acrb-input:hover, .acrb-select:hover, .acrb-textarea:hover { border-color: #94a3b8; }
        .acrb-input:focus, .acrb-select:focus, .acrb-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }
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

        /* Action Buttons */
        .acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
        .acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; flex-shrink: 0; }
        .acrb-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }
        .acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
        .acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-nav-wrapper { flex-direction: column; }
            .acrb-stats-grid { grid-template-columns: 1fr; }
            .acrb-form-grid-2, .acrb-form-grid-3 { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Income & Expense Ledger', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'Track business cash flow, operational expenditures, and calculate net profitability.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( array(
                    'page' => $page_slug, 
                    'tab'  => 'income_expense', 
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
            <?php if ( 'all' === $sub_state && 'edit' !== $sub_state ) : ?>
                <!-- FINANCIAL SUMMARY CARDS -->
                <div class="acrb-stats-grid">
                    <div class="acrb-stat-card">
                        <span class="acrb-stat-label"><?php esc_html_e( 'Total Revenue (Rentals + Manual)', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-stat-val" style="color:#15803d;"><?php echo esc_html( $currency . number_format( $grand_total_income, 2 ) ); ?></span>
                    </div>
                    <div class="acrb-stat-card">
                        <span class="acrb-stat-label"><?php esc_html_e( 'Total Expenses', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-stat-val" style="color:#b91c1c;"><?php echo esc_html( $currency . number_format( $total_manual_expense, 2 ) ); ?></span>
                    </div>
                    <div class="acrb-stat-card">
                        <span class="acrb-stat-label"><?php esc_html_e( 'Net Profitability', 'awesome-car-rental' ); ?></span>
                        <span class="acrb-stat-val" style="color:<?php echo esc_attr( $profit_color ); ?>;"><?php echo esc_html( $currency . number_format( $net_profit, 2 ) ); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( 'edit' === $sub_state || 'add' === $sub_state ) : 
                $entry_post = $edit_id > 0 ? get_post( $edit_id ) : null;
                $e_type     = $entry_post ? get_post_meta( $edit_id, 'ledger_type', true ) : 'expense';
                $e_amount   = $entry_post ? get_post_meta( $edit_id, 'ledger_amount', true ) : '';
                $e_car_id   = $entry_post ? get_post_meta( $edit_id, 'ledger_car_id', true ) : 0;
                $e_cat      = $entry_post ? get_post_meta( $edit_id, 'ledger_category', true ) : 'Maintenance';
                $e_date     = $entry_post ? get_post_meta( $edit_id, 'ledger_date', true ) : current_time( 'Y-m-d' );
                $e_notes    = $entry_post ? get_post_meta( $edit_id, 'ledger_notes', true ) : '';
            ?>
                <!-- ADD / EDIT LEDGER FORM -->
                <div class="acrb-card">
                    <h2><?php echo $edit_id > 0 ? esc_html__( 'Edit Ledger Transaction', 'awesome-car-rental' ) : esc_html__( 'Record New Ledger Transaction', 'awesome-car-rental' ); ?></h2>
                    
                    <form method="post">
                        <?php wp_nonce_field( 'acrb_ledger_action', 'acrb_ledger_nonce' ); ?>
                        <input type="hidden" name="entry_id" value="<?php echo absint( $edit_id ); ?>">

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Transaction Type', 'awesome-car-rental' ); ?></label>
                                <select name="entry_type" class="acrb-select">
                                    <option value="expense" <?php selected( $e_type, 'expense' ); ?>><?php esc_html_e( '🔴 Expense (Money Out)', 'awesome-car-rental' ); ?></option>
                                    <option value="income" <?php selected( $e_type, 'income' ); ?>><?php esc_html_e( '🟢 Income (Manual Money In)', 'awesome-car-rental' ); ?></option>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php printf( esc_html__( 'Amount (%s)', 'awesome-car-rental' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="entry_amount" value="<?php echo esc_attr( $e_amount ); ?>" class="acrb-input" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="acrb-form-group" style="margin-bottom:20px;">
                            <label class="acrb-label"><?php esc_html_e( 'Transaction Title / Description', 'awesome-car-rental' ); ?></label>
                            <input type="text" name="entry_title" value="<?php echo $entry_post ? esc_attr( $entry_post->post_title ) : ''; ?>" class="acrb-input" placeholder="e.g. Monthly Commercial Fleet Insurance Policy" required>
                        </div>

                        <div class="acrb-form-grid-3">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></label>
                                <select name="entry_category" class="acrb-select">
                                    <option value="Maintenance" <?php selected( $e_cat, 'Maintenance' ); ?>>Maintenance & Repairs</option>
                                    <option value="Insurance" <?php selected( $e_cat, 'Insurance' ); ?>>Insurance</option>
                                    <option value="Fuel & Wash" <?php selected( $e_cat, 'Fuel & Wash' ); ?>>Fuel & Wash</option>
                                    <option value="Lease / Loan" <?php selected( $e_cat, 'Lease / Loan' ); ?>>Lease / Loan Payment</option>
                                    <option value="Marketing" <?php selected( $e_cat, 'Marketing' ); ?>>Marketing & Ads</option>
                                    <option value="Other" <?php selected( $e_cat, 'Other' ); ?>>Other Expense</option>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Related Vehicle (Optional)', 'awesome-car-rental' ); ?></label>
                                <select name="entry_car_id" class="acrb-select">
                                    <option value="0"><?php esc_html_e( '-- General / Company --', 'awesome-car-rental' ); ?></option>
                                    <?php foreach ( $cars as $car ) : ?>
                                        <option value="<?php echo absint( $car->ID ); ?>" <?php selected( $e_car_id, $car->ID ); ?>>
                                            <?php echo esc_html( $car->post_title ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" name="entry_date" value="<?php echo esc_attr( $e_date ); ?>" class="acrb-input">
                            </div>
                        </div>

                        <div class="acrb-form-group" style="margin-bottom:24px;">
                            <label class="acrb-label"><?php esc_html_e( 'Additional Notes / Receipt ID', 'awesome-car-rental' ); ?></label>
                            <textarea name="entry_notes" rows="3" class="acrb-textarea"><?php echo esc_textarea( $e_notes ); ?></textarea>
                        </div>

                        <button type="submit" name="acrb_save_ledger" class="acrb-btn-primary">
                            <?php echo $edit_id > 0 ? esc_html__( 'Update Ledger Entry', 'awesome-car-rental' ) : esc_html__( 'Save Ledger Entry', 'awesome-car-rental' ); ?>
                        </button>
                    </form>
                </div>

            <?php else : ?>
                <!-- LEDGER TABLE -->
                <div class="acrb-table-wrap">
                    <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Transaction', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Amount', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
                                <th class="all" style="text-align:right; width:100px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $entries = get_posts( array(
                                'post_type'   => 'acrb_ledger',
                                'numberposts' => -1,
                                'post_status' => 'publish',
                                'orderby'     => 'date',
                                'order'       => 'DESC',
                            ) );

                            if ( ! empty( $entries ) ) :
                                foreach ( $entries as $en ) :
                                    $type   = get_post_meta( $en->ID, 'ledger_type', true ) ?: 'expense';
                                    $amount = floatval( get_post_meta( $en->ID, 'ledger_amount', true ) );
                                    $cat    = get_post_meta( $en->ID, 'ledger_category', true ) ?: 'General';
                                    $car_id = get_post_meta( $en->ID, 'ledger_car_id', true );
                                    $car_t  = $car_id ? get_the_title( $car_id ) : '—';
                                    $date   = get_post_meta( $en->ID, 'ledger_date', true );
                                    
                                    $edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense&sub=edit&entry_id=' . $en->ID );
                                    $del_url  = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense&delete_entry=' . $en->ID ), 'acrb_del_ledger_' . $en->ID );
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $en->post_title ); ?></strong></td>
                                <td>
                                    <?php 
                                    $badge_bg    = ( 'income' === $type ) ? '#dcfce7' : '#fee2e2'; 
                                    $badge_color = ( 'income' === $type ) ? '#15803d' : '#b91c1c';
                                    ?>
                                    <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; display:inline-block; white-space:nowrap;">
                                        <?php echo esc_html( $type ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $cat ); ?></td>
                                <td><?php echo esc_html( $car_t ); ?></td>
                                <td data-order="<?php echo esc_attr( 'income' === $type ? $amount : -$amount ); ?>">
                                    <strong style="color:<?php echo 'income' === $type ? '#15803d' : '#b91c1c'; ?>; white-space:nowrap;">
                                        <?php echo esc_html( ( 'income' === $type ? '+' : '-' ) . $currency . number_format( $amount, 2 ) ); ?>
                                    </strong>
                                </td>
                                <td data-order="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( $date ); ?></td>
                                <td style="text-align:right;">
                                    <div class="acrb-actions-group">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this ledger entry?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No financial transactions logged yet. Use the "Record Transaction" tab above to add entries.', 'awesome-car-rental' ); ?></td>
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