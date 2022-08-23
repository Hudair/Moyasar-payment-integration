<?php

class WC_Controller_Moyasar_Apple_Pay_Register
{
    public static $instance;

    protected $gateway;
    protected $logger;

    public static function init()
    {
        $controller = new static();
        static::$instance = $controller;

        if (! $controller->gateway->enable_apple_pay) {
            return;
        }

        add_action('parse_request', array($controller, 'serve_moy_association'));

        return $controller;
    }

    public function __construct()
    {
        $this->gateway = new WC_Gateway_Moyasar_Payment_Form();
        $this->logger = wc_get_logger();
    }

    /**
     * @param WP $wp
     */
    public function serve_moy_association($wp)
    {
        $association_file = __DIR__ . '/ap-association.txt';

        ini_set('display_errors', 0);

        if (! preg_match('/^\.well-known\/apple-developer-merchantid-domain-association(\.txt)?/', trim($_SERVER['REQUEST_URI'], '/'))) {
            return;
        }

        if (! file_exists($association_file)) {
            $this->logger->warning('Could not find Moyasar Apple Pay domain association file');
            return;
        }

        header('Content-Type: text/plain');
        echo file_get_contents($association_file);

        exit;
    }
}
