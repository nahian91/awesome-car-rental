<?php
/**
 * Income & Expense Financial Ledger Module Dispatcher (`income-expense.php`)
 * Location: admin/income-expense.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_income_expense_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ) );
    }

    global $wpdb;
    $table_finance  = $wpdb->prefix . 'dev_nahian_income_expense';
    $table_bookings = $wpdb->prefix . 'dev_nahian_bookings';

    $page_slug = 'awesome_car_rental';
    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $entry_id  = isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;

    if ( $view_id > 0 ) {
        $sub_state = 'view';
        $entry_id  = $view_id;
    }

    $redirect_script = '';

    // 1. HANDLE FORM SUBMISSION & SAFE JS REDIRECT (No Header Sent Errors)
    if ( isset( $_POST['acrb_save_ledger'] ) && check_admin_referer( 'acrb_ledger_action', 'acrb_ledger_nonce' ) ) {
        $id          = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $entry_type  = sanitize_text_field( wp_unslash( $_POST['entry_type'] ?? 'expense' ) );
        $category    = sanitize_text_field( wp_unslash( $_POST['entry_category'] ?? 'General' ) );
        $amount      = floatval( wp_unslash( $_POST['entry_amount'] ?? 0.00 ) );
        $entry_date  = sanitize_text_field( wp_unslash( $_POST['entry_date'] ?? current_time( 'Y-m-d' ) ) );
        $description = sanitize_textarea_field( wp_unslash( $_POST['entry_title'] ?? '' ) );

        if ( ! empty( $description ) && $amount > 0 ) {
            $data = array(
                'type'             => $entry_type,
                'category'         => $category,
                'amount'           => $amount,
                'transaction_date' => ! empty( $entry_date ) ? $entry_date : current_time( 'Y-m-d' ),
                'description'      => $description,
            );
            $formats = array( '%s', '%s', '%f', '%s', '%s' );

            if ( $id > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update( $table_finance, $data, array( 'id' => $id ), $formats, array( '%d' ) );
                $msg_code = 'updated';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_finance, $data, array_merge( $formats, array( '%s' ) ) );
                $id       = $wpdb->insert_id;
                $msg_code = 'created';
            }

            delete_transient( 'acrb_dashboard_revenue' );

            $target_url      = add_query_arg(
                array(
                    'page'     => $page_slug,
                    'tab'      => 'income_expense',
                    'sub'      => 'edit',
                    'entry_id' => $id,
                    'updated'  => $msg_code,
                ),
                admin_url( 'admin.php' )
            );
            
            // JavaScript Safe Redirect
            $redirect_script = '<script>window.location.href = ' . wp_json_encode( $target_url ) . ';</script>';
        }
    }

    echo $redirect_script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>

    <div class="acrb-dash-wrap">
        <header class="acrb-header-flex">
            <div class="acrb-title-wrapper">
                <div class="acrb-title-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="acrb-title-content">
                    <h1><?php esc_html_e( 'Income & Expense Ledger', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Track business cash flow, operational expenditures, and calculate net profitability.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <?php
        // Include sub-navigation tab strip
        if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-tab.php' ) ) {
            require_once ACRB_PATH . 'admin/income-expense/income-expense-tab.php';
        }

        // Render appropriate submodule view based on state
        switch ( $sub_state ) {
            case 'add':
            case 'edit':
                if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-add-edit.php' ) ) {
                    require_once ACRB_PATH . 'admin/income-expense/income-expense-add-edit.php';
                }
                break;
            case 'view':
                if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-view.php' ) ) {
                    require_once ACRB_PATH . 'admin/income-expense/income-expense-view.php';
                }
                break;
            case 'delete':
                if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-delete.php' ) ) {
                    require_once ACRB_PATH . 'admin/income-expense/income-expense-delete.php';
                }
                break;
            case 'list':
            default:
                if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-list.php' ) ) {
                    require_once ACRB_PATH . 'admin/income-expense/income-expense-list.php';
                }
                break;
        }
        ?>
    </div>
    <?php
}