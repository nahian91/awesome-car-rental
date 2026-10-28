<?php
/**
 * Plugin Admin Menus (`menus.php`)
 * Registers the main top-level admin menu for the Car Rental system.
 *
 * @package Awesome_Car_Rental
 * @since   1.4.6
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
# Register Main Top-Level Menu Only (No Duplicate Submenus)
--------------------------------------------------------------*/
add_action( 'admin_menu', function() {
	// Main Car Rental Menu (Sidebar navigation is handled internally by the plugin's UI layout)
	add_menu_page(
		__( 'Car Rental', 'awesome-car-rental' ),
		__( 'Car Rental', 'awesome-car-rental' ),
		'manage_options',
		'awesome_car_rental', 
		'acrb_main_page',
		'dashicons-car', 
		20
	);
});