<?php
/**
 * Fleet Booking Directory Logic
 * Location: admin/bookings/acrb-bookings-list.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Handle Quick Status Updates
if ( isset( $_GET['acrb_action'] ) && 'set_status' === $_GET['acrb_action'] && isset( $_GET['bid'] ) ) {
    if ( check_admin_referer( 'acrb_status_nonce' ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            $booking_id = intval( $_GET['bid'] );
            $new_status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
            update_post_meta( $booking_id, 'status', $new_status );

            // Clear dashboard transient cache
            delete_transient( 'acrb_dashboard_revenue' );

            echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0 0 0;"><p>';
            /* translators: 1: Booking ID, 2: New status name */
            $success_msg1 = sprintf( esc_html__( 'Booking #%1$d updated to %2$s.', 'awesome-car-rental' ), absint( $booking_id ), esc_html( ucfirst( $new_status ) ) );
            echo wp_kses_post( $success_msg1 );
            echo '</p></div>';
        }
    }
}

// 2. Handle Delete Feature
if ( isset( $_GET['acrb_action'] ) && 'delete_booking' === $_GET['acrb_action'] && isset( $_GET['bid'] ) ) {
    if ( check_admin_referer( 'acrb_delete_nonce' ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            $booking_id   = intval( $_GET['bid'] );
            $trash_result = wp_trash_post( $booking_id );

            if ( $trash_result ) {
                // Clear dashboard transient cache
                delete_transient( 'acrb_dashboard_revenue' );

                echo '<div class="notice notice-warning is-dismissible" style="margin: 20px 0 0 0;"><p>';
                /* translators: %d: The ID number of the booking being trashed */
                $success_msg2 = sprintf( esc_html__( 'Booking #%d moved to trash successfully.', 'awesome-car-rental' ), absint( $booking_id ) );
                echo wp_kses_post( $success_msg2 );
                echo '</p></div>';
            }
        }
    }
}

// 3. Fetch Data (with optional status filtering from the SaaS topbar)
$filter_status = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : '';

$query_args = array(
    'post_type'   => 'acrb_bookings',
    'numberposts' => -1,
    'post_status' => 'any',
    'orderby'     => 'date',
    'order'       => 'DESC',
);

if ( 'pending' === $filter_status ) {
    $query_args['meta_query'] = array(
        'relation' => 'OR',
        array( array( 'key' => 'status', 'value' => 'pending', 'compare' => '=' ) ),
        array( array( 'key' => 'status', 'compare' => 'NOT EXISTS' ) )
    );
}

$bookings_list = get_posts( $query_args );

$currency     = get_option( 'acrb_currency', '£' );
$base_tab_url = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings' );
?>

<style>
    /* Modern SaaS Layout Theme */
    .acrb-dash-wrap { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; }
    
    /* Table Wrap Container */
    .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
    .acrb-table-wrap table.widefat { border: none; margin: 0; box-shadow: none; }
    .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
    .acrb-table-wrap table.widefat tbody td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
    .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

    .acrb-id-badge { background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; }
    .acrb-client-name { font-size: 14px; font-weight: 600; color: #0f172a; }
    .acrb-client-email { font-size: 12px; color: #64748b; }
    
    .acrb-rental-meta { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #475569; }
    .acrb-car-title { font-weight: 600; color: #0f172a; font-size: 13px; }
    .acrb-ref-tag { font-size: 11px; color: #64748b; background: #f8fafc; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0; display: inline-block; margin-top: 4px; }
    .acrb-price-display { font-size: 15px; font-weight: 700; color: #15803d; }

    /* Status Pills */
    .acrb-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-confirmed { background: #dcfce7; color: #15803d; }
    .status-picked_up { background: #e0f2fe; color: #0369a1; }
    .status-returned { background: #f3e8ff; color: #7e22ce; }
    .status-cancelled { background: #fee2e2; color: #b91c1c; }

    /* Action Buttons Group */
    .acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
    .acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; }
    .acrb-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }
    .acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
    .acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
</style>

<div class="acrb-dash-wrap">
    <div class="acrb-table-wrap">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th width="70"><?php esc_html_e( 'ID', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Client Details', 'awesome-car-rental' ); ?></th>
                    <th width="20%"><?php esc_html_e( 'Rental Schedule', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Vehicle Info', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Total Price', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                    <th width="140" style="text-align:right;"><?php esc_html_e( 'Management', 'awesome-car-rental' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $bookings_list ) ) : ?>
                    <?php
                    foreach ( $bookings_list as $post_item ) :
                        $booking_id     = $post_item->ID;
                        $customer_name  = get_post_meta( $booking_id, 'customer_name', true );
                        $customer_email = get_post_meta( $booking_id, 'customer_email', true );
                        $car_id         = get_post_meta( $booking_id, 'car_id', true );
                        $total_val      = get_post_meta( $booking_id, 'total_price', true );
                        $total_numeric  = is_numeric( $total_val ) ? floatval( $total_val ) : 0;
                        $status_val     = get_post_meta( $booking_id, 'status', true ) ?: 'pending';
                        $start_date     = get_post_meta( $booking_id, 'pickup_date', true );
                        $end_date       = get_post_meta( $booking_id, 'return_date', true );
                        $car_title      = $car_id ? get_the_title( $car_id ) : __( 'Deleted Vehicle', 'awesome-car-rental' );

                        $delete_url = wp_nonce_url( $base_tab_url . '&acrb_action=delete_booking&bid=' . $booking_id, 'acrb_delete_nonce' );
                        $view_url   = wp_nonce_url( $base_tab_url . '&action=view&booking_id=' . absint( $booking_id ), 'acrb_car_action' );
                        $edit_url   = wp_nonce_url( $base_tab_url . '&action=edit&booking_id=' . absint( $booking_id ), 'acrb_car_action' );
                        ?>
                        <tr>
                            <td><span class="acrb-id-badge">#<?php echo absint( $booking_id ); ?></span></td>
                            <td>
                                <strong class="acrb-client-name"><?php echo esc_html( $customer_name ?: __( 'Guest User', 'awesome-car-rental' ) ); ?></strong><br>
                                <span class="acrb-client-email"><?php echo esc_html( $customer_email ); ?></span>
                            </td>
                            <td>
                                <div class="acrb-rental-meta">
                                    <span>📅 <strong>In:</strong> <?php echo esc_html( $start_date ?: '—' ); ?></span>
                                    <span>🏁 <strong>Out:</strong> <?php echo esc_html( $end_date ?: '—' ); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="acrb-car-title"><?php echo esc_html( $car_title ); ?></span><br>
                                <span class="acrb-ref-tag">
                                    <?php
                                    /* translators: %s: date-based reference string */
                                    printf( esc_html__( 'Ref: %s', 'awesome-car-rental' ), esc_html( get_the_date( 'Ymd-Hi', $booking_id ) ) );
                                    ?>
                                </span>
                            </td>
                            <td>
                                <strong class="acrb-price-display">
                                    <?php echo esc_html( $currency ) . esc_html( number_format( $total_numeric, 2 ) ); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="acrb-status status-<?php echo esc_attr( $status_val ); ?>">
                                    <?php echo esc_html( ucfirst( $status_val ) ); ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div class="acrb-actions-group">
                                    <a class="acrb-btn-action" href="<?php echo esc_url( $view_url ); ?>" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    
                                    <a class="acrb-btn-action" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit Booking', 'awesome-car-rental' ); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>

                                    <a class="acrb-btn-action acrb-btn-delete" 
                                       href="<?php echo esc_url( $delete_url ); ?>" 
                                       onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to move this booking to trash?', 'awesome-car-rental' ); ?>');"
                                       title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>"
                                       style="color: #b91c1c;">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7" style="text-align:center; padding: 40px; color:#64748b;"><?php esc_html_e( 'No bookings found.', 'awesome-car-rental' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>