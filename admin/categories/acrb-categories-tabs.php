<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Categories Tab Logic - Refactored & Secured
 */
function acrb_category_tab() {
	// Media uploader for category thumbnails
	wp_enqueue_media();

	// Define sub-tabs and their icons
	$acrb_sub_tabs = array(
		'all' => array( 'label' => __( 'All Categories', 'awesome-car-rental' ), 'icon' => 'dashicons-category' ),
		'add' => array( 'label' => __( 'Add New', 'awesome-car-rental' ), 'icon' => 'dashicons-plus' ),
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

	echo '<div class="acrb-admin-container">';

	// Header Section
	echo '<header class="acrb-header">';
	echo '  <div class="acrb-header-text">';
	echo '      <h1>' . esc_html__( 'Car Categories', 'awesome-car-rental' ) . '</h1>';
	echo '      <p>' . esc_html__( 'Organize your car into types (Luxury, SUV, Economy, etc.)', 'awesome-car-rental' ) . '</p>';
	echo '  </div>';

	// Quick Action Button
	if ( 'add' !== $acrb_active_sub ) {
		$add_url = add_query_arg( array( 'tab' => 'categories', 'sub' => 'add' ), admin_url( 'admin.php?page=' . $acrb_current_page ) );
		echo '  <a href="' . esc_url( $add_url ) . '" class="acrb-btn acrb-btn-primary">';
		echo '      <span class="dashicons dashicons-plus-alt"></span> ' . esc_html__( 'Create Category', 'awesome-car-rental' );
		echo '  </a>';
	}
	echo '</header>';

	// Modern Navigation Tabs
	echo '<nav class="acrb-nav-wrapper">';
	foreach ( $acrb_sub_tabs as $acrb_key => $acrb_data ) {
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

		echo '<a href="' . esc_url( $acrb_url ) . '" class="acrb-nav-item' . esc_attr( $acrb_active_class ) . '">';
		echo '  <span class="dashicons ' . esc_attr( $acrb_data['icon'] ) . '"></span>';
		echo '  <span class="acrb-nav-label">' . esc_html( $acrb_data['label'] ) . '</span>';
		echo '</a>';
	}
	echo '</nav>';

	// Main Card Content
	echo '<main class="acrb-content-card">';
	switch ( $acrb_active_sub ) {
		case 'add':
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
			
			if ( $acrb_item_id > 0 ) {
				acrb_category_view( $acrb_item_id );
				
				// Example of your required translator comment format if you needed a status msg here:
				// // translators: 1: Booking ID, 2: New status name.
				// $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_item_id ), esc_html__( 'Viewed', 'awesome-car-rental' ) );
				// echo wp_kses_post( $acrb_msg );
			} else {
				echo '<p>' . esc_html__( 'No category selected.', 'awesome-car-rental' ) . '</p>';
			}
			break;

		default:
			acrb_category_list();
			break;
	}
	echo '</main>';

	echo '</div>'; // End Container
}