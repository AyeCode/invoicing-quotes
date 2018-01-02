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
            $localize['convert_quote'] = __('Are you sure you want to convert from Quote to Invoice?', 'wpinv-quotes');
            $localize['save_quote'] = __('Save Quote', 'wpinv-quotes');
        }
        wp_localize_script($this->plugin_name, 'wpinv_quotes_admin', $localize);

    }

    /**
     * add quote settings in wpinv_settings
     *
     * @since    1.0.0
     */
    public function wpinv_quote_update_settings()
    {
        if ( is_admin() && get_option( 'activated_quotes' ) == 'wpinv-quotes' ) {

            global $wpinv_options;

            $pages = apply_filters( 'wpinv_create_pages', array(
                'quote_history_page' => array(
                    'name'    => _x( 'wpi-quotes-history', 'Page slug', 'wpinv-quotes' ),
                    'title'   => _x( 'Quote History', 'Page title', 'wpinv-quotes' ),
                    'content' => '[' . apply_filters( 'wpinv_quote_history_shortcode_tag', 'wpinv_quote_history' ) . ']',
                    'parent' => 'wpi-checkout',
                ),
            ) );

            foreach ( $pages as $key => $page ) {
                wpinv_create_page( esc_sql( $page['name'] ), $key, $page['title'], $page['content'], $page['parent'] );
            }

            // Pull options from WP, not GD Invoice's global
            $current_options = get_option( 'wpinv_settings', array() );
            $options = array();

            // Populate some default values
            foreach( wpinv_get_registered_settings() as $tab => $sections ) {
                foreach( $sections as $section => $settings) {
                    // Check for backwards compatibility
                    $tab_sections = wpinv_get_settings_tab_sections( $tab );
                    if( ! is_array( $tab_sections ) || ! array_key_exists( $section, $tab_sections ) ) {
                        $section = 'main';
                        $settings = $sections;
                    }

                    foreach ( $settings as $option ) {
                        if ( !empty( $option['id'] ) && !isset( $wpinv_options[ $option['id'] ] ) ) {
                            if ( 'checkbox' == $option['type'] && !empty( $option['std'] ) ) {
                                $options[ $option['id'] ] = '1';
                            } else if ( !empty( $option['std'] ) ) {
                                $options[ $option['id'] ] = $option['std'];
                            }
                        }
                    }
                }
            }

            $merged_options_current     = array_merge( $wpinv_options, $options );
            $merged_options     = array_merge( $merged_options_current, $current_options );
            $wpinv_options      = $merged_options;

            update_option( 'wpinv_settings', $merged_options );

            delete_option( 'activated_quotes' );
        }
    }

    /**
     * Filter the JavaScript for the admin area of invoicing plugin.
     *
     * @since    1.0.0
     * @param array $localize localize array of main invoicing plugin
     * @return array $localize
     */
    public function wpinv_quote_admin_js_localize($localize)
    {

        global $pagenow, $post;

        if (isset($post->ID) && $post->post_type == 'wpi_quote' && ($pagenow == 'post-new.php' || $pagenow == 'post.php')) {
            $localize['emptyInvoice'] = __('Add at least one item to save quote!', 'wpinv-quotes');
            $localize['OneItemMin'] = __('Quote must contain at least one item', 'wpinv-quotes');
            $localize['deletePackage'] = __('GD package items should be deleted from GD payment manager only, otherwise it will break quotes created with this package!', 'wpinv-quotes');
            $localize['deleteInvoiceFirst'] = __('This item is in use! Before delete this item, you need to delete all the quote(s) using this item.', 'wpinv-quotes');
        }

        return $localize;

    }

    /**
     * Creates a quote custom post type
     *
     * @since    1.0.0
     */
    public function wpinv_quote_new_cpt()
    {

        $cap_type = 'wpi_quote';
        $plural = __('Quotes', 'wpinv-quotes');
        $single = __('Quote', 'wpinv-quotes');
        $menu_icon = WPINV_QUOTES_URL . '/images/favicon.ico';
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
        $opts['rewrite'] = TRUE;
        $opts['show_in_admin_bar'] = TRUE;
        $opts['show_in_menu'] = current_user_can( 'manage_invoicing' ) ? 'wpinv' : true;
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

        $opts['labels']['add_new'] = __("Add New {$single}", 'wpinv-quotes');
        $opts['labels']['add_new_item'] = __("Add New {$single}", 'wpinv-quotes');
        $opts['labels']['all_items'] = __($plural, 'wpinv-quotes');
        $opts['labels']['edit_item'] = __("Edit {$single}", 'wpinv-quotes');
        $opts['labels']['menu_name'] = __($plural, 'wpinv-quotes');
        $opts['labels']['name'] = __($plural, 'wpinv-quotes');
        $opts['labels']['name_admin_bar'] = __($single, 'wpinv-quotes');
        $opts['labels']['new_item'] = __("New {$single}", 'wpinv-quotes');
        $opts['labels']['not_found'] = __("No {$plural} Found", 'wpinv-quotes');
        $opts['labels']['not_found_in_trash'] = __("No {$plural} Found in Trash", 'wpinv-quotes');
        $opts['labels']['parent_item_colon'] = __("Parent {$plural} :", 'wpinv-quotes');
        $opts['labels']['search_items'] = __("Search {$plural}", 'wpinv-quotes');
        $opts['labels']['singular_name'] = __($single, 'wpinv-quotes');
        $opts['labels']['view_item'] = __("View {$single}", 'wpinv-quotes');

        $opts = apply_filters('wpinv_quote_params', $opts);

        register_post_type('wpi_quote', $opts);

    }

    /**
     * Return columns for admin side quote listing
     *
     * @since    1.0.0
     * @param array $columns get post columns
     * @return array $columns new columns for quotes listing
     */
    function wpinv_quote_columns($columns)
    {
        $columns = array(
            'cb' => $columns['cb'],
            'number' => __( 'Number', 'wpinv-quotes' ),
            'customer' => __('Customer', 'wpinv-quotes'),
            'amount' => __('Amount', 'wpinv-quotes'),
            'quote_date' => __('Date', 'wpinv-quotes'),
            'status' => __('Status', 'wpinv-quotes'),
            'ID' => __('ID', 'wpinv-quotes'),
            'wpi_actions' => __('Actions', 'wpinv-quotes'),
        );

        return apply_filters('wpi_quote_table_columns', $columns);
    }

    /**
     * Remove bulk edit option from admin side quote listing
     *
     * @since    1.0.0
     * @param array $actions post actions
     * @return array $actions actions without edit option
     */
    function wpinv_quote_bulk_actions($actions)
    {
        if (isset($actions['edit'])) {
            unset($actions['edit']);
        }

        return $actions;
    }

    /**
     * Return sortable columns for quote listing
     *
     * @since    1.0.0
     * @param array $columns post columns
     * @return array $columns new columns for quote listing
     */
    function wpinv_quote_sortable_columns($columns)
    {
        $columns = array(
            'ID' => array('ID', true),
            'number' => array('number', false),
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
     * @param string $column_name current column name in quote listing
     * @return string $value value for current column
     */
    function wpinv_quote_posts_custom_column($column_name)
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
                $customer_name = $customer_name != '' ? $customer_name : __('Customer', 'wpinv-quotes');
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
                $value = $wpi_invoice->get_status(true);
                break;
            case 'number' :
                $edit_link = get_edit_post_link( $post->ID );
                $value = '<a title="' . esc_attr__( 'View Quote Details', 'wpinv-quotes' ) . '" href="' . esc_url( $edit_link ) . '">' . $wpi_invoice->get_number() . '</a>';
                break;
            case 'wpi_actions' :
                $value = '';
                if (!empty($post->post_name)) {
                    $value .= '<a title="' . esc_attr__('Print quote', 'wpinv-quotes') . '" href="' . esc_url(get_permalink($post->ID)) . '" class="button ui-tip column-act-btn" title="" target="_blank"><span class="dashicons dashicons-print"><i style="" class="fa fa-print"></i></span></a>';
                }

                if ($email = $wpi_invoice->get_email()) {
                    $value .= '<a title="' . esc_attr__('Send quote to customer', 'wpinv-quotes') . '" href="' . esc_url(add_query_arg(array('wpi_action' => 'send_quote', 'quote_id' => $post->ID))) . '" class="button ui-tip column-act-btn"><span class="dashicons dashicons-email-alt"></span></a>';
                }

                if ("wpi_quote" === $wpi_invoice->post_type && in_array($wpi_invoice->post_status, array('wpi-quote-pending'))) {
                    $action_url = add_query_arg(array('wpi_action' => 'convert_quote_to_invoice', 'quote_id' => $post->ID));
                    $action_url = esc_url(wp_nonce_url($action_url, 'convert', 'wpinv_convert_quote'));
                    $value .= '<a title="' . esc_attr__('Convert quote to invoice', 'wpinv-quotes') . '" href="' . $action_url . '" class="button ui-tip column-act-btn"><span class="dashicons dashicons-controls-repeat"></span></a>';
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
     * @param array $actions row actions
     * @param $post object of post
     * @return array $actions empty all actions for quote
     */
    function wpinv_quote_post_row_actions($actions, $post)
    {
        if (!empty($post->ID) && 'wpi_quote' == $post->post_type ) {
            $actions = array();
        }
        return $actions;
    }

    /**
     * Add metaboxes for quote post type
     *
     * @since 1.0.0
     * @param string $post_type current post type
     * @param $post current post
     */
    function wpinv_quoute_add_meta_boxes($post_type, $post)
    {
        global $wpi_mb_invoice;
        if (!empty($post->ID) && 'wpi_quote' == $post->post_type) {
            $wpi_mb_invoice = wpinv_get_invoice($post->ID);
            add_meta_box('wpinv-details', __('Quote Details', 'wpinv-quotes'), 'WPInv_Meta_Box_Details::output', 'wpi_quote', 'side', 'default');
            add_meta_box('wpinv-address', __('Billing Details', 'wpinv-quotes'), 'WPInv_Meta_Box_Billing_Details::output', 'wpi_quote', 'normal', 'high');
            add_meta_box('wpinv-items', __('Quote Items', 'wpinv-quotes'), 'WPInv_Meta_Box_Items::output', 'wpi_quote', 'normal', 'high');
            add_meta_box('wpinv-notes', __('Quote Notes', 'wpinv-quotes'), 'WPInv_Meta_Box_Notes::output', 'wpi_quote', 'normal', 'high');
            if (!empty($wpi_mb_invoice) && $wpi_mb_invoice->has_status(array('wpi-quote-pending'))) {
                add_meta_box('wpinv-mb-resend-invoice', __('Resend Quote', 'wpinv-quotes'), 'WPInv_Meta_Box_Details::resend_invoice', 'wpi_quote', 'side', 'high');
                add_meta_box('wpinv-mb-convert-quote', __('Convert Quote', 'wpinv-quotes'), 'WPInv_Quote_Meta_Box::quote_to_invoice_output', 'wpi_quote', 'side', 'high');
            }
        }
    }

    /**
     * Change resend quote metabox params text values
     *
     * @since 1.0.0
     * @param string $text old text displayed in metabox
     * @return string $text new text to display in metabox
     */
    function wpinv_quote_resend_quote_metabox_text($text)
    {
        global $post;
        if (!empty($post->ID) && 'wpi_quote' == $post->post_type) {
            $text = array(
                'message' => esc_attr__('This will send a copy of the quote to the customer&#8217;s email address.', 'wpinv-quotes'),
                'button_text' => __('Resend Quote', 'wpinv-quotes'),
            );
        }
        return $text;
    }

    /**
     * Change resend quote button url using filter
     *
     * @since    1.0.0
     * @param array $email_actions old email URL
     * @return array $email_actions new email URL
     */
    function wpinv_quote_resend_quote_email_actions($email_actions)
    {
        global $post;
        if (!empty($post->ID) && 'wpi_quote' == $post->post_type) {
            $email_actions['email_url'] = add_query_arg(array('wpi_action' => 'send_quote', 'quote_id' => $post->ID, 'wpi_action_type' => 'resend_quote'));
        }
        return $email_actions;
    }

    /**
     * Change quote details metabox input labels
     *
     * @since    1.0.0
     * @param array $title labels of form fields
     * @param object $post current post object
     * @return array $title new labels of form fields
     */
    function wpinv_quote_detail_metabox_titles($title, $post)
    {
        if (!empty($post->ID) && 'wpi_quote' == $post->post_type) {
            $title['status'] = __('Quote Status:', 'wpinv-quotes');
            $title['number'] = __('Quote Number:', 'wpinv-quotes');
        }
        return $title;
    }

    /**
     * Change quote details metabox input labels
     *
     * @since    1.0.0
     * @param array $title labels of form fields
     * @param object $post current post object
     * @return array $title new labels of form fields
     */
    function wpinv_quote_items_total_label($title, $quote)
    {
        if (!empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            $title = __('Quote Total:', 'wpinv-quotes');
        }
        return $title;
    }

    /**
     * Change quote details metabox mail notice
     *
     * @since    1.0.0
     * @param string $mail_notice
     * @param object $post
     * @return string $mail_notice notice to display after Send Quote field
     */
    function wpinv_quote_metabox_mail_notice($mail_notice, $post)
    {
        if (!empty($post->ID) && 'wpi_quote' == $post->post_type) {
            $mail_notice = __('After saving quote, this will send a copy of the quote to the user&#8217;s email address.', 'wpinv-quotes');
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
        register_post_status('wpi-quote-pending', array(
            'label' => _x('Pending', 'Quote status', 'wpinv-quotes'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'wpinv-quotes')
        ));
        register_post_status('wpi-quote-accepted', array(
            'label' => _x('Accepted', 'Quote status', 'wpinv-quotes'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Accepted <span class="count">(%s)</span>', 'Accepted <span class="count">(%s)</span>', 'wpinv-quotes')
        ));
        register_post_status('wpi-quote-declined', array(
            'label' => _x('Declined', 'Quote status', 'wpinv-quotes'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Declined <span class="count">(%s)</span>', 'Declined <span class="count">(%s)</span>', 'wpinv-quotes')
        ));
    }

    /**
     * Add quote settings tab
     *
     * @since    1.0.0
     * @param array $tabs all tabs of wpinv-quotes settings
     * @return array $tabs add with quote tab
     */
    function wpinv_quote_settings_tabs($tabs)
    {
        $tabs['quote'] = __('Quote', 'wpinv-quotes');

        return $tabs;
    }

    /**
     * Add quote settings tab main
     *
     * @since    1.0.0
     * @param array $sections all sections of wpinv-quotes settings
     * @return array $sections add quote main sections
     */
    function wpinv_quote_settings_sections($sections)
    {

        $quote_sections = array(
            'quote' => apply_filters('wpinv_settings_sections_quote', array(
                'main' => __('Quote Settings', 'wpinv-quotes'),
            )),
        );

        $sections = array_merge($sections, $quote_sections);

        return $sections;
    }

    /**
     * Add quote settings tab
     *
     * @since    1.0.0
     * @param array $wpinv_settings all settings fields of invoicing settings
     * @return array $sections add quote settings
     */
    function wpinv_quote_registered_settings($wpinv_settings)
    {
        $pages = wpinv_get_pages( true );
        $quote_number_padd_options = array();
        for ($i = 0; $i <= 20; $i++) {
            $quote_number_padd_options[$i] = $i;
        }

        $last_number = '';
        if ( $last_quote_number = get_option( 'wpinv_last_quote_number' ) ) {
            $last_quote_number = is_numeric( $last_quote_number ) ? $last_quote_number : Wpinv_Quotes_Shared::wpinv_clean_quote_number( $last_quote_number );

            if ( !empty( $last_quote_number ) ) {
                $last_number = ' ' . wp_sprintf( __( "( Last Quote's sequential number: <b>%s</b> )", 'wpinv-quotes' ), $last_quote_number );
            }
        }
        $quote_settings = array(
            'quote' => apply_filters('wpinv_settings_quote',
                array(
                    'main' => array(
                        'quote_number_format_settings' => array(
                            'id' => 'quote_number_format_settings',
                            'name' => '<h3>' . __('Quote Number', 'wpinv-quotes') . '</h3>',
                            'type' => 'header',
                        ),
                        'sequential_quote_number' => array(
                            'id'   => 'sequential_quote_number',
                            'name' => __( 'Sequential Quote Numbers', 'wpinv-quotes' ),
                            'desc' => __( 'Check this box to enable sequential quote numbers.', 'wpinv-quotes' ),
                            'type' => 'checkbox',
                        ),
                        'quote_sequence_start' => array(
                            'id'   => 'quote_sequence_start',
                            'name' => __( 'Sequential Starting Number', 'wpinv-quotes' ),
                            'desc' => __( 'The number at which the quote number sequence should begin.', 'wpinv-quotes' ) . $last_number,
                            'type' => 'number',
                            'size' => 'small',
                            'std'  => '1',
                            'class'=> 'w100'
                        ),
                        'quote_number_padd' => array(
                            'id' => 'quote_number_padd',
                            'name' => __('Minimum digits', 'wpinv-quotes'),
                            'desc' => __('If the quote number has less digits than this number, it is left padded with 0s. Ex: quote number 108 will padded to 00108 if digits set to 5. The default 0 means no padding.', 'wpinv-quotes'),
                            'type' => 'select',
                            'options' => $quote_number_padd_options,
                            'std' => 5,
                            'chosen' => true,
                        ),
                        'quote_number_prefix' => array(
                            'id' => 'quote_number_prefix',
                            'name' => __('Quote Number Prefix', 'wpinv-quotes'),
                            'desc' => __('A prefix to prepend to all quote numbers. Ex: WPQUO-', 'wpinv-quotes'),
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => 'WPQUO-',
                            'placeholder' => 'WPQUO-',
                        ),
                        'quote_number_postfix' => array(
                            'id' => 'quote_number_postfix',
                            'name' => __('Quote Number Postfix', 'wpinv-quotes'),
                            'desc' => __('A postfix to append to all quote numbers.', 'wpinv-quotes'),
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => ''
                        ),
                        'quote_page_settings' => array(
                            'id' => 'quote_page_settings',
                            'name' => '<h3>' . __('Quote Page Settings', 'wpinv-quotes') . '</h3>',
                            'type' => 'header',
                        ),
                        'quote_history_page' => array(
                            'id'          => 'quote_history_page',
                            'name'        => __( 'Quote History Page', 'wpinv-quotes' ),
                            'desc'        => __( 'This page displays history of quotes. The <b>[wpinv_quotes]</b> short code should be on this page.', 'wpinv-quotes' ),
                            'type'        => 'select',
                            'options'     => $pages,
                            'chosen'      => true,
                            'placeholder' => __( 'Select a page', 'wpinv-quotes' ),
                        ),
                        'accept_quote_settings' => array(
                            'id' => 'accept_quote_settings',
                            'name' => '<h3>' . __('Accept Quote', 'wpinv-quotes') . '</h3>',
                            'type' => 'header',
                        ),
                        'accepted_quote_action' => array(
                            'name' => __('Accepted Quote Action', 'wpinv-quotes'),
                            'desc' => __('Actions to perform automatically when client accepts quote.', 'wpinv-quotes'),
                            'id' => 'accepted_quote_action',
                            'type' => 'select',
                            'default' => 'convert',
                            'options' => array(
                                'convert' => __('Convert quote to invoice', 'wpinv-quotes'),
                                'convert_send' => __('Convert quote to invoice and send to client', 'wpinv-quotes'),
                                'duplicate' => __('Create invoice, but keep quote', 'wpinv-quotes'),
                                'duplicate_send' => __('Create invoice and send to client, but keep quote', 'wpinv-quotes'),
                                'do_nothing' => __('Do nothing', 'wpinv-quotes'),
                            ),
                            'std' => 'convert',
                        ),
                        'accepted_quote_message' => array(
                            'name' => __('Accepted Quote Message', 'wpinv-quotes'),
                            'desc' => __('Message to display if client accepts the quote.', 'wpinv-quotes'),
                            'id' => 'accepted_quote_message',
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => __('You have accepted this quote.', 'wpinv-quotes'),
                        ),
                        'declined_quote_message' => array(
                            'name' => __('Declined Quote Message', 'wpinv-quotes'),
                            'desc' => __('Message to display if client declines the quote.', 'wpinv-quotes'),
                            'id' => 'declined_quote_message',
                            'type' => 'text',
                            'size' => 'regular',
                            'std' => __('You have declined this quote.', 'wpinv-quotes'),
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
     * @param array $emails all email settings fields of invoicing settings
     * @return array $sections add quote email settings
     */
    function wpinv_quote_mail_settings($emails)
    {
        $user_quote = array(
            'user_quote' => array(
                'email_user_quote_header' => array(
                    'id' => 'email_user_quote_header',
                    'name' => '<h3>' . __('Customer Quote', 'wpinv-quotes') . '</h3>',
                    'desc' => __('Customer Quote email can be sent to customers containing their quote information.', 'wpinv-quotes'),
                    'type' => 'header',
                ),
                'email_user_quote_active' => array(
                    'id' => 'email_user_quote_active',
                    'name' => __('Enable/Disable', 'wpinv-quotes'),
                    'desc' => __('Enable this email notification', 'wpinv-quotes'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_subject' => array(
                    'id' => 'email_user_quote_subject',
                    'name' => __('Subject', 'wpinv-quotes'),
                    'desc' => __('Enter the subject line for the quote receipt email.', 'wpinv-quotes'),
                    'type' => 'text',
                    'std' => __('[{site_title}] Your quote from {quote_date}', 'wpinv-quotes'),
                    'size' => 'large'
                ),
                'email_user_quote_heading' => array(
                    'id' => 'email_user_quote_heading',
                    'name' => __('Email Heading', 'wpinv-quotes'),
                    'desc' => __('Enter the main heading contained within the email notification for the quote receipt email.', 'wpinv-quotes'),
                    'type' => 'text',
                    'std' => __('Your quote {quote_number} details', 'wpinv-quotes'),
                    'size' => 'large'
                ),
                'email_user_quote_admin_bcc' => array(
                    'id' => 'email_user_quote_admin_bcc',
                    'name' => __('Enable Admin BCC', 'wpinv-quotes'),
                    'desc' => __('Check if you want to send this notification email to site Admin.', 'wpinv-quotes'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_body' => array(
                    'id'   => 'email_user_quote_body',
                    'name' => __( 'Email Content', 'wpinv-quotes' ),
                    'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'wpinv-quotes' ),
                    'type' => 'rich_editor',
                    'std'  => __( '<p>Hi {name},</p><p>We have provided you with our quote on {site_title}. </p><p>Click on the following link to view it online where you will be able to accept or decline the quote. <a class="btn btn-success" href="{quote_link}">View & Accept / Decline Quote</a></p>', 'wpinv-quotes' ),
                    'class' => 'large',
                    'size' => '10'
                ),
            ),
            'user_quote_accepted' => array(
                'email_user_quote_accepted_header' => array(
                    'id' => 'email_user_quote_accepted_header',
                    'name' => '<h3>' . __('Quote Accepted', 'wpinv-quotes') . '</h3>',
                    'desc' => __('This email will be sent to admin if user has accepted quote.', 'wpinv-quotes'),
                    'type' => 'header',
                ),
                'email_user_quote_accepted_active' => array(
                    'id' => 'email_user_quote_accepted_active',
                    'name' => __('Enable/Disable', 'wpinv-quotes'),
                    'desc' => __('Enable this email notification', 'wpinv-quotes'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_accepted_subject' => array(
                    'id' => 'email_user_quote_accepted_subject',
                    'name' => __('Subject', 'wpinv-quotes'),
                    'desc' => __('Enter the subject line for the quote accepted email.', 'wpinv-quotes'),
                    'type' => 'text',
                    'std' => __('[{site_title}] User has accepted the quote {quote_number}', 'wpinv-quotes'),
                    'size' => 'large'
                ),
                'email_user_quote_accepted_heading' => array(
                    'id' => 'email_user_quote_accepted_heading',
                    'name' => __('Email Heading', 'wpinv-quotes'),
                    'desc' => __('Enter the main heading contained within the email notification for the quote accepted email.', 'wpinv-quotes'),
                    'type' => 'text',
                    'std' => __('Quote {quote_number} Accepted by user', 'wpinv-quotes'),
                    'size' => 'large'
                ),
                'email_user_quote_accepted_body' => array(
                    'id'   => 'email_user_quote_accepted_body',
                    'name' => __( 'Email Content', 'wpinv-quotes' ),
                    'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'wpinv-quotes' ),
                    'type' => 'rich_editor',
                    'std'  => __( '<p>Hi {name},</p><p>Quote on {site_title} has been accepted. </p>', 'wpinv-quotes' ),
                    'class' => 'large',
                    'size' => '10'
                ),
            ),
            'user_quote_declined' => array(
                'email_user_quote_declined_header' => array(
                    'id' => 'email_user_quote_declined_header',
                    'name' => '<h3>' . __('Quote Declined', 'wpinv-quotes') . '</h3>',
                    'desc' => __('This email will be sent to admin if user has declined quote.', 'wpinv-quotes'),
                    'type' => 'header',
                ),
                'email_user_quote_declined_active' => array(
                    'id' => 'email_user_quote_declined_active',
                    'name' => __('Enable/Disable', 'wpinv-quotes'),
                    'desc' => __('Enable this email notification', 'wpinv-quotes'),
                    'type' => 'checkbox',
                    'std' => 1
                ),
                'email_user_quote_declined_subject' => array(
                    'id' => 'email_user_quote_declined_subject',
                    'name' => __('Subject', 'wpinv-quotes'),
                    'desc' => __('Enter the subject line for the quote declined email.', 'wpinv-quotes'),
                    'type' => 'text',
                    'std' => __('[{site_title}] User has declined the quote {quote_number}', 'wpinv-quotes'),
                    'size' => 'large'
                ),
                'email_user_quote_declined_heading' => array(
                    'id' => 'email_user_quote_declined_heading',
                    'name' => __('Email Heading', 'wpinv-quotes'),
                    'desc' => __('Enter the main heading contained within the email notification for the quote declined email.', 'wpinv-quotes'),
                    'type' => 'text',
                    'std' => __('Quote {quote_number} Declined by user', 'wpinv-quotes'),
                    'size' => 'large'
                ),
                'email_user_quote_declined_body' => array(
                    'id'   => 'email_user_quote_declined_body',
                    'name' => __( 'Email Content', 'wpinv-quotes' ),
                    'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'wpinv-quotes' ),
                    'type' => 'rich_editor',
                    'std'  => __( '<p>Hi {name},</p><p>Quote on {site_title} has been declined.</p><p><b>Reason:</b> {quote_decline_reason}</p>', 'wpinv-quotes' ),
                    'class' => 'large',
                    'size' => '10'
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
     * @param object Quote object.
     */
    function wpinv_send_quote_after_save($quote)
    {
        if ( empty( $_POST['wpi_save_send'] ) ) {
            return;
        }

        if ( !empty( $quote->ID ) && !empty( $quote->post_type ) && 'wpi_quote' == $quote->post_type ) {
            $this->wpinv_user_quote_notification( $quote->ID );
        }
    }

    /**
     * Function to send customer quote email notification to customer
     *
     * @since  1.0.0
     * @param int $quote_id ID of post/quote
     * @return bool $sent email sent or not
     */
    function wpinv_user_quote_notification( $quote_id, $args = array() ) {
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
            $subject .= ' - ADMIN BCC COPY';
            wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);
        }

        return $sent;
    }

    /**
     * Apply filter to change the recipient for quotes
     *
     * @since 1.0.0
     * @param string $recipient recepient of email
     * @param string $email_type type of email
     * @param int $quote_id ID of post/quote
     * @param object $quote post/quote object
     * @return bool $sent email sent or not
     */
    function wpinv_quote_email_recipient($recipient, $email_type, $quote_id, $quote)
    {
        if (!empty($quote_id) &&'wpi_quote' == $quote->post_type ) {
            switch ($email_type) {
                case 'user_quote_accepted':
                case 'user_quote_declined':
                    $recipient = wpinv_get_admin_email();
                    break;
                case 'user_quote':
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
     * @param int $quote_id ID of post/quote
     * @param string $new_status new status of quote
     * @param string $old_status old status of quote
     */
    function wpinv_quote_record_status_change($quote_id, $new_status, $old_status)
    {
        if ('wpi_quote' != get_post_type($quote_id)) {
            return;
        }

        $quote = wpinv_get_invoice($quote_id);

        $old_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($old_status);
        $new_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($new_status);

        $status_change = sprintf(__('Quote status changed from %s to %s', 'wpinv-quotes'), $old_status_nicename, $new_status_nicename);

        // Add note
        $quote->add_note($status_change, false, false, true);

        if (is_admin()) { // Actions for quote status change from admin side
            switch ($new_status) {
                case 'wpi-quote-accepted':
                    $this->process_quote_published($quote_id);
                    break;
                case 'wpi-quote-declined':
                    $this->process_quote_declined($quote_id);
                    break;
            }
        }

        return;
    }

    /**
     * process when quote accepted
     *
     * @since    1.0.0
     * @param int $quote_id ID of post/quote
     */
    function process_quote_published($quote_id)
    {
        if (empty($quote_id)) return;

        do_action('wpinv_quote_before_process_published', $quote_id);

        $accepted_action = wpinv_get_option('accepted_quote_action');
        $gateway = wpinv_get_default_gateway();
        $new_invoice_id = 0;

        if ($accepted_action === 'convert' || $accepted_action === 'convert_send' || empty($accepted_action)) {

            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-quote-accepted',
            ));

            $this->wpinv_user_quote_accepted_notification($quote_id);

            //convert quote to invoice
            set_post_type($quote_id, 'wpi_invoice');

            $number = wpinv_update_invoice_number($quote_id, true);
            if (empty($number)) {
                $number = wpinv_format_invoice_number($quote_id);
            }

            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-pending',
                'post_title' => $number,
                'post_name' => wpinv_generate_post_name( $quote_id ),
            ));

            //update meta data
            update_post_meta($quote_id, '_wpinv_number', $number);
            update_post_meta($quote_id, '_wpinv_gateway', $gateway);

            $quote = wpinv_get_invoice($quote_id);
            $quote->add_note(sprintf(__('Converted from Quote #%s to Invoice.', 'wpinv-quotes'), $quote_id), false, false, true);

            if ($accepted_action === 'convert_send') {
                global $wpinv_quote, $post;
                $prev_wpinv_quote = $wpinv_quote;
                $prev_post = $post;
                $wpinv_quote = $quote;
                $post = get_post($quote_id);
                wpinv_user_invoice_notification($quote_id);
                $wpinv_quote = $prev_wpinv_quote;
                $post = $prev_post;
            }

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');

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
                'post_status' => 'wpi-pending',
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

            $number = wpinv_update_invoice_number($new_invoice_id, true);
            if (empty($number)) {
                $number = wpinv_format_invoice_number($new_invoice_id);
            }
            $post_name = wpinv_generate_post_name($new_invoice_id);

            $quote = wpinv_get_invoice($new_invoice_id);
            $quote->add_note(sprintf(__('Created Invoice from Quote #%s.', 'wpinv-quotes'), $quote_id), false, false, true);

            // Update post title and date
            wp_update_post(array(
                'ID' => $new_invoice_id,
                'post_title' => $number,
                'post_name' => $post_name,
            ));

            update_post_meta($new_invoice_id, '_wpinv_number', $number);
            update_post_meta($new_invoice_id, '_wpinv_gateway', $gateway);
            update_post_meta($new_invoice_id, '_wpinv_quote_reference_id', $quote_id);

            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-quote-accepted',
            ));

            delete_post_meta($quote_id, '_wpinv_key');
            $quote = wpinv_get_invoice($quote_id);
            $quote->add_note(sprintf(__('Converted from Quote to Invoice #%s.', 'wpinv-quotes'), $new_invoice_id), false, false, true);

            if ($accepted_action === 'duplicate_send') {
                global $wpinv_quote, $post;
                $prev_wpinv_quote = $wpinv_quote;
                $prev_post = $post;
                $wpinv_quote = $quote;
                $post = get_post($quote_id);
                wpinv_user_invoice_notification($new_invoice_id);
                $wpinv_quote = $prev_wpinv_quote;
                $post = $prev_post;
            }

            $this->wpinv_user_quote_accepted_notification($quote_id);

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');

        } else {
            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-quote-accepted',
            ));

            $this->wpinv_user_quote_accepted_notification($quote_id);

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');
        }

        do_action('wpinv_quote_after_process_published', $quote_id, $new_invoice_id);
    }

    /**
     * Notify when quote accepted
     *
     * @since    1.0.0
     * @param int $quote_id ID of post/quote
     * @return bool $sent is mail sent or not
     */
    function wpinv_user_quote_accepted_notification($quote_id)
    {
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

        return $sent;
    }

    /**
     * process when quote declined
     *
     * @since    1.0.0
     * @param int $quote_id ID of post/quote
     */
    function process_quote_declined($quote_id = 0, $reason = '')
    {
        if (empty($quote_id)) return;
        do_action('wpinv_quote_before_process_declined', $quote_id, $reason);
        $this->wpinv_quote_decrease_the_discounts($quote_id);
        // make the quote as declined
        wp_update_post(array(
            'ID' => $quote_id,
            'post_status' => 'wpi-quote-declined',
        ));
        $this->wpinv_user_quote_declined_notification($quote_id, $reason);
        do_action('wpinv_quote_after_process_declined', $quote_id, $reason);
    }

    /**
     * Function to decrease the discount if quote declined
     *
     * @since    1.0.0
     * @param int $quote_id ID of post/quote
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
     * @param int $quote_id ID of post/quote
     * @return bool $sent is mail sent or not
     */
    function wpinv_user_quote_declined_notification($quote_id, $reason = '')
    {
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

        return $sent;
    }

    /**
     * Add quote status change note
     *
     * @since    1.0.0
     * @param array $data url data
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

        $old_status = 'wpi-quote-pending';
        $reason = '';

        if ($data['action'] == 'accept') {
            $new_status = 'wpi-quote-accepted';
            $check_nonce = 'wpinv_client_accept_quote_nonce';

        } elseif ($data['action'] == 'decline') {
            $new_status = 'wpi-quote-declined';
            $check_nonce = 'wpinv_client_decline_quote_nonce';
            $reason = ! empty( $_POST['wpq_decline_reason'] ) ? esc_textarea( $_POST['wpq_decline_reason'] ) : '';
        }

        if (!wp_verify_nonce($data['_wpnonce'], $check_nonce)) {
            return;
        }

        do_action('wpinv_front_quote_actions_before_process', $quote_id, $old_status, $new_status);

        $quote = wpinv_get_invoice($quote_id);

        $old_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($old_status, $quote);
        $new_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename($new_status, $quote);

        if ( $reason && $new_status == 'wpi-quote-declined') {
            $status_change = wp_sprintf(__('Quote was %s by user. Reason: %s', 'wpinv-quotes'), $new_status_nicename, $reason);
        } else {
            $status_change = wp_sprintf(__('Quote status changed from %s to %s by user.', 'wpinv-quotes'), $old_status_nicename, $new_status_nicename);
        }

        $quote->add_note($status_change, false, false, true);// Add note

        switch ($new_status) {
            case 'wpi-quote-accepted':
                $this->process_quote_published($quote_id);
                break;
            case 'wpi-quote-declined':
                update_post_meta( $quote_id, '_wpinv_quote_decline_reason', $reason );
                $this->process_quote_declined($quote_id, $reason);
                break;
        }

        do_action('wpinv_front_quote_actions_after_process', $quote_id, $old_status, $new_status);

        $permalink =  get_post_permalink($quote_id);
        $key = $quote->get_key();
        if($key){
            $permalink = add_query_arg( 'invoice_key', $key, $permalink);
        }
        wp_redirect($permalink);
        exit();
    }

    /**
     * Provide the filter for updating quote status
     *
     * @since    1.0.0
     * @param bool $update should update quote or not
     * @param int $quote_id ID of post/quote
     * @param string $new_status new status of quote
     * @param string $old_status old status of quote
     * @return bool $update allow quote update
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
     * @param array $data quote data for sending in mail
     */
    function wpinv_send_customer_quote($data = array())
    {
        $quote_id = !empty($data['quote_id']) ? absint($data['quote_id']) : NULL;

        if (empty($quote_id)) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to send quote notification', 'wpinv-quotes'), __('Error', 'wpinv-quotes'), array('response' => 403));
        }

        $sent = $this->wpinv_user_quote_notification($quote_id);

        $status = $sent ? 'email_sent' : 'email_fail';

        $redirect = add_query_arg(array('wpinv-message' => $status, 'wpi_action' => false, 'quote_id' => false));
        wp_redirect($redirect);
        exit;
    }

    /**
     * Change quote details title in email template
     *
     * @since    1.0.0
     * @param string $title label of email field
     * @param object $quote quote object
     * @return string $title new label of email field
     */
    function wpinv_quote_email_details_title($title, $quote)
    {
        if (!empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            $title = __('Quote Details:', 'wpinv-quotes');
        }
        return $title;
    }

    /**
     * Change quote number title.
     *
     * @since    1.0.0
     * @param string $title label of number field
     * @param object $quote quote object
     * @return string $title new label of number field
     */
    function wpinv_quote_number_label($title, $quote)
    {
        if (!empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            $title = __('Quote Number', 'wpinv-quotes');
        }
        return $title;
    }

    /**
     * Change quote date title.
     *
     * @since    1.0.0
     * @param string $title label of date field
     * @param object $quote quote object
     * @return string $title new label of date field
     */
    function wpinv_quote_date_label($title, $quote)
    {
        if (!empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            $title = __('Quote Date', 'wpinv-quotes');
        }
        return $title;
    }

    /**
     * Change quote status title.
     *
     * @since    1.0.0
     * @param string $title label of status field
     * @param object $quote quote object
     * @return string $title new label of status field
     */
    function wpinv_quote_status_label($title, $quote)
    {
        if (!empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            $title = __('Quote Status', 'wpinv-quotes');
        }
        return $title;
    }

    /**
     * Change quote user vat number title.
     *
     * @since    1.0.0
     * @param string $label User vat number title.
     * @param object $quote quote object
     * @param string $vat_name Vat name.
     * @return string $title new label of status field
     */
    function wpinv_quote_user_vat_number_label($title, $quote, $vat_name)
    {
        if (!empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            $title = wp_sprintf( __( 'Quote %s Number', 'wpinv-quotes' ), $vat_name );
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
            add_settings_error('wpinv-quote-notices', 'wpinv-discount-added', __('Quote converted to invoice successfully.', 'wpinv-quotes'), 'updated');
            settings_errors('wpinv-quote-notices');
        }
    }

    /**
     * Allow all post with custom status in quote listing
     *
     * @since    1.0.0
     */
    function wpinv_quote_request( $vars ) {
        global $typenow, $wp_post_statuses;

        if ( 'wpi_quote' === $typenow ) {
            if ( !isset( $vars['post_status'] ) ) {
                $post_statuses = Wpinv_Quotes_Shared::wpinv_get_quote_statuses();

                foreach ( $post_statuses as $status => $value ) {
                    if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
                        unset( $post_statuses[ $status ] );
                    }
                }

                $vars['post_status'] = array_keys( $post_statuses );
            }
        }

        return $vars;
    }

    function wpinv_quote_post_name_prefix( $prefix, $post_type ) {
        if ( $post_type == 'wpi_quote' ) {
            $prefix = 'quote-';
        }

        return $prefix;
    }

    function wpinv_save_number_post_saved( $post_ID, $post, $update ) {
        global $wpdb;

        if ( !$update && !get_post_meta( $post_ID, '_wpinv_number', true ) ) {
            Wpinv_Quotes_Shared::wpinv_update_quote_number( $post_ID, $post->post_status != 'auto-draft' );
        }

        if ( !$update ) {
            $wpdb->update( $wpdb->posts, array( 'post_name' => wpinv_generate_post_name( $post_ID ) ), array( 'ID' => $post_ID ) );
            clean_post_cache( $post_ID );
        }

        $valid_date = isset( $_POST['wpinv_valid_date'] ) ? sanitize_text_field( $_POST['wpinv_valid_date'] ) : '';
        update_post_meta($post_ID, 'wpinv_quote_valid_until', $valid_date);
    }

    function wpinv_save_number_post_updated( $post_ID, $post_after, $post_before ) {
        if ( !empty( $post_after->post_type ) && $post_after->post_type == 'wpi_quote' && $post_before->post_status == 'auto-draft' && $post_after->post_status != $post_before->post_status ) {
            Wpinv_Quotes_Shared::wpinv_update_quote_number( $post_ID, true );
        }
    }

    function wpinv_pre_format_quote_number( $value, $number, $type ) {
        if ( $type == 'wpi_quote' ) {
            $value = Wpinv_Quotes_Shared::wpinv_format_quote_number( $number );
        }

        return $value;
    }

    function wpinv_pre_check_sequential_number_active( $value, $type ) {
        if ( $type == 'wpi_quote' ) {
            $value = Wpinv_Quotes_Shared::wpinv_sequential_number_active();
        }

        return $value;
    }

    function wpinv_get_pre_next_quote_number( $value, $type ) {
        if ( $type == 'wpi_quote' ) {
            $value = Wpinv_Quotes_Shared::wpinv_get_next_quote_number();
        }

        return $value;
    }

    function wpinv_pre_clean_quote_number( $value, $number, $type ) {
        if ( $type == 'wpi_quote' ) {
            $value = Wpinv_Quotes_Shared::wpinv_clean_quote_number( $number );
        }

        return $value;
    }

    function wpinv_pre_update_quote_number( $value, $post_ID, $save_sequential, $type ) {
        if ( $type == 'wpi_quote' ) {
            $value = Wpinv_Quotes_Shared::wpinv_update_quote_number( $post_ID, $save_sequential );
        }

        return $value;
    }

    function quote_to_invoice_redirect() {
        if ( !empty( $_GET['invoice_key'] ) && is_404() && get_query_var( 'post_type' ) == 'wpi_quote' ) {
            if ( $invoice_id = wpinv_get_invoice_id_by_key( sanitize_text_field( $_GET['invoice_key'] ) ) ) {
                $redirect = get_permalink( $invoice_id );
                $redirect = add_query_arg( $_GET, $redirect );
                wp_redirect( $redirect, 301 ); // Permanent redirect
                exit;
            }
        }
    }

    function wpinv_quote_email_format_text( $replace_array, $content, $quote ) {
        if ( empty( $quote ) ) {
            return $replace_array;
        }
        
        if ( is_int( $quote ) ) {
            $quote = wpinv_get_invoice( $quote );
        }
        
        if ( ! ( ! empty( $quote ) && is_object( $quote ) && ! empty( $quote->ID ) ) ) {
            return $replace_array;
        }

        if ( 'wpi_quote' != $quote->post_type ) {
            return $replace_array;
        }

        $replace_array['{quote_number}']            = $quote->get_number();
        $replace_array['{quote_date}']              = $quote->get_invoice_date();
        $replace_array['{quote_link}']              = $quote->get_view_url( true );
        $replace_array['{valid_until}']             = $this->get_valid_date( true, $quote->ID );
        $replace_array['{quote_decline_reason}']    = $quote->post_status == 'wpi-quote-declined' ? get_post_meta( $quote->ID, '_wpinv_quote_decline_reason', true ) : '';

        return $replace_array;
    }

    function get_valid_date( $display = false, $quote_id ) {
        $valid_date = get_post_meta($quote_id, 'wpinv_quote_valid_until', true);
        $valid_date = apply_filters( 'wpinv_valid_date', $valid_date, $quote_id );

        if ( !$display || empty( $valid_date ) ) {
            return $valid_date;
        }

        return date_i18n( get_option( 'date_format' ), strtotime( $valid_date ) );
    }

    function wpinv_meta_box_details_after_due_date($quote_id = 0) {
        $quote = wpinv_get_invoice($quote_id);
        if ( $quote && 'wpi_quote' != $quote->post_type ) {
            return;
        }

        $date_created       = $quote->get_created_date();
        $datetime_created   = strtotime( $date_created );
        $date_completed     = $quote->get_completed_date();
        $date_completed     = $date_completed != '' && $date_completed != '0000-00-00 00:00:00' ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date_completed ) ) : 'n/a';
        $valid = $this->get_valid_date(false, $quote_id);

        if($quote->has_status( array( 'auto-draft', 'wpi-quote-pending' ) )){
        ?>
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-date-valid">
            <div class="gdmbx-th"><label for="wpinv_valid_date"><?php _e( 'Valid Until Date:', 'wpinv-quotes' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" placeholder="<?php esc_attr_e( 'yyyy-mm-dd', 'wpinv-quotes' );?>" value="<?php echo esc_attr( $valid );?>" id="wpinv_valid_date" name="wpinv_valid_date" class="regular-text wpiDatepicker" data-minDate="<?php echo esc_attr( date_i18n( 'Y-m-d', $datetime_created ) );?>" data-dateFormat="yy-mm-dd">
            </div>
        </div>
        <?php }
        if ( $date_completed && $date_completed != 'n/a' ) {
            $valid_display = $this->get_valid_date(false, $quote_id);
            ?>
            <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-date-completed">
                <div class="gdmbx-th"><label><?php _e( 'Valid Untill Date:', 'wpinv-quotes' );?></label></div>
                <div class="gdmbx-td"><?php echo $valid_display;?></div>
            </div>
        <?php }
    }

    function wpinv_display_details_after_due_date( $quote_id = 0 ) {
        $quote = wpinv_get_invoice($quote_id);
        if ( $quote && 'wpi_quote' != $quote->post_type ) {
            return;
        }
        $valid = $this->get_valid_date(true, $quote_id);
        if(isset($valid) && !empty($valid)) {
            ?>
            <tr class="wpi-row-date">
                <th><?php echo apply_filters('wpinv_quote_valid_until_date_label', __('Valid Until Date', 'wpinv-quotes'), $quote); ?></th>
                <td><?php echo $valid; ?></td>
            </tr>
            <?php
        }
    }

    function wpinv_email_invoice_details_after_due_date( $quote_id = 0 ) {
        $quote = wpinv_get_invoice($quote_id);
        if ( $quote && 'wpi_quote' != $quote->post_type ) {
            return;
        }
        $valid = $this->get_valid_date(true, $quote_id);
        if(isset($valid) && !empty($valid)) {
            ?>
            <tr>
                <td><?php echo apply_filters('wpinv_quote_valid_until_date_label', __('Valid Until Date', 'wpinv-quotes'), $quote); ?></td>
                <td><?php echo wp_sprintf('<time datetime="%s">%s</time>', date_i18n('c', strtotime($valid)), $valid); ?></td>
            </tr>
            <?php
        }
    }

    function wpinv_settings_email_wildcards_description( $description, $active_tab, $section ) {

        if ( 'emails' == $active_tab && in_array($section, array('user_quote','user_quote_accepted','user_quote_declined','user_note')) ) {
            $description .= __( '<strong>{quote_number} :</strong> The quote number<br>
                <strong>{quote_link} :</strong> The quote link<br>
                <strong>{quote_date} :</strong> The date the quote was created<br>
                <strong>{valid_until} :</strong> The date the quote is valid until<br>', 'wpinv-quotes' );
        }
        if ( 'emails' == $active_tab && in_array($section, array('user_quote', 'user_quote_declined', 'user_note')) ) {
            $description .= __( '<strong>{quote_decline_reason} :</strong> The reason for declining the quote<br>', 'wpinv-quotes' );
        }

        return $description;
    }
    
    function wpinv_quote_items_actions($item_actions, $quote, $post){
        if (!empty($post->ID) && 'wpi_quote' == $post->post_type || !empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            if($quote->has_status(array('wpi-quote-accepted','wpi-quote-declined')) || in_array($post->post_status, array('wpi-quote-accepted','wpi-quote-declined'))){
                return '';
            }
        }
        return $item_actions;
    }

    function wpinv_quote_disable_apply_discount($disable_discount, $quote){
        if (!empty($quote->ID) && 'wpi_quote' == $quote->post_type) {
            if($quote->has_status(array('wpi-quote-accepted','wpi-quote-declined'))){
                return true;
            }
        }
        return $disable_discount;
    }
}