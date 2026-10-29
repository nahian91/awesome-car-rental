<?php
/**
 * Frontend Main Controller
 *
 * Loads all frontend modules, shortcode views, and public-facing templates.
 *
 * @package Awesome_Car_Rental
 * @since   1.8.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Array of required frontend component files relative to this directory.
 */
$acrb_frontend_modules = array(
    'auth/acrb-frontend-auth.php',
    'cars/acrb-frontend-cars.php',
    'cars/acrb-frontend-cars-grid.php',
    'login/acrb-frontend-login.php',
    'registration/acrb-frontend-register.php',
    'search/acrb-frontend-search-form.php',
    'account/acrb-frontned-account.php',
    'single/acrb-frontned-car-single.php',
    'thanks/acrb-frontned-thanks.php',
    'checkout/acrb-frontned-checkout.php',
);

// Safely load each module if it exists
foreach ( $acrb_frontend_modules as $module ) {
    $module_path = plugin_dir_path( __FILE__ ) . $module;
    if ( file_exists( $module_path ) ) {
        include_once $module_path;
    }
}