<?php

class WC_Controller_Moyasar_Payment
{
    public static $instance;

    protected $gateway;
    protected $logger;

    public static function init()
    {
        $controller = new static();

        add_action('rest_api_init', array($controller, 'register_routes'));

        return static::$instance = $controller;
    }

    public function __construct()
    {
        $this->gateway = new WC_Gateway_Moyasar_Payment_Form();
        $this->logger = wc_get_logger();
    }

    public function register_routes()
    {
        register_rest_route(
            'moyasar/v2',
            'payment/initiated',
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'save_initiated_payment'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'moyasar/v2',
            'payment/failed',
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'cancel_current_order'),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function save_initiated_payment(WP_REST_Request $request)
    {
        ini_set('display_errors', 0);

        $id = $request->get_param('id');

        if (! $id) {
            $this->logger->warning('Moyasar: missing payment ID from save initiated payment endpoint');

            $response = new WP_REST_Response(array(
                'success' => false
            ));

            $response->set_status(400);
            return $response;
        }

        $this->gateway->save_initiated_payment($id);

        if ($request->get_param('status') == 'paid') {
            $this->gateway->validate_order_payment($this->gateway->get_current_order());
        }

        $response = new WP_REST_Response(array(
            'success' => true
        ));

        $response->set_status(201);
        return $response;
    }

    public function cancel_current_order(WP_REST_Request $request)
    {
        ini_set('display_errors', 0);

        $message = $request->get_param('message');
        $this->gateway->cancel_current_order($message);

        return array(
            'message' => 'Success'
        );
    }
}
