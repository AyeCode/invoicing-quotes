<?php
/**
 * Displays right side of the invoice header.
 *
 * This template can be overridden by copying it to yourtheme/invoicing-quotes/header-right-actions.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( $invoice->is_type( 'quote' ) ) : ?>

    <a class="btn btn-sm btn-secondary invoice-action-print" onclick="window.print();" href="javascript:void(0)">
        <?php _e( 'Print Quote', 'wpinv-quotes' ); ?>
    </a>

    <?php if ( is_user_logged_in() ) : ?>
        &nbsp;&nbsp;
        <a class="btn btn-sm btn-secondary invoice-action-history" href="<?php echo esc_url( Wpinv_Quotes_Shared::wpinv_get_quote_history_page_uri() ); ?>">
            <?php _e( 'Quote History', 'invoicing' ); ?>
        </a>
    <?php endif; ?>

    <?php if ( wpinv_current_user_can_manage_invoicing() ) : ?>
        &nbsp;&nbsp;
        <a class="btn btn-sm btn-secondary invoice-action-edit" href="<?php echo esc_url( get_edit_post_link( $invoice->get_id() ) ); ?>">
            <?php _e( 'Edit Quote', 'invoicing' ); ?>
        </a>
    <?php endif; ?>

<?php endif; ?>

<?php
