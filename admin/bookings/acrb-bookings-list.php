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

            delete_transient( 'acrb_dashboard_revenue' );

            echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0 0 0;"><p>';
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
                delete_transient( 'acrb_dashboard_revenue' );

                echo '<div class="notice notice-warning is-dismissible" style="margin: 20px 0 0 0;"><p>';
                $success_msg2 = sprintf( esc_html__( 'Booking #%d moved to trash successfully.', 'awesome-car-rental' ), absint( $booking_id ) );
                echo wp_kses_post( $success_msg2 );
                echo '</p></div>';
            }
        }
    }
}

// 3. Fetch Data
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
    .acrb-dash-wrap { width: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; margin: 0; }
    
    /* Outer Card */
    .acrb-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .acrb-table-wrap .dataTables_wrapper { padding: 16px 20px; }
    .acrb-table-wrap .dataTables_wrapper::before,
    .acrb-table-wrap .dataTables_wrapper::after { content: ''; display: table; clear: both; }

    /* Top Controls */
    .acrb-table-wrap .dataTables_length { float: left; margin-bottom: 16px; font-size: 13px; font-weight: 500; color: #64748b; }
    .acrb-table-wrap .dataTables_length select { border: 1px solid #cbd5e1 !important; border-radius: 8px !important; padding: 5px 10px !important; font-size: 13px !important; color: #0f172a !important; background: #ffffff !important; outline: none !important; }
    
    .acrb-table-wrap .dataTables_filter { float: right; margin-bottom: 16px; }
    .acrb-table-wrap .dataTables_filter input { width: 240px; height: 36px; padding: 0 14px !important; border-radius: 8px !important; border: 1px solid #cbd5e1 !important; background: #ffffff !important; font-size: 13px !important; color: #0f172a !important; outline: none !important; transition: all 0.15s ease-in-out; }
    .acrb-table-wrap .dataTables_filter input:focus { border-color: #4f46e5 !important; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important; }

    /* Table Styling */
    .acrb-table-wrap table.widefat { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin: 0; box-shadow: none; width: 100% !important; }
    .acrb-table-wrap table.widefat thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .acrb-table-wrap table.widefat tbody td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .acrb-table-wrap table.widefat tbody tr:last-child td { border-bottom: none; }
    .acrb-table-wrap table.widefat tbody tr:hover { background: #f8fafc; }

    /* Bottom Controls */
    .acrb-table-wrap .dataTables_info { float: left; padding-top: 14px !important; font-size: 12.5px !important; color: #64748b !important; font-weight: 500; }
    .acrb-table-wrap .dataTables_paginate { float: right; padding-top: 10px !important; }
    .acrb-table-wrap .dataTables_paginate .paginate_button { border-radius: 6px !important; border: 1px solid transparent !important; padding: 4px 10px !important; margin: 0 2px !important; font-size: 12.5px !important; color: #64748b !important; }
    .acrb-table-wrap .dataTables_paginate .paginate_button:hover { background: #f1f5f9 !important; color: #0f172a !important; border-color: #e2e8f0 !important; }
    .acrb-table-wrap .dataTables_paginate .paginate_button.current,
    .acrb-table-wrap .dataTables_paginate .paginate_button.current:hover { background: #4f46e5 !important; color: #ffffff !important; border-color: #4f46e5 !important; }

    /* Column Specifics */
    .acrb-id-badge { background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; display: inline-block; }
    .acrb-client-name { font-size: 14px; font-weight: 600; color: #0f172a; }
    .acrb-client-email { font-size: 12px; color: #64748b; }
    .acrb-car-title { font-weight: 600; color: #0f172a; font-size: 13px; }
    .acrb-ref-tag { font-size: 11px; color: #64748b; background: #f8fafc; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0; display: inline-block; margin-top: 4px; white-space: nowrap; }
    .acrb-price-display { font-size: 15px; font-weight: 700; color: #15803d; white-space: nowrap; }

    /* Status Pills */
    .acrb-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; white-space: nowrap; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-confirmed { background: #dcfce7; color: #15803d; }
    .status-picked_up { background: #e0f2fe; color: #0369a1; }
    .status-returned { background: #f3e8ff; color: #7e22ce; }
    .status-cancelled { background: #fee2e2; color: #b91c1c; }

    /* Actions */
    .acrb-actions-group { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
    .acrb-btn-action { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; }
    .acrb-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; display: inline-flex; align-items: center; justify-content: center; }
    .acrb-btn-action:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
    .acrb-btn-delete:hover { background: #fee2e2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
</style>

<div class="acrb-dash-wrap">
    <div class="acrb-table-wrap">
        <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
            <thead>
                <tr>
                    <th width="70" class="all"><?php esc_html_e( 'ID', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Client Details', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Vehicle Info', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Total Price', 'awesome-car-rental' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'awesome-car-rental' ); ?></th>
                    <th class="all" width="120" style="text-align:right;" data-orderable="false"><?php esc_html_e( 'Action', 'awesome-car-rental' ); ?></th>
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
                        $car_title      = $car_id ? get_the_title( $car_id ) : __( 'Deleted Vehicle', 'awesome-car-rental' );

                        $delete_url = wp_nonce_url( $base_tab_url . '&acrb_action=delete_booking&bid=' . $booking_id, 'acrb_delete_nonce' );
                        $view_url   = wp_nonce_url( $base_tab_url . '&action=view&booking_id=' . absint( $booking_id ), 'acrb_car_action' );
                        $edit_url   = wp_nonce_url( $base_tab_url . '&action=edit&booking_id=' . absint( $booking_id ), 'acrb_car_action' );
                        ?>
                        <tr>
                            <td data-order="<?php echo absint( $booking_id ); ?>">
                                <span class="acrb-id-badge">#<?php echo absint( $booking_id ); ?></span>
                            </td>
                            <td>
                                <strong class="acrb-client-name"><?php echo esc_html( $customer_name ?: __( 'Guest User', 'awesome-car-rental' ) ); ?></strong><br>
                                <span class="acrb-client-email"><?php echo esc_html( $customer_email ?: '—' ); ?></span>
                            </td>
                            <td>
                                <span class="acrb-car-title"><?php echo esc_html( $car_title ); ?></span><br>
                                <span class="acrb-ref-tag">
                                    <?php printf( esc_html__( 'Ref: %s', 'awesome-car-rental' ), esc_html( get_the_date( 'Ymd-Hi', $booking_id ) ) ); ?>
                                </span>
                            </td>
                            <td data-order="<?php echo esc_attr( $total_numeric ); ?>">
                                <strong class="acrb-price-display">
                                    <?php echo esc_html( $currency ) . esc_html( number_format( $total_numeric, 2 ) ); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="acrb-status status-<?php echo esc_attr( $status_val ); ?>">
                                    <?php echo esc_html( ucfirst( str_replace( '_', ' ', $status_val ) ) ); ?>
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
                    <tr><td colspan="6" style="text-align:center; padding: 40px; color:#64748b;"><?php esc_html_e( 'No bookings found.', 'awesome-car-rental' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>