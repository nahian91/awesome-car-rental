<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Cars Tab Logic - SaaS Style
 * Handles routing between inventory list, add, edit, and view states.
 */
function acrb_cars_tab() {
    // 1. Get current state safely
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $car_id    = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;

    $tabs = [
        'all' => [
            'label' => __( 'All Cars', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-list-view'
        ],
        'add' => [
            'label' => __( 'Add New Car', 'awesome-car-rental' ), 
            'icon'  => 'dashicons-plus-alt2'
        ],
    ];
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
            margin-bottom: 24px; 
            position: relative;
            overflow: hidden;
        }
        .acrb-title-wrapper { display: flex; align-items: center; gap: 18px; }
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

        /* Content Card Container */
        .acrb-content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-nav-wrapper { flex-direction: column; }
        }
    </style>

    <div class="acrb-dash-wrap">
        
        <!-- Header section -->
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-car"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Car Management', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'View and manage your rental vehicle inventory and fleet details.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $tabs as $k => $data ) :
                $url = add_query_arg( [
                    'page' => 'awesome_car_rental', 
                    'tab'  => 'cars', 
                    'sub'  => $k
                ], admin_url( 'admin.php' ) );

                $active_class = ( $sub_state === $k || ( 'all' === $k && in_array( $sub_state, [ 'edit', 'view' ], true ) ) ) ? ' is-active' : '';
                ?>
                <a href="<?php echo esc_url( $url ); ?>" class="acrb-nav-item<?php echo esc_attr( $active_class ); ?>">
                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
                    <span class="acrb-nav-label"><?php echo esc_html( $data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Content Area -->
        <main class="acrb-content-card">
            <?php
            switch ( $sub_state ) {
                case 'add':
                    // Security nonces are checked INSIDE this function during $_POST submission
                    acrb_add_edit_car_tab(); 
                    break;
                case 'edit':
                    acrb_add_edit_car_tab( $car_id );
                    break;
                case 'view':
                    acrb_view_car_tab( $car_id );
                    break;
                default:
                    acrb_cars_list();
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}