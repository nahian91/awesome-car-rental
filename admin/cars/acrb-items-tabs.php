<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Cars Tab Logic - SaaS Style
 */
function acrb_cars_tab() {
    // 1. Get current state
    $sub    = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $car_id = isset( $_GET['car_id'] ) ? absint( wp_unslash( $_GET['car_id'] ) ) : 0;

    $tabs = [
        'all' => ['label' => __('All Cars', 'awesome-car-rental'), 'icon' => 'dashicons-list-view'],
        'add' => ['label' => __('Add New Car', 'awesome-car-rental'), 'icon' => 'dashicons-plus-alt'],
    ];

    echo '<div class="acrb-admin-container">';
    
    // Header section
    echo '<header class="acrb-header">';
    echo '  <div class="acrb-header-text">';
    echo '      <h1>' . esc_html__('Car Management', 'awesome-car-rental') . '</h1>';
    echo '      <p>' . esc_html__('View and manage your rental vehicle inventory.', 'awesome-car-rental') . '</p>';
    echo '  </div>';
    
    if ($sub !== 'add') {
        $add_url = admin_url('admin.php?page=awesome_car_rental&tab=cars&sub=add');
        echo '  <a href="' . esc_url($add_url) . '" class="acrb-btn acrb-btn-primary">';
        echo '      <span class="dashicons dashicons-plus-alt"></span> ' . esc_html__('Add Car', 'awesome-car-rental');
        echo '  </a>';
    }
    echo '</header>';

    // Navigation Tabs
    echo '<nav class="acrb-nav-wrapper">';
    foreach ($tabs as $k => $data) {
        $url = add_query_arg(['page' => 'awesome_car_rental', 'tab' => 'cars', 'sub' => $k], admin_url('admin.php'));
        $active_class = ($sub === $k || ($k === 'all' && in_array($sub, ['edit', 'view']))) ? ' is-active' : '';

        echo '<a href="' . esc_url($url) . '" class="acrb-nav-item' . esc_attr($active_class) . '">';
        echo '  <span class="dashicons ' . esc_attr($data['icon']) . '"></span>';
        echo '  <span class="acrb-nav-label">' . esc_html($data['label']) . '</span>';
        echo '</a>';
    }
    echo '</nav>';

    // Content Area
    echo '<main class="acrb-content-card">';
    switch ($sub) {
        case 'add':
            // Security nonces are checked INSIDE this function during $_POST
            acrb_add_edit_car_tab(); 
            break;
        case 'edit':
            acrb_add_edit_car_tab($car_id);
            break;
        case 'view':
            acrb_view_car_tab($car_id);
            break;
        default:
            acrb_cars_list();
            break;
    }
    echo '</main></div>';
}