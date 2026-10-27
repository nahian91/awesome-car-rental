<?php
/**
 * Staff Management Module - Professional SaaS Layout with 2-Column Form Design
 * Location: admin/staff.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_staff_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    $page_slug = 'awesome_car_rental';
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $staff_id  = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0;

    $tabs = [
        'all' => [
            'label' => __( 'All Staff', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-id-alt'
        ],
        'add' => [
            'label' => __( 'Add New Staff', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-plus-alt2'
        ],
    ];

    // Enqueue WordPress media uploader for profile pictures
    wp_enqueue_media();

    // 1. HANDLE FORM SUBMISSION (Save / Update Staff)
    if ( isset( $_POST['acrb_save_staff'] ) ) {
        $nonce = isset( $_POST['acrb_staff_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_staff_nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'acrb_staff_action' ) ) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Security verification failed.', 'awesome-car-rental' ) . '</p></div>';
        } else {
            $staff_user_id = isset( $_POST['staff_user_id'] ) ? absint( $_POST['staff_user_id'] ) : 0;
            $staff_name    = isset( $_POST['staff_name'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_name'] ) ) : '';
            $staff_email   = isset( $_POST['staff_email'] ) ? sanitize_email( wp_unslash( $_POST['staff_email'] ) ) : '';
            $staff_phone   = isset( $_POST['staff_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_phone'] ) ) : '';
            $staff_salary  = isset( $_POST['staff_salary'] ) ? floatval( wp_unslash( $_POST['staff_salary'] ) ) : 0;
            $staff_desig   = isset( $_POST['staff_designation'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_designation'] ) ) : 'Agent';
            $staff_avatar  = isset( $_POST['staff_profile_pic'] ) ? absint( wp_unslash( $_POST['staff_profile_pic'] ) ) : 0;

            if ( $staff_user_id > 0 ) {
                wp_update_user( array(
                    'ID'           => $staff_user_id,
                    'display_name' => $staff_name,
                    'user_email'   => $staff_email,
                ) );

                update_user_meta( $staff_user_id, 'acrb_staff_role', $staff_desig );
                update_user_meta( $staff_user_id, 'acrb_staff_phone', $staff_phone );
                update_user_meta( $staff_user_id, 'acrb_staff_salary', $staff_salary );
                update_user_meta( $staff_user_id, 'acrb_staff_profile_pic', $staff_avatar );

                echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p><strong>' . esc_html__( 'Success:', 'awesome-car-rental' ) . '</strong> ' . esc_html__( 'Staff member profile updated.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    // 2. HANDLE NEW STAFF REGISTRATION (Directly creates WP user / assigns staff meta)
    if ( isset( $_POST['acrb_add_staff_submit'] ) ) {
        $nonce = isset( $_POST['acrb_add_staff_nonce'] ) ? sanitize_key( wp_unslash( $_POST['acrb_add_staff_nonce'] ) ) : '';

        if ( wp_verify_nonce( $nonce, 'acrb_add_staff_action' ) ) {
            $new_name   = isset( $_POST['new_staff_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_staff_name'] ) ) : '';
            $new_email  = isset( $_POST['new_staff_email'] ) ? sanitize_email( wp_unslash( $_POST['new_staff_email'] ) ) : '';
            $new_phone  = isset( $_POST['new_staff_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['new_staff_phone'] ) ) : '';
            $new_salary = isset( $_POST['new_staff_salary'] ) ? floatval( wp_unslash( $_POST['new_staff_salary'] ) ) : 0;
            $new_desig  = isset( $_POST['new_staff_designation'] ) ? sanitize_text_field( wp_unslash( $_POST['new_staff_designation'] ) ) : 'Agent';
            $new_avatar = isset( $_POST['new_staff_profile_pic'] ) ? absint( wp_unslash( $_POST['new_staff_profile_pic'] ) ) : 0;

            if ( ! empty( $new_email ) && email_exists( $new_email ) === false ) {
                $random_password = wp_generate_password( 12, true );
                $username        = sanitize_user( current( explode( '@', $new_email ) ), true );
                
                // Ensure unique username
                $base_username = $username;
                $counter = 1;
                while ( username_exists( $username ) ) {
                    $username = $base_username . $counter;
                    $counter++;
                }

                $new_user_id = wp_create_user( $username, $random_password, $new_email );

                if ( ! is_wp_error( $new_user_id ) ) {
                    if ( ! empty( $new_name ) ) {
                        wp_update_user( array( 'ID' => $new_user_id, 'display_name' => $new_name ) );
                    }

                    update_user_meta( $new_user_id, 'acrb_is_staff', 'yes' );
                    update_user_meta( $new_user_id, 'acrb_staff_role', $new_desig );
                    update_user_meta( $new_user_id, 'acrb_staff_phone', $new_phone );
                    update_user_meta( $new_user_id, 'acrb_staff_salary', $new_salary );
                    update_user_meta( $new_user_id, 'acrb_staff_profile_pic', $new_avatar );
                    
                    echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'New staff member successfully created.', 'awesome-car-rental' ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html( $new_user_id->get_error_message() ) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Error: Email already exists or is invalid.', 'awesome-car-rental' ) . '</p></div>';
            }
        }
    }

    // 3. HANDLE STAFF REVOCATION
    if ( isset( $_GET['rev_staff'] ) && current_user_can( 'manage_options' ) ) {
        $revoke_id = absint( $_GET['rev_staff'] );
        $nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( wp_verify_nonce( $nonce, 'acrb_rev_staff_' . $revoke_id ) ) {
            delete_user_meta( $revoke_id, 'acrb_is_staff' );
            delete_user_meta( $revoke_id, 'acrb_staff_role' );
            delete_user_meta( $revoke_id, 'acrb_staff_phone' );
            delete_user_meta( $revoke_id, 'acrb_staff_salary' );
            delete_user_meta( $revoke_id, 'acrb_staff_profile_pic' );
            echo '<div class="notice notice-warning is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Staff privileges revoked for user.', 'awesome-car-rental' ) . '</p></div>';
        }
    }

    $currency = get_option( 'acrb_currency', '£' );
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
        .acrb-card h2, .acrb-card h3 { font-size: 15px; font-weight: 600; margin: 0 0 20px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .acrb-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .acrb-form-group { display: flex; flex-direction: column; }

        .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .acrb-table-wrap table.widefat { border: none; margin: 0; box-shadow: none; }
        .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
        .acrb-table-wrap table.widefat tbody td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
        .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

        .acrb-input, .acrb-select { width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 14px; color: #1e293b; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.01); transition: all 0.2s ease; }
        .acrb-input:hover, .acrb-select:hover { border-color: #94a3b8; }
        .acrb-input:focus, .acrb-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); outline: none; background: #fff; }

        .acrb-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #334155; }

        .acrb-btn-primary { background: #3b82f6; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); transition: background 0.2s; display: inline-flex; align-items: center; justify-content: center; height: 42px; text-decoration: none; }
        .acrb-btn-primary:hover { background: #2563eb; color: #fff; }

        .acrb-btn-secondary { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 16px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.25s ease; cursor: pointer; font-size: 13px; height: 40px; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
        .acrb-btn-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-nav-wrapper { flex-direction: column; }
            .acrb-form-grid-2 { grid-template-columns: 1fr; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-id-alt"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Fleet Operations Staff', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'Manage front-desk operators, chauffeurs, and maintenance supervisors.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( [
                    'page' => $page_slug, 
                    'tab'  => 'staff', 
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
            <?php if ( 'edit' === $sub_state && $staff_id > 0 ) : 
                $staff_user = get_userdata( $staff_id );
                if ( ! $staff_user ) {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Staff user not found.', 'awesome-car-rental' ) . '</p></div>';
                    return;
                }
                $staff_name    = $staff_user->display_name;
                $staff_email   = $staff_user->user_email;
                $staff_phone   = get_user_meta( $staff_id, 'acrb_staff_phone', true );
                $staff_salary  = get_user_meta( $staff_id, 'acrb_staff_salary', true );
                $staff_desig   = get_user_meta( $staff_id, 'acrb_staff_role', true ) ?: 'Front Desk Agent';
                $staff_avatar  = get_user_meta( $staff_id, 'acrb_staff_profile_pic', true );
            ?>
                <!-- EDIT STAFF FORM -->
                <div class="acrb-card">
                    <h2><?php printf( esc_html__( 'Edit Staff Profile: %s', 'awesome-car-rental' ), esc_html( $staff_name ) ); ?></h2>
                    <form method="post">
                        <?php wp_nonce_field( 'acrb_staff_action', 'acrb_staff_nonce' ); ?>
                        <input type="hidden" name="staff_user_id" value="<?php echo absint( $staff_id ); ?>">

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Name', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="staff_name" value="<?php echo esc_attr( $staff_name ); ?>" class="acrb-input" required>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Email', 'awesome-car-rental' ); ?></label>
                                <input type="email" name="staff_email" value="<?php echo esc_attr( $staff_email ); ?>" class="acrb-input" required>
                            </div>
                        </div>

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="staff_phone" value="<?php echo esc_attr( $staff_phone ); ?>" class="acrb-input" placeholder="+1 (555) 000-0000">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Salary', 'awesome-car-rental' ); ?> (<?php echo esc_html( $currency ); ?>)</label>
                                <input type="number" step="0.01" name="staff_salary" value="<?php echo esc_attr( $staff_salary ); ?>" class="acrb-input" placeholder="0.00">
                            </div>
                        </div>

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Designation', 'awesome-car-rental' ); ?></label>
                                <select name="staff_designation" class="acrb-select">
                                    <option value="Front Desk Agent" <?php selected( $staff_desig, 'Front Desk Agent' ); ?>><?php esc_html_e( 'Front Desk Agent', 'awesome-car-rental' ); ?></option>
                                    <option value="Chauffeur / Driver" <?php selected( $staff_desig, 'Chauffeur / Driver' ); ?>><?php esc_html_e( 'Chauffeur / Driver', 'awesome-car-rental' ); ?></option>
                                    <option value="Fleet Manager" <?php selected( $staff_desig, 'Fleet Manager' ); ?>><?php esc_html_e( 'Fleet Manager', 'awesome-car-rental' ); ?></option>
                                    <option value="Maintenance Technician" <?php selected( $staff_desig, 'Maintenance Technician' ); ?>><?php esc_html_e( 'Maintenance Technician', 'awesome-car-rental' ); ?></option>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Profile Pic', 'awesome-car-rental' ); ?></label>
                                <div style="display:flex; align-items:center; gap:16px;">
                                    <div id="acrb-avatar-preview">
                                        <?php if ( $staff_avatar ) : 
                                            echo wp_get_attachment_image( $staff_avatar, [50, 50], false, ['style' => 'border-radius:50%; width:50px; height:50px; object-fit:cover; border:1px solid #e2e8f0;'] );
                                        else :
                                            echo get_avatar( $staff_id, 50, '', '', array( 'style' => 'border-radius:50%; border:1px solid #e2e8f0;' ) );
                                        endif; ?>
                                    </div>
                                    <input type="hidden" name="staff_profile_pic" id="acrb_staff_profile_pic" value="<?php echo esc_attr( $staff_avatar ); ?>">
                                    <button type="button" class="acrb-btn-secondary" id="acrb_upload_avatar_btn"><?php esc_html_e( 'Select Picture', 'awesome-car-rental' ); ?></button>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:10px;">
                            <button type="submit" name="acrb_save_staff" class="acrb-btn-primary">
                                <?php esc_html_e( 'Update Staff Profile', 'awesome-car-rental' ); ?>
                            </button>
                        </div>
                    </form>
                </div>

            <?php elseif ( 'add' === $sub_state ) : ?>
                <!-- ADD STAFF PANEL -->
                <div class="acrb-card">
                    <h3><?php esc_html_e( 'Add New Staff Member', 'awesome-car-rental' ); ?></h3>
                    <p style="font-size:13px; color:#64748b; margin-top:0; margin-bottom:20px;"><?php esc_html_e( 'Enter the details below to add a new staff member to the operations team.', 'awesome-car-rental' ); ?></p>
                    
                    <form method="post">
                        <?php wp_nonce_field( 'acrb_add_staff_action', 'acrb_add_staff_nonce' ); ?>
                        
                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Name', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="new_staff_name" class="acrb-input" placeholder="Full Name" required>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Email', 'awesome-car-rental' ); ?></label>
                                <input type="email" name="new_staff_email" class="acrb-input" placeholder="email@example.com" required>
                            </div>
                        </div>

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></label>
                                <input type="text" name="new_staff_phone" class="acrb-input" placeholder="+1 (555) 000-0000">
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Salary', 'awesome-car-rental' ); ?> (<?php echo esc_html( $currency ); ?>)</label>
                                <input type="number" step="0.01" name="new_staff_salary" class="acrb-input" placeholder="0.00">
                            </div>
                        </div>

                        <div class="acrb-form-grid-2">
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Designation', 'awesome-car-rental' ); ?></label>
                                <select name="new_staff_designation" class="acrb-select">
                                    <option value="Front Desk Agent"><?php esc_html_e( 'Front Desk Agent', 'awesome-car-rental' ); ?></option>
                                    <option value="Chauffeur / Driver"><?php esc_html_e( 'Chauffeur / Driver', 'awesome-car-rental' ); ?></option>
                                    <option value="Fleet Manager"><?php esc_html_e( 'Fleet Manager', 'awesome-car-rental' ); ?></option>
                                    <option value="Maintenance Technician"><?php esc_html_e( 'Maintenance Technician', 'awesome-car-rental' ); ?></option>
                                </select>
                            </div>
                            <div class="acrb-form-group">
                                <label class="acrb-label"><?php esc_html_e( 'Profile Pic', 'awesome-car-rental' ); ?></label>
                                <div style="display:flex; align-items:center; gap:16px;">
                                    <div id="acrb-avatar-preview">
                                        <div style="width:50px; height:50px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; border:1px solid #e2e8f0;">
                                            <span class="dashicons dashicons-camera" style="font-size:22px; width:22px; height:22px;"></span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="new_staff_profile_pic" id="acrb_staff_profile_pic" value="">
                                    <button type="button" class="acrb-btn-secondary" id="acrb_upload_avatar_btn"><?php esc_html_e( 'Select Picture', 'awesome-car-rental' ); ?></button>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:10px;">
                            <button type="submit" name="acrb_add_staff_submit" class="acrb-btn-primary">
                                <?php esc_html_e( 'Save Staff Member', 'awesome-car-rental' ); ?>
                            </button>
                        </div>
                    </form>
                </div>

            <?php else : ?>
                <!-- STAFF LIST TABLE -->
                <div class="acrb-table-wrap">
                    <div style="padding: 20px 24px 0;">
                        <h3 style="margin:0; border-bottom:1px solid #f1f5f9; padding-bottom:14px; font-size:15px; font-weight:600; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Active Operations Team', 'awesome-car-rental' ); ?></h3>
                    </div>
                    
                    <table class="widefat striped" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th style="width: 70px;"><?php esc_html_e( 'Pic', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Name & Email', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Designation', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Phone', 'awesome-car-rental' ); ?></th>
                                <th><?php esc_html_e( 'Salary', 'awesome-car-rental' ); ?></th>
                                <th style="text-align:right;"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $staff_list = get_users( array(
                                'meta_key'   => 'acrb_is_staff',
                                'meta_value' => 'yes',
                            ) );

                            if ( ! empty( $staff_list ) ) :
                                foreach ( $staff_list as $staff_member ) :
                                    $item_desig  = get_user_meta( $staff_member->ID, 'acrb_staff_role', true ) ?: 'Front Desk Agent';
                                    $item_phone  = get_user_meta( $staff_member->ID, 'acrb_staff_phone', true ) ?: '—';
                                    $item_salary = get_user_meta( $staff_member->ID, 'acrb_staff_salary', true );
                                    $item_avatar = get_user_meta( $staff_member->ID, 'acrb_staff_profile_pic', true );
                                    $edit_url    = admin_url( 'admin.php?page=' . $page_slug . '&tab=staff&sub=edit&staff_id=' . $staff_member->ID );
                                    $rev_url     = wp_nonce_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=staff&rev_staff=' . $staff_member->ID ), 'acrb_rev_staff_' . $staff_member->ID );
                            ?>
                            <tr>
                                <td>
                                    <?php if ( $item_avatar ) : 
                                        echo wp_get_attachment_image( $item_avatar, [42, 42], false, ['style' => 'border-radius:50%; width:42px; height:42px; object-fit:cover; border:1px solid #e2e8f0;'] );
                                    else :
                                        echo get_avatar( $staff_member->ID, 42, '', '', array( 'style' => 'border-radius:50%; border:1px solid #e2e8f0;' ) );
                                    endif; ?>
                                </td>
                                <td>
                                    <strong style="color:#0f172a; display:block;"><?php echo esc_html( $staff_member->display_name ); ?></strong>
                                    <span style="font-size:12px; color:#64748b;"><?php echo esc_html( $staff_member->user_email ); ?></span>
                                </td>
                                <td>
                                    <span style="background:#eff6ff; color:#3b82f6; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                                        <?php echo esc_html( $item_desig ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $item_phone ); ?></td>
                                <td style="font-weight:600; color:#16a34a;">
                                    <?php echo $item_salary !== '' ? esc_html( $currency . number_format( floatval( $item_salary ), 2 ) ) : '—'; ?>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>" style="display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border-radius:6px;">
                                            <span class="dashicons dashicons-edit" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $rev_url ); ?>" class="button button-small" style="color:#b91c1c; display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border-radius:6px;" onclick="return confirm('<?php esc_attr_e( 'Revoke staff privileges for this user?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Revoke Staff', 'awesome-car-rental' ); ?>">
                                            <span class="dashicons dashicons-trash" style="font-size:14px; width:14px; height:14px; display:inline-flex; align-items:center; justify-content:center;"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else : ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:#64748b;"><?php esc_html_e( 'No staff members assigned yet. Use the "Add New Staff" tab above to add team members.', 'awesome-car-rental' ); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    jQuery(document).ready(function($){
        // WP Media Uploader for Profile Picture
        var mediaFrame;
        $('#acrb_upload_avatar_btn').click(function(e){
            e.preventDefault();
            if(mediaFrame) { mediaFrame.open(); return; }

            mediaFrame = wp.media({ title: 'Select Profile Picture', button: { text: 'Use Image' }, multiple: false });
            mediaFrame.on('select', function(){
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                $('#acrb_staff_profile_pic').val(attachment.id);
                $('#acrb-avatar-preview').html('<img src="'+attachment.url+'" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:1px solid #e2e8f0; display:block;">');
            }).open();
        });
    });
    </script>
    <?php
}