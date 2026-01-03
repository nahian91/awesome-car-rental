<?php

if ( ! defined( 'ABSPATH' ) ) exit; 
/*-----------------------------------------
# Settings Main Controller
-----------------------------------------*/

// 1. Include all tab files from the subfolder
include_once plugin_dir_path(__FILE__) . 'auth/acrb-frontend-auth.php';
include_once plugin_dir_path(__FILE__) . 'cars/acrb-frontend-cars.php';
include_once plugin_dir_path(__FILE__) . 'cars/acrb-frontend-cars-grid.php';
include_once plugin_dir_path(__FILE__) . 'login/acrb-frontend-login.php';
include_once plugin_dir_path(__FILE__) . 'registration/acrb-frontend-register.php';
include_once plugin_dir_path(__FILE__) . 'search/acrb-frontend-search-form.php';
include_once plugin_dir_path(__FILE__) . 'account/acrb-frontned-account.php';
include_once plugin_dir_path(__FILE__) . 'single/acrb-frontned-car-single.php';
include_once plugin_dir_path(__FILE__) . 'thanks/acrb-frontned-thanks.php';