<?php
/**
 * SEO & Social Optimization Engine Module (`seo.php`)
 * Enterprise SERP Snippet Preview, Social Share Cards, and Automated JSON-LD Structured Data.
 *
 * @package Awesome_Car_Rental
 * @since   3.1.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
# 1. Frontend Head Tag Injection & Schema Engine
--------------------------------------------------------------*/
add_action( 'wp_head', 'acrb_render_seo_meta_tags', 1 );

function acrb_render_seo_meta_tags(): void {
	if ( is_admin() ) {
		return;
	}

	$meta_title       = get_option( 'acrb_seo_meta_title', 'Awesome Car Rental | Premium Fleet Booking' );
	$meta_desc        = get_option( 'acrb_seo_meta_desc', 'Rent luxury and economy vehicles with real-time availability and transparent pricing.' );
	$og_image         = get_option( 'acrb_seo_og_image', '' );
	$enable_schema    = get_option( 'acrb_seo_enable_schema', 'yes' );
	$enable_canonical = get_option( 'acrb_seo_enable_canonical', 'yes' );
	$robots_index     = get_option( 'acrb_seo_robots_index', 'index' );
	$twitter_card     = get_option( 'acrb_seo_twitter_card_type', 'summary_large_image' );

	// Token Replacements.
	$site_name    = get_bloginfo( 'name' );
	$current_year = gmdate( 'Y' );
	$tokens       = array( '{site_name}', '{year}' );
	$replacements = array( $site_name, $current_year );

	$parsed_title = str_replace( $tokens, $replacements, (string) $meta_title );
	$parsed_desc  = str_replace( $tokens, $replacements, (string) $meta_desc );

	echo "\n<!-- Awesome Car Rental SEO Engine -->\n";
	if ( 'noindex' === $robots_index ) {
		echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
	}

	echo '<meta name="description" content="' . esc_attr( $parsed_desc ) . '" />' . "\n";

	if ( 'yes' === $enable_canonical ) {
		global $wp;
		$canonical_url = home_url( add_query_arg( array(), $wp->request ?? '' ) );
		echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";
	}

	// Open Graph.
	echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '" />' . "\n";
	echo '<meta property="og:type" content="website" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $parsed_title ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $parsed_desc ) . '" />' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
	if ( ! empty( $og_image ) ) {
		echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
	}

	// Twitter Card.
	echo '<meta name="twitter:card" content="' . esc_attr( $twitter_card ) . '" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $parsed_title ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $parsed_desc ) . '" />' . "\n";
	if ( ! empty( $og_image ) ) {
		echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '" />' . "\n";
	}

	// Structured Data (JSON-LD).
	if ( 'yes' === $enable_schema ) {
		$schema_data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'AutoRental',
			'name'            => $site_name,
			'description'     => $parsed_desc,
			'url'             => home_url(),
			'potentialAction' => array(
				'@type'  => 'ReserveAction',
				'target' => home_url( '/acrb-cars/' ),
				'result' => array(
					'@type' => 'RentalCarReservation',
					'name'  => 'Car Rental Reservation',
				),
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "</script>\n";
	}
	echo "<!-- /Awesome Car Rental SEO Engine -->\n\n";
}

/*--------------------------------------------------------------
# 2. Admin Interface Controller & UI
--------------------------------------------------------------*/
function acrb_seo_tab(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
	}

	$page_slug = 'awesome_car_rental';
	$base_url  = admin_url( 'admin.php?page=' . $page_slug . '&tab=seo' );

	// Handle Form Save & Safe Redirect.
	if ( isset( $_POST['acrb_seo_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acrb_seo_nonce'] ) ), 'acrb_save_seo_settings' ) ) {
		update_option( 'acrb_seo_meta_title', sanitize_text_field( wp_unslash( $_POST['acrb_seo_meta_title'] ?? '' ) ) );
		update_option( 'acrb_seo_meta_desc', sanitize_textarea_field( wp_unslash( $_POST['acrb_seo_meta_desc'] ?? '' ) ) );
		update_option( 'acrb_seo_og_image', esc_url_raw( wp_unslash( $_POST['acrb_seo_og_image'] ?? '' ) ) );
		update_option( 'acrb_seo_enable_schema', isset( $_POST['acrb_seo_enable_schema'] ) ? 'yes' : 'no' );
		update_option( 'acrb_seo_enable_canonical', isset( $_POST['acrb_seo_enable_canonical'] ) ? 'yes' : 'no' );
		update_option( 'acrb_seo_twitter_card_type', sanitize_key( wp_unslash( $_POST['acrb_seo_twitter_card_type'] ?? 'summary_large_image' ) ) );
		update_option( 'acrb_seo_robots_index', isset( $_POST['acrb_seo_robots_index'] ) ? sanitize_key( wp_unslash( $_POST['acrb_seo_robots_index'] ) ) : 'index' );

		$redirect_url = add_query_arg( 'updated', '1', $base_url );

		if ( headers_sent() ) {
			echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
			echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
			exit;
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	// Retrieve Options.
	$meta_title        = (string) get_option( 'acrb_seo_meta_title', __( 'Awesome Car Rental | {site_name} Fleet & Booking', 'awesome-car-rental' ) );
	$meta_desc         = (string) get_option( 'acrb_seo_meta_desc', __( 'Reserve economy and luxury vehicles with instant confirmation, live availability, and transparent daily rates.', 'awesome-car-rental' ) );
	$og_image          = (string) get_option( 'acrb_seo_og_image', '' );
	$enable_schema     = (string) get_option( 'acrb_seo_enable_schema', 'yes' );
	$enable_canonical  = (string) get_option( 'acrb_seo_enable_canonical', 'yes' );
	$twitter_card_type = (string) get_option( 'acrb_seo_twitter_card_type', 'summary_large_image' );
	$robots_index      = (string) get_option( 'acrb_seo_robots_index', 'index' );

	// Enqueue Native WordPress Media Uploader.
	wp_enqueue_media();
	?>

	<style>
		.acrb-seo-wrap {
			width: 100%;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #1e293b;
			box-sizing: border-box;
		}
		.acrb-seo-wrap *,
		.acrb-seo-wrap *::before,
		.acrb-seo-wrap *::after {
			box-sizing: border-box;
		}

		/* Topbar Header Banner */
		.acrb-seo-topbar-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
			border: 1px solid #e2e8f0;
			border-left: 4px solid #4f46e5;
			border-radius: 14px;
			padding: 18px 24px;
			box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
			margin-bottom: 22px;
			gap: 16px;
			flex-wrap: wrap;
		}
		.acrb-seo-topbar-title-group {
			display: flex;
			align-items: center;
			gap: 14px;
		}
		.acrb-seo-topbar-icon {
			width: 44px;
			height: 44px;
			min-width: 44px;
			background: #eef2ff;
			color: #4f46e5;
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 2px 6px rgba(79, 70, 229, 0.15);
			flex-shrink: 0;
		}
		.acrb-seo-topbar-icon .dashicons {
			font-size: 22px;
			width: 22px;
			height: 22px;
		}
		.acrb-seo-topbar-meta h1 {
			font-size: 19px;
			font-weight: 800;
			color: #0f172a;
			margin: 0 0 3px 0;
			letter-spacing: -0.02em;
			line-height: 1.2;
		}
		.acrb-seo-topbar-meta p {
			margin: 0;
			font-size: 13px;
			color: #64748b;
		}

		/* SEO Score Meter */
		.acrb-seo-score-meter {
			display: flex;
			align-items: center;
			gap: 12px;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 8px 16px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}
		.acrb-seo-score-circle {
			width: 38px;
			height: 38px;
			border-radius: 50%;
			background: #059669;
			color: #ffffff;
			font-size: 14px;
			font-weight: 800;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: background 0.2s ease;
		}
		.acrb-seo-score-text strong {
			display: block;
			font-size: 13px;
			color: #0f172a;
			font-weight: 700;
		}
		.acrb-seo-score-text span {
			font-size: 11px;
			color: #64748b;
		}

		/* Feedback Notice */
		.acrb-seo-alert-success {
			background: #ecfdf5;
			border: 1px solid #a7f3d0;
			color: #065f46;
			padding: 12px 18px;
			border-radius: 10px;
			font-size: 13.5px;
			font-weight: 600;
			margin-bottom: 22px;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		/* 2-Column Responsive Layout */
		.acrb-seo-grid {
			display: grid;
			grid-template-columns: 1fr 440px;
			gap: 24px;
			align-items: start;
		}
		.acrb-seo-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
			padding: 24px;
			margin-bottom: 22px;
		}
		.acrb-seo-card-title {
			margin: 0 0 18px;
			font-size: 14.5px;
			font-weight: 800;
			color: #0f172a;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			display: flex;
			align-items: center;
			gap: 8px;
			border-bottom: 1px solid #f1f5f9;
			padding-bottom: 12px;
		}
		.acrb-seo-card-title .dashicons {
			color: #4f46e5;
			font-size: 18px;
			width: 18px;
			height: 18px;
		}
		.acrb-seo-form-group {
			margin-bottom: 20px;
		}
		.acrb-seo-form-group:last-child {
			margin-bottom: 0;
		}
		.acrb-seo-label-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 6px;
		}
		.acrb-seo-label {
			font-weight: 700;
			color: #1e293b;
			font-size: 13.5px;
		}
		.acrb-seo-counter {
			font-size: 11.5px;
			font-weight: 700;
			color: #64748b;
		}
		.acrb-seo-counter.limit-good { color: #059669; }
		.acrb-seo-counter.limit-warn { color: #d97706; }
		.acrb-seo-counter.limit-bad  { color: #dc2626; }

		/* Form Controls */
		.acrb-seo-input,
		.acrb-seo-textarea,
		.acrb-seo-select {
			display: block;
			width: 100% !important;
			max-width: 100% !important;
			background: #f8fafc !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 14px !important;
			font-size: 13.5px !important;
			font-family: inherit !important;
			color: #1e293b !important;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
			transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
		}
		.acrb-seo-input,
		.acrb-seo-select {
			height: 42px !important;
			line-height: 40px !important;
		}
		.acrb-seo-textarea {
			height: auto !important;
			padding: 10px 14px !important;
			line-height: 1.5 !important;
			resize: vertical;
		}
		.acrb-seo-input:hover,
		.acrb-seo-textarea:hover,
		.acrb-seo-select:hover {
			border-color: #94a3b8 !important;
			background: #ffffff !important;
		}
		.acrb-seo-input:focus,
		.acrb-seo-textarea:focus,
		.acrb-seo-select:focus {
			background: #ffffff !important;
			border-color: #4f46e5 !important;
			outline: none !important;
			box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
		}

		/* Select Dropdown Wrap */
		.acrb-seo-select-wrap {
			position: relative;
			width: 100%;
		}
		.acrb-seo-select-wrap select {
			appearance: none;
			-webkit-appearance: none;
			padding-right: 36px !important;
			cursor: pointer;
		}
		.acrb-seo-select-arrow {
			position: absolute;
			right: 12px;
			top: 50%;
			transform: translateY(-50%);
			pointer-events: none;
			color: #64748b;
			font-size: 18px;
			width: 18px;
			height: 18px;
		}

		/* Token Pills */
		.acrb-seo-tokens-wrap {
			display: flex;
			gap: 6px;
			flex-wrap: wrap;
			margin-top: 8px;
		}
		.acrb-seo-token-btn {
			background: #f1f5f9;
			border: 1px solid #e2e8f0;
			color: #475569;
			font-size: 11px;
			font-weight: 700;
			padding: 3px 8px;
			border-radius: 6px;
			cursor: pointer;
			transition: all 0.15s ease;
		}
		.acrb-seo-token-btn:hover {
			background: #e2e8f0;
			color: #0f172a;
		}
		.acrb-seo-hint {
			margin: 6px 0 0;
			font-size: 12px;
			color: #64748b;
			line-height: 1.4;
		}
		.acrb-seo-media-row {
			display: flex;
			gap: 8px;
		}
		.acrb-seo-btn-media {
			background: #f8fafc;
			border: 1px solid #cbd5e1;
			color: #334155;
			font-weight: 700;
			font-size: 13px;
			border-radius: 8px;
			padding: 0 16px;
			cursor: pointer;
			white-space: nowrap;
			display: flex;
			align-items: center;
			gap: 6px;
			transition: all 0.15s ease;
			height: 42px;
		}
		.acrb-seo-btn-media:hover {
			background: #f1f5f9;
			color: #0f172a;
			border-color: #94a3b8;
		}

		/* Toggles */
		.acrb-seo-toggle-group {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 14px 16px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			margin-bottom: 12px;
		}
		.acrb-seo-toggle-group:last-child {
			margin-bottom: 0;
		}
		.acrb-seo-toggle-info {
			display: flex;
			flex-direction: column;
			gap: 2px;
		}
		.acrb-seo-toggle-title {
			font-size: 13.5px;
			font-weight: 700;
			color: #0f172a;
		}
		.acrb-seo-toggle-desc {
			font-size: 12px;
			color: #64748b;
		}
		.acrb-seo-switch {
			position: relative;
			display: inline-block;
			width: 44px;
			height: 24px;
			flex-shrink: 0;
		}
		.acrb-seo-switch input {
			opacity: 0;
			width: 0;
			height: 0;
		}
		.acrb-seo-slider {
			position: absolute;
			cursor: pointer;
			inset: 0;
			background-color: #cbd5e1;
			transition: .25s ease;
			border-radius: 24px;
		}
		.acrb-seo-slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: white;
			transition: .25s ease;
			border-radius: 50%;
			box-shadow: 0 1px 3px rgba(0,0,0,0.15);
		}
		input:checked + .acrb-seo-slider {
			background-color: #4f46e5;
		}
		input:checked + .acrb-seo-slider:before {
			transform: translateX(20px);
		}

		/* Submit Button */
		.acrb-seo-submit-btn {
			background: #4f46e5;
			color: #ffffff;
			border: none;
			padding: 12px 28px;
			font-size: 13.5px;
			font-weight: 700;
			border-radius: 8px;
			cursor: pointer;
			box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
			transition: background 0.15s ease, transform 0.15s ease;
			display: inline-flex;
			align-items: center;
			gap: 8px;
		}
		.acrb-seo-submit-btn:hover {
			background: #4338ca;
			transform: translateY(-1px);
		}

		/* Sidebar Preview Modules */
		.acrb-seo-preview-sidebar {
			position: sticky;
			top: 40px;
		}
		.acrb-seo-device-bar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 12px;
		}
		.acrb-seo-device-title {
			font-size: 12px;
			font-weight: 800;
			color: #475569;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}
		.acrb-seo-device-toggles {
			display: flex;
			gap: 3px;
			background: #f1f5f9;
			padding: 3px;
			border-radius: 8px;
		}
		.acrb-seo-device-btn {
			background: transparent;
			border: none;
			padding: 4px 10px;
			font-size: 11px;
			font-weight: 700;
			color: #64748b;
			border-radius: 6px;
			cursor: pointer;
			transition: all 0.15s ease;
		}
		.acrb-seo-device-btn.active {
			background: #ffffff;
			color: #0f172a;
			box-shadow: 0 1px 3px rgba(0,0,0,0.08);
		}

		/* Google SERP Card Preview */
		.acrb-google-box {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px;
			transition: max-width 0.2s ease;
		}
		.acrb-google-box.mobile {
			max-width: 340px;
			margin: 0 auto;
		}
		.acrb-google-url-line {
			font-size: 12px;
			color: #202124;
			display: flex;
			align-items: center;
			gap: 6px;
			margin-bottom: 4px;
		}
		.acrb-google-favicon-demo {
			width: 16px;
			height: 16px;
			background: #4f46e5;
			border-radius: 50%;
			display: inline-block;
			flex-shrink: 0;
		}
		.acrb-google-title-text {
			font-size: 17px;
			font-weight: 400;
			color: #1a0dab;
			line-height: 1.3;
			margin-bottom: 4px;
			word-break: break-word;
			cursor: pointer;
		}
		.acrb-google-title-text:hover {
			text-decoration: underline;
		}
		.acrb-google-desc-text {
			font-size: 13px;
			color: #4d5156;
			line-height: 1.45;
			margin: 0;
			word-break: break-word;
		}

		/* Social Share Card Preview */
		.acrb-social-preview-box {
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			overflow: hidden;
			background: #ffffff;
		}
		.acrb-social-preview-image {
			width: 100%;
			height: 180px;
			background-color: #f1f5f9;
			background-size: cover;
			background-position: center;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #94a3b8;
			font-size: 12.5px;
			font-weight: 600;
		}
		.acrb-social-preview-body {
			padding: 14px;
			background: #f8fafc;
			border-top: 1px solid #e2e8f0;
		}
		.acrb-social-preview-domain {
			font-size: 11px;
			color: #64748b;
			text-transform: uppercase;
			font-weight: 700;
			margin-bottom: 4px;
		}
		.acrb-social-preview-title {
			font-size: 13.5px;
			font-weight: 700;
			color: #0f172a;
			margin-bottom: 4px;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.acrb-social-preview-desc {
			font-size: 12px;
			color: #64748b;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			overflow: hidden;
			line-height: 1.4;
		}

		@media (max-width: 1024px) {
			.acrb-seo-grid {
				grid-template-columns: 1fr;
			}
			.acrb-seo-preview-sidebar {
				position: static;
			}
		}
	</style>

	<div class="acrb-seo-wrap">

		<!-- Topbar Heading Banner -->
		<header class="acrb-seo-topbar-header">
			<div class="acrb-seo-topbar-title-group">
				<div class="acrb-seo-topbar-icon">
					<span class="dashicons dashicons-search"></span>
				</div>
				<div class="acrb-seo-topbar-meta">
					<h1><?php esc_html_e( 'SEO & Social Optimization', 'awesome-car-rental' ); ?></h1>
					<p><?php esc_html_e( 'Configure metadata, social card distributions, Google SERP rendering, and automated Vehicle JSON-LD Schema.', 'awesome-car-rental' ); ?></p>
				</div>
			</div>
			<div class="acrb-seo-score-meter">
				<div class="acrb-seo-score-circle" id="acrb_score_display">100</div>
				<div class="acrb-seo-score-text">
					<strong id="acrb_score_status"><?php esc_html_e( 'Optimized', 'awesome-car-rental' ); ?></strong>
					<span><?php esc_html_e( 'SEO Health Grade', 'awesome-car-rental' ); ?></span>
				</div>
			</div>
		</header>

		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) :
		?>
			<div class="acrb-seo-alert-success">
				<span class="dashicons dashicons-yes-alt"></span>
				<span><?php esc_html_e( 'SEO and social parameters have been updated and synchronized.', 'awesome-car-rental' ); ?></span>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'acrb_save_seo_settings', 'acrb_seo_nonce' ); ?>

			<div class="acrb-seo-grid">

				<div class="acrb-seo-main-col">

					<!-- Snippet Content Card -->
					<div class="acrb-seo-card">
						<h3 class="acrb-seo-card-title">
							<span class="dashicons dashicons-admin-site-alt3"></span>
							<?php esc_html_e( 'Search Snippet Content', 'awesome-car-rental' ); ?>
						</h3>

						<div class="acrb-seo-form-group">
							<div class="acrb-seo-label-row">
								<label class="acrb-seo-label" for="acrb_meta_title_input"><?php esc_html_e( 'Global Page Title Template', 'awesome-car-rental' ); ?></label>
								<span class="acrb-seo-counter" id="acrb_title_counter">0 / 60</span>
							</div>
							<input 
								type="text" 
								id="acrb_meta_title_input" 
								name="acrb_seo_meta_title" 
								class="acrb-seo-input" 
								value="<?php echo esc_attr( $meta_title ); ?>" 
							/>
							<div class="acrb-seo-tokens-wrap">
								<button type="button" class="acrb-seo-token-btn" data-target="acrb_meta_title_input" data-token="{site_name}">+ {site_name}</button>
								<button type="button" class="acrb-seo-token-btn" data-target="acrb_meta_title_input" data-token="{year}">+ {year}</button>
							</div>
							<p class="acrb-seo-hint"><?php esc_html_e( 'Recommended length: 50–60 characters. Target brand and core rental keyword.', 'awesome-car-rental' ); ?></p>
						</div>

						<div class="acrb-seo-form-group">
							<div class="acrb-seo-label-row">
								<label class="acrb-seo-label" for="acrb_meta_desc_input"><?php esc_html_e( 'Global Meta Description Template', 'awesome-car-rental' ); ?></label>
								<span class="acrb-seo-counter" id="acrb_desc_counter">0 / 160</span>
							</div>
							<textarea 
								id="acrb_meta_desc_input" 
								name="acrb_seo_meta_desc" 
								class="acrb-seo-textarea" 
								rows="3"
							><?php echo esc_textarea( $meta_desc ); ?></textarea>
							<div class="acrb-seo-tokens-wrap">
								<button type="button" class="acrb-seo-token-btn" data-target="acrb_meta_desc_input" data-token="{site_name}">+ {site_name}</button>
								<button type="button" class="acrb-seo-token-btn" data-target="acrb_meta_desc_input" data-token="{year}">+ {year}</button>
							</div>
							<p class="acrb-seo-hint"><?php esc_html_e( 'Recommended length: 140–160 characters. Summarize the rental inventory and highlight availability.', 'awesome-car-rental' ); ?></p>
						</div>

						<div class="acrb-seo-form-group">
							<label class="acrb-seo-label" for="acrb_seo_robots_index"><?php esc_html_e( 'Indexing Visibility', 'awesome-car-rental' ); ?></label>
							<div class="acrb-seo-select-wrap">
								<select id="acrb_seo_robots_index" name="acrb_seo_robots_index" class="acrb-seo-select">
									<option value="index" <?php selected( $robots_index, 'index' ); ?>><?php esc_html_e( 'Index, Follow (Recommended - Allow search engines to index)', 'awesome-car-rental' ); ?></option>
									<option value="noindex" <?php selected( $robots_index, 'noindex' ); ?>><?php esc_html_e( 'Noindex, Nofollow (Prevent search engines from indexing)', 'awesome-car-rental' ); ?></option>
								</select>
								<span class="dashicons dashicons-arrow-down-alt2 acrb-seo-select-arrow"></span>
							</div>
						</div>
					</div>

					<!-- Social Card Sharing Card -->
					<div class="acrb-seo-card">
						<h3 class="acrb-seo-card-title">
							<span class="dashicons dashicons-share"></span>
							<?php esc_html_e( 'Social Card Sharing (Open Graph / X)', 'awesome-car-rental' ); ?>
						</h3>

						<div class="acrb-seo-form-group">
							<div class="acrb-seo-label-row">
								<label class="acrb-seo-label" for="acrb_og_image_input"><?php esc_html_e( 'Default Share Banner (1200 x 630px)', 'awesome-car-rental' ); ?></label>
							</div>
							<div class="acrb-seo-media-row">
								<input 
									type="text" 
									id="acrb_og_image_input" 
									name="acrb_seo_og_image" 
									class="acrb-seo-input" 
									value="<?php echo esc_url( $og_image ); ?>" 
									placeholder="<?php esc_attr_e( 'https://yourdomain.com/wp-content/uploads/og-banner.jpg', 'awesome-car-rental' ); ?>" 
								/>
								<button type="button" class="acrb-seo-btn-media" id="acrb_media_uploader_btn">
									<span class="dashicons dashicons-upload"></span>
									<span><?php esc_html_e( 'Media Library', 'awesome-car-rental' ); ?></span>
								</button>
							</div>
							<p class="acrb-seo-hint"><?php esc_html_e( 'Shown when rental links are posted to WhatsApp, Facebook, iMessage, and LinkedIn.', 'awesome-car-rental' ); ?></p>
						</div>

						<div class="acrb-seo-form-group">
							<label class="acrb-seo-label" for="acrb_seo_twitter_card_type"><?php esc_html_e( 'Twitter / X Card Format', 'awesome-car-rental' ); ?></label>
							<div class="acrb-seo-select-wrap">
								<select id="acrb_seo_twitter_card_type" name="acrb_seo_twitter_card_type" class="acrb-seo-select">
									<option value="summary_large_image" <?php selected( $twitter_card_type, 'summary_large_image' ); ?>><?php esc_html_e( 'Summary with Large Image (Optimal for vehicle showcases)', 'awesome-car-rental' ); ?></option>
									<option value="summary" <?php selected( $twitter_card_type, 'summary' ); ?>><?php esc_html_e( 'Compact Thumbnail Summary', 'awesome-car-rental' ); ?></option>
								</select>
								<span class="dashicons dashicons-arrow-down-alt2 acrb-seo-select-arrow"></span>
							</div>
						</div>
					</div>

					<!-- Structured Data Card -->
					<div class="acrb-seo-card">
						<h3 class="acrb-seo-card-title">
							<span class="dashicons dashicons-code-standards"></span>
							<?php esc_html_e( 'Structured Data Engine', 'awesome-car-rental' ); ?>
						</h3>

						<div class="acrb-seo-toggle-group">
							<div class="acrb-seo-toggle-info">
								<span class="acrb-seo-toggle-title"><?php esc_html_e( 'Auto JSON-LD Schema (AutoRental)', 'awesome-car-rental' ); ?></span>
								<span class="acrb-seo-toggle-desc"><?php esc_html_e( 'Injects Schema.org compliant structured data for Google Rich Results.', 'awesome-car-rental' ); ?></span>
							</div>
							<label class="acrb-seo-switch">
								<input type="checkbox" name="acrb_seo_enable_schema" value="yes" <?php checked( $enable_schema, 'yes' ); ?>>
								<span class="acrb-seo-slider"></span>
							</label>
						</div>

						<div class="acrb-seo-toggle-group">
							<div class="acrb-seo-toggle-info">
								<span class="acrb-seo-toggle-title"><?php esc_html_e( 'Canonical Tag Injection', 'awesome-car-rental' ); ?></span>
								<span class="acrb-seo-toggle-desc"><?php esc_html_e( 'Prevents duplicate content penalties caused by date and filter query strings.', 'awesome-car-rental' ); ?></span>
							</div>
							<label class="acrb-seo-switch">
								<input type="checkbox" name="acrb_seo_enable_canonical" value="yes" <?php checked( $enable_canonical, 'yes' ); ?>>
								<span class="acrb-seo-slider"></span>
							</label>
						</div>
					</div>

					<div>
						<button type="submit" class="acrb-seo-submit-btn">
							<span class="dashicons dashicons-saved"></span>
							<span><?php esc_html_e( 'Save SEO Configuration', 'awesome-car-rental' ); ?></span>
						</button>
					</div>

				</div>

				<!-- Preview Sidebar -->
				<div class="acrb-seo-preview-sidebar">

					<div class="acrb-seo-card">
						<div class="acrb-seo-device-bar">
							<span class="acrb-seo-device-title"><?php esc_html_e( 'Search Engine Snippet', 'awesome-car-rental' ); ?></span>
							<div class="acrb-seo-device-toggles">
								<button type="button" class="acrb-seo-device-btn active" id="acrb_btn_desktop">Desktop</button>
								<button type="button" class="acrb-seo-device-btn" id="acrb_btn_mobile">Mobile</button>
							</div>
						</div>

						<div class="acrb-google-box" id="acrb_google_box">
							<div class="acrb-google-url-line">
								<span class="acrb-google-favicon-demo"></span>
								<span><?php echo esc_html( home_url() ); ?> › fleet</span>
							</div>
							<div class="acrb-google-title-text" id="acrb_serp_title_preview"><?php echo esc_html( $meta_title ); ?></div>
							<p class="acrb-google-desc-text" id="acrb_serp_desc_preview"><?php echo esc_html( $meta_desc ); ?></p>
						</div>
					</div>

					<div class="acrb-seo-card">
						<h3 class="acrb-seo-card-title">
							<span class="dashicons dashicons-desktop"></span>
							<?php esc_html_e( 'Social Card Share Preview', 'awesome-car-rental' ); ?>
						</h3>
						<div class="acrb-social-preview-box">
							<div class="acrb-social-preview-image" id="acrb_social_image_preview">
								<span id="acrb_social_placeholder"><?php esc_html_e( 'No Image Selected (1200x630)', 'awesome-car-rental' ); ?></span>
							</div>
							<div class="acrb-social-preview-body">
								<div class="acrb-social-preview-domain"><?php echo esc_html( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
								<div class="acrb-social-preview-title" id="acrb_social_title_preview"><?php echo esc_html( $meta_title ); ?></div>
								<div class="acrb-social-preview-desc" id="acrb_social_desc_preview"><?php echo esc_html( $meta_desc ); ?></div>
							</div>
						</div>
					</div>

				</div>

			</div>
		</form>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const titleInput = document.getElementById('acrb_meta_title_input');
			const descInput  = document.getElementById('acrb_meta_desc_input');
			const imgInput   = document.getElementById('acrb_og_image_input');

			const serpTitlePreview   = document.getElementById('acrb_serp_title_preview');
			const serpDescPreview    = document.getElementById('acrb_serp_desc_preview');
			const socialTitlePreview = document.getElementById('acrb_social_title_preview');
			const socialDescPreview  = document.getElementById('acrb_social_desc_preview');
			const socialImgPreview   = document.getElementById('acrb_social_image_preview');
			const socialPlaceholder  = document.getElementById('acrb_social_placeholder');

			const titleCounter = document.getElementById('acrb_title_counter');
			const descCounter  = document.getElementById('acrb_desc_counter');

			const scoreDisplay = document.getElementById('acrb_score_display');
			const scoreStatus  = document.getElementById('acrb_score_status');

			const siteName = <?php echo wp_json_encode( get_bloginfo( 'name' ) ); ?>;
			const curYear  = <?php echo wp_json_encode( gmdate( 'Y' ) ); ?>;

			function parseTokens(str) {
				return str.replace(/{site_name}/g, siteName).replace(/{year}/g, curYear);
			}

			function updateAudits() {
				let score = 100;
				const rawTitle = titleInput ? titleInput.value.trim() : '';
				const rawDesc  = descInput ? descInput.value.trim() : '';

				const tLen = rawTitle.length;
				if (titleCounter) {
					titleCounter.textContent = tLen + ' / 60';
					if (tLen < 30 || tLen > 65) {
						titleCounter.className = 'acrb-seo-counter limit-bad';
						score -= 25;
					} else if (tLen < 45 || tLen > 60) {
						titleCounter.className = 'acrb-seo-counter limit-warn';
						score -= 10;
					} else {
						titleCounter.className = 'acrb-seo-counter limit-good';
					}
				}

				const dLen = rawDesc.length;
				if (descCounter) {
					descCounter.textContent = dLen + ' / 160';
					if (dLen < 70 || dLen > 175) {
						descCounter.className = 'acrb-seo-counter limit-bad';
						score -= 25;
					} else if (dLen < 120 || dLen > 160) {
						descCounter.className = 'acrb-seo-counter limit-warn';
						score -= 10;
					} else {
						descCounter.className = 'acrb-seo-counter limit-good';
					}
				}

				if (imgInput && !imgInput.value.trim()) {
					score -= 20;
				}

				score = Math.max(0, score);
				if (scoreDisplay) {
					scoreDisplay.textContent = score;
					if (score >= 85) {
						scoreDisplay.style.background = '#059669';
						if (scoreStatus) scoreStatus.textContent = <?php echo wp_json_encode( __( 'Optimized', 'awesome-car-rental' ) ); ?>;
					} else if (score >= 60) {
						scoreDisplay.style.background = '#d97706';
						if (scoreStatus) scoreStatus.textContent = <?php echo wp_json_encode( __( 'Acceptable', 'awesome-car-rental' ) ); ?>;
					} else {
						scoreDisplay.style.background = '#dc2626';
						if (scoreStatus) scoreStatus.textContent = <?php echo wp_json_encode( __( 'Action Needed', 'awesome-car-rental' ) ); ?>;
					}
				}

				const parsedTitle = parseTokens(rawTitle || siteName + ' Car Rental');
				const parsedDesc  = parseTokens(rawDesc || 'Browse our complete rental fleet.');

				if (serpTitlePreview) serpTitlePreview.textContent = parsedTitle;
				if (socialTitlePreview) socialTitlePreview.textContent = parsedTitle;

				if (serpDescPreview) serpDescPreview.textContent = parsedDesc;
				if (socialDescPreview) socialDescPreview.textContent = parsedDesc;

				const imgUrl = imgInput ? imgInput.value.trim() : '';
				if (socialImgPreview && socialPlaceholder) {
					if (imgUrl) {
						socialImgPreview.style.backgroundImage = 'url(' + encodeURI(imgUrl) + ')';
						socialPlaceholder.style.display = 'none';
					} else {
						socialImgPreview.style.backgroundImage = 'none';
						socialPlaceholder.style.display = 'inline';
					}
				}
			}

			if (titleInput) titleInput.addEventListener('input', updateAudits);
			if (descInput) descInput.addEventListener('input', updateAudits);
			if (imgInput) imgInput.addEventListener('input', updateAudits);
			updateAudits();

			// Dynamic Token Injector Buttons
			document.querySelectorAll('.acrb-seo-token-btn').forEach(function(btn) {
				btn.addEventListener('click', function() {
					const targetId = this.getAttribute('data-target');
					const targetInput = targetId ? document.getElementById(targetId) : null;
					const token = this.getAttribute('data-token');
					if (targetInput && token) {
						targetInput.value += ' ' + token;
						updateAudits();
					}
				});
			});

			// Device View Switcher
			const googleBox  = document.getElementById('acrb_google_box');
			const btnDesktop = document.getElementById('acrb_btn_desktop');
			const btnMobile  = document.getElementById('acrb_btn_mobile');

			if (btnDesktop && btnMobile && googleBox) {
				btnDesktop.addEventListener('click', function() {
					this.classList.add('active');
					btnMobile.classList.remove('active');
					googleBox.classList.remove('mobile');
				});

				btnMobile.addEventListener('click', function() {
					this.classList.add('active');
					btnDesktop.classList.remove('active');
					googleBox.classList.add('mobile');
				});
			}

			// Native Media Uploader Integration
			const mediaBtn = document.getElementById('acrb_media_uploader_btn');
			if (mediaBtn && imgInput) {
				mediaBtn.addEventListener('click', function(e) {
					e.preventDefault();
					const frame = wp.media({
						title: <?php echo wp_json_encode( __( 'Select Social Share Image', 'awesome-car-rental' ) ); ?>,
						button: { text: <?php echo wp_json_encode( __( 'Use Image', 'awesome-car-rental' ) ); ?> },
						multiple: false
					}).on('select', function() {
						const attachment = frame.state().get('selection').first().toJSON();
						imgInput.value = attachment.url;
						updateAudits();
					}).open();
				});
			}
		});
	</script>
	<?php
}