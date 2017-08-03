<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 */

/**
 * Class with shared functions which can be used for admin and public both
 *
 * @since      1.0.0
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */

/**
 * Calls the class.
 */
function wpinv_quote_call_shared_class()
{
    new Wpinv_Quotes_Shared();

}

add_action('wpinv_quotes_loaded', 'wpinv_quote_call_shared_class', 2);

class Wpinv_Quotes_Shared
{
    /**
     * @var  object  Instance of this class
     */
    protected static $instance;

    private static $quote_statuses = array();

    public function __construct()
    {

        add_action('wpinv_statuses', array($this, 'wpinv_quote_statuses'), 99);

        self::$quote_statuses = apply_filters('wpinv_quote_statuses', array(
            'pending' => __('Pending', 'invoicing'),
            'wpi-quote-sent' => __('Sent', 'invoicing'),
            'wpi-quote-accepted' => __('Accepted', 'invoicing'),
            'wpi-quote-cancelled' => __('Cancelled', 'invoicing'),
            'wpi-quote-declined' => __('Declined', 'invoicing'),
        ));

    }

    public static function get_instance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add statuses to the dropdown in quote details metabox
     *
     * @since    1.0.0
     * @return array quote statuses
     */
    public function wpinv_get_quote_statuses()
    {
        return self::$quote_statuses;
    }

    /**
     * Add statuses to the dropdown in quote details metabox
     *
     * @since    1.0.0
     *
     */
    public function wpinv_quote_statuses($quote_statuses)
    {
        global $post;
        if ($post->post_type == 'wpi_quote' && !empty($post->ID)) {
            return self::$quote_statuses;
        }
        //return array_merge($quote_statuses,self::$quote_statuses);
        return $quote_statuses;
    }

    /**
     * Get quote status nicename
     *
     * @since    1.0.0
     * @param string $status status to get nice name of
     * @return string $status nicename of status
     */
    function wpinv_quote_status_nicename($status)
    {
        $statuses = self::$quote_statuses;
        $status = isset($statuses[$status]) ? $statuses[$status] : __($status, 'invoicing');

        return $status;
    }

    /**
     * Get quote history columns
     *
     * @since    1.0.0
     * @return array $columns columns for displaying in quote history page
     */
    function wpinv_get_user_quote_columns()
    {
        $columns = array(
            'quote-number' => array('title' => __('ID', 'invoicing'), 'class' => 'text-left'),
            'quote-date' => array('title' => __('Date', 'invoicing'), 'class' => 'text-left'),
            'quote-status' => array('title' => __('Status', 'invoicing'), 'class' => 'text-center'),
            'quote-total' => array('title' => __('Total', 'invoicing'), 'class' => 'text-right'),
            'quote-actions' => array('title' => '&nbsp;', 'class' => 'text-center'),
        );

        return apply_filters('wpinv_user_quotes_columns', $columns);
    }

    /**
     * Get quote history quote data
     *
     * @since    1.0.0
     * @param array $args to retrive quotes
     * @return object post object of all matching quotes
     */
    function wpinv_get_quotes($args)
    {
        $args = wp_parse_args($args, array(
            'status' => array_keys(self::$quote_statuses),
            'type' => 'wpi_quote',
            'parent' => null,
            'user' => null,
            'email' => '',
            'limit' => get_option('posts_per_page'),
            'offset' => null,
            'page' => 1,
            'exclude' => array(),
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
            'paginate' => false,
        ));

        // Handle some BW compatibility arg names where wp_query args differ in naming.
        $map_legacy = array(
            'numberposts' => 'limit',
            'post_type' => 'type',
            'post_status' => 'status',
            'post_parent' => 'parent',
            'author' => 'user',
            'posts_per_page' => 'limit',
            'paged' => 'page',
        );

        foreach ($map_legacy as $from => $to) {
            if (isset($args[$from])) {
                $args[$to] = $args[$from];
            }
        }

        $wpinv_cpt = $_REQUEST['wpinv-cpt'];

        if (get_query_var('paged') && 'wpi_quote' == $wpinv_cpt)
            $args['page'] = get_query_var('paged');
        else if (get_query_var('page') && 'wpi_quote' == $wpinv_cpt)
            $args['page'] = get_query_var('page');
        else if (!empty($args['page']))
            $args['page'] = $args['page'];
        else
            $args['page'] = 1;

        /**
         * Generate WP_Query args. This logic will change if orders are moved to
         * custom tables in the future.
         */
        $wp_query_args = array(
            'post_type' => 'wpi_quote',
            'post_status' => $args['status'],
            'posts_per_page' => $args['limit'],
            'meta_query' => array(),
            'date_query' => !empty($args['date_query']) ? $args['date_query'] : array(),
            'fields' => 'ids',
            'orderby' => $args['orderby'],
            'order' => $args['order'],
        );

        if (!empty($args['user'])) {
            $wp_query_args['author'] = absint($args['user']);
        }

        if (!is_null($args['parent'])) {
            $wp_query_args['post_parent'] = absint($args['parent']);
        }

        if (!is_null($args['offset'])) {
            $wp_query_args['offset'] = absint($args['offset']);
        } else {
            $wp_query_args['paged'] = absint($args['page']);
        }

        if (!empty($args['exclude'])) {
            $wp_query_args['post__not_in'] = array_map('absint', $args['exclude']);
        }

        if (!$args['paginate']) {
            $wp_query_args['no_found_rows'] = true;
        }

        // Get results.
        $quotes = new WP_Query($wp_query_args);

        if ('objects' === $args['return']) {
            $return = array_map('wpinv_get_invoice', $quotes->posts);
        } elseif ('self' === $args['return']) {
            return $quotes;
        } else {
            $return = $quotes->posts;
        }

        if ($args['paginate']) {
            return (object)array(
                'quotes' => $return,
                'total' => $quotes->found_posts,
                'max_num_pages' => $quotes->max_num_pages,
            );
        } else {
            return $return;
        }
    }

    /**
     * Get url to accept quote from front side
     *
     * @since    1.0.0
     * @param int $quote_id ID of quote
     * @return string $url url for accept quote button
     */
    function get_accept_quote_url($quote_id)
    {
        $nonce = wp_create_nonce('wpinv_client_accept_quote_nonce');
        $url = get_permalink($quote_id) . '?wpi_action=quote_action&action=accept&qid=' . $quote_id . '&_wpnonce=' . $nonce;
        return $url;
    }

    /**
     * Get url to decline quote from front side
     *
     * @since    1.0.0
     * @param int $quote_id ID of quote
     * @return string $url url for decline quote button
     */
    function get_decline_quote_url($quote_id)
    {
        $nonce = wp_create_nonce('wpinv_client_decline_quote_nonce');
        $url = get_permalink($quote_id) . '?wpi_action=quote_action&action=decline&qid=' . $quote_id . '&_wpnonce=' . $nonce;
        return $url;
    }

}