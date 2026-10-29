<?php
/**
 * Fleet Booking Directory Logic
 * Location: admin/bookings/acrb-bookings-list.php
 *
 * @package Awesome_Car_Rental
 * @since   1.6.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Handle Quick Status Updates
if ( isset( $_GET['acrb_action'] ) && 'set_status' === $_GET['acrb_action'] && isset( $_GET['bid'] ) ) {
    if ( check_admin_referer( 'acrb_status_nonce' ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            $booking_id = absint( $_GET['bid'] );
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
            $booking_id = absint( $_GET['bid'] );

            $delete_result = wp_delete_post( $booking_id, true );

            if ( $delete_result ) {
                delete_transient( 'acrb_dashboard_revenue' );

                echo '<div class="notice notice-warning is-dismissible" style="margin: 20px 0 0 0;"><p>';
                $success_msg2 = sprintf( esc_html__( 'Booking #%d deleted successfully.', 'awesome-car-rental' ), absint( $booking_id ) );
                echo wp_kses_post( $success_msg2 );
                echo '</p></div>';
            }
        }
    }
}

// 3. Fetch Bookings from Custom Post Type (`acrb_bookings`)
$filter_status = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : '';

$args = array(
    'post_type'   => 'acrb_bookings',
    'numberposts' => -1,
    'post_status' => 'publish',
    'orderby'     => 'date',
    'order'       => 'DESC',
);

if ( ! empty( $filter_status ) ) {
    $args['meta_query'] = array(
        array(
            'key'   => 'status',
            'value' => $filter_status,
        ),
    );
}

$bookings_list = get_posts( $args );

$currency     = get_option( 'acrb_currency', '£' );
$base_tab_url = admin_url( 'admin.php?page=awesome_car_rental&tab=bookings' );
?>

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
                    <?php foreach ( $bookings_list as $row ) :
                        $booking_id    = absint( $row->ID );
                        $customer_name = esc_html( get_post_meta( $booking_id, 'customer_name', true ) ?: __( 'Guest User', 'awesome-car-rental' ) );
                        $customer_email= esc_html( get_post_meta( $booking_id, 'customer_email', true ) ?: '—' );
                        
                        $car_id        = absint( get_post_meta( $booking_id, 'car_id', true ) );
                        $car_name      = $car_id ? esc_html( get_the_title( $car_id ) ) : __( 'Fleet Vehicle', 'awesome-car-rental' );
                        
                        $total_numeric = floatval( get_post_meta( $booking_id, 'total_price', true ) ?: 0 );
                        $status_val    = sanitize_key( get_post_meta( $booking_id, 'status', true ) ?: 'pending' );

                        $delete_url = wp_nonce_url( $base_tab_url . '&acrb_action=delete_booking&bid=' . $booking_id, 'acrb_delete_nonce' );
                        $view_url   = wp_nonce_url( $base_tab_url . '&action=view&booking_id=' . $booking_id, 'acrb_car_action' );
                        $edit_url   = wp_nonce_url( $base_tab_url . '&action=edit&booking_id=' . $booking_id, 'acrb_car_action' );
                        ?>
                        <tr>
                            <td data-order="<?php echo absint( $booking_id ); ?>">
                                <span class="acrb-id-badge">#<?php echo absint( $booking_id ); ?></span>
                            </td>
                            <td>
                                <strong class="acrb-client-name"><?php echo esc_html( $customer_name ); ?></strong><br>
                                <span class="acrb-client-email"><?php echo esc_html( $customer_email ); ?></span>
                            </td>
                            <td>
                                <span class="acrb-car-title"><?php echo esc_html( $car_name ); ?></span><br>
                                <span class="acrb-ref-tag">
                                    <?php printf( esc_html__( 'Ref: #%d', 'awesome-car-rental' ), absint( $booking_id ) ); ?>
                                </span>
                            </td>
                            <td data-order="<?php echo esc_attr( (string) $total_numeric ); ?>">
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
                                       onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this booking?', 'awesome-car-rental' ); ?>');"
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