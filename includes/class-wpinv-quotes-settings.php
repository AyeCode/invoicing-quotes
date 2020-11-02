<?php
/**
 * Contains the main settings class.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing
 * @subpackage Quotes
 */

/**
 * The main settings class.
 *
 * @package    Invoicing
 * @subpackage Quotes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class WPInv_Quotes_Settings {

	/**
	 * Class constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		add_filter( 'wpinv_settings_tabs', array( $this, 'register_tab' ) );
		add_filter( 'wpinv_settings_sections', array( $this, 'register_sections' ) );
		add_filter( 'wpinv_registered_settings', array( $this, 'register_settings' ) );
		add_filter( 'wpinv_get_emails', array( $this, 'register_email_settings' ) );

	}


	/**
	 * Registers the quotes tab.
	 *
	 * @since    1.0.0
	 * @param array $tabs Current setting tabs.
	 * @return array $tabs Updated setting tabs.
	 */
	public function register_tab( $tabs ) {

		return array_merge(
			$tabs,
			array(
				'quote' => __( 'Quotes', 'wpinv-quotes' ),
			)
		);

	}

	/**
	 * Registers the quotes sections.
	 *
	 * @since    1.0.0
	 * @param array $sections Current setting sections.
	 * @return array $sections Updated setting sections.
	 */
	public function register_sections( $sections ) {

		return array_merge(
			$sections,
			array(
				'quote'    => array(
					'main' => __( 'Quote Settings', 'wpinv-quotes' ),
				),
			)
		);

	}

	/**
	 * Registers the quotes settings.
	 *
	 * @since    1.0.0
	 * @param array $settings Current settings.
	 * @return array $settings Updated settings.
	 */
	public function register_settings( $settings ) {

		return array_merge(
			$settings,
			array(
				'quote' => array(

					'main' => array(

						'quote_number_prefix'          => array(
							'id'          => 'quote_number_prefix',
							'name'        => __( 'Quote Number Prefix', 'wpinv-quotes' ),
							'desc'        => __( 'A prefix to prepend to all quote numbers. Ex: QUOTE-', 'wpinv-quotes' ),
							'type'        => 'text',
							'size'        => 'regular',
							'std'         => 'QUOTE-',
							'placeholder' => 'QUOTE-',
						),

						'quote_number_postfix'         => array(
							'id'          => 'quote_number_postfix',
							'name'        => __( 'Quote Number Postfix', 'wpinv-quotes' ),
							'desc'        => __( 'A postfix to append to all quote numbers.', 'wpinv-quotes' ),
							'type'        => 'text',
							'size'        => 'regular',
							'std'         => ''
						),

						'quote_history_page'           => array(
							'id'          => 'quote_history_page',
							'name'        => __( 'Quotes History Page', 'wpinv-quotes' ),
							'desc'        => __( 'The <b>[wpinv_quotes]</b> short code should be on this page.', 'wpinv-quotes' ),
							'type'        => 'select',
							'options'     => wpinv_get_pages( true ),
							'chosen'      => true,
							'placeholder' => __( 'Select a page', 'wpinv-quotes' ),
						),

						'accepted_quote_action'        => array(
							'name'        => __( 'Accepted Quote Action', 'wpinv-quotes' ),
							'desc'        => __( 'What should happen after a client accepts a quote.', 'wpinv-quotes' ),
							'id'          => 'accepted_quote_action',
							'type'        => 'select',
							'options'     => array(
								'convert'        => __( 'Convert quote to invoice', 'wpinv-quotes' ),
								'duplicate'      => __( 'Create invoice, but keep quote', 'wpinv-quotes' ),
								'do_nothing'     => __( 'Do nothing', 'wpinv-quotes'),
							),
							'std'         => 'convert',
						),

						'accepted_quote_message'       => array(
							'name'        => __( 'Accepted Quote Message', 'wpinv-quotes' ),
							'desc'        => __( 'Message to display if a client accepts their quote.', 'wpinv-quotes' ),
							'id'          => 'accepted_quote_message',
							'type'        => 'text',
							'size'        => 'regular',
							'std'         => __( 'You have accepted this quote.', 'wpinv-quotes' ),
						),

						'declined_quote_message' => array(
							'name' => __( 'Declined Quote Message', 'wpinv-quotes' ),
							'desc' => __( 'Message to display if a client declines their quote.', 'wpinv-quotes' ),
							'id' => 'declined_quote_message',
							'type' => 'text',
							'size' => 'regular',
							'std' => __( 'You have declined this quote.', 'wpinv-quotes' ),
						),

					),

				)
			)
		);

	}

	/**
	 * Registers the quotes email settings.
	 *
	 * @since    1.0.0
	 * @param array $settings Current email settings.
	 */
	public function register_email_settings( $settings ) {

		return array_merge(
			$settings,
			array(

				'user_quote' => array(

					'email_user_quote_header' => array(
						'id'       => 'email_user_quote_header',
						'name'     => '<h3>' . __( 'Customer Quote', 'wpinv-quotes' ) . '</h3>',
						'desc'     => __( 'These emails are sent to the customer with information about their quote.', 'wpinv-quotes' ),
						'type'     => 'header',
					),

					'email_user_quote_active' => array(
						'id'       => 'email_user_quote_active',
						'name'     => __( 'Enable/Disable', 'wpinv-quotes' ),
						'desc'     => __( 'Enable this email notification', 'wpinv-quotes' ),
						'type'     => 'checkbox',
						'std'      => 1
					),

					'email_user_quote_admin_bcc' => array(
						'id'       => 'email_user_quote_admin_bcc',
						'name'     => __( 'Enable Admin BCC', 'wpinv-quotes' ),
						'desc'     => __( 'Check if you want to send a copy of this notification email to to the site admin.', 'wpinv-quotes' ),
						'type'     => 'checkbox',
						'std'      => 0
					),

					'email_user_quote_subject' => array(
						'id'       => 'email_user_quote_subject',
						'name'     => __( 'Subject', 'wpinv-quotes' ),
						'desc'     => __( 'Enter the subject line for this email.', 'wpinv-quotes' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( '[{site_title}] Your quote from {quote_date}', 'wpinv-quotes' ),
						'size'     => 'large'
					),

					'email_user_quote_heading' => array(
						'id'       => 'email_user_quote_heading',
						'name'     => __( 'Email Heading', 'wpinv-quotes' ),
						'desc'     => __( 'Enter the main heading contained within the email notification.', 'wpinv-quotes' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( 'Your quote details', 'wpinv-quotes' ),
						'size'     => 'large'
					),

					'email_user_quote_body' => array(
						'id'       => 'email_user_quote_body',
						'name'     => __( 'Email Content', 'wpinv-quotes' ),
						'desc'     => $this->get_merge_tags_help_text(),
						'type'     => 'rich_editor',
						'std'      => __( '<p>Hi {name},</p><p>We have provided you with our quote on {site_title}. </p><p>Click on the following link to view it online where you will be able to accept or decline the quote. <a class="btn btn-success" href="{quote_link}">View & Accept / Decline Quote</a></p>', 'wpinv-quotes' ),
						'class'    => 'large',
						'size'     => '10'
					),
				),

				'user_quote_accepted' => array(

					'email_user_quote_accepted_header' => array(
						'id'       => 'email_user_quote_accepted_header',
						'name'     => '<h3>' . __( 'Quote Accepted', 'wpinv-quotes' ) . '</h3>',
						'desc'     => __( 'These emails are sent to the admin whenever a customer accepts their quote.', 'wpinv-quotes' ),
						'type'     => 'header',
					),

					'email_user_quote_accepted_active' => array(
						'id'       => 'email_user_quote_accepted_active',
						'name'     => __( 'Enable/Disable', 'wpinv-quotes' ),
						'desc'     => __( 'Enable this email notification', 'wpinv-quotes' ),
						'type'     => 'checkbox',
						'std'      => 1
					),

					'email_user_quote_accepted_subject' => array(
						'id'       => 'email_user_quote_accepted_subject',
						'name'     => __( 'Subject', 'wpinv-quotes' ),
						'desc'     => __( 'Enter the subject line for this email.', 'wpinv-quotes' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( '[{site_title}] Congratulations. Quote {quote_number} has been accepted by the client.', 'wpinv-quotes' ),
						'size'     => 'large'
					),

					'email_user_quote_accepted_heading' => array(
						'id'       => 'email_user_quote_accepted_heading',
						'name'     => __( 'Email Heading', 'wpinv-quotes' ),
						'desc'     => __( 'Enter the main heading contained within the email notification.', 'wpinv-quotes' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( 'Your quote has been accepted', 'wpinv-quotes' ),
						'size'     => 'large'
					),

					'email_user_quote_accepted_body' => array(
						'id'       => 'email_user_quote_accepted_body',
						'name'     => __( 'Email Content', 'wpinv-quotes' ),
						'desc'     => $this->get_merge_tags_help_text(),
						'type'     => 'rich_editor',
						'std'      => __( '<p>Here are the quote details.</p>', 'wpinv-quotes' ),
						'class'    => 'large',
						'size'     => '10'
					),
				),

				'user_quote_declined' => array(

					'email_user_quote_declined_header' => array(
						'id'       => 'email_user_quote_declined_header',
						'name'     => '<h3>' . __( 'Quote Declined', 'wpinv-quotes' ) . '</h3>',
						'desc'     => __( 'These emails are sent to the admin whenever a customer declines their quote.', 'wpinv-quotes' ),
						'type'     => 'header',
					),

					'email_user_quote_declined_active' => array(
						'id'       => 'email_user_quote_declined_active',
						'name'     => __( 'Enable/Disable', 'wpinv-quotes' ),
						'desc'     => __( 'Enable this email notification', 'wpinv-quotes' ),
						'type'     => 'checkbox',
						'std'      => 1
					),

					'email_user_quote_declined_subject' => array(
						'id'       => 'email_user_quote_declined_subject',
						'name'     => __( 'Subject', 'wpinv-quotes' ),
						'desc'     => __( 'Enter the subject line for this email.', 'wpinv-quotes' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( '[{site_title}] Quote {quote_number} has been declined by the client.', 'wpinv-quotes' ),
						'size'     => 'large'
					),

					'email_user_quote_declined_heading' => array(
						'id'       => 'email_user_quote_declined_heading',
						'name'     => __( 'Email Heading', 'wpinv-quotes' ),
						'desc'     => __( 'Enter the main heading contained within the email notification.', 'wpinv-quotes' ),
						'help-tip' => true,
						'type'     => 'text',
						'std'      => __( 'Your quote has been declined', 'wpinv-quotes' ),
						'size'     => 'large'
					),

					'email_user_quote_declined_body' => array(
						'id'       => 'email_user_quote_declined_body',
						'name'     => __( 'Email Content', 'wpinv-quotes' ),
						'desc'     => $this->get_merge_tags_help_text(),
						'type'     => 'rich_editor',
						'std'      => __( '<p>{quote_decline_reason}</p><p>Here are the quote details.</p>', 'wpinv-quotes' ),
						'class'    => 'large',
						'size'     => '10'
					),
				),

			)
		);

	}

	/**
	 * Returns the merge tags help text.
	 *
	 * @since    1.0.0
	 * @return string
	 */
	public function get_merge_tags_help_text() {

		$link        = sprintf(
			'<strong><a href="%s" target="_blank">%s</a></strong>',
			'https://gist.github.com/picocodes/d09377e8ac4232f6bfc5f5025f7ff77b',
			esc_html__( 'View available merge tags.', 'wpinv-quotes' )
		);

		$description = esc_html__( 'The content of the email (Merge Tags and HTML are allowed).', 'wpinv-quotes' );

		return "$description $link";

	}

}
