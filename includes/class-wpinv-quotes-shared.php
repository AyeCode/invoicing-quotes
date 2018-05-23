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
        add_action('wpinv_get_status', array($this, 'wpinv_quote_get_status'), 99, 4);
        add_action('wpinv_setup_invoice', array($this, 'wpinv_quote_setup_quote'), 10, 1);
        add_action( 'init', array( 'Wpinv_Quote_Shortcodes', 'init' ) );

        self::$quote_statuses = apply_filters('wpinv_quote_statuses', array(
            'wpi-quote-pending' => __('Pending', 'wpinv-quotes'),
            'wpi-quote-accepted' => __('Accepted', 'wpinv-quotes'),
            'wpi-quote-declined' => __('Declined', 'wpinv-quotes'),
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
    public static function wpinv_get_quote_statuses()
    {
        return self::$quote_statuses;
    }

    /**
     * Add statuses to the dropdown in quote details metabox
     *
     * @since    1.0.0
     *
     */
    public static function wpinv_quote_statuses( $quote_statuses, $quote = array() ) {
        global $wpinv_quote, $post;

        if ( !empty( $quote ) && 'wpi_invoice' == $quote->post_type ) {
            return $quote_statuses;
        }

        if ( ( !empty( $post->ID ) && 'wpi_quote' == $post->post_type ) || ( !empty( $wpinv_quote->ID ) && 'wpi_quote' == $wpinv_quote->post_type ) ) {
            return self::$quote_statuses;
        }

        return $quote_statuses;
    }

    /**
     * Add statuses to the dropdown in quote details metabox
     *
     * @since    1.0.0
     *
     */
    public static function wpinv_quote_get_status($status, $nicename, $quote_id, $quote)
    {
        if (!empty($quote->ID) && 'wpi_quote' === $quote->post_type) {
            if($nicename){
                return self::wpinv_quote_status_nicename($status);
            } else {
                return $status;
            }
        }
        if (!empty($quote->ID) && 'wpi_invoice' === $quote->post_type) {
            $invoice_statuses = array(
                'wpi-pending' => __( 'Pending Payment', 'invoicing' ),
                'publish' => __( 'Paid', 'invoicing'),
                'wpi-processing' => __( 'Processing', 'invoicing' ),
                'wpi-onhold' => __( 'On Hold', 'invoicing' ),
                'wpi-refunded' => __( 'Refunded', 'invoicing' ),
                'wpi-cancelled' => __( 'Cancelled', 'invoicing' ),
                'wpi-failed' => __( 'Failed', 'invoicing' ),
                'wpi-renewal' => __( 'Renewal Payment', 'invoicing' )
            );
            if($nicename){
                if(isset($invoice_statuses[$status]) && !empty($invoice_statuses[$status])){
                    return $invoice_statuses[$status];
                } else {
                    return $status;
                }
            } else {
                return $status;
            }
        }
        return $status;
    }

    /**
     * Get quote status nicename
     *
     * @since    1.0.0
     * @param string $status status to get nice name of
     * @return string $status nicename of status
     */
    public static function wpinv_quote_status_nicename($status)
    {
        $statuses = self::$quote_statuses;
        $status = isset($statuses[$status]) ? $statuses[$status] : __($status, 'wpinv-quotes');

        return $status;
    }

    /**
     * set global variable to use in add-on
     *
     * @since    1.0.0
     * @param object $quote quote object
     */
    public static function wpinv_quote_setup_quote($quote)
    {
        global $wpinv_quote;
        $wpinv_quote = $quote;
        if('wpi_quote' == $wpinv_quote->post_type){
            $wpinv_quote->status_nicename = self::wpinv_quote_status_nicename( $wpinv_quote->post_status );
        }
    }

    /**
     * Get quote status label for history page
     *
     * @since    1.0.0
     * @param string $status status to get label for
     * @param string $status_display status nicename
     * @return string $label label with status name and class
     */
    public static function wpinv_quote_invoice_status_label( $status, $status_display)
    {
        if ( empty( $status_display ) ) {
            $status_display = self::wpinv_quote_status_nicename( $status );
        }

        switch ( $status ) {
            case 'wpi-quote-accepted' :
                $class = 'label-success';
                break;
            case 'wpi-quote-pending' :
                $class = 'label-primary';
                break;
            case 'wpi-quote-declined' :
                $class = 'label-danger';
                break;
            default:
                $class = 'label-default';
                break;
        }

        $label = '<span class="label label-inv-' . $status . ' ' . $class . '">' . $status_display . '</span>';

        return $label;
    }

    /**
     * Get quote history columns
     *
     * @since    1.0.0
     * @return array $columns columns for displaying in quote history page
     */
    public static function wpinv_get_user_quote_columns()
    {
        $columns = array(
            'quote-number' => array('title' => __('ID', 'wpinv-quotes'), 'class' => 'text-left'),
            'quote-date' => array('title' => __('Date', 'wpinv-quotes'), 'class' => 'text-left'),
            'quote-status' => array('title' => __('Status', 'wpinv-quotes'), 'class' => 'text-center'),
            'quote-total' => array('title' => __('Total', 'wpinv-quotes'), 'class' => 'text-right'),
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
    public static function wpinv_get_quotes($args)
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

        if (get_query_var('paged'))
            $args['page'] = get_query_var('paged');
        else if (get_query_var('page'))
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
    public static function get_accept_quote_url($quote_id)
    {
        $nonce = wp_create_nonce('wpinv_client_accept_quote_nonce');
        $url = get_permalink($quote_id);
        $url = add_query_arg( array(
            'wpi_action' => 'quote_action',
            'action' => 'accept',
            'qid' => $quote_id,
            '_wpnonce' => $nonce,
        ), $url );
        return $url;
    }

    /**
     * Get url to decline quote from front side
     *
     * @since    1.0.0
     * @param int $quote_id ID of quote
     * @return string $url url for decline quote button
     */
    public static function get_decline_quote_url($quote_id)
    {
        $nonce = wp_create_nonce('wpinv_client_decline_quote_nonce');
        $url = get_permalink($quote_id);
        $url = add_query_arg( array(
            'wpi_action' => 'quote_action',
            'action' => 'decline',
            'qid' => $quote_id,
            '_wpnonce' => $nonce,
        ), $url );
        return $url;
    }

    /**
     * Get url of quote history page
     *
     * @since    1.0.0
     * @return string $url url of quote history page
     */
    public static function wpinv_get_quote_history_page_uri() {
        $page_id = wpinv_get_option( 'quote_history_page', 0 );
        $page_id = absint( $page_id );

        return apply_filters( 'wpinv_get_quote_page_uri', get_permalink( $page_id ) );
    }

    /**
     * Check sequential number or not for quote.
     *
     * @since    1.0.1
     *
     * @return   bool True if active else False.
     */
    public static function wpinv_sequential_number_active() {
        return wpinv_get_option( 'sequential_quote_number' );
    }

    public static function wpinv_update_quote_number( $post_ID, $save_sequential = false ) {
        global $wpdb;

        if ( self::wpinv_sequential_number_active() ) {
            $number = self::wpinv_get_next_quote_number();

            if ( $save_sequential ) {
                update_option( 'wpinv_last_quote_number', $number );
            }
        } else {
            $number = $post_ID;
        }

        $number = self::wpinv_format_quote_number( $number );

        update_post_meta( $post_ID, '_wpinv_number', $number );

        $wpdb->update( $wpdb->posts, array( 'post_title' => $number ), array( 'ID' => $post_ID ) );

        clean_post_cache( $post_ID );

        return $number;
    }

    public static function wpinv_get_next_quote_number() {
        if ( ! self::wpinv_sequential_number_active() ) {
            return false;
        }

        $number = $last_number = get_option( 'wpinv_last_quote_number' );
        $start  = wpinv_get_option( 'quote_sequence_start' );
        if ( !absint( $start ) > 0 ) {
            $start = 1;
        }
        $increment_number = true;
        $save_number = false;

        if ( !empty( $number ) && !is_numeric( $number ) && $number == self::wpinv_format_quote_number( $number ) ) {
            $number = self::wpinv_clean_quote_number( $number );
        }

        if ( empty( $number ) ) {
            if ( !( $last_number === 0 || $last_number === '0' ) ) {
                $quote_statuses = array_keys( self::wpinv_get_quote_statuses() );
                $quote_statuses[] = 'trash';
                $last_quote = self::wpinv_get_quotes( array( 'limit' => 1, 'order' => 'DESC', 'orderby' => 'ID', 'return' => 'posts', 'fields' => 'ids', 'status' => $quote_statuses ) );

                if ( !empty( $last_quote[0] ) && $quote_number = wpinv_get_invoice_number( $last_quote[0] ) ) {
                    if ( is_numeric( $quote_number ) ) {
                        $number = $quote_number;
                    } else {
                        $number = self::wpinv_clean_quote_number( $quote_number );
                    }
                }

                if ( empty( $number ) ) {
                    $increment_number = false;
                    $number = $start;
                    $save_number = ( $number - 1 );
                } else {
                    $save_number = $number;
                }
            }
        }

        if ( $start > $number ) {
            $increment_number = false;
            $number = $start;
            $save_number = ( $number - 1 );
        }

        if ( $save_number !== false ) {
            update_option( 'wpinv_last_quote_number', $save_number );
        }

        $increment_number = apply_filters( 'wpinv_increment_payment_quote_number', $increment_number, $number );

        if ( $increment_number ) {
            $number++;
        }

        return apply_filters( 'wpinv_get_next_quote_number', $number );
    }

    public static function wpinv_clean_quote_number( $number ) {
        $prefix  = wpinv_get_option( 'quote_number_prefix' );
        $postfix = wpinv_get_option( 'quote_number_postfix' );

        $number = preg_replace( '/' . $prefix . '/', '', $number, 1 );

        $length      = strlen( $number );
        $postfix_pos = strrpos( $number, $postfix );

        if ( false !== $postfix_pos ) {
            $number      = substr_replace( $number, '', $postfix_pos, $length );
        }

        $number = intval( $number );

        return apply_filters( 'wpinv_clean_quote_number', $number, $prefix, $postfix );
    }

    /**
     * Send customer quote email notification if Send Quote is selected "yes"
     *
     * @since    1.0.0
     * @param int $number quote id
     * @return string $formatted_number change formatted number of quote
     */
    public static function wpinv_format_quote_number($number)
    {
        if ( !empty( $number ) && !is_numeric( $number ) ) {
            return $number;
        }

        $padd  = wpinv_get_option( 'quote_number_padd' );
        $prefix  = wpinv_get_option( 'quote_number_prefix' );
        $postfix = wpinv_get_option( 'quote_number_postfix' );

        $padd = absint( $padd );
        $formatted_number = absint( $number );

        if ( $padd > 0 ) {
            $formatted_number = zeroise( $formatted_number, $padd );
        }

        $formatted_number = $prefix . $formatted_number . $postfix;

        return apply_filters( 'wpinv_format_quote_number', $formatted_number, $number, $prefix, $postfix, $padd );
    }
}