<?php
/**
 * Fleet Maintenance & Servicing Module - Professional SaaS Layout with Sub-Navigation & Full Fields
 * Location: admin/maintenance.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_maintenance_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $page_slug       = 'awesome_car_rental';
    $currency_symbol = get_option( 'acrb_currency', '£' );
    $sub_state       = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $edit_id         = isset( $_GET['maint_id'] ) ? absint( wp_unslash( $_GET['maint_id'] ) ) : 0;

    $tabs = [
        'all' => [
            'label' => __( 'All Maintenance', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-hammer'
        ],
        'add' => [
            'label' => __( 'Add Maintenance', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-plus-alt2'
        ],
    ];

    // 1. HANDLE FORM SUBMISSION (Add / Update Maintenance Log)
    if ( isset( $_POST['acrb_save_maintenance'] ) ) {
        $nonce = isset( $_POST['acrb_maint_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_maint_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'acrb_maint_action' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security verification failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            $maint_id      = isset( $_POST['maint_id'] ) ? absint( $_POST['maint_id'] ) : 0;
            $car_id        = isset( $_POST['maint_car_id'] ) ? absint( $_POST['maint_car_id'] ) : 0;
            $service_title = isset( $_POST['maint_title'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_title'] ) ) : '';
            $service_type  = isset( $_POST['maint_type'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_type'] ) ) : 'Routine Service';
            $service_date  = isset( $_POST['maint_date'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_date'] ) ) : current_time( 'Y-m-d' );
            $mileage       = isset( $_POST['maint_mileage'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_mileage'] ) ) : '';
            $cost          = isset( $_POST['maint_cost'] ) ? floatval( wp_unslash( $_POST['maint_cost'] ) ) : 0;
            $priority      = isset( $_POST['maint_priority'] ) ? sanitize_key( wp_unslash( $_POST['maint_priority'] ) ) : 'medium';
            $technician    = isset( $_POST['maint_technician'] ) ? sanitize_text_field( wp_unslash( $_POST['maint_technician'] ) ) : '';
            $status        = isset( $_POST['maint_status'] ) ? sanitize_key( wp_unslash( $_POST['maint_status'] ) ) : 'scheduled';
            $notes         = isset( $_POST['maint_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['maint_notes'] ) ) : '';

            if ( $car_id > 0 && ! empty( $service_title ) ) {
                $post_data = array(
                    'post_title'   => $service_title,
                    'post_type'    => 'acrb_maintenance',
                    'post_status'  => 'publish',
                );

                if ( $maint_id > 0 ) {
                    $post_data['ID'] = $maint_id;
                    wp_update_post( $post_data );
                    $target_id   = $maint_id;
                    $success_msg = __( 'Maintenance record updated successfully.', 'awesome-car-rental' );
                } else {
                    $target_id   = wp_insert_post( $post_data );
                    $success_msg = __( 'New maintenance record logged.', 'awesome-car-rental' );
                }

                if ( $target_id && ! is_wp_error( $target_id ) ) {
                    update_post_meta( $target_id, 'maint_car_id', $car_id );
                    update_post_meta( $target_id, 'maint_type', $service_type );
                    update_post_meta( $target_id, 'maint_date', $service_date );
                    update_post_meta( $target_id, 'maint_mileage', $mileage );
                    update_post_meta( $target_id, 'maint_cost', $cost );
                    update_post_meta( $target_id, 'maint_priority', $priority );
                    update_post_meta( $target_id, 'maint_technician', $technician );
                    update_post_meta( $target_id, 'maint_status', $status );
                    update_post_meta( $target_id, 'maint_notes', $notes );

                    // Update vehicle operational status accordingly
                    if ( 'in_progress' === $status ) {
                        update_post_meta( $car_id, 'acrb_vehicle_status', 'maintenance' );
                    } elseif ( 'completed' === $status ) {
                        update_post_meta( $car_id, 'acrb_vehicle_status', 'active' );
                    }

                    echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html( $success_msg ) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Please select a vehicle and enter a service summary title.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    // 2. HANDLE DELETE LOGIC
    if ( isset( $_GET['delete_maint'] ) && current_user_can( 'manage_options' ) ) {
        $delete_id = absint( $_GET['delete_maint'] );
        $nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( wp_verify_nonce( $nonce, 'acrb_del_maint_' . $delete_id ) ) {
            wp_delete_post( $delete_id, true );
            echo '<div class="notice notice-warning is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Maintenance record deleted.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    // Register internal custom post type for maintenance logs if not already registered elsewhere
    if ( ! post_type_exists( 'acrb_maintenance' ) ) {
        register_post_type( 'acrb_maintenance', array(
            'public'  => false,
            'show_ui' => false,
        ) );
    }

    $cars = get_posts( array( 'post_type' => 'acrb_cars', 'numberposts' => -1, 'post_status' => 'publish' ) );
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
                    <span class="dashicons dashicons-hammer"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Fleet Maintenance & Servicing', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'Monitor vehicle health, schedule repairs, and track operational upkeep costs.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( [
                    'page' => $page_slug, 
                    'tab'  => 'maintenance', 
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
                $maint_post = $edit_id > 0 ? get_post( $edit_id ) : null;
                $m_car_id   = $maint_post ? get_post_meta( $edit_id, 'maint_car_id', true ) : 0;
                $m_type     = $maint_post ? get_post_meta( $edit_id, 'maint_type', true ) : 'Routine Service';
                $m_date     = $maint_post ? get_post_meta( $edit_id, 'maint_date', true ) : current_time( 'Y-m-d' );
                $m_mileage  = $maint_post ? get_post_meta( $edit_id, 'maint_mileage', true ) : '';
                $m_cost     = $maint_post ? get_post_meta( $edit_id, 'maint_cost', true ) : '';
                $m_priority = $maint_post ? get_post_meta( $edit_id, 'maint_priority', true ) : 'medium';
                $m_tech     = $maint_post ? get_post_meta( $edit_id, 'maint_technician', true ) : '';
                $m_status   = $maint_post ? get_post_meta( $edit_id, 'maint_status', true ) : 'scheduled';
                $m_notes    = $maint_post ? get_post_meta( $edit_id, 'maint_notes', true ) : '';
            ?>
                <!-- ADD / EDIT MAINTENANCE FORM -->
                <div class="acrb-card">
                    <h2><?php echo $edit_id > 0 ? esc_html__( 'Edit Maintenance Record', 'awesome-car-rental' ) : esc_html__( 'Log New Maintenance Service', 'awesome-car-rental' ); ?></h2>
                    
                    <form method="post">
                        <?php wp_nonce_field( 'acrb_maint_action', 'acrb_maint_nonce' ); ?>
                        <input type="hidden" name="maint_id" value="<?php echo absint( $edit_id ); ?>">

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Select Vehicle', 'awesome-car-rental' ); ?></label>
                                <select name="maint_car_id" class="acrb-select" required>
                                    <option value=""><?php esc_html_e( '-- Choose Car --', 'awesome-car-rental' ); ?></option>
                                    <?php foreach ( $cars as $car ) : ?>
                                        <option value="<?php echo absint( $car->ID ); ?>" <?php selected( $m_car_id, $car->ID ); ?>>
                                            <?php echo esc_html( $car->post_title ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Service Type', 'awesome-car-rental' ); ?></label>
                                <select name="maint_type" class="acrb-select">
                                    <option value="Routine Service" <?php selected( $m_type, 'Routine Service' ); ?>><?php esc_html_e( 'Routine Service / Oil Change', 'awesome-car-rental' ); ?></option>
                                    <option value="Mechanical Repair" <?php selected( $m_type, 'Mechanical Repair' ); ?>><?php esc_html_e( 'Mechanical Repair', 'awesome-car-rental' ); ?></option>
                                    <option value="Tire Replacement" <?php selected( $m_type, 'Tire Replacement' ); ?>><?php esc_html_e( 'Tire Replacement / Alignment', 'awesome-car-rental' ); ?></option>
                                    <option value="Bodywork & Detailing" <?php selected( $m_type, 'Bodywork & Detailing' ); ?>><?php esc_html_e( 'Bodywork & Detailing', 'awesome-car-rental' ); ?></option>
                                    <option value="Inspection & Safety" <?php selected( $m_type, 'Inspection & Safety' ); ?>><?php esc_html_e( 'Inspection & Safety', 'awesome-car-rental' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="acrb-form-group" style="margin-bottom:20px;">
                            <label class="acrb-label"><?php esc_html_e( 'Service Summary Title', 'awesome-car-rental' ); ?></label>
                            <input type="text" name="maint_title" value="<?php echo $maint_post ? esc_attr( $maint_post->post_title ) : ''; ?>" class="acrb-input" placeholder="e.g. 10,000km Engine Check & Brake Pad Replacement" required>
                        </div>

                        <div class="acrb-form-grid-3">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Service Date', 'awesome-car-rental' ); ?></label>
                                <input type="date" name="maint_date" value="<?php echo esc_attr( $m_date ); ?>" class="acrb-input">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Mileage / Odometer', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="maint_mileage" value="<?php echo esc_attr( $m_mileage ); ?>" class="acrb-input" placeholder="e.g. 45,200 mi">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php printf( esc_html__( 'Cost (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></label>
                                <input type="number" step="0.01" name="maint_cost" value="<?php echo esc_attr( $m_cost ); ?>" class="acrb-input" placeholder="0.00">
                            </div>
                        </div>

                        <div class="acrb-form-grid-3">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Priority Level', 'awesome-car-rental' ); ?></label>
                                <select name="maint_priority" class="acrb-select">
                                    <option value="low" <?php selected( $m_priority, 'low' ); ?>><?php esc_html_e( 'Low', 'awesome-car-rental' ); ?></option>
                                    <option value="medium" <?php selected( $m_priority, 'medium' ); ?>><?php esc_html_e( 'Medium', 'awesome-car-rental' ); ?></option>
                                    <option value="high" <?php selected( $m_priority, 'high' ); ?>><?php esc_html_e( 'High / Urgent', 'awesome-car-rental' ); ?></option>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Assigned Technician / Shop', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="maint_technician" value="<?php echo esc_attr( $m_tech ); ?>" class="acrb-input" placeholder="Mechanic Name or Shop">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></label>
                                <select name="maint_status" class="acrb-select">
                                    <option value="scheduled" <?php selected( $m_status, 'scheduled' ); ?>><?php esc_html_e( '⏳ Scheduled', 'awesome-car-rental' ); ?></option>
                                    <option value="in_progress" <?php selected( $m_status, 'in_progress' ); ?>><?php esc_html_e( '🛠️ In Progress', 'awesome-car-rental' ); ?></option>
                                    <option value="completed" <?php selected( $m_status, 'completed' ); ?>><?php esc_html_e( '✅ Completed', 'awesome-car-rental' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="acrb-form-group" style="margin-bottom:24px;">
                            <label class="acrb-label"><?php esc_html_e( 'Repair Notes & Parts Replaced', 'awesome-car-rental' ); ?></label>
                            <textarea name="maint_notes" rows="4" class="acrb-textarea"><?php echo esc_textarea( $m_notes ); ?></textarea>
                        </div>

                        <button type="submit" name="acrb_save_maintenance" class="acrb-btn-primary">
                            <?php echo $edit_id > 0 ? esc_html__( 'Update Maintenance Record', 'awesome-car-rental' ) : esc_html__( 'Save Maintenance Record', 'awesome-car-rental' ); ?>
                        </button>
                    </form>
                </div>

            <?php else : ?>
                <!-- MAINTENANCE LOG TABLE -->
                <div class="acrb-table-wrap">
                    <div style="padding: 20px 24px 0;">
                        <h3 style="margin:0; border-bottom:1px solid #f1f5f9; padding-bottom:14px; font-size:15px; font-weight:600; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Fleet Servicing Logs', 'awesome-car-rental' ); ?></h3>
                    </div>

                    <table class="widefat striped" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Vehicle', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Service Summary', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Cost', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                                <th style="text-align:right;"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $records = get_posts( array(
                                'post_type'   => 'acrb_maintenance',
                                'numberposts' => -1,
                                'post_status' => 'publish',
                                'orderby'     => 'date',
                                'order'       => 'DESC',
                            ) );

                            if ( ! empty( $records ) ) :
                                foreach ( $records as $rec ) :
                                    $car_id    = get_post_meta( $rec->ID, 'maint_car_id', true );
                                    $car_title = $car_id ? get_the_title( $car_id ) : __( 'Unknown Vehicle', 'awesome-car-rental' );
                                    $m_type    = get_post_meta( $rec->ID, 'maint_type', true );
                                    $m_date    = get_post_meta( $rec->ID, 'maint_date', true );
                                    $m_cost    = floatval( get_post_meta( $rec->ID, 'maint_cost', true ) );
                                    $m_status  = get_post_meta( $rec->ID, 'maint_status', true ) ?: 'scheduled';
                                    
                                    $edit_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=maintenance&sub=edit&maint_id=' . $rec->ID );
                                    $del_url  = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=maintenance&delete_maint=' . $rec->ID ), 'acrb_del_maint_' . $rec->ID );
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $car_title ); ?></strong></td>
                                <td><?php echo esc_html( $rec->post_title ); ?></td>
                                <td><span style="background:#f1f5f9; padding:4px 10px; border-radius:6px; font-size:12px; color:#475569;"><?php echo esc_html( $m_type ); ?></span></td>
                                <td><?php echo esc_html( $m_date ); ?></td>
                                <td><strong><?php echo esc_html( $currency_symbol . number_format( $m_cost, 2 ) ); ?></strong></td>
                                <td>
                                    <?php 
                                    $badge_bg = '#fef3c7'; $badge_color = '#d97706';
                                    if ( 'in_progress' === $m_status ) { $badge_bg = '#e0f2fe'; $badge_color = '#0369a1'; }
                                    elseif ( 'completed' === $m_status ) { $badge_bg = '#dcfce7'; $badge_color = '#15803d'; }
                                    ?>
                                    <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                                        <?php echo esc_html( str_replace( '_', ' ', $m_status ) ); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>" style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border-radius:6px;">
                                            <span class="dashicons dashicons-edit" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $del_url ); ?>" class="button button-small" style="color:#b91c1c; display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border-radius:6px;" onclick="return confirm('<?php esc_attr_e( 'Delete this maintenance record?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
                                            <span class="dashicons dashicons-trash" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No maintenance records found. Use the "Add Maintenance" tab above to log new services.', 'awesome-car-rental' ); ?></td>
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