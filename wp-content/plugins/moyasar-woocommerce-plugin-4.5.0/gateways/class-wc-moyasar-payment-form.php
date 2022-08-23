<?php

class WC_Gateway_Moyasar_Payment_Form extends WC_Payment_Gateway
{
    public $new_order_status;

    public $in_test_mode = false;
    public $fixed_width = false;
    public $enable_credit_card = true;
    public $enable_apple_pay = false;
    public $enable_stc_pay = false;
    public $supported_networks = array();

    private $live_api_sk;
    private $live_api_pk;
    private $test_api_sk;
    private $test_api_pk;

    private $api_base_url;

    protected $logger;
    protected $payment_service;

    public function __construct()
    {
        global $woocommerce;

        $this->id = 'moyasar-form';
        $this->has_fields = false;
        $this->logger = wc_get_logger();
        $this->payment_service = new Moyasar_Payment_Service($this, $this->logger);
        $this->method_title = __('Moyasar Payments', 'moyasar-payments-text');
        $this->method_description = __('Moyasar Gateway Settings', 'moyasar-payments-text');

        // Load settings from database
        $this->init_form_fields();
        $this->init_settings();

        $this->title = __('Online Payments', 'moyasar-payments-text');
        $this->description = __('Pay with your credit card, Apple Pay, or stc pay.', 'moyasar-payments-text');

        $this->new_order_status = $this->get_option('new_order_status', 'processing');

        // Form Settings
        $this->in_test_mode = $this->get_boolean_option('in_test_mode');
        $this->fixed_width = $this->get_boolean_option('fixed_width');

        $this->enable_credit_card = $this->get_boolean_option('enable_creditcard', true);
        $this->enable_apple_pay = $this->get_boolean_option('enable_applepay');
        $this->enable_stc_pay = $this->get_boolean_option('enable_stcpay');
        $this->supported_networks = $this->get_option('supported_networks', array());

        $this->live_api_sk = $this->get_option('live_api_sk');
        $this->live_api_pk = $this->get_option('live_api_pk');
        $this->test_api_sk = $this->get_option('test_api_sk');
        $this->test_api_pk = $this->get_option('test_api_pk');

        $this->api_base_url = MOYASAR_API_BASE_URL;

        //// Hooks
        // Save Admin Panel Settings
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

        // Add payment form scripts
        add_action('wp_enqueue_scripts', array($this, 'add_payment_scripts'), PHP_INT_MAX);

        add_action('woocommerce_before_checkout_form', array($this, 'add_admin_notices'));

        add_filter('woocommerce_gateway_icon', array($this, 'render_gateway_icon'), 1024, 2);
    }

    public function render_gateway_icon($icon, $id)
    {
        if ($id !== $this->id) {
            return $icon;
        }

        return $this->icon ? '<img src="' . WC_HTTPS::force_https_url( $this->icon ) . '" alt="' . esc_attr( $this->get_title() ) . '" style="width: auto; height: 32px;" />' : '';
    }

    public function add_admin_notices()
    {
        if (! $this->enabled) {
            // Prevent error messages from showing when method is disabled
            return;
        }

        if ($this->in_test_mode && $this->keys_missing_test_mode()) {
            $this->notice_test_keys_missing();
        }

        if (!$this->in_test_mode && $this->keys_missing_live_mode()) {
            $this->notice_live_keys_missing();
        }

        if (count($this->supported_methods()) == 0) {
            $this->notice_methods_missing();
        }

        if (count($this->supported_networks()) == 0) {
            $this->notice_networks_missing();
        }
    }

    private function keys_missing_test_mode()
    {
        return !($this->test_api_pk && $this->test_api_sk);
    }

    private function keys_missing_live_mode()
    {
        return !($this->live_api_pk && $this->live_api_sk);
    }

    public function notice_test_keys_missing()
    {
        echo '<div class="woocommerce-error">' . esc_html(__('Moyasar Test API keys are missing.', 'moyasar-payments-text')) . '</div>';
    }

    public function notice_live_keys_missing()
    {
        echo '<div class="woocommerce-error">' . esc_html(__('Moyasar Live API keys are missing.', 'moyasar-payments-text')) . '</div>';
    }

    public function notice_methods_missing()
    {
        echo '<div class="woocommerce-error">' . esc_html(__('Moyasar: At least one payment method is required.', 'moyasar-payments-text')) . '</div>';
    }

    public function notice_networks_missing()
    {
        echo '<div class="woocommerce-error">' . esc_html(__('Moyasar: At least one network is required.', 'moyasar-payments-text')) . '</div>';
    }

    public function get_boolean_option($key, $default = false)
    {
        $value = $this->get_option($key, null);

        if (is_null($value)) {
            return $default;
        }

        return filter_var($value, 258);
    }

    public function moyasar_api_url($path = '')
    {
        $url = rtrim($this->api_base_url, '/');

        if (!empty(trim($path))) {
            $url .= '/v1/' . ltrim($path, '/');
        }

        return rtrim($url, '/');
    }

    public function get_all_form_fields()
    {
        $settings = require __DIR__ . '/../utils/admin-settings.php';
        $flatten = [];

        foreach ($settings as $settings_group) {
            foreach ($settings_group as $key => $item) {
                $flatten[$key] = $item;
            }
        }

        return apply_filters('woocommerce_settings_api_form_fields_' . $this->id, array_map(array($this, 'set_defaults'), $flatten));
    }

    public function init_settings()
    {
        $this->settings = get_option( $this->get_option_key(), null );

        // If there are no settings defined, use defaults.
        if ( ! is_array( $this->settings ) ) {
            $form_fields    = $this->get_all_form_fields();
            $this->settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
        }

        $this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
    }

    public function init_form_fields()
    {
        $settings = require __DIR__ . '/../utils/admin-settings.php';
        $section = $this->settings_section();
        $this->form_fields = isset($settings[$section]) ? $settings[$section] : null;

        if (! $this->form_fields) {
            $this->form_fields = $settings['moy_general'];
        }
    }

    public function admin_options()
    {
        echo '<h2>' . esc_html( $this->get_method_title() );
        wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
        echo '</h2>';
        echo wp_kses_post( wpautop( $this->get_method_description() ) );

        echo '<hr>';

        $this->admin_nav_bar();

        echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>'; // WPCS: XSS ok.
    }

    private function settings_section()
    {
        $section = $this->get_query_param('moy-section', null);

        if (! $section) {
            return 'moy_general';
        }

        $allowed = array_keys($this->allowed_settings_sections());

        if (! in_array($section, $allowed)) {
            return 'moy_general';
        }

        return $section;
    }

    private function allowed_settings_sections()
    {
        return array(
            'moy_general' => __('General', 'moyasar-payments-text'),
            'moy_api_keys' => __('API Keys', 'moyasar-payments-text'),
            'moy_methods' => __('Payment Methods', 'moyasar-payments-text'),
            'moy_order' => __('Order Settings', 'moyasar-payments-text')
        );
    }

    private function admin_nav_bar()
    {
        $sections = $this->allowed_settings_sections();
        $active = $this->settings_section();

        echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';

        foreach ($sections as $section => $title) {
            $href = preg_replace('/&?moy-section=[^&]+/', '', $_SERVER['REQUEST_URI']);
            $href .= "&moy-section=$section";

            if ($section == $active) {
                echo '<a href="' . $href . '" class="nav-tab nav-tab-active">';
            } else {
                echo '<a href="' . $href . '" class="nav-tab">';
            }

            echo __($title, 'moyasar-payments-text');
            echo '</a>';
        }

        echo '</nav>';
    }

    public function process_admin_options()
    {
        parent::process_admin_options();
    }

    public function add_payment_scripts()
    {
        if (! is_checkout()) {
            return;
        }

        $mpf_style = MOYASAR_PAYMENT_URL . '/assets/styles/moyasar.css';
        $mpf_script = MOYASAR_PAYMENT_URL . '/assets/scripts/moyasar.js';
        $plugin_style = MOYASAR_PAYMENT_URL . '/assets/styles/plugin.css';
        $plugin_script = MOYASAR_PAYMENT_URL . '/assets/scripts/plugin.js';

        wp_enqueue_style('moyasar-form-stylesheet', $mpf_style, array(), MOYASAR_PAYMENT_VERSION);
        wp_enqueue_style('moyasar-form-plugin-stylesheet', $plugin_style, array(), MOYASAR_PAYMENT_VERSION);

        wp_enqueue_script('polyfill-io-fetch', 'https://polyfill.io/v3/polyfill.min.js?features=fetch', array(), null, false);
        wp_enqueue_script('moyasar-form-js', $mpf_script, array(), MOYASAR_PAYMENT_VERSION, true);
        wp_enqueue_script('moyasar-form-plugin-js', $plugin_script, array(), MOYASAR_PAYMENT_VERSION, true);
    }

    private function checkout_nonce_string()
    {
        $amount = $this->current_order_total_small_unit();
        $currency = $this->current_order_currency();
        $country = $this->current_customer_billing_country();
        $customer_id = WC()->session->get_customer_id();

        return "$amount,$currency,$country,$customer_id";
    }

    private function checkout_nonce()
    {
        return wp_create_nonce($this->checkout_nonce_string());
    }

    public function payment_fields()
    {
        $amount = $this->current_order_total_small_unit();
        $currency = $this->current_order_currency();
        $country = $this->current_customer_billing_country();
        $description = $this->current_session_payment_description();
        $apiKey = $this->p_api_key_for_form();
        $methods = $this->supported_methods();
        $networks = $this->supported_networks();
        $callback_url = moyasar_page_url('return');
        $validation_url = $this->moyasar_api_url('applepay/initiate');
        $applepay_label = moy_get_site_domain();
        $api_base_url = $this->moyasar_api_url();
        $checkout_nonce = $this->checkout_nonce();
        $site_url = moy_trimmed_site_url();
        $fixed_width = $this->fixed_width ? 'true' : 'false';

        require __DIR__ . '/../views/form.php';
    }

    private function supported_methods()
    {
        $methods = [];

        if ($this->enable_credit_card) {
            $methods[] = 'creditcard';
        }

        if ($this->enable_apple_pay) {
            $methods[] = 'applepay';
        }

        if ($this->enable_stc_pay) {
            $methods[] = 'stcpay';
        }

        return $methods;
    }

    private function supported_networks()
    {
        return $this->supported_networks;
    }

    public function p_api_key_for_form()
    {
        if ($this->in_test_mode) {
            return $this->test_api_pk;
        }

        return $this->live_api_pk;
    }

    public function secret_api_key()
    {
        if ($this->in_test_mode) {
            return $this->test_api_sk;
        }

        return $this->live_api_sk;
    }

    private function current_session_payment_description()
    {
        $customer = $this->current_session_customer();
        $description = "A Payment for woocommerce order TBD";

        $order_id = absint($this->get_query_param('order-pay'));

        // Gets order total from "pay for order" page.
        if (0 < $order_id) {
            $description = str_replace('TBD', $order_id, $description);
        }

        if ($email = $customer->get_email()) {
            $description .= ", customer: $email";
        }

        return $description;
    }

    private function current_customer_billing_country()
    {
        $customer = $this->current_session_customer();
        return $customer->get_billing_country();
    }

    private function current_order_currency()
    {
        $order_id = absint($this->get_query_param('order-pay'));

        // Gets order total from "pay for order" page.
        if (0 < $order_id) {
            $order = wc_get_order($order_id);
            return strtoupper($order->get_currency('edit'));
        }

        return strtoupper(get_woocommerce_currency());
    }

    private function current_order_total_small_unit()
    {
        $total = floatval($this->get_order_total());
        $currency = $this->current_order_currency();

        return $this->amount_minor_unit($total, $currency);
    }

    public function amount_minor_unit($amount, $currency)
    {
        $fraction_table = require __DIR__ . '/../utils/currency.php';

        $meta = isset($fraction_table[$currency]) ? $fraction_table[$currency] : null;

        if (!$meta) {
            $meta = array(
                'fraction' => 2
            );
        }

        return intval($amount * (10 ** $meta['fraction']));
    }

    private function current_session_cart()
    {
        return WC()->cart;
    }

    private function current_session_customer()
    {
        return WC()->customer;
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        $order = wc_get_order($order_id);
        $nonce = isset($_POST['moyasar-checkout-nonce']) ? $_POST['moyasar-checkout-nonce'] : '';

        // Check for checkout nonce
        if (! wp_verify_nonce($nonce, $this->checkout_nonce_string())) {
            $message = __('Invalid nonce', 'moyasar-payments-text');

            wc_add_notice($message, 'error');
            $order->set_status('failed', $message);
            $order->save();

            return array(
                'result' => 'failure',
                'reload' => true
            );
        }

        // Set status to pending to indicate that we are still processing payment
        $order->set_status('pending', __('Awaiting payment to complete', 'moyasar-payments-text'));
        $order->save();

        return array(
            'result' => 'success',
            'order_id' => $order_id,
            'redirect' => moyasar_page_url('return')
        );
    }

    public function get_current_order()
    {
        global $wp;
        global $woocommerce;

        $session = WC()->session;

        if (! $session) {
            return null;
        }

        $order_id = $session->get('order_awaiting_payment');

        if (0 < $order_id) {
            return wc_get_order($order_id);
        }

        // If coming from order-pay page
        $order_key = wp_unslash($this->get_query_param('key', 0)); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $order_id = absint($this->get_query_param('order-pay'));
        $order = wc_get_order($order_id);

        if (! $order) {
            return null;
        }

        if ($order_id === $order->get_id() && hash_equals($order->get_order_key(), $order_key)) {
            $session->set('order_awaiting_payment', $order_id);
            return $order;
        }
    }

    public function cancel_order($order, $message)
    {
        if (! $order instanceof WC_Order) {
            return;
        }

        if ($order->is_paid()) {
            return;
        }

        $order->set_status('failed');
        $order->add_order_note("Order was canceled for payment failure. Message: $message.");
        $order->save();
    }

    public function cancel_current_order($message)
    {
        $this->cancel_order($this->get_current_order(), $message);
    }

    /**
     * @param WC_Order $order
     * @return mixed
     */
    public function validate_order_payment($order)
    {
        if ($order->is_paid()) {
            $this->logger->info("Order " . $order->get_id() . " already paid");
            return $this->get_return_url($order);
        }

        $payment_id = $order->get_transaction_id('edit');

        if (! $payment_id) {
            return false;
        }

        $this->logger->info("Fetching payment $payment_id from api.moyasar.com");
        $payment = $this->payment_service->fetch_moyasar_payment($payment_id);

        if (! $payment) {
            $this->logger->warning("Moyasar: no payment associated with order " . $order->get_id());
            return false;
        }

        if ($payment['status'] == 'initiated') {
            $this->logger->warning("Moyasar: cannot validate order " . $order->get_id() . " payment $payment_id, still initiated");
            return isset($payment['source']['transaction_url']) ? $payment['source']['transaction_url'] : false;
        }

        if ($payment['status'] != 'paid') {
            $order->set_status('failed');
            $message = isset($payment['source']['message']) ? $payment['source']['message'] : 'no message';
            $order->add_order_note("Payment $payment_id for order was not complete. Message: $message. Payment Status: " . $payment['status']);
            $order->save();

            wc_add_notice(__('Payment failed', 'moyasar-payments-text'), 'error');

            return $order->get_checkout_payment_url();
        }

        // Check for fraud
        $orderCurrency = strtoupper($order->get_currency());
        $orderAmount = $this->amount_minor_unit((float) $order->get_total(), $orderCurrency);
        if (strtoupper($payment['currency']) != $orderCurrency || $payment['amount'] != $orderAmount) {
            $order->set_status('failed');
            $order->add_order_note("Fraud detected in payment $payment_id");
            $order->add_order_note("Payment amount and currency doesn't match order");

            $order->save();

            // Empty cart and remove order from session
            WC()->cart->empty_cart();
            WC()->session->set('order_awaiting_payment', false);
            wc_add_notice(__('Order has been canceled due to payment mismatch', 'moyasar-payments-text'), 'error');

            return wc_get_cart_url();
        }

        WC()->cart->empty_cart();

        $order->add_order_note("Payment $payment_id for order is complete.");

        add_filter('woocommerce_payment_complete_order_status', array($this, 'determine_new_order_status'), PHP_INT_MAX, 3);
        $order->payment_complete($payment_id);

        if ($source_type = $this->determine_method_from_payment_object($payment)) {
            $order->add_order_note("Payment Source: " . $source_type);
            $order->set_payment_method_title($source_type);
        }

        $order->save();

        WC()->session->set('moyasar_payments_completed_order', $order->get_id());
        $this->logger->info("Payment $payment_id is successful. Redirecting to " . $this->get_return_url($order));

        return $this->get_return_url($order);
    }

    private function determine_method_from_payment_object($payment)
    {
        if (!isset($payment['source']['type'])) {
            return null;
        }

        switch (strtolower($payment['source']['type'])) {
            case 'creditcard':
                return 'Credit Card';
            case 'applepay':
                return 'Apple Pay';
            case 'stcpay':
                return 'stc pay';
        }

        return null;
    }

    public function determine_new_order_status($status, $id, $instance)
    {
        return $this->new_order_status;
    }

    public function save_initiated_payment($payment_id)
    {
        $order = $this->get_current_order();

        if (! $order) {
            $this->logger->info("Moyasar: cannot save payment $payment_id; no order found");
            return false;
        }

        $status = $order->get_status('edit');

        if (! in_array($status, array('pending', 'failed'))) {
            $this->logger->info("Moyasar: Order " . $order->get_id() . " is not pending. Status: $status, ignoring transaction id $payment_id");
            return false;
        }

        try {
            $order->set_transaction_id($payment_id);
            $order->add_order_note("Assigning transaction id $payment_id");
            $order->save();
        } catch (Exception $e) {
            $this->logger->error("Moyasar: Could not save payment ID $payment_id for order " . $order->get_id());
            return false;
        }

        $this->logger->info("Moyasar: Saved payment ID $payment_id for order " . $order->get_id());

        return true;
    }

    public function can_refund_order($order)
    {
        return false;
    }

    private function get_query_param($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
}
