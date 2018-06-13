<?php
/**
 * Personal data exporters.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPInv_Privacy_Exporters Class.
 */
class WPInv_Quotes_Privacy_Exporters {
    /**
     * Finds and exports customer data by email address.
     *
     * @since 1.0.13
     * @param string $email_address The user email address.
     * @param int    $page  Page.
     * @return array An array of quote data in name value pairs
     */
    public static function customer_quotes_data_exporter( $email_address, $page ) {
        $done           = false;
        $page           = (int) $page;
        $data_to_export = array();

        $user           = get_user_by( 'email', $email_address );
        if ( ! $user instanceof WP_User ) {
            return array(
                'data' => $data_to_export,
                'done' => true,
            );
        }

        $args    = array(
            'limit'    => 30,
            'page'     => $page,
            'user'     => $user->ID,
        );

        $quotes = Wpinv_Quotes_Shared::wpinv_get_quotes( $args );

        if ( 0 < count( $quotes ) ) {
            foreach ( $quotes as $quote ) {
                $data_to_export[] = array(
                    'group_id'    => 'customer_quotes',
                    'group_label' => __( 'Quotes Data', 'wpinv-quotes' ),
                    'item_id'     => "wpinv-quote-{$quote->ID}",
                    'data'        => self::get_customer_quote_data( $quote ),
                );
            }
            $done = 30 > count( $quotes );
        } else {
            $done = true;
        }

        return array(
            'data' => $data_to_export,
            'done' => $done,
        );
    }

    /**
     * Get quote data (key/value pairs) for a user.
     *
     * @since 1.0.13
     * @param WPInv_Invoice $quote quote object.
     * @return array
     */
    public static function get_customer_quote_data( $quote ) {
        $personal_data = array();

        $props_to_export = array(
            'number'               => __( 'Quote Number', 'wpinv-quotes' ),
            'created_date'         => __( 'Quote Date', 'wpinv-quotes' ),
            'valid_date'           => __( 'Quote Valid Till', 'wpinv-quotes' ),
            'status'               => __( 'Quote Status', 'wpinv-quotes' ),
            'total'                => __( 'Quote Total', 'wpinv-quotes' ),
            'items'                => __( 'Quote Items', 'wpinv-quotes' ),
            'first_name'           => __( 'First Name', 'wpinv-quotes' ),
            'last_name'            => __( 'Last Name', 'wpinv-quotes' ),
            'email'                => __( 'Email Address', 'wpinv-quotes' ),
            '_wpinv_company'       => __( 'Company', 'wpinv-quotes' ),
            'phone'                => __( 'Phone Number', 'wpinv-quotes' ),
            'address'              => __( 'Address', 'wpinv-quotes' ),
            '_wpinv_city'          => __( 'City', 'wpinv-quotes' ),
            '_wpinv_country'       => __( 'Country', 'wpinv-quotes' ),
            '_wpinv_state'         => __( 'State', 'wpinv-quotes' ),
            '_wpinv_zip'           => __( 'Zip Code', 'wpinv-quotes' ),
            'ip'                   => __( 'IP Address', 'wpinv-quotes' ),
            'view_url'             => __( 'Quote Link', 'wpinv-quotes' ),
        );

        $props_to_export = apply_filters( 'wpinv_privacy_export_quote_personal_data_props', $props_to_export, $quote);

        foreach ( $props_to_export as $prop => $name ) {
            $value = '';

            switch ( $prop ) {
                case 'valid_date':
                    $valid_date = get_post_meta($quote->ID, 'wpinv_quote_valid_until', true);
                    $valid_date = apply_filters( 'wpinv_valid_date', $valid_date, $quote->ID );
                    $value = date_i18n( get_option( 'date_format' ), strtotime( $valid_date ) );
                    break;
                case 'items':
                    $item_names = array();
                    foreach ( $quote->get_cart_details() as $key => $cart_item ) {
                        $item_quantity  = $cart_item['quantity'] > 0 ? absint( $cart_item['quantity'] ) : 1;
                        $item_names[] = $cart_item['name'] . ' x ' . $item_quantity;
                    }
                    $value = implode( ', ', $item_names );
                    break;
                case 'status':
                    $value = $quote->get_status(true);
                    break;
                case 'total':
                    $value = $quote->get_total(true);
                    break;
                default:
                    if ( is_callable( array( $quote, 'get_' . $prop ) ) ) {
                        $value = $quote->{"get_$prop"}();
                    } else {
                        $value = $quote->get_meta($prop);
                    }
                    break;
            }

            $value = apply_filters( 'wpi_privacy_export_quote_personal_data_prop', $value, $prop, $quote );

            if ( $value ) {
                $personal_data[] = array(
                    'name'  => $name,
                    'value' => $value,
                );
            }

        }

        $personal_data = apply_filters( 'wpinv_privacy_export_quote_personal_data', $personal_data, $quote );

        return $personal_data;

    }

}
