<?php

if ( ! defined( 'ABSPATH' ) ) exit; 
/*-----------------------------------------
# Settings Main Controller
-----------------------------------------*/

// 1. Include all tab files from the subfolder
include_once plugin_dir_path(__FILE__) . 'settings/settings-tab.php';
include_once plugin_dir_path(__FILE__) . 'settings/settings-general.php';
include_once plugin_dir_path(__FILE__) . 'settings/settings-payments.php';
include_once plugin_dir_path(__FILE__) . 'settings/settings-emails.php';