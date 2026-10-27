<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Categories Tab Logic - Refactored & Secured with Elite SaaS Aesthetic
 */
function acrb_category_tab() {
    // Media uploader for category thumbnails
    wp_enqueue_media();

    // Define sub-tabs and their icons
    $acrb_sub_tabs = array(
        'all' => array( 'label' => __( 'All Categories', 'awesome-car-rental' ), 'icon' => 'dashicons-category' ),
        'add' => array( 'label' => __( 'Add New', 'awesome-car-rental' ), 'icon' => 'dashicons-plus-alt2' ),
    );

    /**
     * 1. SECURE INPUT HANDLING
     * We use phpcs:ignore here because this is navigation/routing (GET), 
     * not data processing (POST). Nonce verification is handled inside 
     * processing functions like acrb_category_add_edit().
     */
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $acrb_active_sub   = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $acrb_current_page = 'awesome_car_rental';

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

        /* Content Card Container */
        .acrb-content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }

        @media(max-width: 900px) {
            .acrb-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .acrb-nav-wrapper { flex-direction: column; }
        }
    </style>

    <div class="acrb-dash-wrap">
        <!-- Header Section -->
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-category"></span>
                </div>
                <div class="acrb-title-content">
                    <h1 class="acrb-main-title"><?php esc_html_e( 'Car Categories', 'awesome-car-rental' ); ?></h1>
                    <p class="acrb-sub-title"><?php esc_html_e( 'Organize your cars into types (Luxury, SUV, Economy, etc.)', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Modern Navigation Tabs -->
        <nav class="acrb-nav-wrapper">
            <?php foreach ( $acrb_sub_tabs as $acrb_key => $acrb_data ) :
                $acrb_url = add_query_arg(
                    array(
                        'page' => $acrb_current_page,
                        'tab'  => 'categories',
                        'sub'  => $acrb_key,
                    ),
                    admin_url( 'admin.php' )
                );

                $acrb_is_active    = ( $acrb_active_sub === $acrb_key || ( 'all' === $acrb_key && 'view' === $acrb_active_sub ) );
                $acrb_active_class = $acrb_is_active ? ' is-active' : '';
                ?>
                <a href="<?php echo esc_url( $acrb_url ); ?>" class="acrb-nav-item<?php echo esc_attr( $acrb_active_class ); ?>">
                    <span class="dashicons <?php echo esc_attr( $acrb_data['icon'] ); ?>"></span>
                    <span class="acrb-nav-label"><?php echo esc_html( $acrb_data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Main Card Content -->
        <main class="acrb-content-card">
            <?php
            switch ( $acrb_active_sub ) {
                case 'add':
                case 'edit':
                    // The POST nonce check is handled inside this function.
                    acrb_category_add_edit();
                    break;

                case 'all':
                    acrb_category_list();
                    break;

                case 'view':
                    /**
                     * 2. SECURE ITEM HANDLING
                     * We unslash and sanitize the ID. Nonce ignore is applied because 
                     * viewing is a read-only action.
                     */
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $acrb_item_id = isset( $_GET['item'] ) ? absint( wp_unslash( $_GET['item'] ) ) : 0;
                    
                    if ( $acrb_item_id > 0 && function_exists( 'acrb_category_view' ) ) {
                        acrb_category_view( $acrb_item_id );
                    } else {
                        echo '<p>' . esc_html__( 'No category selected or view function missing.', 'awesome-car-rental' ) . '</p>';
                    }
                    break;

                default:
                    acrb_category_list();
                    break;
            }
            ?>
        </main>
    </div>
    <?php
}