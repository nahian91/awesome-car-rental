<?php
/**
 * ACRB HIGH-END SAAS BOOKING VIEW (Custom DB Architecture)
 * Location: admin/bookings/acrb-bookings-view.php
 *
 * @package Awesome_Car_Rental
 * @since   1.6.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';

// 1. DATA RETRIEVAL
$acrb_booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;

// Fetch record directly from custom database table
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking = $wpdb->get_row(
	$wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $acrb_booking_id ),
	ARRAY_A
);

// Validate ID and Record existence
if ( $acrb_booking_id <= 0 || ! $booking ) {
	wp_die( esc_html__( 'Invalid booking record or record not found.', 'awesome-car-rental' ) );
}

// 2. FETCH COLUMNS FROM CUSTOM ROW
$acrb_customer_name  = $booking['customer_name'] ?? '';
$acrb_customer_email = $booking['customer_email'] ?? '';
$acrb_customer_phone = $booking['customer_phone'] ?? '';
$acrb_pickup_loc     = 'Main Hub';
$acrb_dropoff_loc    = 'Main Hub';
$acrb_pickup_date    = $booking['pickup_date'] ?? '';
$acrb_return_date    = $booking['return_date'] ?? '';
$acrb_notes          = '';
$acrb_status         = strtolower( $booking['booking_status'] ?? 'pending' );
$acrb_total          = $booking['total_amount'] ?? 0;
$acrb_payment_method = 'pay_later';
$acrb_car_id         = absint( $booking['car_id'] ?? 0 );
$acrb_car_title      = $booking['car_name'] ?? __( 'Fleet Vehicle', 'awesome-car-rental' );
$acrb_currency       = get_option( 'acrb_currency', '£' );

// 3. PAYMENT METHOD MAPPING
$acrb_pay_labels = [
	'pay_later' => __( 'Book Now, Pay Later', 'awesome-car-rental' ),
	'bank'      => __( 'Direct Bank Transfer', 'awesome-car-rental' ),
	'cash'      => __( 'Cash on Pickup', 'awesome-car-rental' )
];
$acrb_pay_display = isset( $acrb_pay_labels[ $acrb_payment_method ] ) ? $acrb_pay_labels[ $acrb_payment_method ] : ucfirst( str_replace( '_', ' ', $acrb_payment_method ) );
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
	.acrb-title-wrapper { display: flex; align-items: center; gap: 16px; }
	.acrb-title-icon { width: 48px; height: 48px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: inset 0 1px 2px rgba(59, 130, 246, 0.1); }
	.acrb-title-icon .dashicons { font-size: 24px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
	
	.acrb-main-title { font-size: 22px; font-weight: 700; margin: 0 0 4px 0; color: #0f172a; letter-spacing: -0.5px; }
	.acrb-sub-title { font-size: 13px; margin: 0; color: #64748b; font-weight: 400; }
	
	/* Header Actions & Buttons */
	.acrb-header-actions { display: flex; align-items: center; gap: 12px; }
	.btn-saas { 
		background: #ffffff; 
		color: #334155; 
		border: 1px solid #cbd5e1; 
		border-radius: 8px; 
		padding: 9px 18px; 
		font-weight: 600; 
		text-decoration: none; 
		display: inline-flex; 
		align-items: center; 
		gap: 8px; 
		transition: all 0.25s ease; 
		box-shadow: 0 1px 2px rgba(0,0,0,0.02);
		cursor: pointer;
		font-size: 13px;
		height: 38px;
		box-sizing: border-box;
	}
	.btn-saas .dashicons { font-size: 16px; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; }
	.btn-saas:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
	.btn-primary { background: #3b82f6; color: #fff; border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35); }
	.btn-primary:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

	/* Hero Section */
	.acrb-booking-hero { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.01); margin-bottom: 24px; }
	.acrb-title-stack { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
	.acrb-customer-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
	.acrb-id-pill { background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
	.acrb-subtitle { font-size: 13px; color: #64748b; margin: 0; }
	.acrb-subtitle strong { color: #0f172a; }

	.acrb-hero-right { text-align: right; }
	.acrb-total-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
	.acrb-total-amount { font-size: 26px; font-weight: 700; color: #15803d; margin-bottom: 6px; }

	/* Status Badges */
	.acrb-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
	.badge-pending { background: #fef3c7; color: #d97706; }
	.badge-confirmed { background: #dcfce7; color: #15803d; }
	.badge-picked_up { background: #e0f2fe; color: #0369a1; }
	.badge-returned { background: #f3e8ff; color: #7e22ce; }
	.badge-cancelled { background: #fee2e2; color: #b91c1c; }

	/* Content Grids & Cards */
	.acrb-grid { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
	.acrb-left-col, .acrb-right-col { display: flex; flex-direction: column; gap: 24px; }
	
	.acrb-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
	.acrb-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; }
	.acrb-card-header h3 { margin: 0; font-size: 15px; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 8px; }
	.acrb-card-header h3 .dashicons { color: #3b82f6; font-size: 18px; width: 18px; height: 18px; }

	/* Timeline Route */
	.acrb-timeline { display: flex; flex-direction: column; gap: 16px; }
	.timeline-item { display: flex; gap: 14px; align-items: flex-start; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; }
	.timeline-icon { width: 36px; height: 36px; background: #eff6ff; color: #3b82f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
	.timeline-icon-return { background: #f0fdf4; color: #16a34a; }
	.timeline-content { display: flex; flex-direction: column; gap: 4px; }
	.timeline-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
	.timeline-date { font-size: 14px; font-weight: 700; color: #0f172a; }
	.timeline-loc { font-size: 13px; color: #334155; display: flex; align-items: center; gap: 4px; margin-top: 2px; }
	.timeline-loc .dashicons { font-size: 15px; width: 15px; height: 15px; color: #64748b; }

	/* Detail Rows */
	.detail-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
	.detail-row:last-child { border-bottom: none; padding-bottom: 0; }
	.detail-row:first-child { padding-top: 0; }
	.detail-label { color: #64748b; font-weight: 500; }
	.detail-value { color: #0f172a; font-weight: 600; text-align: right; }
	.payment-highlight { background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 10px; }
	.acrb-pay-badge { background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }

	.acrb-notes-body { font-size: 13px; color: #334155; line-height: 1.6; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; }

	/* Vehicle Info Card */
	.acrb-vehicle-info { text-align: center; margin-bottom: 16px; }
	.acrb-vehicle-placeholder { width: 100%; height: 120px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 36px; margin-bottom: 12px; border: 1px solid #e2e8f0; }
	.acrb-vehicle-name { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
	.acrb-vehicle-ref { font-size: 12px; color: #64748b; margin: 0; }

	.acrb-crm-actions { display: flex; flex-direction: column; gap: 10px; }
	.center-btn { width: 100%; justify-content: center; }
	.whatsapp-link { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
	.whatsapp-link:hover { background: #dcfce7; color: #15803d; border-color: #86efac; }
	.text-success { color: #16a34a; }

	@media(max-width: 900px) {
		.acrb-header-flex, .acrb-booking-hero { flex-direction: column; align-items: flex-start; gap: 20px; }
		.acrb-hero-right { text-align: left; }
		.acrb-grid { grid-template-columns: 1fr; }
	}
</style>

<div class="acrb-dash-wrap">
	
	<div class="acrb-header-flex">
		<div class="acrb-title-wrapper">
			<div class="acrb-title-icon">
				<span class="dashicons dashicons-visibility"></span>
			</div>
			<div>
				<h1 class="acrb-main-title"><?php esc_html_e( 'Reservation Overview', 'awesome-car-rental' ); ?></h1>
				<p class="acrb-sub-title"><?php esc_html_e( 'Detailed breakdown of customer itinerary, payment, and assigned vehicle.', 'awesome-car-rental' ); ?></p>
			</div>
		</div>

		<div class="acrb-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=bookings' ) ); ?>" class="btn-saas">
				<span class="dashicons dashicons-arrow-left-alt2"></span> 
				<span><?php esc_html_e( 'Back to Bookings', 'awesome-car-rental' ); ?></span>
			</a>
			<button onclick="window.print()" class="btn-saas">
				<span class="dashicons dashicons-printer"></span> 
				<span><?php esc_html_e( 'Print Voucher', 'awesome-car-rental' ); ?></span>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_car_rental&tab=bookings&action=edit&booking_id=' . $acrb_booking_id ) ); ?>" class="btn-saas btn-primary">
				<span><?php esc_html_e( 'Edit Reservation', 'awesome-car-rental' ); ?></span>
			</a>
		</div>
	</div>

	<div class="acrb-booking-hero">
		<div>
			<div class="acrb-title-stack">
				<h2 class="acrb-customer-title"><?php echo esc_html( $acrb_customer_name ); ?></h2>
				<span class="acrb-id-pill">ID #<?php echo absint( $acrb_booking_id ); ?></span>
			</div>
			<p class="acrb-subtitle">
				<?php esc_html_e( 'Reserved:', 'awesome-car-rental' ); ?> <strong><?php echo esc_html( $acrb_car_title ); ?></strong>
			</p>
		</div>
		<div class="acrb-hero-right">
			<div class="acrb-total-label"><?php esc_html_e( 'Grand Total', 'awesome-car-rental' ); ?></div>
			<div class="acrb-total-amount">
				<?php echo esc_html( $acrb_currency ) . number_format( floatval( $acrb_total ), 2 ); ?>
			</div>
			<span class="acrb-badge badge-<?php echo esc_attr( $acrb_status ); ?>"><?php echo esc_html( ucfirst( $acrb_status ) ); ?></span>
		</div>
	</div>

	<div class="acrb-grid">
		<div class="acrb-left-col">
			
			<div class="acrb-card">
				<div class="acrb-card-header">
					<h3><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Rental Route & Schedule', 'awesome-car-rental' ); ?></h3>
				</div>
				<div class="acrb-timeline">
					<div class="timeline-item">
						<div class="timeline-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
						<div class="timeline-content">
							<div class="timeline-label"><?php esc_html_e( 'Pick-up Details', 'awesome-car-rental' ); ?></div>
							<div class="timeline-date"><?php echo esc_html( $acrb_pickup_date ?: '—' ); ?></div>
							<div class="timeline-loc pickup-text">
								<span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html( $acrb_pickup_loc ?: __( 'Main Hub', 'awesome-car-rental' ) ); ?>
							</div>
						</div>
					</div>
					<div class="timeline-item">
						<div class="timeline-icon timeline-icon-return"><span class="dashicons dashicons-calendar"></span></div>
						<div class="timeline-content">
							<div class="timeline-label"><?php esc_html_e( 'Return Details', 'awesome-car-rental' ); ?></div>
							<div class="timeline-date"><?php echo esc_html( $acrb_return_date ?: '—' ); ?></div>
							<div class="timeline-loc return-text">
								<span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html( $acrb_dropoff_loc ?: __( 'Main Hub', 'awesome-car-rental' ) ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="acrb-card">
				<div class="acrb-card-header">
					<h3><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Customer & Payment', 'awesome-car-rental' ); ?></h3>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Full Name', 'awesome-car-rental' ); ?></span>
					<span class="detail-value"><?php echo esc_html( $acrb_customer_name ); ?></span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Email Address', 'awesome-car-rental' ); ?></span>
					<span class="detail-value"><?php echo esc_html( $acrb_customer_email ); ?></span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Phone Number', 'awesome-car-rental' ); ?></span>
					<span class="detail-value"><?php echo esc_html( $acrb_customer_phone ?: 'N/A' ); ?></span>
				</div>
				<div class="detail-row payment-highlight">
					<span class="detail-label"><?php esc_html_e( 'Payment Method', 'awesome-car-rental' ); ?></span>
					<span class="detail-value">
						<span class="acrb-pay-badge"><?php echo esc_html( $acrb_pay_display ); ?></span>
					</span>
				</div>
			</div>

			<div class="acrb-card">
				<div class="acrb-card-header">
					<h3><span class="dashicons dashicons-testimonial"></span> <?php esc_html_e( 'Customer Notes', 'awesome-car-rental' ); ?></h3>
				</div>
				<div class="acrb-notes-body">
					<?php echo $acrb_notes ? nl2br( esc_html( $acrb_notes ) ) : '<em>' . esc_html__( 'No additional notes provided.', 'awesome-car-rental' ) . '</em>'; ?>
				</div>
			</div>
		</div>

		<div class="acrb-right-col">
			<div class="acrb-card">
				<div class="acrb-card-header">
					<h3><span class="dashicons dashicons-car"></span> <?php esc_html_e( 'Assigned Vehicle', 'awesome-car-rental' ); ?></h3>
				</div>
				<div class="acrb-vehicle-info">
					<div class="acrb-vehicle-placeholder">
						<span class="dashicons dashicons-car"></span>
					</div>
					<h4 class="acrb-vehicle-name"><?php echo esc_html( $acrb_car_title ); ?></h4>
					<p class="acrb-vehicle-ref"><?php esc_html_e( 'Vehicle Reference:', 'awesome-car-rental' ); ?> #<?php echo absint( $acrb_car_id ); ?></p>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Insurance Status', 'awesome-car-rental' ); ?></span>
					<span class="detail-value text-success"><?php esc_html_e( 'Standard Cover', 'awesome-car-rental' ); ?></span>
				</div>
			</div>

			<div class="acrb-card">
				<div class="acrb-card-header">
					<h3><span class="dashicons dashicons-share-alt2"></span> <?php esc_html_e( 'Communication', 'awesome-car-rental' ); ?></h3>
				</div>
				<div class="acrb-crm-actions">
					<a href="mailto:<?php echo esc_attr( $acrb_customer_email ); ?>?subject=Booking Confirmation #<?php echo absint( $acrb_booking_id ); ?>" class="btn-saas center-btn">
						<span class="dashicons dashicons-email"></span> <span><?php esc_html_e( 'Email Customer', 'awesome-car-rental' ); ?></span>
					</a>
					<?php if ( $acrb_customer_phone ) : ?>
					<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $acrb_customer_phone ) ); ?>" target="_blank" class="btn-saas center-btn whatsapp-link" rel="noopener noreferrer">
						<span class="dashicons dashicons-whatsapp"></span> <span><?php esc_html_e( 'Message on WhatsApp', 'awesome-car-rental' ); ?></span>
					</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>