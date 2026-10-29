<?php
/**
 * Plugin Assets Manager (`assets.php`)
 * Conditionally enqueues admin and frontend scripts, stylesheets, and third-party libraries (DataTables).
 *
 * @package Awesome_Car_Rental
 * @since   1.4.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Admin-Only Styles & Scripts (Including DataTables)
 *
 * @param string $hook The current admin page hook.
 */
function acrb_plugin_admin_scripts( $hook ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['page'] ) || 'awesome_car_rental' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
		return;
	}

	wp_enqueue_media();

	// 1. DataTables Core CSS (Loaded locally from assets/css)
	wp_enqueue_style(
		'acrb-datatables-css',
		ACRB_URL . 'assets/css/jquery.dataTables.min.css',
		array(),
		'1.13.7'
	);

	// 2. DataTables Core JS (Loaded locally from assets/js)
	wp_enqueue_script(
		'acrb-datatables-js',
		ACRB_URL . 'assets/js/jquery.dataTables.min.js',
		array( 'jquery' ),
		'1.13.7',
		true
	);

	// 3. Plugin Admin Styles & Scripts
	wp_enqueue_style(
		'acrb-admin-layout',
		ACRB_URL . 'assets/css/admin-style.css',
		array( 'acrb-datatables-css' ),
		ACRB_VERSION
	);

	wp_enqueue_script(
		'acrb-admin-script',
		ACRB_URL . 'assets/js/admin-script.js',
		array( 'jquery', 'acrb-datatables-js' ),
		ACRB_VERSION,
		true
	);

	// 4. Auto-initialize DataTables: Disable all alert warnings & handle empty states smoothly
	$acrb_inline_dt_init = "
		jQuery(document).ready(function($) {
			if ( $.fn.dataTable ) {
				$.fn.dataTable.ext.errMode = 'none';
			}

			$('.acrb-datatable, .awesome-car-rental table.wp-list-table').each(function() {
				var \$tbl = $(this);
				if ( ! $.fn.DataTable.isDataTable(this) ) {
					if ( \$tbl.find('tbody tr').length > 0 ) {
						\$tbl.DataTable({
							responsive: false,
							scrollX: true,
							pageLength: 10,
							order: [],
							language: {
								search: '',
								searchPlaceholder: 'Search records...',
								lengthMenu: 'Show _MENU_ entries',
								emptyTable: 'No records found',
								zeroRecords: 'No matching records found'
							}
						});
					}
				}
			});
		});
	";
	wp_add_inline_script( 'acrb-datatables-js', $acrb_inline_dt_init );
}
add_action( 'admin_enqueue_scripts', 'acrb_plugin_admin_scripts' );

/**
 * Helper: Check if current post contains plugin shortcodes
 */
function acrb_has_shortcodes() {
	if ( ! is_singular() ) {
		return false;
	}

	global $post;
	if ( ! is_a( $post, 'WP_Post' ) ) {
		return false;
	}

	$shortcodes = array(
		'acrb_all_cars', 
		'acrb_single_car', 
		'acrb_account', 
		'acrb_thanks', 
		'acrb_login', 
		'acrb_register'
	);

	foreach ( $shortcodes as $shortcode ) {
		if ( has_shortcode( $post->post_content, $shortcode ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Enqueue Frontend Styles & Scripts (Conditionally Optimized)
 */
function acrb_plugin_frontend_scripts() {
	if ( ! acrb_has_shortcodes() ) {
		return;
	}

	wp_enqueue_style( 'acrb-frontend-style', ACRB_URL . 'assets/css/frontend-style.css', array(), ACRB_VERSION );
	wp_enqueue_script( 'acrb-frontend-script', ACRB_URL . 'assets/js/frontend-script.js', array( 'jquery' ), ACRB_VERSION, true );

	wp_localize_script( 'acrb-frontend-script', 'acrb_ajax_obj', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'acrb_frontend_nonce' ),
		'currency' => get_option( 'acrb_currency', '£' )
	));
}
add_action( 'wp_enqueue_scripts', 'acrb_plugin_frontend_scripts' );