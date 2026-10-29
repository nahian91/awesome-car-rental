<?php
/**
 * Income & Expense Financial Ledger Module Dispatcher (`income-expense.php`)
 * Location: admin/income-expense.php
 *
 * @package Awesome_Car_Rental
 * @since   3.0.2
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acrb_income_expense_tab(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
    }

    global $wpdb;
    $table_finance = $wpdb->prefix . 'dev_nahian_income_expense';
    $page_slug     = 'awesome_car_rental';
    $base_url      = admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense' );

    $sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
    $entry_id  = isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : 0;
    $view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
    $edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

    if ( $view_id > 0 ) {
        $sub_state = 'view';
        $entry_id  = $view_id;
    } elseif ( $edit_id > 0 ) {
        $sub_state = 'edit';
        $entry_id  = $edit_id;
    }

    // 1. Handle Form Submissions (Create / Update)
    if ( isset( $_POST['acrb_save_ledger'] ) && check_admin_referer( 'acrb_ledger_action', 'acrb_ledger_nonce' ) ) {
        $id          = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $entry_type  = sanitize_key( wp_unslash( $_POST['entry_type'] ?? 'expense' ) );
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
                $redirect_url = add_query_arg( array( 'updated' => '1' ), $base_url );
            } else {
                $data['created_at'] = current_time( 'mysql' );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->insert( $table_finance, $data, array_merge( $formats, array( '%s' ) ) );
                $redirect_url = add_query_arg( array( 'created' => '1' ), $base_url );
            }

            delete_transient( 'acrb_dashboard_revenue' );

            if ( headers_sent() ) {
                echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $redirect_url ) ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $redirect_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
    ?>
    <style>
        .acrb-ledger-wrapper {
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            box-sizing: border-box;
        }
        .acrb-ledger-wrapper *,
        .acrb-ledger-wrapper *::before,
        .acrb-ledger-wrapper *::after {
            box-sizing: border-box;
        }

        /* Topbar Header Banner */
        .acrb-ledger-topbar-header {
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
        .acrb-ledger-topbar-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .acrb-ledger-topbar-icon {
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
        .acrb-ledger-topbar-icon .dashicons {
            font-size: 22px;
            width: 22px;
            height: 22px;
        }
        .acrb-ledger-topbar-meta h1 {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 3px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .acrb-ledger-topbar-meta p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        /* Feedback Notices */
        .acrb-ledger-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .acrb-ledger-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>

    <div class="acrb-ledger-wrapper">
        <!-- Topbar Banner -->
        <header class="acrb-ledger-topbar-header">
            <div class="acrb-ledger-topbar-title-group">
                <div class="acrb-ledger-topbar-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="acrb-ledger-topbar-meta">
                    <h1><?php esc_html_e( 'Income & Expense Ledger', 'awesome-car-rental' ); ?></h1>
                    <p><?php esc_html_e( 'Track business cash flow, operational expenditures, and calculate net profitability.', 'awesome-car-rental' ); ?></p>
                </div>
            </div>
        </header>

        <!-- Feedback Notifications -->
        <?php if ( isset( $_GET['created'] ) && '1' === $_GET['created'] ) : ?>
            <div class="acrb-ledger-alert-success">
                ✓ <?php esc_html_e( 'New ledger entry recorded successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
            <div class="acrb-ledger-alert-success">
                ✓ <?php esc_html_e( 'Ledger transaction updated successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php elseif ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) : ?>
            <div class="acrb-ledger-alert-success">
                ✓ <?php esc_html_e( 'Ledger entry deleted successfully.', 'awesome-car-rental' ); ?>
            </div>
        <?php endif; ?>

        <?php
        // Sub-Navigation Tab Strip
        if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-tab.php' ) ) {
            require_once ACRB_PATH . 'admin/income-expense/income-expense-tab.php';
        }

        // Submodule Dispatcher
        switch ( $sub_state ) {
            case 'add':
            case 'edit':
                if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-add-edit.php' ) ) {
                    require_once ACRB_PATH . 'admin/income-expense/income-expense-add-edit.php';
                } else {
                    echo '<div class="acrb-ledger-alert-error">' . esc_html__( 'Ledger add/edit view template missing.', 'awesome-car-rental' ) . '</div>';
                }
                break;

            case 'view':
                if ( file_exists( ACRB_PATH . 'admin/income-expense/income-expense-view.php' ) ) {
                    require_once ACRB_PATH . 'admin/income-expense/income-expense-view.php';
                } else {
                    echo '<div class="acrb-ledger-alert-error">' . esc_html__( 'Ledger view template missing.', 'awesome-car-rental' ) . '</div>';
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
                } else {
                    echo '<div class="acrb-ledger-alert-error">' . esc_html__( 'Ledger list view template missing.', 'awesome-car-rental' ) . '</div>';
                }
                break;
        }
        ?>
    </div>
    <?php
}