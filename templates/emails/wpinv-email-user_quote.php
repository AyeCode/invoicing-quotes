<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

?>
<p><?php printf( __( 'A quote has been created for you on %s.', 'invoicing' ), wpinv_get_business_name()); ?></p> <?php

require_once WP_PLUGIN_DIR.'/wpinv-quotes/templates/emails/wpinv-email-header.php';

require_once WP_PLUGIN_DIR.'/wpinv-quotes/templates/emails/wpinv-email-quote-details.php';

require_once WP_PLUGIN_DIR.'/wpinv-quotes/templates/emails/wpinv-email-quote-items.php';

require_once WP_PLUGIN_DIR.'/wpinv-quotes/templates/emails/wpinv-email-billing-details.php';

require_once WP_PLUGIN_DIR.'/wpinv-quotes/templates/emails/wpinv-email-header.php';