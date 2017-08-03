<?php
/**
 * Define functions for Export Quote data
 *
 * @since      1.0.0
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Calls the class.
 */
function wpinv_quote_call_quote_report_class()
{
    new WPInv_Quote_Reports();

}

add_action('wpinv_quotes_loaded', 'wpinv_quote_call_quote_report_class', 2);

class WPInv_Quote_Reports
{
    public $filetype;
    public $per_page;
    private $section = 'wpinv_reports';
    private $wp_filesystem;
    private $export_dir;
    private $export_url;
    private $export;

    public function __construct()
    {
        $this->init();
        $this->quote_actions();
    }

    public function init()
    {
        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
            global $wp_filesystem;
        }
        $this->wp_filesystem = $wp_filesystem;

        $this->export_dir = $this->quote_export_location();
        $this->export_url = $this->quote_export_location(true);
        $this->export = apply_filters('wpinv_export_type', 'invoicing');
        $this->filetype = 'csv';
        $this->per_page = 2;

        do_action('wpinv_class_reports_init', $this);
    }

    /**
     * Returns export location path
     *
     * @since    1.0.0
     * @access   public
     * @param    bool $relative for relative path
     * @return   string Path of the export location
     */
    public function quote_export_location($relative = false)
    {
        $upload_dir = wp_upload_dir();
        $export_location = $relative ? trailingslashit($upload_dir['baseurl']) . 'cache' : trailingslashit($upload_dir['basedir']) . 'cache';
        $export_location = apply_filters('wpinv_export_location', $export_location, $relative);

        return trailingslashit($export_location);
    }

    /**
     * Defines actions for quote export
     *
     * @since    1.0.0
     * @access   public
     */
    public function quote_actions()
    {
        if (is_admin()) {
            add_action('wp_ajax_wpinv_quote_ajax_export', array($this, 'quote_ajax_export'));
            add_action('wpinv_reports_tab_export_content_bottom', array($this, 'quote_export'));

            // Export Invoices.
            add_action('wpinv_export_set_params_quotes', array($this, 'set_quotes_export'));
            add_filter('wpinv_export_get_columns_quotes', array($this, 'get_quotes_columns_cb'));
            add_filter('wpinv_export_get_data_quotes', array($this, 'get_quotes_data'));
            add_filter('wpinv_get_export_status_quotes', array($this, 'quotes_export_status'));
        }
        do_action('wpinv_class_reports_actions', $this);
    }

    /**
     * Display form in export tab
     *
     * @since    1.0.0
     * @access   public
     */
    public function quote_export()
    {
        $statuses = Wpinv_Quotes_Shared::wpinv_get_quote_statuses();
        $statuses = array_merge(array('any' => __('All Statuses', 'invoicing')), $statuses);
        ?>
        <div class="postbox wpi-export-invoices">
            <h2 class="hndle ui-sortabled-handle"><span><?php _e('Quotes', 'invoicing'); ?></span></h2>
            <div class="inside">
                <p><?php _e('Download a CSV of all quotes.', 'invoicing'); ?></p>
                <form id="wpi-export-quotes" class="wpi-quote-export-form" method="post">
                    <?php echo wpinv_html_date_field(array(
                            'id' => 'wpi_quote_export_from_date',
                            'name' => 'quote_from_date',
                            'data' => array(
                                'dateFormat' => 'yy-mm-dd'
                            ),
                            'placeholder' => __('From date', 'invoicing'))
                    ); ?>
                    <?php echo wpinv_html_date_field(array(
                            'id' => 'wpi_quote_export_to_date',
                            'name' => 'quote_to_date',
                            'data' => array(
                                'dateFormat' => 'yy-mm-dd'
                            ),
                            'placeholder' => __('To date', 'invoicing'))
                    ); ?>
                    <span id="wpinv-status-wrap">
                                <?php echo wpinv_html_select(array(
                                    'options' => $statuses,
                                    'name' => 'quote_status',
                                    'id' => 'wpi_quote_export_status',
                                    'show_option_all' => false,
                                    'show_option_none' => false,
                                    'class' => '',
                                )); ?>
                                <?php wp_nonce_field('wpi_ajax_quote_export', 'wpi_ajax_quote_export'); ?>
                                </span>
                    <span id="wpinv-submit-wrap">
                        <input type="hidden" value="quotes" name="export"/>
                        <input type="submit" value="<?php _e('Generate CSV', 'invoicing'); ?>" class="button-primary"/>
                    </span>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Export the data to csv file
     *
     * @since    1.0.0
     * @access   public
     */
    public function quote_ajax_export()
    {
        $response = array();
        $response['success'] = false;
        $response['msg'] = __('Invalid export request found.', 'invoicing');

        if (empty($_POST['data']) || !current_user_can('manage_options')) {
            wp_send_json($response);
        }

        parse_str($_POST['data'], $data);

        $data['step'] = !empty($_POST['step']) ? absint($_POST['step']) : 1;

        $_REQUEST = (array)$data;
        if (!(!empty($_REQUEST['wpi_ajax_quote_export']) && wp_verify_nonce($_REQUEST['wpi_ajax_quote_export'], 'wpi_ajax_quote_export'))) {
            $response['msg'] = __('Security check failed.', 'invoicing');
            wp_send_json($response);
        }

        if (($error = $this->check_quote_export_location(true)) !== true) {
            $response['msg'] = __('Filesystem ERROR: ' . $error, 'invoicing');
            wp_send_json($response);
        }

        $this->set_quote_export_params($_REQUEST);

        $return = $this->process_quote_export_step();
        $done = $this->get_export_status();

        if ($return) {
            $this->step += 1;

            $response['success'] = true;
            $response['msg'] = '';

            if ($done >= 100) {
                $this->step = 'done';
                $new_filename = 'wpi-' . $this->export . '-' . date('y-m-d-H-i') . '.' . $this->filetype;
                $new_file = $this->export_dir . $new_filename;

                if (file_exists($this->file)) {
                    $this->wp_filesystem->move($this->file, $new_file, true);
                }

                if (file_exists($new_file)) {
                    $response['data']['file'] = array('u' => $this->export_url . $new_filename, 's' => size_format(filesize($new_file), 2));
                }
            }

            $response['data']['step'] = $this->step;
            $response['data']['done'] = $done;
        } else {
            $response['msg'] = __('No data found for export.', 'invoicing');
        }

        wp_send_json($response);
    }

    /**
     * Check if the export location has write permission
     *
     * @since    1.0.0
     * @access   public
     * @return   bool if location is writable
     */
    public function check_quote_export_location()
    {
        try {
            if (empty($this->wp_filesystem)) {
                return __('Filesystem ERROR: Could not access filesystem.', 'invoicing');
            }

            if (is_wp_error($this->wp_filesystem)) {
                return __('Filesystem ERROR: ' . $this->wp_filesystem->get_error_message(), 'invoicing');
            }

            $is_dir = $this->wp_filesystem->is_dir($this->export_dir);
            $is_writeable = $is_dir && is_writeable($this->export_dir);

            if ($is_dir && $is_writeable) {
                return true;
            } else if ($is_dir && !$is_writeable) {
                if (!$this->wp_filesystem->chmod($this->export_dir, FS_CHMOD_DIR)) {
                    return wp_sprintf(__('Filesystem ERROR: Export location %s is not writable, check your file permissions.', 'invoicing'), $this->export_dir);
                }

                return true;
            } else {
                if (!$this->wp_filesystem->mkdir($this->export_dir, FS_CHMOD_DIR)) {
                    return wp_sprintf(__('Filesystem ERROR: Could not create directory %s. This is usually due to inconsistent file permissions.', 'invoicing'), $this->export_dir);
                }

                return true;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Set export parameters
     *
     * @since    1.0.0
     * @access   public
     * @param array $request contains post data
     */
    public function set_quote_export_params($request)
    {
        $this->empty = false;
        $this->step = !empty($request['step']) ? absint($request['step']) : 1;
        $this->export = !empty($request['export']) ? $request['export'] : $this->export;
        $this->filename = 'wpi-' . $this->export . '-' . $request['wpi_ajax_export'] . '.' . $this->filetype;
        $this->file = $this->export_dir . $this->filename;

        do_action('wpinv_export_set_params_' . $this->export, $request);
    }

    /**
     * Returns if next step
     *
     * @since    1.0.0
     * @access   public
     * @return bool
     */
    public function process_quote_export_step()
    {
        if ($this->step < 2) {
            @unlink($this->file);
            $this->print_quote_columns();
        }

        $return = $this->print_quote_rows();

        if ($return) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns columns to print in csv
     *
     * @since    1.0.0
     * @access   public
     * @return string of column names
     */
    public function print_quote_columns()
    {
        $column_data = '';
        $columns = $this->get_quotes_columns();
        $i = 1;
        foreach ($columns as $key => $column) {
            $column_data .= '"' . addslashes($column) . '"';
            $column_data .= $i == count($columns) ? '' : ',';
            $i++;
        }
        $column_data .= "\r\n";

        $this->attach_export_data($column_data);

        return $column_data;
    }

    /**
     * Returns columns to export in csv
     *
     * @since    1.0.0
     * @access   public
     * @param array $request contains post data
     * @return array Columns array
     */
    public function get_quotes_columns()
    {
        $columns = array(
            'id' => __('ID', 'invoicing'),
            'date' => __('Date', 'invoicing')
        );

        return apply_filters('wpinv_export_get_columns_' . $this->export, $columns);
    }

    /**
     * Write data in exported file
     *
     * @since    1.0.0
     * @access   protected
     */
    protected function attach_export_data($data = '')
    {
        $filedata = $this->get_quote_export_file();
        $filedata .= $data;

        $this->wp_filesystem->put_contents($this->file, $filedata);

        $rows = file($this->file, FILE_SKIP_EMPTY_LINES);
        $columns = $this->get_quotes_columns();
        $columns = empty($columns) ? 0 : 1;

        $this->empty = count($rows) == $columns ? true : false;
    }

    /**
     * Get export file
     *
     * @since    1.0.0
     * @access   protected
     * @return object of file
     */
    protected function get_quote_export_file()
    {
        $file = '';

        if ($this->wp_filesystem->exists($this->file)) {
            $file = $this->wp_filesystem->get_contents($this->file);
        } else {
            $this->wp_filesystem->put_contents($this->file, '');
        }

        return $file;
    }

    /**
     * Returns rows for csv
     *
     * @since    1.0.0
     * @access   public
     * @return string rows of csv
     */
    public function print_quote_rows()
    {
        $row_data = '';
        $data = $this->get_quote_export_data();
        $columns = $this->get_quotes_columns();

        if ($data) {
            foreach ($data as $row) {
                $i = 1;
                foreach ($row as $key => $column) {
                    if (array_key_exists($key, $columns)) {
                        $row_data .= '"' . addslashes(preg_replace("/\"/", "'", $column)) . '"';
                        $row_data .= $i == count($columns) ? '' : ',';
                        $i++;
                    }
                }
                $row_data .= "\r\n";
            }

            $this->attach_export_data($row_data);

            return $row_data;
        }

        return false;
    }

    /**
     * Returns quote export data
     *
     * @since    1.0.0
     * @access   public
     * @return array $data
     */
    public function get_quote_export_data()
    {
        $data = array(
            0 => array(
                'id' => '',
                'data' => date('F j, Y')
            ),
            1 => array(
                'id' => '',
                'data' => date('F j, Y')
            )
        );

        $data = apply_filters('wpinv_export_get_data', $data);
        $data = apply_filters('wpinv_export_get_data_' . $this->export, $data);

        return $data;
    }

    /**
     * Returns export status
     *
     * @since    1.0.0
     * @access   public
     * @return int $status status value
     */
    public function get_export_status()
    {
        $status = 100;
        return apply_filters('wpinv_get_export_status_' . $this->export, $status);
    }

    /**
     * Set variables retrived from form data
     *
     * @since    1.0.0
     * @access   public
     */
    public function set_quotes_export($request)
    {
        $this->from_date = isset($request['quote_from_date']) ? sanitize_text_field($request['quote_from_date']) : '';
        $this->to_date = isset($request['quote_to_date']) ? sanitize_text_field($request['quote_to_date']) : '';
        $this->status = isset($request['quote_status']) ? sanitize_text_field($request['quote_status']) : 'wpi-quote-accepted';
    }

    /**
     * Returns quote columns callback
     *
     * @since    1.0.0
     * @access   public
     * @param array $columns columns to export
     * @return array $columns columns to export
     */
    public function get_quotes_columns_cb($columns = array())
    {
        $columns = array(
            'id' => __('ID', 'invoicing'),
            'number' => __('Number', 'invoicing'),
            'date' => __('Date', 'invoicing'),
            'amount' => __('Amount', 'invoicing'),
            'status_nicename' => __('Status Nicename', 'invoicing'),
            'status' => __('Status', 'invoicing'),
            'tax' => __('Tax', 'invoicing'),
            'discount' => __('Discount', 'invoicing'),
            'user_id' => __('User ID', 'invoicing'),
            'email' => __('Email', 'invoicing'),
            'first_name' => __('First Name', 'invoicing'),
            'last_name' => __('Last Name', 'invoicing'),
            'address' => __('Address', 'invoicing'),
            'city' => __('City', 'invoicing'),
            'state' => __('State', 'invoicing'),
            'country' => __('Country', 'invoicing'),
            'zip' => __('Zipcode', 'invoicing'),
            'phone' => __('Phone', 'invoicing'),
            'company' => __('Company', 'invoicing'),
            'vat_number' => __('Vat Number', 'invoicing'),
            'ip' => __('IP', 'invoicing'),
            'gateway' => __('Gateway', 'invoicing'),
            'gateway_nicename' => __('Gateway Nicename', 'invoicing'),
            'transaction_id' => __('Transaction ID', 'invoicing'),
            'currency' => __('Currency', 'invoicing'),
            'due_date' => __('Due Date', 'invoicing'),
        );

        return $columns;
    }

    /**
     * Returns quote data to write in csv
     *
     * @since    1.0.0
     * @access   public
     * @param array $response
     * @return array $data data to write in exported csv
     */
    public function get_quotes_data($response = array())
    {
        $args = array(
            'limit' => $this->per_page,
            'page' => $this->step,
            'order' => 'DESC',
            'orderby' => 'date',
            'is_export' => true,
        );

        if ($this->status != 'any') {
            $args['status'] = $this->status;
        }

        if (!empty($this->from_date) || !empty($this->to_date)) {
            $args['date_query'] = array(
                array(
                    'after' => date('Y-n-d 00:00:00', strtotime($this->from_date)),
                    'before' => date('Y-n-d 23:59:59', strtotime($this->to_date)),
                    'inclusive' => true
                )
            );
        }

        $quotes = Wpinv_Quotes_Shared::wpinv_get_quotes($args);

        $data = array();

        if (!empty($quotes)) {
            foreach ($quotes as $quote) {
                $row = array(
                    'id' => $quote->ID,
                    'number' => $quote->get_number(),
                    'date' => $quote->get_invoice_date(false),
                    'amount' => wpinv_format_amount($quote->get_total(), NULL, true),
                    'status_nicename' => $quote->get_status(true),
                    'status' => $quote->get_status(),
                    'tax' => $quote->get_tax() > 0 ? wpinv_format_amount($quote->get_tax(), NULL, true) : '',
                    'discount' => $quote->get_discount() > 0 ? wpinv_format_amount($quote->get_discount(), NULL, true) : '',
                    'user_id' => $quote->get_user_id(),
                    'email' => $quote->get_email(),
                    'first_name' => $quote->get_first_name(),
                    'last_name' => $quote->get_last_name(),
                    'address' => $quote->get_address(),
                    'city' => $quote->city,
                    'state' => $quote->state,
                    'country' => $quote->country,
                    'zip' => $quote->zip,
                    'phone' => $quote->phone,
                    'company' => $quote->company,
                    'vat_number' => $quote->vat_number,
                    'ip' => $quote->get_ip(),
                    'gateway' => $quote->get_gateway(),
                    'gateway_nicename' => $quote->get_gateway_title(),
                    'transaction_id' => $quote->gateway ? $quote->get_transaction_id() : '',
                    'currency' => $quote->get_currency(),
                    'due_date' => $quote->needs_payment() ? $quote->get_due_date() : '',
                );

                $data[] = apply_filters('wpinv_export_quote_row', $row, $quote);
            }

            return $data;

        }

        return false;
    }

    /**
     * Returns progress of export csv
     *
     * @since    1.0.0
     * @access   public
     * @return int $status count of progress done
     */
    public function quotes_export_status()
    {
        $args = array(
            'limit' => -1,
            'return' => 'ids',
            'is_export' => true,
        );

        if ($this->status != 'any') {
            $args['status'] = $this->status;
        }

        if (!empty($this->from_date) || !empty($this->to_date)) {
            $args['date_query'] = array(
                array(
                    'after' => date('Y-n-d 00:00:00', strtotime($this->from_date)),
                    'before' => date('Y-n-d 23:59:59', strtotime($this->to_date)),
                    'inclusive' => true
                )
            );
        }

        $quotes = Wpinv_Quotes_Shared::wpinv_get_quotes($args);
        $total = !empty($quotes) ? count($quotes) : 0;
        $status = 100;

        if ($total > 0) {
            $status = (($this->per_page * $this->step) / $total) * 100;
        }

        if ($status > 100) {
            $status = 100;
        }

        return $status;
    }
}
