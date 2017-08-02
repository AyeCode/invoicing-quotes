<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/admin
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpinv-quotes-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        global $pagenow, $post;

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpinv-quotes-admin.js', array('jquery'), $this->version, false);

        $localize = array();
        if (isset($post->ID) && $post->post_type == 'wpi_quote' && ($pagenow == 'post-new.php' || $pagenow == 'post.php')) {
            wp_enqueue_script('jquery-ui-datepicker');
            $localize['convert_quote'] = __('Are you sure you want to convert from Quote to Invoice?', 'invoicing');
            $localize['save_quote'] = __('Save Quote', 'invoicing');
        }
        wp_localize_script($this->plugin_name, 'wpinv_quotes_admin', $localize);

    }

    /**
     * Filter the JavaScript for the admin area of invoicing plugin.
     *
     * @since    1.0.0
     */
    public function wpinv_quote_admin_js_localize($localize)
    {

        global $pagenow, $post;

        if (isset($post->ID) && $post->post_type == 'wpi_quote' && ($pagenow == 'post-new.php' || $pagenow == 'post.php')) {
            $localize['emptyInvoice'] = __('Add at least one item to save quote!', 'invoicing');
            $localize['OneItemMin'] = __('Quote must contain at least one item', 'invoicing');
            $localize['deletePackage'] = __('GD package items should be deleted from GD payment manager only, otherwise it will break quotes created with this package!', 'invoicing');
            $localize['deleteInvoiceFirst'] = __('This item is in use! Before delete this item, you need to delete all the quote(s) using this item.', 'invoicing');
        }

        return $localize;

    }

    /**
     * Creates a new custom post type
     *
     * @since    1.0.0
     */
    public function wpinv_quote_new_cpt()
    {

        $cap_type = 'post';
        $plural = __('Quotes', 'invoicing');
        $single = __('Quote', 'invoicing');
        $menu_icon = WPINV_PLUGIN_URL . '/assets/images/favicon.ico';
        $menu_icon = apply_filters('wpinv_menu_icon_quotes', $menu_icon);

        $opts['can_export'] = TRUE;
        $opts['capability_type'] = $cap_type;
        $opts['description'] = '';
        $opts['exclude_from_search'] = TRUE;
        $opts['has_archive'] = FALSE;
        $opts['hierarchical'] = FALSE;
        $opts['map_meta_cap'] = TRUE;
        $opts['menu_icon'] = $menu_icon;
        $opts['public'] = TRUE;
        $opts['publicly_querable'] = TRUE;
        $opts['query_var'] = TRUE;
        $opts['register_meta_box_cb'] = '';
        $opts['rewrite'] = FALSE;
        $opts['show_in_admin_bar'] = TRUE;
        $opts['show_in_menu'] = "wpinv";
        $opts['show_in_nav_menu'] = TRUE;
        $opts['show_ui'] = TRUE;
        $opts['supports'] = array('title');
        $opts['taxonomies'] = array('');

        $opts['capabilities']['delete_others_posts'] = "delete_others_{$cap_type}s";
        $opts['capabilities']['delete_post'] = "delete_{$cap_type}";
        $opts['capabilities']['delete_posts'] = "delete_{$cap_type}s";
        $opts['capabilities']['delete_private_posts'] = "delete_private_{$cap_type}s";
        $opts['capabilities']['delete_published_posts'] = "delete_published_{$cap_type}s";
        $opts['capabilities']['edit_others_posts'] = "edit_others_{$cap_type}s";
        $opts['capabilities']['edit_post'] = "edit_{$cap_type}";
        $opts['capabilities']['edit_posts'] = "edit_{$cap_type}s";
        $opts['capabilities']['edit_private_posts'] = "edit_private_{$cap_type}s";
        $opts['capabilities']['edit_published_posts'] = "edit_published_{$cap_type}s";
        $opts['capabilities']['publish_posts'] = "publish_{$cap_type}s";
        $opts['capabilities']['read_post'] = "read_{$cap_type}";
        $opts['capabilities']['read_private_posts'] = "read_private_{$cap_type}s";

        $opts['labels']['add_new'] = __("Add New {$single}", 'invoicing');
        $opts['labels']['add_new_item'] = __("Add New {$single}", 'invoicing');
        $opts['labels']['all_items'] = __($plural, 'invoicing');
        $opts['labels']['edit_item'] = __("Edit {$single}", 'invoicing');
        $opts['labels']['menu_name'] = __($plural, 'invoicing');
        $opts['labels']['name'] = __($plural, 'invoicing');
        $opts['labels']['name_admin_bar'] = __($single, 'invoicing');
        $opts['labels']['new_item'] = __("New {$single}", 'invoicing');
        $opts['labels']['not_found'] = __("No {$plural} Found", 'invoicing');
        $opts['labels']['not_found_in_trash'] = __("No {$plural} Found in Trash", 'invoicing');
        $opts['labels']['parent_item_colon'] = __("Parent {$plural} :", 'invoicing');
        $opts['labels']['search_items'] = __("Search {$plural}", 'invoicing');
        $opts['labels']['singular_name'] = __($single, 'invoicing');
        $opts['labels']['view_item'] = __("View {$single}", 'invoicing');

        $opts['rewrite']['slug'] = FALSE;
        $opts['rewrite']['with_front'] = FALSE;
        $opts['rewrite']['feeds'] = FALSE;
        $opts['rewrite']['pages'] = FALSE;

        $opts = apply_filters('wpinv_quote_params', $opts);

        register_post_type('wpi_quote', $opts);

    }

    /**
     * Display columns in admin side quote listing
     *
     * @since    1.0.0
     */
    function wpinv_quote_columns($columns)
    {
        $columns = array(
            'cb' => $columns['cb'],
            'ID' => __('ID', 'invoicing'),
            'details' => __('Details', 'invoicing'),
            'customer' => __('Customer', 'invoicing'),
            'amount' => __('Amount', 'invoicing'),
            'quote_date' => __('Date', 'invoicing'),
            'status' => __('Status', 'invoicing'),
            'wpi_actions' => __('Actions', 'invoicing'),
        );

        return apply_filters('wpi_quote_table_columns', $columns);
    }

    /**
     * Remove bulk edit option from admin side quote listing
     *
     * @since    1.0.0
     */
    function wpinv_quote_bulk_actions($actions)
    {
        if (isset($actions['edit'])) {
            unset($actions['edit']);
        }

        return $actions;
    }

    function wpinv_quote_sortable_columns($columns)
    {
        $columns = array(
            'ID' => array('ID', true),
            'amount' => array('amount', false),
            'quote_date' => array('date', false),
            'customer' => array('customer', false),
            'status' => array('status', false),
        );

        return apply_filters('wpi_quote_table_sortable_columns', $columns);
    }

    /**
     * Display custom columns for admin side quote listing
     *
     * @since    1.0.0
     */
    function wpinv_quote_posts_custom_column($column_name, $post_id = 0)
    {
        global $post, $wpi_invoice;

        if (empty($wpi_invoice) || (!empty($wpi_invoice) && $post->ID != $wpi_invoice->ID)) {
            $wpi_invoice = new WPInv_Invoice($post->ID);
        }

        $value = NULL;

        switch ($column_name) {
            case 'email' :
                $value = $wpi_invoice->get_email();
                break;
            case 'customer' :
                $customer_name = $wpi_invoice->get_user_full_name();
                $customer_name = $customer_name != '' ? $customer_name : __('Customer', 'invoicing');
                $value = '<a href="' . esc_url(get_edit_user_link($wpi_invoice->get_user_id())) . '">' . $customer_name . '</a>';
                if ($email = $wpi_invoice->get_email()) {
                    $value .= '<br><a class="email" href="mailto:' . $email . '">' . $email . '</a>';
                }
                break;
            case 'amount' :
                echo $wpi_invoice->get_total(true);
                break;
            case 'quote_date' :
                $date_format = get_option('date_format');
                $time_format = get_option('time_format');
                $date_time_format = $date_format . ' ' . $time_format;

                $t_time = get_the_time($date_time_format);
                $m_time = $post->post_date;
                $h_time = mysql2date($date_format, $m_time);

                $value = '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
                break;
            case 'status' :
                $value = $wpi_invoice->get_status(true) . ($wpi_invoice->is_recurring() && $wpi_invoice->is_parent() ? ' <span class="wpi-suffix">' . __('(r)', 'invoicing') . '</span>' : '');
                break;
            case 'details' :
                $edit_link = get_edit_post_link($post->ID);
                $value = '<a href="' . esc_url($edit_link) . '">' . __('View Quote Details', 'invoicing') . '</a>';
                break;
            case 'wpi_actions' :
                $value = '';
                if (!empty($post->post_name)) {
                    $value .= '<a title="' . esc_attr__('Print quote', 'invoicing') . '" href="' . esc_url(get_permalink($post->ID)) . '" class="button ui-tip column-act-btn" title="" target="_blank"><span class="dashicons dashicons-print"><i style="" class="fa fa-print"></i></span></a>';
                }

                if ($email = $wpi_invoice->get_email()) {
                    $value .= '<a title="' . esc_attr__('Send quote to customer', 'invoicing') . '" href="' . esc_url(add_query_arg(array('wpi_action' => 'send_quote', 'quote_id' => $post->ID))) . '" class="button ui-tip column-act-btn"><span class="dashicons dashicons-email-alt"></span></a>';
                }

                if ("wpi_quote" === $wpi_invoice->post_type) {
                    $action_url = add_query_arg(array('wpi_action' => 'convert_quote_to_invoice', 'quote_id' => $post->ID));
                    $action_url = esc_url(wp_nonce_url($action_url, 'convert', 'wpinv_convert_quote'));
                    $value .= '<a title="' . esc_attr__('Convert quote to invoice', 'invoicing') . '" href="' . $action_url . '" class="button ui-tip column-act-btn"><span class="dashicons dashicons-controls-repeat"></span></a>';
                }

                break;
            default:
                $value = isset($post->$column_name) ? $post->$column_name : '';
                break;

        }
        $value = apply_filters('wpinv_payments_table_column', $value, $post->ID, $column_name);

        if ($value !== NULL) {
            echo $value;
        }
    }

    /**
     * Remove all post row actions for quote post type
     *
     * @since    1.0.0
     */
    function wpinv_quote_post_row_actions($actions, $post)
    {
        if ('wpi_quote' == $post->post_type && !empty($post->ID)) {
            $actions = array();
        }
        return $actions;
    }

    /**
     * Add metaboxes for quote post type
     *
     * @since    1.0.0
     */
    function wpinv_quoute_add_meta_boxes($post_type, $post)
    {
        global $wpi_mb_invoice;
        if ($post_type == 'wpi_quote' && !empty($post->ID)) {
            $wpi_mb_invoice = wpinv_get_invoice($post->ID);
            add_meta_box('wpinv-details', __('Quote Details', 'invoicing'), 'WPInv_Meta_Box_Details::output', 'wpi_quote', 'side', 'default');
            add_meta_box('wpinv-address', __('Billing Details', 'invoicing'), 'WPInv_Meta_Box_Billing_Details::output', 'wpi_quote', 'normal', 'high');
            add_meta_box('wpinv-items', __('Quote Items', 'invoicing'), 'WPInv_Meta_Box_Items::output', 'wpi_quote', 'normal', 'high');
            add_meta_box('wpinv-notes', __('Quote Notes', 'invoicing'), 'WPInv_Meta_Box_Notes::output', 'wpi_quote', 'normal', 'high');
            if (!empty($wpi_mb_invoice) && $wpi_mb_invoice->has_status(array('wpi-quote-sent', 'wpi-quote-declined'))) {
                add_meta_box('wpinv-mb-resend-invoice', __('Resend Quote', 'invoicing'), 'WPInv_Meta_Box_Details::resend_invoice', 'wpi_quote', 'side', 'high');
            }
            if (!empty($wpi_mb_invoice) && $wpi_mb_invoice->has_status(array('pending', 'wpi-quote-sent'))) {
                add_meta_box('wpinv-mb-convert-quote', __('Convert Quote', 'invoicing'), 'WPInv_Quote_Meta_Box::quote_to_invoice_output', 'wpi_quote', 'side', 'high');
            }
        }
    }

    /**
     * Change resend quote metabox params text values
     *
     * @since    1.0.0
     * @param $text string old text displayed in metabox
     * @return $text string new text to display in metabox
     */
    function wpinv_quote_resend_quote_metabox_text($text)
    {
        global $post;
        if ($post->post_type == 'wpi_quote' && !empty($post->ID)) {
            $text = array(
                'message' => esc_attr__('This will send a copy of the quote to the customer&#8217;s email address.', 'invoicing'),
                'button_text' => __('Resend Quote', 'invoicing'),
            );
        }
        return $text;
    }

    /**
     * Change resend quote button url
     *
     * @since    1.0.0
     */
    function wpinv_quote_resend_quote_email_actions($email_actions)
    {
        global $post;
        if ($post->post_type == 'wpi_quote' && !empty($post->ID)) {
            $email_actions['email_url'] = add_query_arg(array('wpi_action' => 'send_quote', 'quote_id' => $post->ID, 'wpi_action_type' => 'resend_quote'));
        }
        return $email_actions;
    }

    /**
     * Change quote details metabox input labels
     *
     * @since    1.0.0
     */
    function wpinv_quote_detail_metabox_titles($title, $post)
    {
        if ($post->post_type == 'wpi_quote' && !empty($post->ID)) {
            $title['status'] = __('Quote Status:', 'invoicing');
            $title['number'] = __('Quote Number:', 'invoicing');
        }
        return $title;
    }

    /**
     * Change quote details metabox mail notice
     *
     * @since    1.0.0
     */
    function wpinv_quote_metabox_mail_notice($mail_notice, $post)
    {
        if ($post->post_type == 'wpi_quote' && !empty($post->ID)) {
            $mail_notice = __('After saveing quote this will send a copy of the quote to the user&#8217;s email address.', 'invoicing');
        }
        return $mail_notice;

    }

    /**
     * Register new statuses for quote
     *
     * @since    1.0.0
     */
    function wpinv_quote_register_post_status()
    {
        register_post_status('wpi-quote-sent', array(
            'label' => _x('Sent', 'Quote status', 'invoicing'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Sent <span class="count">(%s)</span>', 'Sent <span class="count">(%s)</span>', 'invoicing')
        ));
        register_post_status('wpi-quote-cancelled', array(
            'label' => _x('Cancelled', 'Quote status', 'invoicing'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'invoicing')
        ));
        register_post_status('wpi-quote-declined', array(
            'label' => _x('Declined', 'Quote status', 'invoicing'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Declined <span class="count">(%s)</span>', 'Declined <span class="count">(%s)</span>', 'invoicing')
        ));
    }

    /**
     * Add quote settings tab
     *
     * @since    1.0.0
     */
    function wpinv_quote_settings_tabs($tabs)
    {
        $tabs['quote'] = __('Quote', 'invoicing');

        return $tabs;
    }

    /**
     * Add quote settings tab main
     *
     * @since    1.0.0
     */
    function wpinv_quote_settings_sections($sections)
    {

        $quote_sections = array(
            'quote' => apply_filters('wpinv_settings_sections_quote', array(
                'main' => __('Quote Settings', 'invoicing'),
            )),
        );

        $sections = array_merge($sections, $quote_sections);

        return $sections;
    }

    /**
     * Add quote settings tab
     *
     * @since    1.0.0
     */
    function wpinv_quote_registered_settings($wpinv_settings)
    {
        $quote_number_padd_options = array();
        for ($i = 0; $i <= 20; $i++) {
            $quote_number_padd_options[$i] = $i;
        }
        $quote_settings = array(
            'quote' => apply_filters('wpinv_settings_quote',
                array(
                    'main' => array(
                        'quote_number_format_settings' => array(
                            'id' => 'quote_number_format_settings',
                            'name' => '<h3>' . __('Quote Number', 'invoicing') . '</h3>',
                            'type' => 'header',
                        ),
                        'quote_number_padd' => array(
                            'id' => 'quote_number_padd',
                            'name' => __('Minimum digits', 'invoicing'),
                            'desc' => __('If the quote number has less digits than this number, it is left padded with 0s. Ex: quote number 108 will padded to 00108 if digits set to 5. The default 0 means no padding.', 'invoicing'),
                            'type' => 'select',
                            'options' => $quote_number_padd_options,
                            'std' => 5,
                            'chosen' => true,
                        ),
                        'quote_number_prefix' => array(
                            'id' => 'quote_number_prefix',
                            'name' => __('Quote Number Prefix', 'invoicing'),
                            'desc' => __('A prefix to prepend to all quote numbers. Ex: WPINV-', 'invoicing'),
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => 'WPQUO-',
                            'placeholder' => 'WPQUO-',
                        ),
                        'quote_number_postfix' => array(
                            'id' => 'quote_number_postfix',
                            'name' => __('Quote Number Postfix', 'invoicing'),
                            'desc' => __('A postfix to append to all quote numbers.', 'invoicing'),
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => ''
                        ),
                        'accept_quote_settings' => array(
                            'id' => 'accept_quote_settings',
                            'name' => '<h3>' . __('Accept Quote', 'invoicing') . '</h3>',
                            'type' => 'header',
                        ),
                        'accept_quote' => array(
                            'id' => 'accept_quote',
                            'name' => __('Accept Quote Button', 'invoicing'),
                            'desc' => __('Yes, show the \'Accept Quote\' button on quotes.', 'invoicing'),
                            'type' => 'checkbox',
                            'std' => ''
                        ),
                        'accepted_quote_action' => array(
                            'name' => __('Accepted Quote Action', 'invoicing'),
                            'desc' => __('Actions to perform automatically when client accepts quote.', 'invoicing'),
                            'id' => 'accepted_quote_action',
                            'type' => 'select',
                            'default' => 'convert',
                            'options' => array(
                                'convert' => __('Convert quote to invoice', 'invoicing'),
                                'convert_send' => __('Convert quote to invoice and send to client', 'invoicing'),
                                'duplicate' => __('Create invoice, but keep quote', 'invoicing'),
                                'duplicate_send' => __('Create invoice and send to client, but keep quote', 'invoicing'),
                                'do_nothing' => __('Do nothing', 'invoicing'),
                            ),
                        ),
                        'accepted_quote_message' => array(
                            'name' => __('Accepted Quote Message', 'invoicing'),
                            'desc' => __('Message to display if client accepts the quote.', 'invoicing'),
                            'id' => 'accepted_quote_message',
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => __('Success, You have accepted the Quote.', 'invoicing'),
                        ),
                        'declined_quote_message' => array(
                            'name' => __('Declined Quote Message', 'invoicing'),
                            'desc' => __('Message to display if client declines the quote.', 'invoicing'),
                            'id' => 'declined_quote_message',
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => __('You have declined this quote.', 'invoicing'),
                        ),
                    ),
                )
            ),
        );

        $wpinv_settings = array_merge($wpinv_settings, $quote_settings);

        return $wpinv_settings;
    }

    /**
     * Add customer quote email settings
     *
     * @since    1.0.0
     */
    function wpinv_quote_mail_settings($emails)
    {
        $user_quote = array(
            'user_quote' => array(
                'email_user_quote_header' => array(
                    'id' => 'email_user_quote_header',
                    'name' => '<h3>' . __('Customer Quote', 'invoicing') . '</h3>',
                    'desc' => __('Customer Quote email can be sent to customers containing their quote information.', 'invoicing'),
                    'type' => 'header',
                ),
                'email_user_quote_active' => array(
                    'id' => 'email_user_quote_active',
                    'name' => __('Enable/Disable', 'invoicing'),
                    'desc' => __('Enable this email notification', 'invoicing'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_subject' => array(
                    'id' => 'email_user_quote_subject',
                    'name' => __('Subject', 'invoicing'),
                    'desc' => __('Enter the subject line for the quote receipt email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('[{site_title}] Your quote from {quote_date}', 'invoicing'),
                    'size' => 'large'
                ),
                'email_user_quote_heading' => array(
                    'id' => 'email_user_quote_heading',
                    'name' => __('Email Heading', 'invoicing'),
                    'desc' => __('Enter the main heading contained within the email notification for the quote receipt email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('Your quote {quote_number} details', 'invoicing'),
                    'size' => 'large'
                ),
                'email_user_quote_admin_bcc' => array(
                    'id' => 'email_user_quote_admin_bcc',
                    'name' => __('Enable Admin BCC', 'invoicing'),
                    'desc' => __('Check if you want to send this notification email to site Admin.', 'invoicing'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
            ),
            'user_quote_accepted' => array(
                'email_user_quote_accepted_header' => array(
                    'id' => 'email_user_quote_accepted_header',
                    'name' => '<h3>' . __('Quote Accepted', 'invoicing') . '</h3>',
                    'desc' => __('This email will be sent to admin if user has accepted quote.', 'invoicing'),
                    'type' => 'header',
                ),
                'email_user_quote_accepted_active' => array(
                    'id' => 'email_user_quote_accepted_active',
                    'name' => __('Enable/Disable', 'invoicing'),
                    'desc' => __('Enable this email notification', 'invoicing'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_accepted_subject' => array(
                    'id' => 'email_user_quote_accepted_subject',
                    'name' => __('Subject', 'invoicing'),
                    'desc' => __('Enter the subject line for the quote accepted email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('[{site_title}] User has accepted the quote {quote_number}', 'invoicing'),
                    'size' => 'large'
                ),
                'email_user_quote_accepted_heading' => array(
                    'id' => 'email_user_quote_accepted_heading',
                    'name' => __('Email Heading', 'invoicing'),
                    'desc' => __('Enter the main heading contained within the email notification for the quote accepted email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('Quote {quote_number} Accepted by user', 'invoicing'),
                    'size' => 'large'
                ),
            ),
            'user_quote_declined' => array(
                'email_user_quote_declined_header' => array(
                    'id' => 'email_user_quote_declined_header',
                    'name' => '<h3>' . __('Quote Declined', 'invoicing') . '</h3>',
                    'desc' => __('This email will be sent to admin if user has declined quote.', 'invoicing'),
                    'type' => 'header',
                ),
                'email_user_quote_declined_active' => array(
                    'id' => 'email_user_quote_declined_active',
                    'name' => __('Enable/Disable', 'invoicing'),
                    'desc' => __('Enable this email notification', 'invoicing'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_declined_subject' => array(
                    'id' => 'email_user_quote_declined_subject',
                    'name' => __('Subject', 'invoicing'),
                    'desc' => __('Enter the subject line for the quote declined email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('[{site_title}] User has declined the quote {quote_number}', 'invoicing'),
                    'size' => 'large'
                ),
                'email_user_quote_declined_heading' => array(
                    'id' => 'email_user_quote_declined_heading',
                    'name' => __('Email Heading', 'invoicing'),
                    'desc' => __('Enter the main heading contained within the email notification for the quote declined email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('Quote {quote_number} Declined by user', 'invoicing'),
                    'size' => 'large'
                ),
            ),
            'user_quote_cancelled' => array(
                'email_user_quote_cancelled_header' => array(
                    'id' => 'email_user_quote_cancelled_header',
                    'name' => '<h3>' . __('Quote Cancelled', 'invoicing') . '</h3>',
                    'desc' => __('This email will be sent to user if admin has cancelled quote.', 'invoicing'),
                    'type' => 'header',
                ),
                'email_user_quote_cancelled_active' => array(
                    'id' => 'email_user_quote_cancelled_active',
                    'name' => __('Enable/Disable', 'invoicing'),
                    'desc' => __('Enable this email notification', 'invoicing'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_cancelled_subject' => array(
                    'id' => 'email_user_quote_cancelled_subject',
                    'name' => __('Subject', 'invoicing'),
                    'desc' => __('Enter the subject line for the quote cancelled email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('[{site_title}] Admin has cancelled the quote {quote_number}', 'invoicing'),
                    'size' => 'large'
                ),
                'email_user_quote_cancelled_heading' => array(
                    'id' => 'email_user_quote_cancelled_heading',
                    'name' => __('Email Heading', 'invoicing'),
                    'desc' => __('Enter the main heading contained within the email notification for the quote cancelled email.', 'invoicing'),
                    'type' => 'text',
                    'std' => __('Quote {quote_number} Cancelled by admin', 'invoicing'),
                    'size' => 'large'
                ),
            ),
        );

        $emails = array_merge($emails, $user_quote);

        return $emails;
    }

    /**
     * Send customer quote email notification if Send Quote is selected "yes"
     *
     * @since    1.0.0
     */
    function wpinv_format_quote_number($formatted_number, $number)
    {
        global $post;
        if ('wpi_quote' == $post->post_type || 'wpi_quote' == get_post_type($number)) {
            $padd = wpinv_get_option('quote_number_padd');

            // TODO maintain old invoice numbers if invoice number settings not saved. Should be removed before stable release.
            if ($padd === '' || $padd === false || $padd === NULL) {
                return wp_sprintf(__('WPQUO-%d', 'invoicing'), $number);
            }

            $prefix = wpinv_get_option('quote_number_prefix');
            $postfix = wpinv_get_option('quote_number_postfix');

            $padd = absint($padd);
            $formatted_number = absint($number);

            if ($padd > 0) {
                $formatted_number = zeroise($formatted_number, $padd);
            }

            $formatted_number = $prefix . $formatted_number . $postfix;

            return apply_filters('wpinv_format_quote_number', $formatted_number, $number, $prefix, $postfix, $padd);
        }
        return $formatted_number;
    }

    /**
     * Send customer quote email notification if Send Quote is selected "yes"
     *
     * @since    1.0.0
     */
    function wpinv_send_quote_after_save($post_id)
    {

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!current_user_can('manage_options') || 'wpi_quote' != get_post_type($post_id)) {
            return;
        }

        if (!empty($_POST['wpi_save_send'])) {
            $this->wpinv_user_quote_notification($post_id);
        }
    }

    /**
     * Function to send customer quote email notification to customer
     *
     * @since    1.0.0
     */
    function wpinv_user_quote_notification($quote_id)
    {
        global $wpinv_email_search, $wpinv_email_replace;

        $email_type = 'user_quote';

        if (!wpinv_email_is_enabled($email_type)) {
            return false;
        }

        $quote = new WPInv_Invoice($quote_id);

        if (empty($quote)) {
            return false;
        }

        if (!("wpi_quote" === $quote->post_type)) {
            return false;
        }

        $recipient = wpinv_email_get_recipient($email_type, $quote_id, $quote);

        if (!is_email($recipient)) {
            return false;
        }

        $search = array();
        $search['invoice_number'] = '{quote_number}';
        $search['invoice_date'] = '{quote_date}';
        $search['name'] = '{name}';

        $replace = array();
        $replace['invoice_number'] = $quote->get_number();
        $replace['invoice_date'] = $quote->get_invoice_date();
        $replace['name'] = $quote->get_user_full_name();

        $wpinv_email_search = $search;
        $wpinv_email_replace = $replace;

        $subject = wpinv_email_get_subject($email_type, $quote_id, $quote);
        $email_heading = wpinv_email_get_heading($email_type, $quote_id, $quote);
        $headers = wpinv_email_get_headers($email_type, $quote_id, $quote);
        $attachments = wpinv_email_get_attachments($email_type, $quote_id, $quote);

        $content = wpinv_get_template_html('emails/wpinv-email-' . $email_type . '.php', array(
            'quote' => $quote,
            'email_type' => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text' => false,
        ), 'wpinv-quote/', WP_PLUGIN_DIR . '/wpinv-quote/templates/');

        $sent = wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);

        if ($sent) {
            $note = __('Quote has been emailed to the user.', 'invoicing');
        } else {
            $note = __('Fail to send quote to the user!', 'invoicing');
        }
        $quote->add_note($note, '', '', true); // Add system note.

        if (wpinv_mail_admin_bcc_active($email_type)) {
            $recipient = wpinv_get_admin_email();
            $subject .= ' - ADMIN BCC COPY';
            wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);
        }

        return $sent;
    }

    /**
     * Apply filter to change the recipient for quotes
     *
     * @since    1.0.0
     */
    function wpinv_quote_email_recipient($recipient, $email_type, $quote_id, $quote)
    {
        if ($quote->post_type == 'wpi_quote' && !empty($quote_id)) {
            switch ($email_type) {
                case 'user_quote_accepted':
                case 'user_quote_declined':
                    $recipient = wpinv_get_admin_email();
                    break;
                case 'user_quote':
                case 'user_quote_cancelled':
                default:
                    $quote = !empty($quote) && is_object($quote) ? $quote : ($quote_id > 0 ? wpinv_get_invoice($quote_id) : NULL);
                    $recipient = !empty($quote) ? $quote->get_email() : '';
                    break;
            }
        }

        $recipient = apply_filters('wpinv_quote_email_recipient', $recipient, $email_type, $quote_id);
        return $recipient;
    }

    /**
     * Add quote status change note
     *
     * @since    1.0.0
     */
    function wpinv_quote_record_status_change($quote_id, $new_status, $old_status)
    {
        if ('wpi_quote' != get_post_type($quote_id)) {
            return;
        }

        $quote = wpinv_get_invoice($quote_id);

        $old_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($old_status);
        $new_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($new_status);

        $status_change = sprintf(__('Quote status changed from %s to %s', 'invoicing'), $old_status_nicename, $new_status_nicename);

        // Add note
        $quote->add_note($status_change, false, false, true);

        if (is_admin()) { // Actions for quote status change from admin side
            switch ($new_status) {
                case 'publish':
                    $this->process_quote_published($quote_id);
                    break;
                case 'wpi-quote-declined':
                    $this->process_quote_declined($quote_id);
                    break;
                case 'wpi-quote-cancelled':
                    $this->process_quote_cancelled($quote_id);
                    break;
            }
        }

        return;
    }

    /**
     * process when quote accepted
     *
     * @since    1.0.0
     */
    function process_quote_published($quote_id)
    {
        if (empty($quote_id)) return;

        do_action('wpinv_quote_before_process_published', $quote_id);

        $accepted_action = wpinv_get_option('accepted_quote_action');
        $gateway = wpinv_get_default_gateway();
        if ($accepted_action === 'convert' || $accepted_action === 'convert_send' || empty($accepted_action)) {
            //convert quote to invoice
            set_post_type($quote_id, 'wpi_invoice');

            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'pending',
            ));
            //update meta data
            $number = wpinv_format_invoice_number($quote_id);
            update_post_meta($quote_id, '_wpinv_number', $number);
            update_post_meta($quote_id, '_wpinv_gateway', $gateway);

            $quote = wpinv_get_invoice($quote_id);
            $quote->add_note(__('Converted from Quote to Invoice.', 'invoicing'), false, false, true);

            if ($accepted_action === 'convert_send') {
                wpinv_new_invoice_notification($quote_id);
            }

        } elseif ($accepted_action === 'duplicate' || $accepted_action === 'duplicate_send') {
            //create new invoice from quote
            global $wpdb;

            $post = get_post($quote_id);

            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status' => $post->ping_status,
                'post_author' => $post->post_author,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_name' => $post->post_name,
                'post_parent' => $post->post_parent,
                'post_password' => $post->post_password,
                'post_status' => 'pending',
                'post_type' => 'wpi_invoice',
                'post_title' => $post->post_title,
                'to_ping' => $post->to_ping,
                'menu_order' => $post->menu_order,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
            );

            $new_invoice_id = wp_insert_post($args);
            //update meta data
            $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$quote_id");
            if (count($post_meta_infos) != 0) {
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ($post_meta_infos as $meta_info) {
                    $meta_key = $meta_info->meta_key;
                    $meta_value = addslashes($meta_info->meta_value);
                    $sql_query_sel[] = "SELECT $new_invoice_id, '$meta_key', '$meta_value'";
                }
                $sql_query .= implode(" UNION ALL ", $sql_query_sel);
                $wpdb->query($sql_query);
            }

            $number = wpinv_format_invoice_number($new_invoice_id);
            $post_name = sanitize_title($number);

            $quote = wpinv_get_invoice($new_invoice_id);
            $quote->add_note(__('Created Invoice from Quote.', 'invoicing'), false, false, true);

            // Update post title and date
            wp_update_post(array(
                'ID' => $new_invoice_id,
                'post_title' => $number,
                'post_name' => $post_name,
            ));

            update_post_meta($new_invoice_id, '_wpinv_number', $number);
            update_post_meta($new_invoice_id, '_wpinv_gateway', $gateway);
            update_post_meta($new_invoice_id, '_wpinv_quote_reference', $quote_id);

            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'publish',
            ));

            $quote = wpinv_get_invoice($quote_id);
            $quote->add_note(__('Converted from Quote to Invoice.', 'invoicing'), false, false, true);

            if ($accepted_action === 'duplicate_send') {
                wpinv_new_invoice_notification($new_invoice_id);
            }

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');

        } else {
            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'publish',
            ));

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');
        }

        do_action('wpinv_quote_after_process_published', $quote_id);
    }

    /**
     * process when quote declined
     *
     * @since    1.0.0
     */
    function process_quote_declined($quote_id = 0)
    {
        if (empty($quote_id)) return;
        do_action('wpinv_quote_before_process_declined', $quote_id);
        $this->wpinv_quote_decrease_the_discounts($quote_id);
        // make the quote as declined
        wp_update_post(array(
            'ID' => $quote_id,
            'post_status' => 'wpi-quote-declined',
        ));
        $this->wpinv_user_quote_declined_notification($quote_id);
        do_action('wpinv_quote_after_process_declined', $quote_id);
    }

    /**
     * Function to decrease the discount if quote cancelled or declined
     *
     * @since    1.0.0
     */
    private function wpinv_quote_decrease_the_discounts($quote_id = 0)
    {
        $discounts = wpinv_discount_code($quote_id);
        if (empty($discounts)) {
            return;
        }

        if (!is_array($discounts)) {
            $discounts = array_map('trim', explode(',', $discounts));
        }

        foreach ($discounts as $discount) {
            wpinv_decrease_discount_usage($discount);
        }
    }

    /**
     * Notify when quote declined
     *
     * @since    1.0.0
     */
    function wpinv_user_quote_declined_notification($quote_id, $new_status = '')
    {
        global $wpinv_email_search, $wpinv_email_replace;

        $email_type = 'user_quote_declined';

        if (!wpinv_email_is_enabled($email_type)) {
            return false;
        }

        $quote = new WPInv_Invoice($quote_id);

        if (empty($quote)) {
            return false;
        }

        if (!("wpi_quote" === $quote->post_type)) {
            return false;
        }

        $recipient = wpinv_email_get_recipient($email_type, $quote_id, $quote);

        if (!is_email($recipient)) {
            return false;
        }

        $search = array();
        $search['invoice_number'] = '{quote_number}';
        $search['invoice_date'] = '{quote_date}';
        $search['name'] = '{name}';

        $replace = array();
        $replace['invoice_number'] = $quote->get_number();
        $replace['invoice_date'] = $quote->get_invoice_date();
        $replace['name'] = $quote->get_user_full_name();

        $wpinv_email_search = $search;
        $wpinv_email_replace = $replace;

        $subject = wpinv_email_get_subject($email_type, $quote_id, $quote);
        $email_heading = wpinv_email_get_heading($email_type, $quote_id, $quote);
        $headers = wpinv_email_get_headers($email_type, $quote_id, $quote);
        $attachments = wpinv_email_get_attachments($email_type, $quote_id, $quote);

        $content = wpinv_get_template_html('emails/wpinv-email-' . $email_type . '.php', array(
            'quote' => $quote,
            'email_type' => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text' => false,
        ), 'wpinv-quote/', WP_PLUGIN_DIR . '/wpinv-quote/templates/');

        $sent = wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);

        $note = sprintf(__('Quote has been declined!', 'invoicing'));
        $quote->add_note($note, '', '', true); // Add system note.

        return $sent;
    }

    /**
     * process when quote cancelled
     *
     * @since    1.0.0
     */
    function process_quote_cancelled($quote_id = 0)
    {
        if (empty($quote_id)) return;
        do_action('wpinv_quote_before_process_cancelled', $quote_id);
        wp_update_post(array(
            'ID' => $quote_id,
            'post_status' => 'wpi-quote-cancelled',
        ));
        $this->wpinv_user_quote_cancelled_notification($quote_id);
        $this->wpinv_quote_decrease_the_discounts($quote_id);
        do_action('wpinv_quote_after_process_cancelled', $quote_id);
    }

    /**
     * Notify when quote cancelled
     *
     * @since    1.0.0
     */
    function wpinv_user_quote_cancelled_notification($quote_id, $new_status = '')
    {
        global $wpinv_email_search, $wpinv_email_replace;

        $email_type = 'user_quote_cancelled';

        if (!wpinv_email_is_enabled($email_type)) {
            return false;
        }

        $quote = new WPInv_Invoice($quote_id);

        if (empty($quote)) {
            return false;
        }

        if (!("wpi_quote" === $quote->post_type)) {
            return false;
        }

        $recipient = wpinv_email_get_recipient($email_type, $quote_id, $quote);

        if (!is_email($recipient)) {
            return false;
        }

        $search = array();
        $search['invoice_number'] = '{quote_number}';
        $search['invoice_date'] = '{quote_date}';
        $search['name'] = '{name}';

        $replace = array();
        $replace['invoice_number'] = $quote->get_number();
        $replace['invoice_date'] = $quote->get_invoice_date();
        $replace['name'] = $quote->get_user_full_name();

        $wpinv_email_search = $search;
        $wpinv_email_replace = $replace;

        $subject = wpinv_email_get_subject($email_type, $quote_id, $quote);
        $email_heading = wpinv_email_get_heading($email_type, $quote_id, $quote);
        $headers = wpinv_email_get_headers($email_type, $quote_id, $quote);
        $attachments = wpinv_email_get_attachments($email_type, $quote_id, $quote);

        $content = wpinv_get_template_html('emails/wpinv-email-' . $email_type . '.php', array(
            'quote' => $quote,
            'email_type' => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text' => false,
        ), 'wpinv-quote/', WP_PLUGIN_DIR . '/wpinv-quote/templates/');

        $sent = wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);

        $note = sprintf(__('Quote has been cancelled!', 'invoicing'));
        $quote->add_note($note, '', '', true); // Add system note.

        return $sent;
    }

    /**
     * Add quote status change note
     *
     * @since    1.0.0
     */
    function wpinv_front_quote_actions($data)
    {
        $quote_id = !empty($data['qid']) ? absint($data['qid']) : NULL;

        if (empty($quote_id) || empty($data['action'])) {
            return;
        }

        if ('wpi_quote' != get_post_type($quote_id)) {
            return;
        }

        $old_status = 'wpi-quote-sent';

        if ($data['action'] == 'accept') {
            $new_status = 'publish';
            $check_nonce = 'wpinv_client_accept_quote_nonce';

        } elseif ($data['action'] == 'decline') {
            $new_status = 'wpi-quote-declined';
            $check_nonce = 'wpinv_client_decline_quote_nonce';
        }

        if (!wp_verify_nonce($data['_wpnonce'], $check_nonce)) {
            return;
        }

        do_action('wpinv_front_quote_actions_before_process', $quote_id, $old_status, $new_status);

        $quote = wpinv_get_invoice($quote_id);

        $old_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($old_status, $quote);
        $new_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($new_status, $quote);

        $status_change = sprintf(__('Quote status changed from %s to %s by user.', 'invoicing'), $old_status_nicename, $new_status_nicename);

        $quote->add_note($status_change, false, false, true);// Add note

        switch ($new_status) {
            case 'publish':
                $this->process_quote_published($quote_id);
                break;
            case 'wpi-quote-declined':
                $this->process_quote_declined($quote_id);
                break;
        }

        do_action('wpinv_front_quote_actions_after_process', $quote_id, $old_status, $new_status);

        wp_redirect(get_post_permalink($quote_id));
        exit();
    }

    /**
     * Notify when quote accepted
     *
     * @since    1.0.0
     */
    function wpinv_user_quote_accepted_notification($quote_id, $new_status = '')
    {
        global $wpinv_email_search, $wpinv_email_replace;

        $email_type = 'user_quote_accepted';

        if (!wpinv_email_is_enabled($email_type)) {
            return false;
        }

        $quote = new WPInv_Invoice($quote_id);

        if (empty($quote)) {
            return false;
        }

        if (!("wpi_quote" === $quote->post_type)) {
            return false;
        }

        $recipient = wpinv_email_get_recipient($email_type, $quote_id, $quote);

        if (!is_email($recipient)) {
            return false;
        }

        $search = array();
        $search['invoice_number'] = '{quote_number}';
        $search['invoice_date'] = '{quote_date}';
        $search['name'] = '{name}';

        $replace = array();
        $replace['invoice_number'] = $quote->get_number();
        $replace['invoice_date'] = $quote->get_invoice_date();
        $replace['name'] = $quote->get_user_full_name();

        $wpinv_email_search = $search;
        $wpinv_email_replace = $replace;

        $subject = wpinv_email_get_subject($email_type, $quote_id, $quote);
        $email_heading = wpinv_email_get_heading($email_type, $quote_id, $quote);
        $headers = wpinv_email_get_headers($email_type, $quote_id, $quote);
        $attachments = wpinv_email_get_attachments($email_type, $quote_id, $quote);

        $content = wpinv_get_template_html('emails/wpinv-email-' . $email_type . '.php', array(
            'quote' => $quote,
            'email_type' => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text' => false,
        ), 'wpinv-quote/', WP_PLUGIN_DIR . '/wpinv-quote/templates/');

        $sent = wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);

        $note = sprintf(__('Quote has been accepted!', 'invoicing'));
        $quote->add_note($note, '', '', true); // Add system note.

        return $sent;
    }

    /**
     * Provide the filter for updating quote status
     *
     * @since    1.0.0
     */
    function wpinv_quote_should_update_quote_status($update, $quote_id, $new_status, $old_status)
    {
        $update = apply_filters('wpinv_should_update_quote_status', true, $quote_id, $new_status, $old_status);
        return $update;
    }

    /**
     * Send customer quote
     *
     * @since    1.0.0
     */
    function wpinv_send_customer_quote($data = array())
    {
        $quote_id = !empty($data['quote_id']) ? absint($data['quote_id']) : NULL;

        if (empty($quote_id)) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to send quote notification', 'invoicing'), __('Error', 'invoicing'), array('response' => 403));
        }

        $sent = $this->wpinv_user_quote_notification($quote_id);

        $status = $sent ? 'email_sent' : 'email_fail';

        $redirect = add_query_arg(array('wpinv-message' => $status, 'wpi_action' => false, 'quote_id' => false));
        wp_redirect($redirect);
        exit;
    }

    /**
     * Add information before quote email
     *
     * @since    1.0.0
     */
    function wpinv_email_before_quote_details($quote, $email_type)
    {
        if ($quote->post_type == 'wpi_quote' && !empty($email_type)) {
            switch ($email_type) {
                case 'user_quote':
                    $email_output = sprintf(__("<p>Hi there. Your recent quote on %s has been created. Your quote details are shown below for your reference:</p>", 'invoicing'), wpinv_get_business_name());
                    break;
                case 'user_quote_accepted':
                    $email_output = sprintf(__("<p>Hi there. Quote on %s has been accepted. Quote details are shown below for your reference:</p>", 'invoicing'), wpinv_get_business_name());
                    break;
                case 'user_quote_declined':
                    $email_output = sprintf(__("<p>Hi there. Quote on %s has been declined. Quote details are shown below for your reference:</p>", 'invoicing'), wpinv_get_business_name());
                    break;
                case 'user_quote_cancelled':
                    $email_output = sprintf(__("<p>Hi there. Quote on %s has been cancelled. Quote details are shown below for your reference:</p>", 'invoicing'), wpinv_get_business_name());
                    break;
                default:
                    $email_output = '';
                    break;
            }
        }

        $email_output = apply_filters('wpinv_quote_email_message', $email_output, $quote);
        echo $email_output;
    }

    /**
     * Add information before quote note email
     *
     * @since    1.0.0
     */
    function wpinv_quote_email_before_note_details($invoice, $email_type, $sent_to_admin, $customer_note)
    {
        if ("wpi_quote" === $invoice->post_type && !empty($customer_note)) {
            $before_note = '';
            $before_note .= __('Hello, a note has just been added to your quote:', 'invoicing');
            $before_note .= '<blockquote class="wpinv-note">' . wpautop(wptexturize($customer_note)) . '</blockquote>';
            $before_note .= __('For your reference, your quote details are shown below.', 'invoicing');
            echo $before_note;
        }
    }

    /**
     * Change quote details title in email template
     *
     * @since    1.0.0
     */
    function wpinv_quote_email_details_title($title, $quote)
    {
        if ($quote->post_type == 'wpi_quote' && !empty($quote->ID)) {
            $title = __('Quote Details:', 'invoicing');
        }
        return $title;
    }

    /**
     * Change quote number title in email template
     *
     * @since    1.0.0
     */
    function wpinv_quote_email_details_number($title, $quote)
    {
        if ($quote->post_type == 'wpi_quote' && !empty($quote->ID)) {
            $title = __('Quote Number', 'invoicing');
        }
        return $title;
    }

    /**
     * Change quote date title in email template
     *
     * @since    1.0.0
     */
    function wpinv_quote_email_details_date($title, $quote)
    {
        if ($quote->post_type == 'wpi_quote' && !empty($quote->ID)) {
            $title = __('Quote Date', 'invoicing');
        }
        return $title;
    }

    /**
     * Change quote status title in email template
     *
     * @since    1.0.0
     */
    function wpinv_quote_email_details_status($title, $quote)
    {
        if ($quote->post_type == 'wpi_quote' && !empty($quote->ID)) {
            $title = __('Quote Status', 'invoicing');
        }
        return $title;
    }

    /**
     * Convert from quote to invoice action.
     *
     * @since    1.0.0
     */
    function wpinv_convert_quote_to_invoice()
    {

        if (!(isset($_GET['quote_id']) || isset($_POST['post']) || (isset($_REQUEST['wpi_action']) && 'wpinv_quote_to_invoice' == $_REQUEST['wpi_action']))) {
            wp_die('No quote to convert!');
        }

        if (!isset($_GET['wpinv_convert_quote']) || !wp_verify_nonce($_GET['wpinv_convert_quote'], 'convert'))
            wp_die('Ooops, something went wrong, please try again later.');

        $quote_id = (int)$_GET['quote_id'];

        // convert to invoice
        $this->process_quote_published($quote_id);

        do_action('wpinv_manual_convert_quote_to_invoice', $quote_id);

        $redirect = remove_query_arg(array('wpi_action', 'wpinv_convert_quote', 'quote_id'), add_query_arg(array('wpinv-message' => 'wpinv_quote_converted')));
        wp_redirect($redirect);

        exit;

    }

    /**
     * Notice to be displayed based on quote action
     *
     * @since    1.0.0
     */
    function wpinv_quote_admin_notices()
    {
        if (isset($_GET['wpinv-message']) && 'wpinv_quote_converted' == $_GET['wpinv-message'] && current_user_can('manage_options')) {
            add_settings_error('wpinv-quote-notices', 'wpinv-discount-added', __('Quote converted to invoice successfully.', 'invoicing'), 'updated');
            settings_errors('wpinv-quote-notices');
        }
    }

}