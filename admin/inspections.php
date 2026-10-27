<?php
/**
 * Fleet Inspections & Condition Reports Module - Professional SaaS Layout with Sub-Navigation Tabs
 * Location: admin/inspections.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_inspections_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $page_slug = 'awesome_car_rental';
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $edit_id   = isset( $_GET['insp_id'] ) ? absint( wp_unslash( $_GET['insp_id'] ) ) : 0;

    $tabs = [
        'all' => [
            'label' => __( 'All Inspections', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-clipboard'
        ],
        'add' => [
            'label' => __( 'New Inspection', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-plus-alt2'
        ],
    ];

    // 1. HANDLE FORM SUBMISSION (Add / Update Inspection Log)
    if ( isset( $_POST['acrb_save_inspection'] ) ) {
        $nonce = isset( $_POST['acrb_insp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_insp_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'acrb_insp_action' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security verification failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            $inspection_id = isset( $_POST['insp_id'] ) ? absint( $_POST['insp_id'] ) : 0;
            $car_id        = isset( $_POST['insp_car_id'] ) ? absint( $_POST['insp_car_id'] ) : 0;
            $booking_id    = isset( $_POST['insp_booking_id'] ) ? absint( $_POST['insp_booking_id'] ) : 0;
            $insp_type     = isset( $_POST['insp_type'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_type'] ) ) : 'Check-Out';
            $insp_date     = isset( $_POST['insp_date'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_date'] ) ) : current_time( 'Y-m-d H:i' );
            $mileage       = isset( $_POST['insp_mileage'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_mileage'] ) ) : '';
            $fuel_level    = isset( $_POST['insp_fuel'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_fuel'] ) ) : 'Full (100%)';
            $cleanliness   = isset( $_POST['insp_cleanliness'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_cleanliness'] ) ) : 'Clean';
            $damages       = isset( $_POST['insp_damages'] ) ? sanitize_textarea_field( wp_unslash( $_POST['insp_damages'] ) ) : '';
            $inspector     = isset( $_POST['insp_inspector'] ) ? sanitize_text_field( wp_unslash( $_POST['insp_inspector'] ) ) : '';

            if ( $car_id > 0 ) {
                $car_title  = get_the_title( $car_id );
                $post_title = sprintf( '%s - Inspection #%d (%s)', $car_title, $car_id, $insp_type );
                
                $post_data = array(
                    'post_title'   => $post_title,
                    'post_type'    => 'acrb_inspections',
                    'post_status'  => 'publish',
                );

                if ( $inspection_id > 0 ) {
                    $post_data['ID'] = $inspection_id;
                    wp_update_post( $post_data );
                    $target_id   = $inspection_id;
                    $success_msg = __( 'Inspection report updated successfully.', 'awesome-car-rental' );
                } else {
                    $target_id   = wp_insert_post( $post_data );
                    $success_msg = __( 'New inspection report logged.', 'awesome-car-rental' );
                }

                if ( $target_id && ! is_wp_error( $target_id ) ) {
                    update_post_meta( $target_id, 'insp_car_id', $car_id );
                    update_post_meta( $target_id, 'insp_booking_id', $booking_id );
                    update_post_meta( $target_id, 'insp_type', $insp_type );
                    update_post_meta( $target_id, 'insp_date', $insp_date );
                    update_post_meta( $target_id, 'insp_mileage', $mileage );
                    update_post_meta( $target_id, 'insp_fuel', $fuel_level );
                    update_post_meta( $target_id, 'insp_cleanliness', $cleanliness );
                    update_post_meta( $target_id, 'insp_damages', $damages );
                    update_post_meta( $target_id, 'insp_inspector', $inspector );

                    echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html( $success_msg ) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Please select a vehicle for inspection.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    // 2. HANDLE DELETE LOGIC
    if ( isset( $_GET['delete_insp'] ) && current_user_can( 'manage_options' ) ) {
        $delete_id = absint( $_GET['delete_insp'] );
        $nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( wp_verify_nonce( $nonce, 'acrb_del_insp_' . $delete_id ) ) {
            wp_delete_post( $delete_id, true );
            echo '<div class="notice notice-warning is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Inspection report deleted.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    // Register internal custom post type for inspections if not already registered elsewhere
    if ( ! post_type_exists( 'acrb_inspections' ) ) {
        register_post_type( 'acrb_inspections', array(
            'public'  => false,
            'show_ui' => false,
        ) );
    }

    $cars     = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => -1, 'post_status' => 'publish' ) );
    $bookings = get_posts( array( 'post_type' => 'acrb_bookings', 'numberposts' => 50, 'post_status' => 'any', 'orderby' => 'date', 'order' => 'DESC' ) );
    ?>

    <style>
        /* Modern SaaS Layout Theme */
        .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; box-sizing: border-box; }
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

        .acrb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-card h2 { font-size: 15px; font-weight: 600; margin: 0 0 20px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .acrb-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .acrb-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .acrb-form-group { display: flex; flex-direction: column; }

        .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-table-wrap table.widefat { border: none; margin: 0; box-shadow: none; }
        .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
        .acrb-table-wrap table.widefat tbody td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
        .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

        .acrb-input, .acrb-select, .acrb-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-input, .acrb-select { height: 42px; }
        .acrb-textarea { padding: 12px 14px; height: auto; }
        .acrb-input:hover, .acrb-select:hover, .acrb-textarea:hover { border-color: #94a3b8; }
        .acrb-input:focus, .acrb-select:focus, .acrb-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }

        .acrb-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #334155; }

        .acrb-btn-primary { background: #3b82f6; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none; }
        .acrb-btn-primary:hover { background: #2563eb; color: #fff; }

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
                    <span class="dashicons dashicons-clipboard"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Fleet Inspections & Condition Reports', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'Record mileage, fuel levels, cleanliness, and pre/post-rental damage logs.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( [
                    'page' => $page_slug, 
                    'tab'  => 'inspections', 
                    'sub'  => $k
                ], admin_url( 'admin.php' ) );

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
                $insp_post    = $edit_id > 0 ? get_post( $edit_id ) : null;
                $i_car_id     = $insp_post ? get_post_meta( $edit_id, 'insp_car_id', true ) : 0;
                $i_booking_id = $insp_post ? get_post_meta( $edit_id, 'insp_booking_id', true ) : 0;
                $i_type       = $insp_post ? get_post_meta( $edit_id, 'insp_type', true ) : 'Check-Out';
                $i_date       = $insp_post ? get_post_meta( $edit_id, 'insp_date', true ) : current_time( 'Y-m-d H:i' );
                $i_mileage    = $insp_post ? get_post_meta( $edit_id, 'insp_mileage', true ) : '';
                $i_fuel       = $insp_post ? get_post_meta( $edit_id, 'insp_fuel', true ) : 'Full (100%)';
                $i_clean      = $insp_post ? get_post_meta( $edit_id, 'insp_cleanliness', true ) : 'Clean';
                $i_damages    = $insp_post ? get_post_meta( $edit_id, 'insp_damages', true ) : '';
                $i_inspector  = $insp_post ? get_post_meta( $edit_id, 'insp_inspector', true ) : wp_get_current_user()->display_name;
            ?>
                <!-- ADD / EDIT INSPECTION FORM -->
                <div class="acrb-card">
                    <h2><?php echo $edit_id > 0 ? esc_html__( 'Edit Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Create New Inspection Report', 'awesome-car-rental' ); ?></h2>
                    
                    <form method="post">
                        <?php wp_nonce_field( 'acrb_insp_action', 'acrb_insp_nonce' ); ?>
                        <input type="hidden" name="insp_id" value="<?php echo absint( $edit_id ); ?>">

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Select Vehicle', 'awesome-car-rental' ); ?></label>
                                <select name="insp_car_id" class="acrb-select" required>
                                    <option value=""><?php esc_html_e( '-- Choose Car --', 'awesome-car-rental' ); ?></option>
                                    <?php foreach ( $cars as $car ) : ?>
                                        <option value="<?php echo absint( $car->ID ); ?>" <?php selected( $i_car_id, $car->ID ); ?>>
                                            <?php echo esc_html( $car->post_title ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Inspection Stage', 'awesome-car-rental' ); ?></label>
                                <select name="insp_type" class="acrb-select">
                                    <option value="Check-Out" <?php selected( $i_type, 'Check-Out' ); ?>><?php esc_html_e( '🚗 Check-Out (Before Rental)', 'awesome-car-rental' ); ?></option>
                                    <option value="Check-In" <?php selected( $i_type, 'Check-In' ); ?>><?php esc_html_e( '🏁 Check-In (After Return)', 'awesome-car-rental' ); ?></option>
                                    <option value="Periodic Check" <?php selected( $i_type, 'Periodic Check' ); ?>><?php esc_html_e( '🛠️ Periodic Maintenance Audit', 'awesome-car-rental' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Linked Booking (Optional)', 'awesome-car-rental' ); ?></label>
                                <select name="insp_booking_id" class="acrb-select">
                                    <option value="0"><?php esc_html_e( '-- None / General Audit --', 'awesome-car-rental' ); ?></option>
                                    <?php foreach ( $bookings as $b ) : 
                                        $c_name = get_post_meta( $b->ID, 'customer_name', true ) ?: 'Guest';
                                    ?>
                                        <option value="<?php echo absint( $b->ID ); ?>" <?php selected( $i_booking_id, $b->ID ); ?>>
                                            <?php printf( '#%d — %s', absint( $b->ID ), esc_html( $c_name ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Inspection Date & Time', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="insp_date" value="<?php echo esc_attr( $i_date ); ?>" class="acrb-input" placeholder="YYYY-MM-DD HH:MM">
                            </div>
                        </div>

                        <div class="acrb-form-grid-3">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Current Mileage', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="insp_mileage" value="<?php echo esc_attr( $i_mileage ); ?>" class="acrb-input" placeholder="e.g. 45,200 km">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Fuel Level', 'awesome-car-rental' ); ?></label>
                                <select name="insp_fuel" class="acrb-select">
                                    <option value="Full (100%)" <?php selected( $i_fuel, 'Full (100%)' ); ?>>Full (100%)</option>
                                    <option value="3/4 Tank" <?php selected( $i_fuel, '3/4 Tank' ); ?>>3/4 Tank</option>
                                    <option value="1/2 Tank" <?php selected( $i_fuel, '1/2 Tank' ); ?>>1/2 Tank</option>
                                    <option value="1/4 Tank" <?php selected( $i_fuel, '1/4 Tank' ); ?>>1/4 Tank</option>
                                    <option value="Empty" <?php selected( $i_fuel, 'Empty' ); ?>>Empty</option>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Cleanliness', 'awesome-car-rental' ); ?></label>
                                <select name="insp_cleanliness" class="acrb-select">
                                    <option value="Pristine" <?php selected( $i_clean, 'Pristine' ); ?>>Pristine</option>
                                    <option value="Clean" <?php selected( $i_clean, 'Clean' ); ?>>Clean</option>
                                    <option value="Needs Wash" <?php selected( $i_clean, 'Needs Wash' ); ?>>Needs Wash</option>
                                    <option value="Dirty / Muddy" <?php selected( $i_clean, 'Dirty / Muddy' ); ?>>Dirty / Muddy</option>
                                </select>
                            </div>
                        </div>

                        <div class="acrb-form-group" style="margin-bottom:20px;">
                            <label class="acrb-label"><?php esc_html_e( 'Damage Log / Scratches / Dents', 'awesome-car-rental' ); ?></label>
                            <textarea name="insp_damages" rows="3" class="acrb-textarea" placeholder="Describe any existing or new scratches, bumper dents, or interior stains..."><?php echo esc_textarea( $i_damages ); ?></textarea>
                        </div>

                        <div class="acrb-form-group" style="margin-bottom:24px; max-width:400px;">
                            <label class="acrb-label"><?php esc_html_e( 'Inspector Name / Staff', 'awesome-car-rental' ); ?></label>
                            <input type="text" name="insp_inspector" value="<?php echo esc_attr( $i_inspector ); ?>" class="acrb-input">
                        </div>

                        <button type="submit" name="acrb_save_inspection" class="acrb-btn-primary">
                            <?php echo $edit_id > 0 ? esc_html__( 'Update Inspection Report', 'awesome-car-rental' ) : esc_html__( 'Save Inspection Report', 'awesome-car-rental' ); ?>
                        </button>
                    </form>
                </div>

            <?php else : ?>
                <!-- INSPECTION LOG TABLE -->
                <div class="acrb-table-wrap">
                    <div style="padding: 20px 24px 0;">
                        <h3 style="margin:0; border-bottom:1px solid #f1f5f9; padding-bottom:14px; font-size:15px; font-weight:600; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Condition Reports', 'awesome-car-rental' ); ?></h3>
                    </div>

                    <table class="widefat striped" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Stage', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Mileage / Fuel', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Booking Ref', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Inspector', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
                                <th style="text-align:right;"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $reports = get_posts( array(
                                'post_type'   => 'acrb_inspections',
                                'numberposts' => -1,
                                'post_status' => 'publish',
                                'orderby'     => 'date',
                                'order'       => 'DESC',
                            ) );

                            if ( ! empty( $reports ) ) :
                                foreach ( $reports as $rep ) :
                                    $car_id     = get_post_meta( $rep->ID, 'insp_car_id', true );
                                    $car_title  = $car_id ? get_the_title( $car_id ) : __( 'Unknown Vehicle', 'awesome-car-rental' );
                                    $booking_id = get_post_meta( $rep->ID, 'insp_booking_id', true );
                                    $i_type     = get_post_meta( $rep->ID, 'insp_type', true ) ?: 'Check-Out';
                                    $i_mileage  = get_post_meta( $rep->ID, 'insp_mileage', true ) ?: '—';
                                    $i_fuel     = get_post_meta( $rep->ID, 'insp_fuel', true ) ?: '—';
                                    $inspector  = get_post_meta( $rep->ID, 'insp_inspector', true ) ?: 'Admin';
                                    $i_date     = get_post_meta( $rep->ID, 'insp_date', true );
                                    
                                    $edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=inspections&sub=edit&insp_id=' . $rep->ID );
                                    $del_url  = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=inspections&delete_insp=' . $rep->ID ), 'acrb_del_insp_' . $rep->ID );
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $car_title ); ?></strong></td>
                                <td>
                                    <?php 
                                    $badge_bg = '#e0f2fe'; $badge_color = '#0369a1';
                                    if ( 'Check-In' === $i_type ) { $badge_bg = '#dcfce7'; $badge_color = '#15803d'; }
                                    elseif ( 'Periodic Check' === $i_type ) { $badge_bg = '#fef3c7'; $badge_color = '#d97706'; }
                                    ?>
                                    <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                                        <?php echo esc_html( $i_type ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $i_mileage . ' / ' . $i_fuel ); ?></td>
                                <td><?php echo $booking_id ? sprintf( '#%d', absint( $booking_id ) ) : '—'; ?></td>
                                <td><?php echo esc_html( $inspector ); ?></td>
                                <td><?php echo esc_html( $i_date ); ?></td>
                                <td style="text-align:right;">
                                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>" style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border-radius:6px;">
                                            <span class="dashicons dashicons-edit" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $del_url ); ?>" class="button button-small" style="color:#b91c1c; display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border-radius:6px;" onclick="return confirm('<?php esc_attr_e( 'Delete this inspection report?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
                                            <span class="dashicons dashicons-trash" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No inspection reports found. Use the "New Inspection" tab above to create reports.', 'awesome-car-rental' ); ?></td>
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