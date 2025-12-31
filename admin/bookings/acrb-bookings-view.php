<?php
if (!defined('ABSPATH')) exit;

/**
 * ACRB HIGH-END SAAS BOOKING VIEW - (Security Nonce Removed)
 */

// 1. DATA RETRIEVAL
$acrb_booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;

// Validate ID and Post Type
if (!$acrb_booking_id || get_post_type($acrb_booking_id) !== 'acrb_bookings') {
    wp_die(esc_html__('Invalid booking record.', 'awesome-car-rental'));
}

// 2. FETCH METADATA
$acrb_customer_name  = get_post_meta($acrb_booking_id, 'customer_name', true);
$acrb_customer_email = get_post_meta($acrb_booking_id, 'customer_email', true);
$acrb_customer_phone = get_post_meta($acrb_booking_id, 'customer_phone', true);
$acrb_pickup_loc     = get_post_meta($acrb_booking_id, 'pickup_location', true);
$acrb_dropoff_loc    = get_post_meta($acrb_booking_id, 'dropoff_location', true);
$acrb_pickup_date    = get_post_meta($acrb_booking_id, 'pickup_date', true);
$acrb_return_date    = get_post_meta($acrb_booking_id, 'return_date', true);
$acrb_notes          = get_post_meta($acrb_booking_id, 'notes', true);
$acrb_status         = strtolower(get_post_meta($acrb_booking_id, 'status', true) ?: 'pending');
$acrb_total          = get_post_meta($acrb_booking_id, 'total_price', true);
$acrb_payment_method = get_post_meta($acrb_booking_id, 'payment_method', true);
$acrb_car_id         = get_post_meta($acrb_booking_id, 'car_id', true);
$acrb_car_title      = $acrb_car_id ? get_the_title($acrb_car_id) : __('Vehicle Not Found', 'awesome-car-rental');
$acrb_currency       = get_option('acrb_currency', '£');

// 3. PAYMENT METHOD MAPPING
$acrb_pay_labels = [
    'pay_later' => __('Book Now, Pay Later', 'awesome-car-rental'),
    'bank'      => __('Direct Bank Transfer', 'awesome-car-rental'),
    'cash'      => __('Cash on Pickup', 'awesome-car-rental')
];
$acrb_pay_display = isset($acrb_pay_labels[$acrb_payment_method]) ? $acrb_pay_labels[$acrb_payment_method] : ucfirst(str_replace('_', ' ', $acrb_payment_method));

?>

<div class="acrb-saas-container">
    
    <div class="acrb-top-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=awesome_car_rental&tab=bookings')); ?>" class="btn-saas btn-secondary">
            <span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e('Back to Bookings', 'awesome-car-rental'); ?>
        </a>
        <div class="acrb-nav-actions">
            <button onclick="window.print()" class="btn-saas btn-secondary">
                <span class="dashicons dashicons-printer"></span> <?php esc_html_e('Print Voucher', 'awesome-car-rental'); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=awesome_car_rental&tab=bookings&action=edit&booking_id=' . $acrb_booking_id)); ?>" class="btn-saas btn-primary">
                <?php esc_html_e('Edit Reservation', 'awesome-car-rental'); ?>
            </a>
        </div>
    </div>

    <?php if ( $acrb_booking_id ) : ?>
        <div class="acrb-status-notice">
            <?php 
            // translators: 1: Booking ID, 2: New status name.
            $acrb_msg = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $acrb_booking_id ), esc_html( $acrb_status ) );
            echo wp_kses_post( $acrb_msg ); 
            ?>
        </div>
    <?php endif; ?>

    <div class="acrb-booking-hero">
        <div class="acrb-hero-left">
            <div class="acrb-title-stack">
                <h1 class="acrb-customer-title"><?php echo esc_html($acrb_customer_name); ?></h1>
                <span class="acrb-id-pill">ID #<?php echo absint($acrb_booking_id); ?></span>
            </div>
            <p class="acrb-subtitle">
                <?php esc_html_e('Reserved:', 'awesome-car-rental'); ?> <strong><?php echo esc_html($acrb_car_title); ?></strong>
            </p>
        </div>
        <div class="acrb-hero-right">
            <div class="acrb-total-label"><?php esc_html_e('Grand Total', 'awesome-car-rental'); ?></div>
            <div class="acrb-total-amount">
                <?php echo esc_html($acrb_currency) . number_format(floatval($acrb_total), 2); ?>
            </div>
            <span class="acrb-badge badge-<?php echo esc_attr($acrb_status); ?>"><?php echo esc_html($acrb_status); ?></span>
        </div>
    </div>

    <div class="acrb-grid">
        <div class="acrb-left-col">
            
            <div class="acrb-card">
                <div class="acrb-card-header">
                    <h3><span class="dashicons dashicons-location"></span> <?php esc_html_e('Rental Route & Schedule', 'awesome-car-rental'); ?></h3>
                </div>
                <div class="acrb-timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                        <div class="timeline-content">
                            <div class="timeline-label"><?php esc_html_e('Pick-up Details', 'awesome-car-rental'); ?></div>
                            <div class="timeline-date"><?php echo esc_html($acrb_pickup_date ?: '—'); ?></div>
                            <div class="timeline-loc pickup-text">
                                <span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html($acrb_pickup_loc ?: __('Main Hub', 'awesome-car-rental')); ?>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon timeline-icon-return"><span class="dashicons dashicons-calendar"></span></div>
                        <div class="timeline-content">
                            <div class="timeline-label"><?php esc_html_e('Return Details', 'awesome-car-rental'); ?></div>
                            <div class="timeline-date"><?php echo esc_html($acrb_return_date ?: '—'); ?></div>
                            <div class="timeline-loc return-text">
                                <span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html($acrb_dropoff_loc ?: __('Main Hub', 'awesome-car-rental')); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="acrb-card">
                <div class="acrb-card-header">
                    <h3><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Customer & Payment', 'awesome-car-rental'); ?></h3>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Full Name', 'awesome-car-rental'); ?></span>
                    <span class="detail-value"><?php echo esc_html($acrb_customer_name); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Email Address', 'awesome-car-rental'); ?></span>
                    <span class="detail-value"><?php echo esc_html($acrb_customer_email); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Phone Number', 'awesome-car-rental'); ?></span>
                    <span class="detail-value"><?php echo esc_html($acrb_customer_phone ?: 'N/A'); ?></span>
                </div>
                <div class="detail-row payment-highlight">
                    <span class="detail-label"><?php esc_html_e('Payment Method', 'awesome-car-rental'); ?></span>
                    <span class="detail-value">
                        <span class="acrb-pay-badge"><?php echo esc_html($acrb_pay_display); ?></span>
                    </span>
                </div>
            </div>

            <div class="acrb-card">
                <div class="acrb-card-header">
                    <h3><span class="dashicons dashicons-testimonial"></span> <?php esc_html_e('Customer Notes', 'awesome-car-rental'); ?></h3>
                </div>
                <div class="acrb-notes-body">
                    <?php echo $acrb_notes ? nl2br(esc_html($acrb_notes)) : '<em>' . esc_html__('No additional notes provided.', 'awesome-car-rental') . '</em>'; ?>
                </div>
            </div>
        </div>

        <div class="acrb-right-col">
            <div class="acrb-card">
                <div class="acrb-card-header">
                    <h3><span class="dashicons dashicons-car"></span> <?php esc_html_e('Assigned Vehicle', 'awesome-car-rental'); ?></h3>
                </div>
                <div class="acrb-vehicle-info">
                    <?php if (has_post_thumbnail($acrb_car_id)): ?>
                        <img src="<?php echo esc_url(get_the_post_thumbnail_url($acrb_car_id, 'medium')); ?>" class="acrb-vehicle-img">
                    <?php else: ?>
                        <div class="acrb-vehicle-placeholder">
                             <span class="dashicons dashicons-car"></span>
                        </div>
                    <?php endif; ?>
                    
                    <h4 class="acrb-vehicle-name"><?php echo esc_html($acrb_car_title); ?></h4>
                    <p class="acrb-vehicle-ref"><?php esc_html_e('Vehicle Reference:', 'awesome-car-rental'); ?> #<?php echo absint($acrb_car_id); ?></p>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php esc_html_e('Insurance Status', 'awesome-car-rental'); ?></span>
                    <span class="detail-value text-success"><?php esc_html_e('Standard Cover', 'awesome-car-rental'); ?></span>
                </div>
            </div>

            <div class="acrb-card">
                <div class="acrb-card-header">
                    <h3><span class="dashicons dashicons-share-alt2"></span> <?php esc_html_e('Communication', 'awesome-car-rental'); ?></h3>
                </div>
                <div class="acrb-crm-actions">
                    <a href="mailto:<?php echo esc_attr($acrb_customer_email); ?>?subject=Booking Confirmation #<?php echo absint($acrb_booking_id); ?>" class="btn-saas btn-secondary center-btn">
                        <span class="dashicons dashicons-email"></span> <?php esc_html_e('Email Customer', 'awesome-car-rental'); ?>
                    </a>
                    <?php if($acrb_customer_phone): ?>
                    <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $acrb_customer_phone)); ?>" target="_blank" class="btn-saas btn-secondary center-btn whatsapp-link">
                        <span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e('Message on WhatsApp', 'awesome-car-rental'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>