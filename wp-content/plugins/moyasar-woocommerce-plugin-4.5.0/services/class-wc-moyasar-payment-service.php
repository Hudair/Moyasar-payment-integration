<?php

class Moyasar_Payment_Service
{
    protected $gateway;
    protected $logger;

    public function __construct($gateway, $logger)
    {
        $this->gateway = $gateway;
        $this->logger = $logger;
    }

    public function fetch_moyasar_payment($id)
    {
        $response = null;

        try {
            $response = Moyasar_Quick_Http::make()
                ->basic_auth($this->gateway->secret_api_key())
                ->request('GET', $this->gateway->moyasar_api_url("payments/$id"));
        } catch (Exception $e) {
            $this->logger->error("Moyasar: An error occurred while trying to fetch payment $id. Error: " . $e->getMessage());
            return null;
        }

        $status = $response['status'];
        $body = $response['body'];

        if (preg_match('/4\d\d/', $status)) {
            $type = isset($body['type']) ? $body['type'] : 'unknown';
            $message = isset($body['message']) ? $body['type'] : 'unknown';
            $this->logger->error("Moyasar: Could not fetch payment $id. Status: $status, type: $type, message: $message");
            return null;
        }

        if ($status != '200') {
            $this->logger->error("Moyasar: Could not fetch payment $id. Status: $status", $body);
            return null;
        }

        return $response['body'];
    }
}