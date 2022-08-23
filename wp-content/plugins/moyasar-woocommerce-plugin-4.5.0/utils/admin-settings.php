<?php

return array(
    'moy_general' => array(
        'enabled' => array(
            'title' => __('Enable/Disable', 'moyasar-payments-text'),
            'type' => 'checkbox',
            'label' => __('Enable Moyasar Payment Gateway', 'moyasar-payments-text'),
            'default' => 'yes'
        ),
        'in_test_mode' => array(
            'title' => __('Enable Test Mode', 'moyasar-payments-text'),
            'type' => 'checkbox',
            'label' => __('In test mode, test API keys will be used instead of live. Also form will work in websites with no HTTPS. This allows you to quickly switch without changing your other settings.', 'moyasar-payments-text'),
            'default' => 'no'
        ),
        'fixed_width' => array(
            'title' => __('Fixed Width', 'moyasar-payments-text'),
            'type' => 'checkbox',
            'label' => __('Set form max width to 340px', 'moyasar-payments-text'),
            'default' => 'yes'
        )
    ),
    'moy_methods' => array(
        'enable_creditcard' => array(
            'title' => __('Enable Credit Card', 'moyasar-payments-text'),
            'type' => 'checkbox',
            'label' => __('This option allows you to enable Credit Card method.', 'moyasar-payments-text'),
            'default' => 'yes'
        ),
        'enable_applepay' => array(
            'title' => __('Enable Apple Pay', 'moyasar-payments-text'),
            'type' => 'checkbox',
            'label' => __('This option allows you to enable Apple Pay method.', 'moyasar-payments-text'),
            'default' => 'no'
        ),
        'enable_stcpay' => array(
            'title' => __('Enable stc pay', 'moyasar-payments-text'),
            'type' => 'checkbox',
            'label' => __('This option allows you to enable stc pay method.', 'moyasar-payments-text'),
            'default' => 'no'
        ),
        'supported_networks' => array(
            'title' => __('Supported Networks', 'moyasar-payments-text'),
            'type' => 'multiselect',
            'description' => __('Supported networks by Credit Card method and Apple Pay.', 'moyasar-payments-text'),
            'options' => array(
                'mada' => __('Mada', 'moyasar-payments-text'),
                'visa' => __('VISA', 'moyasar-payments-text'),
                'mastercard' => __('Mastercard', 'moyasar-payments-text'),
                'amex' => __('American Express', 'moyasar-payments-text')
            ),
            'default' => array(
                'mada',
                'visa',
                'mastercard'
            )
        )
    ),
    'moy_api_keys' => array(
        'live_api_sk' => array(
            'title' => __('Live Secret Key', 'moyasar-payments-text'),
            'type' => 'text',
            'description' => __('This key is used by the server to verify payments upon clients return.', 'moyasar-payments-text')
        ),
        'live_api_pk' => array(
            'title' => __('Live Publishable Key', 'moyasar-payments-text'),
            'type' => 'text',
            'description' => __('This key is used by client\'s browser to create a new payment.', 'moyasar-payments-text')
        ),
        'test_api_sk' => array(
            'title' => __('Test Secret Key', 'moyasar-payments-text'),
            'type' => 'text',
            'description' => __('This key is used by the server to verify payments upon clients return. Used in Test Mode', 'moyasar-payments-text')
        ),
        'test_api_pk' => array(
            'title' => __('Test Publishable Key', 'moyasar-payments-text'),
            'type' => 'text',
            'description' => __('This key is used by client\'s browser to create a new payment. Used in Test Mode', 'moyasar-payments-text')
        )
    ),
    'moy_order' => array(
        'new_order_status' => array(
            'title' => __('New Order Status', 'moyasar-payments-text'),
            'type' => 'select',
            'default' => 'processing',
            'options' => array(
                'processing' => __('Processing', 'moyasar-payments-text'),
                'on-hold' => __('On Hold', 'moyasar-payments-text'),
                'completed' => __('Completed', 'moyasar-payments-text'),
            )
        )
    ),
);
