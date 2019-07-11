<?php
/**
 * Function to send customer quote email notification to customer
 *
 * @since  1.0.0
 * @param int $quote_id ID of post/quote
 * @return bool $sent email sent or not
 */
function wpinv_user_quote_notification( $quote_id ) {
    $email_type = 'user_quote';

    if ( ! wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $quote = new WPInv_Invoice( $quote_id );

    if ( empty( $quote ) ) {
        return false;
    }

    if ( !( "wpi_quote" === $quote->post_type ) ) {
        return false;
    }

    $recipient = wpinv_email_get_recipient( $email_type, $quote_id, $quote );
    if ( ! is_email( $recipient ) ) {
        return false;
    }

    $subject = wpinv_email_get_subject($email_type, $quote_id, $quote);
    $email_heading = wpinv_email_get_heading($email_type, $quote_id, $quote);
    $headers = wpinv_email_get_headers($email_type, $quote_id, $quote);
    $attachments = wpinv_email_get_attachments($email_type, $quote_id, $quote);
    $message_body   = wpinv_email_get_content( $email_type, $quote_id, $quote );

    $content = wpinv_get_template_html('emails/wpinv-email-' . $email_type . '.php', array(
        'quote' => $quote,
        'email_type' => $email_type,
        'email_heading' => $email_heading,
        'sent_to_admin' => false,
        'plain_text' => false,
        'message_body'    => $message_body,
    ), 'invoicing-quotes/', WP_PLUGIN_DIR . '/invoicing-quotes/templates/');

    $sent = wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);

    if ($sent) {
        $note = __('Quote has been emailed to the user.', 'wpinv-quotes');
    } else {
        $note = __('Fail to send quote to the user!', 'wpinv-quotes');
    }
    $quote->add_note($note, '', '', true); // Add system note.

    if (wpinv_mail_admin_bcc_active($email_type)) {
        $recipient = wpinv_get_admin_email();
        $subject .= __(' - ADMIN BCC COPY', 'wpinv-quotes');
        wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);
    }

    return $sent;
}