<?php

class WC_Controller_Moyasar_Return
{
    public static $instance;

    protected $gateway;
    protected $logger;

    public static function init()
    {
        $controller = new static();

        add_action('wp', array($controller, 'handle_user_return'));

        return static::$instance = $controller;
    }

    public function __construct()
    {
        $this->gateway = new WC_Gateway_Moyasar_Payment_Form();
        $this->logger = wc_get_logger();
    }

    private function request_query($key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }

    private function is_moyasar_page($page)
    {
        return $this->request_query('moyasar_page') == $page;
    }

    private function perform_redirect($url)
    {
        remove_all_filters('wp_redirect');
        remove_all_filters('wp_redirect_status');
        wp_redirect($url);
        exit;
    }

    private function user_order()
    {
        $order = $this->gateway->get_current_order();

        if (! $order && $order_id = WC()->session->get('moyasar_payments_completed_order')) {
            $order = wc_get_order($order_id);
        }

        return $order;
    }

    public function handle_user_return(WP $wordpress)
    {
        ini_set('display_errors', 0);

        if (! $this->is_moyasar_page('return')) {
            return;
        }

        $id = $this->request_query('id');
        if (! $id) {
            $this->perform_redirect('/');
        }

        $order = $this->user_order();
        if (! $order) {
            $this->perform_redirect('/');
        }

        $result = $this->gateway->validate_order_payment($order);

        WC()->session->set('moyasar_payments_completed_order', false);

        if ($result === false) {
            $this->perform_redirect('/');
        }

        $this->perform_redirect($result);
    }
}
